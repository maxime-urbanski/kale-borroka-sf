<?php

declare(strict_types=1);

namespace App\Controller\Security;

use App\Entity\User;
use App\Form\ChangePasswordFormType;
use App\Form\ResetPasswordRequestFormType;
use App\Repository\UserRepository;
use App\Security\UserAuthenticator;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Form\FormError;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\RateLimiter\RateLimiterFactoryInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\RememberMeBadge;
use Symfony\Component\Security\Http\Util\TargetPathTrait;
use SymfonyCasts\Bundle\ResetPassword\Controller\ResetPasswordControllerTrait;
use SymfonyCasts\Bundle\ResetPassword\Exception\ResetPasswordExceptionInterface;
use SymfonyCasts\Bundle\ResetPassword\ResetPasswordHelperInterface;

#[Route('/reset-password')]
class ResetPasswordController extends AbstractController
{
    use ResetPasswordControllerTrait;
    use TargetPathTrait;

    private const string REQUESTED_EMAIL = 'reset_password_requested_email';

    public function __construct(
        private readonly ResetPasswordHelperInterface $resetPasswordHelper,
        private readonly EntityManagerInterface $entityManager,
        private readonly UserRepository $userRepository,
    ) {
    }

    /**
     * Display & process form to request a password reset.
     *
     * @throws TransportExceptionInterface
     */
    #[Route('', name: 'app_forgot_password_request', methods: [Request::METHOD_GET, Request::METHOD_POST])]
    public function request(Request $request, MailerInterface $mailer, RateLimiterFactoryInterface $resetPasswordLimiter): Response
    {
        $form = $this->createForm(ResetPasswordRequestFormType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $limit = $resetPasswordLimiter->create($request->getClientIp())->consume();

            if (!$limit->isAccepted()) {
                $minutes = max(1, (int) ceil(($limit->getRetryAfter()->getTimestamp() - time()) / 60));
                $form->addError(new FormError(\sprintf('Trop de demandes depuis ta connexion : réessaie dans %d minute%s.', $minutes, $minutes > 1 ? 's' : '')));

                return $this->render('reset_password/request.html.twig', [
                    'requestForm' => $form,
                ], new Response(status: Response::HTTP_TOO_MANY_REQUESTS));
            }

            $email = User::normalizeEmail((string) $form->get('email')->getData());
            // Shown back on the next page whether or not an account exists, to spot a typo.
            $request->getSession()->set(self::REQUESTED_EMAIL, $email);

            return $this->processSendingPasswordResetEmail($email, $mailer);
        }

        // Passing the form, not its view, answers 422 when it is invalid.
        return $this->render('reset_password/request.html.twig', [
            'requestForm' => $form,
        ]);
    }

    /**
     * Confirmation page after a user has requested a password reset.
     */
    #[Route('/check-email', name: 'app_check_email', methods: [Request::METHOD_GET])]
    public function checkEmail(
        Request $request,
        #[Autowire(param: 'app.reset_password.throttle_limit')]
        int $throttleLimit,
    ): Response {
        // Generate a fake token if the user does not exist or someone hit this page directly.
        // This prevents exposing whether or not a user was found with the given email address or not
        if (null === ($resetToken = $this->getTokenObjectFromSession())) {
            $resetToken = $this->resetPasswordHelper->generateFakeResetToken();
        }

        return $this->render('reset_password/check_email.html.twig', [
            'resetToken' => $resetToken,
            // Read once: on a shared computer, the next visitor does not see it.
            'email' => $request->getSession()->remove(self::REQUESTED_EMAIL),
            'retryMinutes' => intdiv($throttleLimit, 60),
        ]);
    }

    /**
     * Validates and process the reset URL that the user clicked in their email.
     */
    #[Route('/reset/{token}', name: 'app_reset_password', methods: [Request::METHOD_GET, Request::METHOD_POST])]
    public function reset(Request $request, UserPasswordHasherInterface $passwordHasher, Security $security, ?string $token = null): Response
    {
        if ($token) {
            // We store the token in session and remove it from the URL, to avoid the URL being
            // loaded in a browser and potentially leaking the token to 3rd party JavaScript.
            $this->storeTokenInSession($token);

            return $this->redirectToRoute('app_reset_password');
        }

        $token = $this->getTokenFromSession();
        if (null === $token) {
            throw $this->createNotFoundException('No reset password token found in the URL or in the session.');
        }

        try {
            $user = $this->resetPasswordHelper->validateTokenAndFetchUser($token);
        } catch (ResetPasswordExceptionInterface) {
            // Invalid or expired alike: the bundle's reasons are long and say « vous ».
            $this->addFlash('reset_password_error', 'Ce lien ne marche plus : il a déjà servi ou il a expiré. Demande-en un nouveau.');

            return $this->redirectToRoute('app_forgot_password_request');
        }

        // The token is valid; allow the user to change their password.
        \assert($user instanceof User);
        $form = $this->createForm(ChangePasswordFormType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // A password reset token should be used only once, remove it.
            $this->resetPasswordHelper->removeResetRequest($token);

            // Encode(hash) the plain password, and set it.
            $encodedPassword = $passwordHasher->hashPassword(
                $user,
                $form->get('plainPassword')->getData()
            );

            $user->setPassword($encodedPassword);
            $this->entityManager->flush();

            // The session is cleaned up after the password has been changed.
            $this->cleanSessionAfterReset();

            // « Tu seras connecté·e juste après »: the link proved the mailbox, the form the
            // new password. A full login, so the account pages open, with the remember-me
            // cookie every login gets (always_remember_me).
            $security->login($user, UserAuthenticator::class, 'main', [new RememberMeBadge()]);
            $this->addFlash('success', 'Mot de passe modifié : tu es connecté·e.');

            // Back where the login was asked for (the order tunnel), else to the account.
            $targetPath = $this->getTargetPath($request->getSession(), 'main');
            $this->removeTargetPath($request->getSession(), 'main');

            return null !== $targetPath ? $this->redirect($targetPath) : $this->redirectToRoute('app_user_informations');
        }

        return $this->render('reset_password/reset.html.twig', [
            'resetForm' => $form,
        ]);
    }

    /**
     * @throws TransportExceptionInterface
     */
    private function processSendingPasswordResetEmail(string $emailFormData, MailerInterface $mailer): RedirectResponse
    {
        $user = $this->userRepository->findOneByEmail($emailFormData);

        // Do not reveal whether a user account was found or not.
        if (!$user) {
            return $this->redirectToRoute('app_check_email');
        }

        try {
            $resetToken = $this->resetPasswordHelper->generateResetToken($user);
        } catch (ResetPasswordExceptionInterface) {
            // Already asked within the throttle_limit: the previous link still works. Saying so
            // would tell that the account exists.
            return $this->redirectToRoute('app_check_email');
        }

        // No from(): every mail leaves from MAILER_DEFAULT_SENDER (mailer.yaml).
        $email = new TemplatedEmail()
            // The address only, and no name in the text either: the names are typed by whoever
            // registered, and nothing verifies that the address was theirs.
            ->to((string) $user->getEmail())
            ->subject('Réinitialise ton mot de passe')
            ->htmlTemplate('reset_password/email.html.twig')
            ->textTemplate('reset_password/email.txt.twig')
            ->context([
                'resetToken' => $resetToken,
            ])
        ;

        $mailer->send($email);

        // Store the token object in session for retrieval in check-email route.
        $this->setTokenObjectInSession($resetToken);

        return $this->redirectToRoute('app_check_email');
    }
}
