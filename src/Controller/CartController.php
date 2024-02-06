<?php

declare(strict_types=1);

namespace App\Controller;

use App\Service\CartServiceInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\RouterInterface;

#[Route('/cart', name: 'app_cart_')]
class CartController extends AbstractController
{
    public function __construct(private readonly CartServiceInterface $cartService)
    {
    }

    #[Route('/', 'index')]
    public function index(): Response
    {
        $cart = $this->cartService->getFullCart();
        $total = $this->cartService->getTotal();

        return $this->render('cart/index.html.twig', [
            'cart' => $cart,
            'total' => $total,
        ]);
    }

    /**
     * @throws \Exception
     */
    #[Route('/add/{id}', 'add')]
    public function addToCart(
        Request $request,
        RouterInterface $router,
        int $id
    ): ?Response {
        $routeReferer = (string) $request->headers->get('referer');
        $refererPathInfo = Request::create($routeReferer)->getPathInfo();
        $routeInfos = $router->match($refererPathInfo);

        if (!$routeInfos) {
            return null;
        }

        unset($routeInfos['_controller']);

        try {
            $this->cartService->addToCart($id);
            $this->addFlash('success', 'article ajouté au panier');
        } catch (\Exception $e) {
            throw new \Exception('error');
        }

        return $this->redirectToRoute($routeInfos['_route'], $routeInfos);
    }

    #[Route('/remove/{id}', 'remove')]
    public function removeToCart(int $id): Response
    {
        $this->cartService->removeToCart($id);

        return $this->redirectToRoute('app_cart_index');
    }

    #[Route('/empty_cart', 'empty')]
    public function emptyCart(): Response
    {
        $this->cartService->removeAll();

        return $this->redirectToRoute('app_cart_index');
    }

    #[Route('/add_quantity/{id}', 'add_quantity')]
    public function addQuantity(int $id): Response
    {
        $this->cartService->addQuantity($id);

        return $this->redirectToRoute('app_cart_index');
    }

    #[Route('/remove_quantity/{id}', 'remove_quantity')]
    public function removeQuantity(int $id): Response
    {
        $this->cartService->removeQuantity($id);

        return $this->redirectToRoute('app_cart_index');
    }
}
