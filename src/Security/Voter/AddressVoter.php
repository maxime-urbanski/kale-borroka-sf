<?php

declare(strict_types=1);

namespace App\Security\Voter;

use App\Entity\Address;
use App\Entity\User;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Vote;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

/**
 * A customer edits, deletes and picks as default their own addresses only. Admins go
 * through the back office, not the account pages.
 *
 * @extends Voter<string, Address>
 */
class AddressVoter extends Voter
{
    public const string EDIT = 'ADDRESS_EDIT';

    protected function supports(string $attribute, mixed $subject): bool
    {
        return self::EDIT === $attribute && $subject instanceof Address;
    }

    protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token, ?Vote $vote = null): bool
    {
        $user = $token->getUser();

        return $user instanceof User && $subject->getUsers() === $user;
    }
}
