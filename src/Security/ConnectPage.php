<?php

declare(strict_types=1);

namespace App\Security;

use App\Entity\User;
use App\Form\RegistrationFormType;
use App\Repository\PageRepository;
use App\Routing\PageMatcher;
use App\Service\CartInterface;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;
use Symfony\Component\Security\Http\Util\TargetPathTrait;
use Twig\Environment;

/**
 * One page, two columns: log in or create an account (/login and /register show both, the
 * route only picks the tab shown on mobile and the page's h1). Coming from the cart, a banner
 * says it is kept; the authenticator then goes back to the order tunnel either way.
 */
final readonly class ConnectPage
{
    use TargetPathTrait;

    public const string LOGIN = 'login';
    public const string REGISTER = 'register';

    /** Linked from the terms checkbox when published. */
    public const string TERMS_SLUG = 'cgv';
    public const string PRIVACY_SLUG = 'politique-de-confidentialite';

    public function __construct(
        private Environment $twig,
        private FormFactoryInterface $formFactory,
        private UrlGeneratorInterface $urlGenerator,
        private AuthenticationUtils $authenticationUtils,
        private RequestStack $requestStack,
        private PageMatcher $pageMatcher,
        private CartInterface $cart,
        private PageRepository $pages,
    ) {
    }

    /**
     * @return FormInterface<mixed>
     */
    public function registrationForm(User $user): FormInterface
    {
        return $this->formFactory->create(RegistrationFormType::class, $user, [
            'action' => $this->urlGenerator->generate('app_register'),
        ]);
    }

    /**
     * @param self::LOGIN|self::REGISTER $tab
     * @param FormInterface<mixed>|null  $registrationForm the submitted one, or a blank one when null
     */
    public function render(string $tab, ?FormInterface $registrationForm = null): Response
    {
        $registrationForm ??= $this->registrationForm(new User());
        $cart = $this->fromCart() ? $this->cart->getFullCart() : [];

        $response = new Response($this->twig->render('security/connect.html.twig', [
            'tab' => $tab,
            'error' => $this->authenticationUtils->getLastAuthenticationError(),
            'last_username' => $this->authenticationUtils->getLastUsername(),
            'registrationForm' => $registrationForm->createView(),
            'cart' => [] === $cart ? null : [
                'items' => $this->cart->countItems(),
                'total' => $this->cart->getTotal($cart),
            ],
            'legal' => $this->pages->findPublishedSlugs([self::TERMS_SLUG, self::PRIVACY_SLUG]),
        ]));

        // As AbstractController::render() does for an invalid form.
        if ($registrationForm->isSubmitted() && !$registrationForm->isValid()) {
            $response->setStatusCode(Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        return $response;
    }

    /**
     * Whether the visitor was sent here by « Valider le panier »: the entry of the order tunnel,
     * not any order page (a past order's page has nothing to do with the cart).
     */
    private function fromCart(): bool
    {
        $session = $this->requestStack->getSession();
        $targetPath = $this->getTargetPath($session, 'main');
        $path = null === $targetPath ? null : parse_url($targetPath, \PHP_URL_PATH);

        if (!\is_string($path)) {
            return false;
        }

        $route = $this->pageMatcher->match($path)['_route'] ?? null;

        return 'app_order_delivery' === $route;
    }
}
