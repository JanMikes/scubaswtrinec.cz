<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\Discussion;
use App\Form\DiscussionPostType;
use App\Guestbook\SpamClassifier;
use App\Repository\DiscussionBanRepository;
use App\Repository\DiscussionRepository;
use App\Service\FormTimeToken;
use App\Service\Pager;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Form\FormError;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\RateLimiter\RateLimiterFactoryInterface;
use Symfony\Component\Routing\Attribute\Route;

/**
 * The guest book. Layered spam defence, cheapest first; every rejection is logged (channel
 * "guestbook", with the reason and the real client IP) and answered with the same generic message,
 * so a bot learns nothing about which rule caught it:
 *   1. honeypot field + signed fill-time token (FormTimeToken: >= 3 s, <= 6 h)
 *   2. IP bans (discussion_ban) and a per-IP rate limit (3 posts / 10 min)
 *   3. content rules (SpamClassifier): clear spam rejected, suspicious posts stored hidden for moderation
 * Escalation path if this ever stops being enough: a self-hosted proof-of-work captcha (ALTCHA), see
 * docs/guestbook-spam.md.
 */
final class DiscussionController extends AbstractController
{
    public const string GENERIC_ERROR = 'Příspěvek se nepodařilo odeslat. Zkontrolujte prosím text a zkuste to znovu za chvíli.';
    public const string PENDING_NOTICE = 'Děkujeme! Váš příspěvek se zobrazí po schválení správcem.';

    #[Route('/diskuze', name: 'discussion', methods: ['GET', 'HEAD', 'POST'])]
    public function __invoke(
        Request $request,
        DiscussionRepository $discussions,
        DiscussionBanRepository $bans,
        FormTimeToken $timeToken,
        SpamClassifier $classifier,
        EntityManagerInterface $em,
        #[Autowire(service: 'monolog.logger.guestbook')]
        LoggerInterface $logger,
        #[Autowire(service: 'limiter.discussion_post')]
        RateLimiterFactoryInterface $postLimiter,
    ): Response {
        $post = new Discussion();
        $form = $this->createForm(DiscussionPostType::class, $post);
        $form->handleRequest($request);

        if ($form->isSubmitted()) {
            $ip = $request->getClientIp();
            $rejection = match (true) {
                '' !== trim((string) $form->get('website')->getData()) => 'honeypot filled',
                !$timeToken->isValid((string) $form->get('ts')->getData()) => 'fill-time token invalid, too fast or expired',
                $bans->isBanned($ip) => 'banned IP',
                default => null,
            };

            if (null === $rejection && $form->isValid()) {
                $verdict = $classifier->classify($post->author, $post->subject, $post->text);
                if ($verdict->isRejected()) {
                    $rejection = 'content: '.$verdict->reason;
                } elseif (!$postLimiter->create($ip ?? 'unknown')->consume()->isAccepted()) {
                    $rejection = 'rate limit';
                } else {
                    $post->ip = null === $ip ? null : substr($ip, 0, 45);
                    $post->additionalInfo = $request->headers->get('User-Agent');
                    $post->insProcess = 'web:discussion';
                    $post->active = $verdict->isAccepted();
                    $em->persist($post);
                    $em->flush();
                    if (!$verdict->isAccepted()) {
                        $logger->notice('Guest-book post held for moderation', ['id' => $post->getId(), 'ip' => $ip, 'reason' => $verdict->reason]);
                    }
                    $this->addFlash('success', $verdict->isAccepted() ? 'Vaše zpráva byla úspěšně odeslána!' : self::PENDING_NOTICE);

                    return $this->redirectToRoute('discussion');
                }
            }

            if (null !== $rejection) {
                $logger->warning('Guest-book post rejected', [
                    'reason' => $rejection,
                    'ip' => $ip,
                    'user_agent' => $request->headers->get('User-Agent'),
                    'author' => mb_substr((string) $post->author, 0, 50),
                ]);
                $form->addError(new FormError(self::GENERIC_ERROR));
            }
        }

        $qb = $discussions->visibleQb('r')->orderBy('r.id', \SortDirection::Descending);

        return $this->render('public/discussion.html.twig', [
            'form' => $form,
            // Rendered into the hidden `ts` field on every render (a corrected form needs a fresh one).
            'ts' => $timeToken->issue(),
            'pager' => Pager::fromQuery($qb, $request->query->get('page'), 10),
        ], new Response(status: $form->isSubmitted() ? Response::HTTP_UNPROCESSABLE_ENTITY : Response::HTTP_OK));
    }
}
