<?php

declare(strict_types=1);

namespace App\Security;

use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Twig\Attribute\AsTwigFunction;

/**
 * Content-Security-Policy of the HTML pages. Scripts only come from our own origin, plus
 * inline scripts carrying the page's nonce: `csp_nonce('script')` in Twig, which EasyAdmin
 * calls on its own (`{% guard function csp_nonce %}`). Styles stay 'unsafe-inline': the
 * templates and EasyAdmin use style="" attributes, and a style nonce in the header would
 * make browsers ignore 'unsafe-inline' for them (CSP has no per-attribute exception that
 * every browser supports), while inline styles cannot run code.
 *
 * The nonce is kept on the main request, never on the service: in FrankenPHP worker mode
 * the service outlives the request. nosniff, framing and referrer headers come from Caddy.
 */
final readonly class ContentSecurityPolicy
{
    private const string NONCE_ATTRIBUTE = '_csp_script_nonce';

    public function __construct(
        private RequestStack $requestStack,
    ) {
    }

    /**
     * Same nonce for every inline script and style of the page, sub-requests included.
     */
    #[AsTwigFunction('csp_nonce')]
    public function nonce(string $usage = 'script'): string
    {
        $request = $this->requestStack->getMainRequest();

        if (null === $request) {
            return '';
        }

        if (!$request->attributes->has(self::NONCE_ATTRIBUTE)) {
            $request->attributes->set(self::NONCE_ATTRIBUTE, rtrim(strtr(base64_encode(random_bytes(18)), '+/', '-_'), '='));
        }

        return (string) $request->attributes->get(self::NONCE_ATTRIBUTE);
    }

    #[AsEventListener(event: KernelEvents::RESPONSE)]
    public function onResponse(ResponseEvent $event): void
    {
        $response = $event->getResponse();

        if (!$event->isMainRequest()
            || $response->headers->has('Content-Security-Policy')
            || !str_contains((string) $response->headers->get('Content-Type', 'text/html'), 'html')
        ) {
            return;
        }

        $nonce = $event->getRequest()->attributes->get(self::NONCE_ATTRIBUTE);
        $scripts = null === $nonce ? "'self'" : \sprintf("'self' 'nonce-%s'", $nonce);

        $response->headers->set('Content-Security-Policy', implode('; ', [
            "default-src 'self'",
            'script-src '.$scripts,
            "style-src 'self' 'unsafe-inline'",
            "img-src 'self' data:",
            "font-src 'self' data:",
            "connect-src 'self'",
            "object-src 'none'",
            "base-uri 'self'",
            "form-action 'self'",
            "frame-ancestors 'self'",
        ]));
    }
}
