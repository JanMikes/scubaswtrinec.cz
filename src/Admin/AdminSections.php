<?php

declare(strict_types=1);

namespace App\Admin;

use App\Entity\Actuality;
use App\Entity\ActualityPhoto;
use App\Entity\Article;
use App\Entity\ArticlePhoto;
use App\Entity\Discussion;
use App\Entity\Gallery;
use App\Entity\GalleryPhoto;
use App\Entity\Instructor;
use App\Entity\Link;
use App\Form\Admin\GalleryType;
use App\Form\Admin\InstructorType;
use App\Form\Admin\LinkType;
use App\Form\Admin\PhotoType;
use App\Form\Admin\PostType;
use App\Form\DiscussionPostType;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

final class AdminSections
{
    public const string SLUG_REQUIREMENT = 'actuality|article|gallery|instructor|link|discussion|actuality-photo|article-photo|gallery-photo';

    /** @var array<string, AdminSection>|null */
    private static ?array $sections = null;

    /** @return array<string, AdminSection> */
    public static function all(): array
    {
        return self::$sections ??= [
            'actuality' => new AdminSection('actuality', Actuality::class, PostType::class, 'Aktuality', 'aktualitu', 'admin/list/post.html.twig', ['date' => 'DESC', 'id' => 'DESC'], photosSlug: 'actuality-photo'),
            'actuality-photo' => new AdminSection('actuality-photo', ActualityPhoto::class, PhotoType::class, 'Fotografie', 'fotografii', 'admin/list/photo.html.twig', ['order' => 'DESC', 'id' => 'DESC'], orderable: true, parentSlug: 'actuality', parentParam: 'actualityId', parentField: 'actuality', uploadDir: 'img/actuality/{parent}'),
            'article' => new AdminSection('article', Article::class, PostType::class, 'Články', 'článek', 'admin/list/post.html.twig', ['date' => 'DESC', 'id' => 'DESC'], photosSlug: 'article-photo'),
            'article-photo' => new AdminSection('article-photo', ArticlePhoto::class, PhotoType::class, 'Fotografie', 'fotografii', 'admin/list/photo.html.twig', ['order' => 'DESC', 'id' => 'DESC'], orderable: true, parentSlug: 'article', parentParam: 'articleId', parentField: 'article', uploadDir: 'img/article/{parent}'),
            'gallery' => new AdminSection('gallery', Gallery::class, GalleryType::class, 'Galerie', 'galerii', 'admin/list/gallery.html.twig', ['order' => 'DESC', 'id' => 'DESC'], orderable: true, uploadDir: 'img/gallery/{id}', photosSlug: 'gallery-photo'),
            'gallery-photo' => new AdminSection('gallery-photo', GalleryPhoto::class, PhotoType::class, 'Fotografie', 'fotografii', 'admin/list/photo.html.twig', ['order' => 'DESC', 'id' => 'DESC'], orderable: true, parentSlug: 'gallery', parentParam: 'galleryId', parentField: 'gallery', uploadDir: 'img/gallery/{parent}'),
            'instructor' => new AdminSection('instructor', Instructor::class, InstructorType::class, 'Instruktoři', 'instruktora', 'admin/list/instructor.html.twig', ['order' => 'DESC', 'id' => 'DESC'], orderable: true, uploadDir: 'img/instructor/{id}'),
            'link' => new AdminSection('link', Link::class, LinkType::class, 'Odkazy', 'odkaz', 'admin/list/link.html.twig', ['order' => 'DESC', 'id' => 'DESC'], orderable: true, uploadDir: 'img/link/{id}'),
            'discussion' => new AdminSection('discussion', Discussion::class, DiscussionPostType::class, 'Diskuze', 'příspěvek', 'admin/list/discussion.html.twig', ['insDt' => 'DESC', 'id' => 'DESC']),
        ];
    }

    public static function get(string $slug): AdminSection
    {
        return self::all()[$slug] ?? throw new NotFoundHttpException(\sprintf('Unknown admin section "%s".', $slug));
    }
}
