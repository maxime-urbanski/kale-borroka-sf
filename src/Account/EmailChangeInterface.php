<?php

declare(strict_types=1);

namespace App\Account;

use App\Entity\User;
use Symfony\Component\HttpFoundation\Request;

/**
 * A new e-mail is only taken once the customer opens the link mailed to it: a typo cannot
 * lock them out, and nobody can move an account to an address they do not read.
 */
interface EmailChangeInterface
{
    /** How long the link works. */
    public const string LIFETIME = 'PT24H';

    /**
     * Mails the confirmation link to the new address. Nothing changes until it is opened.
     */
    public function request(User $user, string $newEmail): void;

    /**
     * Applies the change the link carries, and tells the old address. Returns why not, if it
     * cannot: a link expired, altered, already used or for another account, or an address
     * taken in the meantime.
     */
    public function confirm(User $user, Request $request): ?string;
}
