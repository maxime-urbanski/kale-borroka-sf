<?php

namespace App\Controller\User;

use App\Repository\OrderRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

class AccountOrderController extends AbstractController
{
    #[Route('/mon-compte/mes-commandes', name: 'app_user_orders')]
    #[isGranted('IS_AUTHENTICATED_FULLY')]
    public function index(
        Security $security,
        OrderRepository $orderRepository
    ): Response
    {
        $user = $security->getUser();

        $userOrders = $orderRepository->findBy([
            'buyer' => $user
        ]);

        return $this->render('user/order.html.twig', [
            'orders' => $userOrders
        ]);
    }
}
