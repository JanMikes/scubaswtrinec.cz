<?php

declare(strict_types=1);

namespace App\EventSubscriber;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * 301s for every URL shape the 2014 Nette router accepted, so old links and search results land on
 * the canonical page (crawled 2026-10-21, see docs/rebuild-checklist.md §1):
 *   /kontakt/           -> /kontakt         (trailing slash)
 *   /KONTAKT            -> /kontakt         (case)
 *   /detailAktuality/1  -> /detail-aktuality/1 (Nette camelCase action)
 *   /kontakt/5          -> /kontakt         (Nette `<action>[/<id>]` accepted any id on list pages)
 *   /default            -> /                (a lorem-ipsum template page)
 * Static user-data paths are case-sensitive files and are never rewritten.
 */
final class LegacyUrlSubscriber implements EventSubscriberInterface
{
    /** Old list actions => canonical path. */
    private const array LIST_ACTIONS = [
        'aktuality' => '/',
        'default' => '/',
        'clanky' => '/clanky',
        'galerie' => '/galerie',
        'instruktori' => '/instruktori',
        'diskuze' => '/diskuze',
        'odkazy' => '/odkazy',
        'kontakt' => '/kontakt',
    ];

    private const string STATIC_PREFIXES = '#^/(img|upload|images|assets|_profiler|_wdt|bundles)(/|$)#';

    public static function getSubscribedEvents(): array
    {
        // Before the router (priority 32).
        return [KernelEvents::REQUEST => ['onRequest', 64]];
    }

    public function onRequest(RequestEvent $event): void
    {
        if (!$event->isMainRequest()) {
            return;
        }
        $request = $event->getRequest();
        if (!\in_array($request->getMethod(), ['GET', 'HEAD'], true)) {
            return;
        }

        $path = $request->getPathInfo();
        if ('/' === $path || 1 === preg_match(self::STATIC_PREFIXES, $path) || str_contains($path, '.')) {
            return;
        }

        $canonical = $this->canonicalize($path);
        if ($canonical === $path) {
            return;
        }

        $qs = $request->getQueryString();
        $event->setResponse(new RedirectResponse($request->getBaseUrl().$canonical.(null !== $qs ? '?'.$qs : ''), 301));
    }

    public function canonicalize(string $path): string
    {
        $trimmed = rtrim($path, '/');
        if ('' === $trimmed) {
            return '/';
        }

        // camelCase -> kebab-case, then lower case (Nette treated actions case-insensitively).
        $segments = explode('/', ltrim($trimmed, '/'));
        $segments = array_map(
            static fn (string $s): string => strtolower((string) preg_replace('/(?<=[a-z0-9])([A-Z])/', '-$1', $s)),
            $segments,
        );

        if ('admin' !== $segments[0]) {
            $action = $segments[0];
            if (isset(self::LIST_ACTIONS[$action]) && ('default' === $action || 'aktuality' === $action || \count($segments) > 1)) {
                return self::LIST_ACTIONS[$action];
            }
        }

        return '/'.implode('/', $segments);
    }
}
