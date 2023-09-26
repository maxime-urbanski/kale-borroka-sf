<?php

namespace App\Controller;

use App\Service\CartService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\RouterInterface;

class CartController extends AbstractController
{
    #[Route('/cart', 'app_cart_index')]
    public function index(CartService $cartService): Response
    {
        $cart = $cartService->getFullCart();
        $total = $cartService->getTotal();

        return $this->render('cart/index.html.twig', [
            'cart' => $cart,
            'total' => $total,
        ]);
    }

    /**
     * @throws \Exception
     */
    #[Route('/cart/add/{id}', 'app_cart_add')]
    public function addToCart(
        CartService $cartService,
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

        unset($routeControllerName, $routeInfos['_controller']);

        try {
            $cartService->addToCart($id);
            $this->addFlash('success', 'article ajouté au panier');
        } catch (\Exception $e) {
            throw new \Exception($e);
        }

        return $this->redirectToRoute($routeInfos['_route'], $routeInfos);
    }

    #[Route('/cart/remove/{id}', 'app_cart_remove')]
    public function removeToCart(
        CartService $cartService,
        int $id
    ): Response {
        $cartService->removeToCart($id);

        return $this->redirectToRoute('app_cart_index');
    }
}
