<?php

declare(strict_types=1);

namespace App\Data;

use App\Entity\Address;
use App\Entity\Payment;
use App\Entity\Transporter;

class OrderDeliveryDto
{
    public ?Address $deliveryAddress = null;

    public ?Transporter $transporter = null;

    public ?Payment $paymentMethod = null;
}
