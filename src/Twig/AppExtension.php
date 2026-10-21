<?php

declare(strict_types=1);

namespace App\Twig;

use App\Entity\Gallery;
use App\Entity\LegacyRecord;
use App\Entity\Photo;
use App\Entity\Post;
use App\Repository\ActualityRepository;
use App\Repository\GalleryRepository;
use App\Service\PhotoStorage;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Twig\Attribute\AsTwigFilter;
use Twig\Attribute\AsTwigFunction;

final readonly class AppExtension
{
    public function __construct(
        private PhotoStorage $photos,
        private ActualityRepository $actualities,
        private GalleryRepository $galleries,
        #[Autowire('%env(default::GA_MEASUREMENT_ID)%')]
        private ?string $gaMeasurementId,
    ) {
    }

    /** Thumbnail URL or null (missing original => the template shows a placeholder, never a broken image). */
    #[AsTwigFunction('photo_thumb')]
    public function photoThumb(?Photo $photo, string $type = 'card'): ?string
    {
        return $this->photos->thumbnailUrl($photo, $type);
    }

    /** Lightbox-size image: the `large` thumbnail, falling back to the original. */
    #[AsTwigFunction('photo_large')]
    public function photoLarge(?Photo $photo): ?string
    {
        return $this->photos->thumbnailUrl($photo, 'large') ?? $this->photos->originalUrl($photo);
    }

    #[AsTwigFunction('photo_original')]
    public function photoOriginal(?Photo $photo): ?string
    {
        return $this->photos->originalUrl($photo);
    }

    /** First visible photo of a post / gallery whose file exists (for list cards). */
    #[AsTwigFunction('cover_photo')]
    public function coverPhoto(Post|Gallery $record): ?Photo
    {
        if ($record instanceof Gallery && $this->photos->originalExists($record->photo)) {
            return $record->photo;
        }
        foreach ($record->getPhotos() as $p) {
            if ($p->isVisible() && $this->photos->originalExists($p->photo)) {
                return $p->photo;
            }
        }

        return null;
    }

    /**
     * @param iterable<mixed> $records
     *
     * @return list<LegacyRecord>
     */
    #[AsTwigFunction('visible')]
    public function visible(iterable $records): array
    {
        $out = [];
        foreach ($records as $r) {
            if ($r instanceof LegacyRecord && $r->isVisible()) {
                $out[] = $r;
            }
        }

        return $out;
    }

    /** The 2014 list teaser: `text|striptags|truncate:450`. */
    #[AsTwigFilter('excerpt')]
    public function excerpt(?string $html, int $length = 450): string
    {
        $text = html_entity_decode(strip_tags((string) $html), \ENT_QUOTES | \ENT_HTML5, 'UTF-8');
        $text = trim((string) preg_replace('/\s+/u', ' ', $text));
        if (mb_strlen($text) <= $length) {
            return $text;
        }
        $cut = mb_substr($text, 0, $length);
        $space = mb_strrpos($cut, ' ');

        return rtrim(false !== $space && $space > $length * 0.6 ? mb_substr($cut, 0, $space) : $cut, ' ,.;:-').'…';
    }

    /** @return list<\App\Entity\Actuality> */
    #[AsTwigFunction('recent_actualities')]
    public function recentActualities(int $limit = 3): array
    {
        /* @var list<\App\Entity\Actuality> */
        return $this->actualities->visibleQb('r')->orderBy('r.date', 'DESC')->addOrderBy('r.id', 'DESC')->setMaxResults($limit)->getQuery()->getResult();
    }

    /** @return list<Gallery> */
    #[AsTwigFunction('recent_galleries')]
    public function recentGalleries(int $limit = 3): array
    {
        /* @var list<Gallery> */
        return $this->galleries->visibleQb('r')->orderBy('r.order', 'DESC')->setMaxResults($limit)->getQuery()->getResult();
    }

    #[AsTwigFunction('ga_measurement_id')]
    public function gaMeasurementId(): ?string
    {
        return '' === (string) $this->gaMeasurementId ? null : $this->gaMeasurementId;
    }
}
