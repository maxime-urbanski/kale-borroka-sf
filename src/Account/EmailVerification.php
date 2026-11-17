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
 * The link is signed (UriSigner, expiry) and carries the account id and a fingerprint of the
 * address it was mailed to: a change of address kills older links. Opening it only shows a
 * button (mail scanners open links on their own), the POST it sends proves the address.
 */
final readonly class EmailVerification implements EmailVerificationInterface
{
    public function __construct(
        private UriSigner $uriSigner,
        private UrlGeneratorInterface $urlGenerator,
        private MailerInterface $mailer,
        private UserRepository $userRepository,
        private EntityManagerInterface $entityManager,
    ) {
    }

    public function send(User $user): void
    {
        $link = $this->uriSigner->sign($this->urlGenerator->generate('app_email_verify', [
            'id' => $user->getId(),
            'k' => self::fingerprint((string) $user->getEmail()),
        ], UrlGeneratorInterface::ABSOLUTE_URL), new \DateInterval(self::LIFETIME));

        // No from(): every mail leaves from MAILER_DEFAULT_SENDER (mailer.yaml).
        $this->mailer->send(new TemplatedEmail()
            ->to((string) $user->getEmail())
            ->subject('Confirme ton adresse e-mail')
            ->htmlTemplate('user/email/verify_email.html.twig')
            ->textTemplate('user/email/verify_email.txt.twig')
            ->context(['link' => $link]));
    }

    public function check(Request $request): ?User
    {
        if (!$this->uriSigner->checkRequest($request)) {
            return null;
        }

        $user = $this->userRepository->find($request->query->getInt('id'));

        return $user instanceof User
            && hash_equals(self::fingerprint((string) $user->getEmail()), $request->query->getString('k'))
            ? $user
            : null;
    }

    public function confirm(Request $request): ?User
    {
        $user = $this->check($request);

        if (null !== $user && !$user->isEmailVerified()) {
            $user->markEmailVerified();
            $this->entityManager->flush();
        }

        return $user;
    }

    private static function fingerprint(string $email): string
    {
        return substr(hash('sha256', mb_strtolower($email)), 0, 24);
    }
}
