<?php

declare(strict_types=1);

namespace App\Wantlist;

use App\Catalog\RecordNames;
use App\Entity\Article;
use App\Repository\WishlistItemRepository;
use Psr\Log\LoggerInterface;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\Mailer\MailerInterface;

/**
 * Wantlist alert (maquette 11): one e-mail to each user whose wantlist holds the article with
 * its alert on, when the article can be bought again. Sent on the return only (isReturn()),
 * by BackInStockListener and StockManager::putBack(): one e-mail per return, never a
 * reminder. The mails are queued (messenger.yaml), so each subscriber costs a render, not an
 * SMTP exchange.
 */
final readonly class BackInStockAlert
{
    public function __construct(
        private WishlistItemRepository $wishlistItemRepository,
        private MailerInterface $mailer,
        private LoggerInterface $logger,
    ) {
    }

    /**
     * Whether the article, on sale now, was not before: sold out or offline.
     */
    public static function isReturn(bool $wasPublished, int $stockBefore, Article $article): bool
    {
        return !($wasPublished && $stockBefore > 0) && $article->isInStock();
    }

    public function send(Article $article): void
    {
        $names = RecordNames::of($article);

        try {
            $emails = $this->wishlistItemRepository->emailsWithAlertOn($article);
        } catch (\Throwable $exception) {
            $this->logger->error('Wantlist alert subscribers not read for article {article}: {message}', [
                'article' => $article->getId(),
                'message' => $exception->getMessage(),
                'exception' => $exception,
            ]);

            return;
        }

        foreach ($emails as $email) {
            try {
                // No from(): every mail leaves from MAILER_DEFAULT_SENDER (mailer.yaml).
                $this->mailer->send(new TemplatedEmail()
                    ->to($email)
                    ->subject('De retour : '.($names['artist'] ? $names['artist'].' — ' : '').$names['title'])
                    ->htmlTemplate('wantlist/back_in_stock.html.twig')
                    ->textTemplate('wantlist/back_in_stock.txt.twig')
                    ->context(['article' => $article, 'names' => $names]));
            } catch (\Throwable $exception) {
                // An alert is a courtesy: it must not undo the stock change (a cancelled order,
                // a back-office save) that triggered it.
                $this->logger->error('Wantlist alert not queued for article {article}: {message}', [
                    'article' => $article->getId(),
                    'message' => $exception->getMessage(),
                    'exception' => $exception,
                ]);
            }
        }
    }
}
