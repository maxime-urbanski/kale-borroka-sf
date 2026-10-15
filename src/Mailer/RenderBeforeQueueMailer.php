<?php

declare(strict_types=1);

namespace App\Mailer;

use Symfony\Component\DependencyInjection\Attribute\AsDecorator;
use Symfony\Component\DependencyInjection\Attribute\AutowireDecorated;
use Symfony\Component\Mailer\Envelope;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\BodyRendererInterface;
use Symfony\Component\Mime\Message;
use Symfony\Component\Mime\RawMessage;

/**
 * Renders templated mails during the request, before they are queued (messenger.yaml): the
 * mailer only renders a copy for its events and queues the templates, so Twig would run in
 * the worker. There, url() has no request (links to router.default_uri, not to the host
 * SYMFONY_TRUSTED_HOSTS checked), and the worker may run another version of the templates
 * during a deploy. Rendered, the queued mail no longer depends on either.
 */
#[AsDecorator('mailer.mailer')]
final readonly class RenderBeforeQueueMailer implements MailerInterface
{
    public function __construct(
        #[AutowireDecorated]
        private MailerInterface $mailer,
        private BodyRendererInterface $bodyRenderer,
    ) {
    }

    public function send(RawMessage $message, ?Envelope $envelope = null): void
    {
        // Does nothing for a mail without templates or already rendered.
        if ($message instanceof Message) {
            $this->bodyRenderer->render($message);
        }

        $this->mailer->send($message, $envelope);
    }
}
