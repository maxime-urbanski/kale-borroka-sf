<?php

declare(strict_types=1);

namespace App\Order\Exception;

/**
 * The order cannot be placed as requested (empty cart, address of another customer, …).
 * The message is meant for the customer.
 */
class InvalidOrderException extends \DomainException
{
}
