<?php

declare(strict_types=1);

namespace App\Controller;

use App\Repository\ActualityRepository;
use App\Repository\ArticleRepository;
use App\Repository\GalleryRepository;
use Doctrine\DBAL\Connection;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class SystemController extends AbstractController
{
    /** Process is up (Traefik LB check). */
    #[Route('/-/health-check/liveness', name: 'health_liveness', methods: ['GET', 'HEAD'])]
    public function liveness(): JsonResponse
    {
        return new JsonResponse(['status' => 'ok']);
    }

    /** Process + database (Docker healthcheck, blackbox): a DB-less probe must not stay green (D58). */
    #[Route('/-/health-check/readiness', name: 'health_readiness', methods: ['GET', 'HEAD'])]
    public function readiness(Connection $db): JsonResponse
    {
        try {
            $db->executeQuery('SELECT COUNT(*) FROM `actuality`')->fetchOne();
        } catch (\Throwable) {
            return new JsonResponse(['status' => 'error', 'database' => 'unreachable'], Response::HTTP_SERVICE_UNAVAILABLE);
        }

        return new JsonResponse(['status' => 'ok', 'database' => 'ok']);
    }

    #[Route('/sitemap.xml', name: 'sitemap', methods: ['GET', 'HEAD'])]
    public function sitemap(ActualityRepository $actualities, ArticleRepository $articles, GalleryRepository $galleries): Response
    {
        $response = $this->render('public/sitemap.xml.twig', [
            'actualities' => $actualities->visibleQb('r')->orderBy('r.date', 'DESC')->getQuery()->getResult(),
            'articles' => $articles->visibleQb('r')->orderBy('r.date', 'DESC')->getQuery()->getResult(),
            'galleries' => $galleries->visibleQb('r')->orderBy('r.order', 'DESC')->getQuery()->getResult(),
        ]);
        $response->headers->set('Content-Type', 'application/xml; charset=UTF-8');

        return $response;
    }
}
