<?php

declare(strict_types=1);

namespace App\Account;

use App\Entity\User;
use Symfony\Component\HttpFoundation\Request;

/**
 * The label mails the buyer (payment link, parcel): an order waits until the address is proven
 * by opening a link mailed to it. The account itself works before that.
 */
interface EmailVerificationInterface
{
    /** How long the link works. */
    public const string LIFETIME = 'PT48H';

    /** Mails the signed link to the account's current address. */
    public function send(User $user): void;

    /**
     * The account the link proves the address of, or null when it does not work: altered,
     * expired, or for an address the account no longer has. Changes nothing.
     */
    public function check(Request $request): ?User;

    /** Marks the account check() returned as verified (flushed). Twice is harmless. */
    public function confirm(User $user): void;
}
