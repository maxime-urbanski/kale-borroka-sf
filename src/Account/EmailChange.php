<?php

declare(strict_types=1);

namespace App\Account;

use App\Entity\User;
use App\Repository\UserRepository;
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\UriSigner;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

/**
 * The link is signed (UriSigner, kernel secret, expiry), works for the pending address only
 * (User::$pendingEmail: the latest request, cleared once used or by a password change) and is
 * bound to the address it replaces (`from`, a fingerprint of it).
 */
final readonly class EmailChange implements EmailChangeInterface
{
    private const string EXPIRED = 'Ce lien ne marche plus : il a expiré, une demande plus récente l’a remplacé, ou ton adresse a changé depuis. Redemande un changement d’adresse.';

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
        $user->setPendingEmail($newEmail);
        $newEmail = (string) $user->getPendingEmail();
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

        // Nothing changes without the password (ConfirmEmailChangeController): if the request
        // came from a session left open, this tells the owner to change it.
        $this->mailer->send(new TemplatedEmail()
            ->to((string) $user->getEmail())
            ->subject('Changement d’adresse e-mail demandé')
            ->htmlTemplate('user/email/email_change_requested.html.twig')
            ->textTemplate('user/email/email_change_requested.txt.twig')
            ->context(['newEmail' => $newEmail]));
    }

    public function check(User $user, Request $request): ?string
    {
        $valid = $this->uriSigner->checkRequest($request)
            && null !== $user->getPendingEmail()
            && hash_equals($user->getPendingEmail(), $request->query->getString('email'))
            && hash_equals(self::fingerprint((string) $user->getEmail()), $request->query->getString('from'));

        return $valid ? null : self::EXPIRED;
    }

    public function confirm(User $user, Request $request): ?string
    {
        $failure = $this->check($user, $request);

        if (null !== $failure) {
            return $failure;
        }

        $email = $request->query->getString('email');
        $owner = $this->userRepository->findOneByEmail($email);

        if (null !== $owner && $owner !== $user) {
            return 'Un compte utilise déjà cette adresse e-mail.';
        }

        $oldEmail = (string) $user->getEmail();
        $user->setEmail($email)->setPendingEmail(null);

        try {
            $this->entityManager->flush();
        } catch (UniqueConstraintViolationException) {
            // Taken between the check and the flush.
            return 'Un compte utilise déjà cette adresse e-mail.';
        }

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
