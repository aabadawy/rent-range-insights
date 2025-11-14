<?php

namespace App\Queries;

use App\ValueObjects\Money;

class MaxRentQuery
{
    public function handle(): Money
    {
        return Money::make(0);
    }
}
