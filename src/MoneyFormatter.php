<?php

namespace App;

use Brick\Money\Money;

class MoneyFormatter
{
    public static function format(Money $money): string
    {
        return '€ ' . number_format($money->getAmount()->toFloat(), 2, ',', '.');
    }
}