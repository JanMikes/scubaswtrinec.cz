<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\Discussion;
use App\Form\DiscussionPostType;
use App\Repository\DiscussionBanRepository;
use App\Repository\DiscussionRepository;
use App\Service\FormTimeToken;
use App\Service\Pager;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Form\FormError;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\RateLimiter\RateLimiterFactoryInterface;
use Symfony\Component\Routing\Attribute\Route;

final class DiscussionController extends AbstractController
{
    #[Route('/diskuze', name: 'discussion', methods: ['GET', 'HEAD', 'POST'])]
    public function __invoke(
        Request $request,
        DiscussionRepository $discussions,
        DiscussionBanRepository $bans,
        FormTimeToken $timeToken,
        EntityManagerInterface $em,
        #[Autowire(service: 'limiter.discussion_post')]
        RateLimiterFactoryInterface $postLimiter,
    ): Response {
        $post = new Discussion();
        $form = $this->createForm(DiscussionPostType::class, $post);
        $form->handleRequest($request);

        if ($form->isSubmitted()) {
            $ip = $request->getClientIp();
            $spam = '' !== trim((string) $form->get('website')->getData())
                || !$timeToken->isValid((string) $form->get('ts')->getData());
            if ($spam) {
                $form->addError(new FormError('Byl detekován pokus o spam.'));
            }
            if ($bans->isBanned($ip)) {
                $form->addError(new FormError('V diskuzi již nemůžete přispívat!'));
            }
            if ($form->isValid() && !$postLimiter->create($ip ?? 'unknown')->consume()->isAccepted()) {
                $form->addError(new FormError('Příliš mnoho příspěvků za krátkou dobu, zkuste to prosím později.'));
            }

            if ($form->isValid()) {
                $post->ip = null === $ip ? null : substr($ip, 0, 45);
                $post->additionalInfo = $request->headers->get('User-Agent');
                $post->insProcess = 'web:discussion';
                $em->persist($post);
                $em->flush();
                $this->addFlash('success', 'Vaše zpráva byla úspěšně odeslána!');

                return $this->redirectToRoute('discussion');
            }
        }

        $qb = $discussions->visibleQb('r')->orderBy('r.id', 'DESC');

        return $this->render('public/discussion.html.twig', [
            'form' => $form,
            // Rendered into the hidden `ts` field on every render (a corrected form needs a fresh one).
            'ts' => $timeToken->issue(),
            'pager' => Pager::fromQuery($qb, $request->query->get('page'), 10),
        ], new Response(status: $form->isSubmitted() ? Response::HTTP_UNPROCESSABLE_ENTITY : Response::HTTP_OK));
    }
}
