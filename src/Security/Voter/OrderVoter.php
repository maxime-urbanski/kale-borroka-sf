<?php

declare(strict_types=1);

namespace App\Security\Voter;

use App\Entity\Order;
use App\Entity\User;
use App\Enum\OrderStatus;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Vote;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

/**
 * A customer sees and cancels their own orders only — cancelling only while unpaid.
 * Admins see every order; whether they can cancel it is up to the workflow.
 *
 * @extends Voter<string, Order>
 */
class OrderVoter extends Voter
{
    public const string VIEW = 'ORDER_VIEW';
    public const string CANCEL = 'ORDER_CANCEL';

    public function __construct(
        private readonly Security $security,
    ) {
    }

    protected function supports(string $attribute, mixed $subject): bool
    {
        return \in_array($attribute, [self::VIEW, self::CANCEL], true) && $subject instanceof Order;
    }

    protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token, ?Vote $vote = null): bool
    {
        $user = $token->getUser();

        if (!$user instanceof User) {
            return false;
        }

        if ($this->security->isGranted('ROLE_ADMIN')) {
            return true;
        }

        if ($subject->getBuyer() !== $user) {
            return false;
        }

        return match ($attribute) {
            self::VIEW => true,
            self::CANCEL => OrderStatus::PENDING === $subject->getStatus(),
            default => false,
        };
    }
}
