<?php

declare(strict_types=1);

namespace App\Account;

use App\Entity\Address;
use App\Entity\User;
use App\Repository\AddressRepository;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Response;
use Twig\Environment;

/**
 * Shared by the page itself and by the add and edit actions, which show it again with the
 * errors of the form they received.
 */
final readonly class AddressBookPage implements AddressBookPageInterface
{
    public function __construct(
        private AddressRepository $addressRepository,
        private Environment $twig,
    ) {
    }

    public function render(User $user, ?FormInterface $form = null, ?Address $editing = null, int $status = Response::HTTP_OK, ?array $addresses = null): Response
    {
        return new Response($this->twig->render('user/addresses.html.twig', [
            'addresses' => $addresses ?? $this->addressRepository->getAllUserAddresses($user),
            'form' => $form?->createView(),
            'editing' => $editing,
            'max' => self::MAX_ADDRESSES,
        ]), $status);
    }
}
