<?php

declare(strict_types=1);

namespace App\Security;

use Symfony\Component\DependencyInjection\Attribute\Autowire;
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
 * One nonce per usage: EasyAdmin prints the style one in plain view (<meta name="csp-nonce">),
 * so it must never unlock scripts. Nonces are kept on the main request, never on the
 * service: in FrankenPHP worker mode the service outlives the request. No policy on the
 * debug error page, whose inline scripts carry no nonce. nosniff, framing and referrer
 * headers come from Caddy.
 */
final readonly class ContentSecurityPolicy
{
    private const string NONCE_ATTRIBUTE = '_csp_nonce_';

    public function __construct(
        private RequestStack $requestStack,
        #[Autowire('%kernel.debug%')]
        private bool $debug,
    ) {
    }

    /**
     * Same nonce for a given usage across the page, sub-requests included.
     */
    #[AsTwigFunction('csp_nonce')]
    public function nonce(string $usage = 'script'): string
    {
        $request = $this->requestStack->getMainRequest();

        if (null === $request) {
            return '';
        }

        $attribute = self::NONCE_ATTRIBUTE.('script' === $usage ? 'script' : 'other');

        if (!$request->attributes->has($attribute)) {
            $request->attributes->set($attribute, rtrim(strtr(base64_encode(random_bytes(18)), '+/', '-_'), '='));
        }

        return (string) $request->attributes->get($attribute);
    }

    #[AsEventListener(event: KernelEvents::RESPONSE)]
    public function onResponse(ResponseEvent $event): void
    {
        $response = $event->getResponse();

        if (!$event->isMainRequest()
            || $response->headers->has('Content-Security-Policy')
            || !str_contains((string) $response->headers->get('Content-Type', 'text/html'), 'html')
            || ($this->debug && $response->headers->has('X-Debug-Exception'))
        ) {
            return;
        }

        $nonce = $event->getRequest()->attributes->get(self::NONCE_ATTRIBUTE.'script');
        $scripts = null === $nonce ? "'self'" : \sprintf("'self' 'nonce-%s'", $nonce);

        $response->headers->set('Content-Security-Policy', implode('; ', [
            "default-src 'self'",
            'script-src '.$scripts,
            "style-src 'self' 'unsafe-inline'",
            // CMS pages may show https images (the rich text sanitizer keeps them); blob: for
            // the previews of the back office's editor. Images cannot run code.
            "img-src 'self' data: blob: https:",
            "font-src 'self' data:",
            "connect-src 'self'",
            "object-src 'none'",
            "base-uri 'self'",
            "form-action 'self'",
            "frame-ancestors 'self'",
        ]));
    }
}
