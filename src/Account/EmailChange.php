<?php

declare(strict_types=1);

namespace App\Account;

use App\Entity\User;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\UriSigner;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

/**
 * The link is signed (UriSigner, kernel secret, expiry) and bound to the address it replaces
 * (`from`, a fingerprint of it): once the e-mail has changed, every older link stops working.
 */
final readonly class EmailChange implements EmailChangeInterface
{
    public function __construct(
        private UriSigner $uriSigner,
        private UrlGeneratorInterface $urlGenerator,
        private MailerInterface $mailer,
        private UserRepository $userRepository,
        private EntityManagerInterface $entityManager,
    ) {
    }

    public function request(User $user, string $newEmail): void
    {
        $newEmail = mb_strtolower($newEmail);
        $link = $this->uriSigner->sign($this->urlGenerator->generate('app_user_email_confirm', [
            'email' => $newEmail,
            'from' => self::fingerprint((string) $user->getEmail()),
        ], UrlGeneratorInterface::ABSOLUTE_URL), new \DateInterval(self::LIFETIME));

        // No from(): every mail leaves from MAILER_DEFAULT_SENDER (mailer.yaml).
        $this->mailer->send(new TemplatedEmail()
            ->to($newEmail)
            ->subject('Confirme ta nouvelle adresse e-mail')
            ->htmlTemplate('user/email/confirm_email.html.twig')
            ->textTemplate('user/email/confirm_email.txt.twig')
            ->context(['link' => $link]));
    }

    public function confirm(User $user, Request $request): ?string
    {
        $email = $request->query->getString('email');

        if (!$this->uriSigner->checkRequest($request)
            || !hash_equals(self::fingerprint((string) $user->getEmail()), $request->query->getString('from'))) {
            return 'Ce lien ne marche plus : il a expiré, ou ton adresse a changé depuis. Redemande un changement d’adresse.';
        }

        $owner = $this->userRepository->findOneByEmail($email);

        if (null !== $owner && $owner !== $user) {
            return 'Un compte utilise déjà cette adresse e-mail.';
        }

        $oldEmail = (string) $user->getEmail();
        $user->setEmail($email);
        $this->entityManager->flush();

        // Someone else may have done it (an open session): the old address hears of it.
        $this->mailer->send(new TemplatedEmail()
            ->to($oldEmail)
            ->subject('L’adresse e-mail de ton compte a changé')
            ->htmlTemplate('user/email/email_changed.html.twig')
            ->textTemplate('user/email/email_changed.txt.twig')
            ->context(['newEmail' => $email]));

        return null;
    }

    private static function fingerprint(string $email): string
    {
        return substr(hash('sha256', mb_strtolower($email)), 0, 24);
    }
}
