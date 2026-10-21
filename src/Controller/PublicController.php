<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\Actuality;
use App\Entity\Article;
use App\Entity\Gallery;
use App\Entity\Post;
use App\Repository\ActualityRepository;
use App\Repository\ArticleRepository;
use App\Repository\ContactRepository;
use App\Repository\GalleryRepository;
use App\Repository\InstructorRepository;
use App\Repository\LinkRepository;
use App\Service\Pager;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

/**
 * The public pages. URLs are the 2014 ones (Nette route `<action>[/<id>]`, default action
 * `aktuality` served at `/`); page sizes and orderings are the 2014 ones too.
 */
final class PublicController extends AbstractController
{
    public function __construct(
        private readonly ActualityRepository $actualities,
        private readonly ArticleRepository $articles,
        private readonly GalleryRepository $galleries,
    ) {
    }

    #[Route('/', name: 'home', methods: ['GET', 'HEAD'])]
    public function home(Request $request): Response
    {
        $qb = $this->actualities->visibleQb('r')->orderBy('r.date', 'DESC')->addOrderBy('r.id', 'DESC');

        return $this->render('public/home.html.twig', [
            'pager' => Pager::fromQuery($qb, $request->query->get('page'), 5),
        ]);
    }

    /** Nette canonicalised the default action to `/`; keep the query string (`?page=N`). */
    #[Route('/aktuality', name: 'legacy_aktuality', methods: ['GET', 'HEAD'])]
    public function aktuality(Request $request): Response
    {
        $qs = $request->getQueryString();

        return $this->redirectToRoute('home', [], Response::HTTP_MOVED_PERMANENTLY)->setTargetUrl('/'.(null !== $qs ? '?'.$qs : ''));
    }

    #[Route('/detail-aktuality/{id}', name: 'actuality_detail', requirements: ['id' => '\d+'], defaults: ['id' => null], methods: ['GET', 'HEAD'])]
    public function actualityDetail(?int $id): Response
    {
        $actuality = null !== $id ? $this->actualities->findVisible($id) : null;
        if (!$actuality instanceof Actuality) {
            return $this->redirectToRoute('home');
        }

        return $this->renderPost($actuality, 'actuality');
    }

    #[Route('/clanky', name: 'articles', methods: ['GET', 'HEAD'])]
    public function articles(Request $request): Response
    {
        $qb = $this->articles->visibleQb('r')->orderBy('r.date', 'DESC')->addOrderBy('r.id', 'DESC');

        return $this->render('public/articles.html.twig', [
            'pager' => Pager::fromQuery($qb, $request->query->get('page'), 5),
        ]);
    }

    #[Route('/detail-clanku/{id}', name: 'article_detail', requirements: ['id' => '\d+'], defaults: ['id' => null], methods: ['GET', 'HEAD'])]
    public function articleDetail(?int $id): Response
    {
        $article = null !== $id ? $this->articles->findVisible($id) : null;
        if (!$article instanceof Article) {
            return $this->redirectToRoute('articles');
        }

        return $this->renderPost($article, 'article');
    }

    #[Route('/galerie', name: 'galleries', methods: ['GET', 'HEAD'])]
    public function galleries(Request $request): Response
    {
        $qb = $this->galleries->visibleQb('r')->orderBy('r.order', 'DESC')->addOrderBy('r.id', 'DESC');

        return $this->render('public/galleries.html.twig', [
            'pager' => Pager::fromQuery($qb, $request->query->get('page'), 9),
        ]);
    }

    #[Route('/detail-galerie/{id}', name: 'gallery_detail', requirements: ['id' => '\d+'], defaults: ['id' => null], methods: ['GET', 'HEAD'])]
    public function galleryDetail(?int $id): Response
    {
        $gallery = null !== $id ? $this->galleries->findVisible($id) : null;
        if (!$gallery instanceof Gallery) {
            return $this->redirectToRoute('galleries');
        }

        return $this->render('public/gallery_detail.html.twig', [
            'gallery' => $gallery,
            'photos' => $gallery->getPhotos()->filter(static fn ($p): bool => $p->isVisible())->getValues(),
        ]);
    }

    #[Route('/instruktori', name: 'instructors', methods: ['GET', 'HEAD'])]
    public function instructors(InstructorRepository $instructors): Response
    {
        return $this->render('public/instructors.html.twig', [
            'instructors' => $instructors->visibleQb('r')->orderBy('r.order', 'DESC')->getQuery()->getResult(),
        ]);
    }

    #[Route('/odkazy', name: 'links', methods: ['GET', 'HEAD'])]
    public function links(LinkRepository $links): Response
    {
        return $this->render('public/links.html.twig', [
            'links' => $links->visibleQb('r')->orderBy('r.order', 'DESC')->addOrderBy('r.id', 'ASC')->getQuery()->getResult(),
        ]);
    }

    #[Route('/kontakt', name: 'contact', methods: ['GET', 'HEAD'])]
    public function contact(ContactRepository $contacts): Response
    {
        return $this->render('public/contact.html.twig', ['contact' => $contacts->latest()]);
    }

    private function renderPost(Post $post, string $kind): Response
    {
        return $this->render('public/post_detail.html.twig', [
            'post' => $post,
            'kind' => $kind,
            'photos' => array_values(array_filter(iterator_to_array($post->getPhotos()), static fn ($p): bool => $p->isVisible())),
        ]);
    }
}
