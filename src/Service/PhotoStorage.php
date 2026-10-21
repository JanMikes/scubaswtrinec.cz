<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\Photo;
use App\Entity\Thumbnail;
use App\Entity\ThumbnailType;
use App\Repository\ThumbnailRepository;
use Doctrine\ORM\EntityManagerInterface;
use Intervention\Image\Drivers\Imagick\Driver;
use Intervention\Image\ImageManager;
use Intervention\Image\Interfaces\ImageInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Contracts\Service\ResetInterface;

/**
 * Originals and thumbnails on disk, exactly in the 2014 layout so existing rows keep working:
 *   original:  public/<photo.path>/<photo.filename>          e.g. img/gallery/12/345.jpg
 *   thumbnail: public/<photo.path>/<type code>/<filename>     e.g. img/gallery/12/gallery/345.jpg
 * Thumbnails are registered in `thumbnail` and generated on upload and lazily on first use.
 *
 * Unlike 2014, replacing an image does not unlink the previous files: rows are soft-deleted,
 * files stay (most 2014 originals were lost; we do not delete anything we do not have to).
 */
final class PhotoStorage implements ResetInterface
{
    /** Originals larger than this are downscaled on upload (the 2014 site kept 4-8 MB camera files). */
    private const int MAX_ORIGINAL_EDGE = 3000;

    private readonly ImageManager $images;

    /** @var array<string, ThumbnailType|null> */
    private array $types = [];

    public function __construct(
        private readonly string $publicDir,
        private readonly EntityManagerInterface $em,
        private readonly ThumbnailRepository $thumbnails,
        private readonly LoggerInterface $logger,
        private readonly Filesystem $fs = new Filesystem(),
    ) {
        $this->images = new ImageManager(new Driver());
    }

    /**
     * Stores an upload as `<dir>/<basename>.<ext>` and creates its `photo` row (flushed).
     *
     * @param list<string> $thumbTypes generated eagerly (the rest is generated on first use)
     */
    public function store(UploadedFile $file, string $dir, string $basename, array $thumbTypes = ['admin']): Photo
    {
        $dir = trim($dir, '/');
        $ext = $this->extensionFor($file);
        $filename = $basename.'.'.$ext;
        $absDir = $this->publicDir.'/'.$dir;
        $this->fs->mkdir($absDir, 0o755);

        $image = $this->images->read($file->getPathname());
        $image = $image->orient();
        if ($image->width() > self::MAX_ORIGINAL_EDGE || $image->height() > self::MAX_ORIGINAL_EDGE) {
            $image = $image->scaleDown(self::MAX_ORIGINAL_EDGE, self::MAX_ORIGINAL_EDGE);
        }
        $this->save($image, $absDir.'/'.$filename, $ext, 90);

        $photo = new Photo();
        $photo->filename = $filename;
        $photo->path = $dir;
        $photo->width = $image->width();
        $photo->height = $image->height();
        $photo->insProcess = __METHOD__;
        $this->em->persist($photo);
        $this->em->flush();

        foreach ($thumbTypes as $type) {
            $this->generate($photo, $type);
        }

        return $photo;
    }

    public function originalExists(?Photo $photo): bool
    {
        return null !== $photo && null !== $photo->filename && is_file($this->publicDir.'/'.$photo->relativePath());
    }

    /** Root-relative URL of the original, or null when the file is missing on disk. */
    public function originalUrl(?Photo $photo): ?string
    {
        return null !== $photo && $this->originalExists($photo) ? '/'.$photo->relativePath() : null;
    }

    /** Root-relative URL of a thumbnail (generated on demand), or null when no image is available. */
    public function thumbnailUrl(?Photo $photo, string $type): ?string
    {
        if (null === $photo || $photo->deleted) {
            return null;
        }

        $thumb = $this->thumbnails->findFor($photo, $type);
        if (null !== $thumb && is_file($this->publicDir.'/'.$thumb->relativePath())) {
            return '/'.$thumb->relativePath();
        }

        if (!$this->originalExists($photo)) {
            return null;
        }

        $thumb = $this->generate($photo, $type);

        return null === $thumb ? null : '/'.$thumb->relativePath();
    }

    public function generate(Photo $photo, string $typeCode): ?Thumbnail
    {
        $type = $this->type($typeCode);
        if (null === $type || !$this->originalExists($photo)) {
            return null;
        }

        try {
            $image = $this->images->read($this->publicDir.'/'.$photo->relativePath())->orient();
            $w = (int) $type->width;
            $h = (int) $type->height;
            if ('fit' === $type->description) {
                $image = $image->scaleDown($w, $h);
            } else {
                $image = $image->coverDown($w, $h);
            }

            $thumbDir = trim((string) $photo->path, '/').'/'.$typeCode;
            $this->fs->mkdir($this->publicDir.'/'.$thumbDir, 0o755);
            $ext = strtolower(pathinfo((string) $photo->filename, \PATHINFO_EXTENSION));
            $this->save($image, $this->publicDir.'/'.$thumbDir.'/'.$photo->filename, $ext, 85);
        } catch (\Throwable $e) {
            $this->logger->warning('Thumbnail generation failed', ['photo' => $photo->getId(), 'type' => $typeCode, 'exception' => $e]);

            return null;
        }

        // Same bookkeeping as 2014: older rows of this photo+type are soft-deleted.
        foreach ($this->thumbnails->allFor($photo) as $old) {
            if ($old->type === $type) {
                $old->deleted = true;
                $old->updProcess = __METHOD__;
            }
        }

        $thumb = new Thumbnail();
        $thumb->type = $type;
        $thumb->photo = $photo;
        $thumb->filename = $photo->filename;
        $thumb->path = $thumbDir;
        $thumb->width = $image->width();
        $thumb->height = $image->height();
        $thumb->insProcess = __METHOD__;
        $this->em->persist($thumb);
        $this->em->flush();

        return $thumb;
    }

    /** Soft-deletes a photo row and its thumbnail rows (files stay on disk). */
    public function discard(?Photo $photo): void
    {
        if (null === $photo) {
            return;
        }
        $photo->deleted = true;
        $photo->updProcess = __METHOD__;
        foreach ($this->thumbnails->allFor($photo) as $thumb) {
            $thumb->deleted = true;
            $thumb->updProcess = __METHOD__;
        }
    }

    public function reset(): void
    {
        $this->types = [];
    }

    private function type(string $code): ?ThumbnailType
    {
        if (!\array_key_exists($code, $this->types)) {
            $this->types[$code] = $this->em->getRepository(ThumbnailType::class)->findOneBy(['code' => $code, 'deleted' => false]);
        }

        return $this->types[$code];
    }

    private function extensionFor(UploadedFile $file): string
    {
        $ext = strtolower($file->guessExtension() ?? $file->getClientOriginalExtension());

        return match ($ext) {
            'jpeg', 'jpg' => 'jpg',
            'png' => 'png',
            'gif' => 'gif',
            'webp' => 'webp',
            default => 'jpg',
        };
    }

    private function save(ImageInterface $image, string $path, string $ext, int $quality): void
    {
        $encoded = match ($ext) {
            'png' => $image->toPng(),
            'gif' => $image->toGif(),
            'webp' => $image->toWebp($quality),
            default => $image->toJpeg($quality),
        };
        $encoded->save($path);
    }
}
