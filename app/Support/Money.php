<?php

namespace App\Support;

final class Money
{
    /**
     * Format a numeric amount for display with the Euro symbol (suffix).
     */
    public static function eur(float|int|string|null $amount, int $decimals = 2): string
    {
        $value = is_numeric($amount) ? (float) $amount : 0.0;

        return number_format($value, $decimals, '.', '').' €';
    }
}
