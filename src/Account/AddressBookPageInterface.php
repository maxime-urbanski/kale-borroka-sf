<?php

declare(strict_types=1);

namespace App\Account;

use App\Entity\Address;
use App\Entity\User;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Response;

interface AddressBookPageInterface
{
    /** Addresses a customer may keep. */
    public const int MAX_ADDRESSES = 5;

    /**
     * Mes adresses: the cards, and under them the form being filled, if any: a new address
     * when $editing is null, else the one being edited.
     *
     * @param FormInterface<mixed>|null $form
     * @param array<Address>|null       $addresses the customer's addresses, when already loaded
     */
    public function render(User $user, ?FormInterface $form = null, ?Address $editing = null, int $status = Response::HTTP_OK, ?array $addresses = null): Response;
}
