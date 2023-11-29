<?php

declare(strict_types=1);

namespace App\Data;

class UpdatePasswordData
{
    public string $current_password = '';
    public string $new_password = '';
}
