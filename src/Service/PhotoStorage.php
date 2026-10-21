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
use Symfony\Component\Lock\LockFactory;
use Symfony\Component\Lock\SharedLockInterface;
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
    /**
     * Imagick pixel cache kept in RAM per process; larger images spill to Imagick's disk cache. A
     * 6000x4000 photo needs ~190 MB in RAM at Q16 — times up to 32 FrankenPHP threads that OOM-killed
     * the 768 MB container on the first crawl after cutover (2026-10-21, D83).
     */
    private const int IMAGICK_MEMORY_LIMIT = 96 * 1024 * 1024;

    /** Seconds of on-demand thumbnail work one request may spend (waiting + decoding) before it stops generating. */
    private const float REQUEST_BUDGET_SECONDS = 10.0;

    private float $spentSeconds = 0.0;

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
        private readonly LockFactory $locks,
        private readonly Filesystem $fs = new Filesystem(),
    ) {
        \Imagick::setResourceLimit(\Imagick::RESOURCETYPE_MEMORY, self::IMAGICK_MEMORY_LIMIT);
        \Imagick::setResourceLimit(\Imagick::RESOURCETYPE_MAP, 2 * self::IMAGICK_MEMORY_LIMIT);
        \Imagick::setResourceLimit(\Imagick::RESOURCETYPE_THREAD, 1);
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

        $lock = $this->acquireImageLock(60.0)
            ?? throw new \RuntimeException('Server is busy processing images, please try the upload again.');

        try {
            $image = $this->images->read($file->getPathname())->orient();
            if ($image->width() > self::MAX_ORIGINAL_EDGE || $image->height() > self::MAX_ORIGINAL_EDGE) {
                $image = $image->scaleDown(self::MAX_ORIGINAL_EDGE, self::MAX_ORIGINAL_EDGE);
            }
            $this->save($image, $absDir.'/'.$filename, $ext, 90);
        } finally {
            $lock->release();
        }
        unset($lock);

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

        // One image decode at a time per container (see IMAGICK_MEMORY_LIMIT); a request waiting here
        // re-checks whether a concurrent request already produced the thumbnail.
        if ($this->spentSeconds >= self::REQUEST_BUDGET_SECONDS) {
            return null; // this request has done its share; later views / app:thumbnails:warm finish the rest
        }
        $t0 = microtime(true);
        $lock = $this->acquireImageLock(min(5.0, self::REQUEST_BUDGET_SECONDS - $this->spentSeconds));
        if (null === $lock) {
            $this->spentSeconds += microtime(true) - $t0;

            return null; // busy: this request renders without it; the next one (or app:thumbnails:warm) creates it
        }

        try {
            $existing = $this->thumbnails->findFor($photo, $typeCode);
            if (null !== $existing && is_file($this->publicDir.'/'.$existing->relativePath())) {
                return $existing;
            }
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
        } finally {
            $lock->release();
            $this->spentSeconds += microtime(true) - $t0;
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

    /**
     * Bounded wait for the per-container image lock: a cold-cache crawl must not pile requests up
     * behind it until max_execution_time (reproduced locally: 30 s timeouts -> 500).
     */
    private function acquireImageLock(float $maxWaitSeconds): ?SharedLockInterface
    {
        $lock = $this->locks->createLock('photo-storage-image-work', 120);
        $deadline = microtime(true) + $maxWaitSeconds;
        do {
            if ($lock->acquire(false)) {
                return $lock;
            }
            usleep(100_000);
        } while (microtime(true) < $deadline);

        return null;
    }

    /** CLI warm-up: no per-request budget. */
    public function disableBudget(): void
    {
        $this->spentSeconds = -\INF;
    }

    public function reset(): void
    {
        $this->types = [];
        $this->spentSeconds = 0.0;
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
