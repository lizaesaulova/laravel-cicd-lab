<?php

declare(strict_types=1);

namespace App\Services;

use InvalidArgumentException;

final class DiscountCalculator
{
    public function calculate(float $price, int $discountPercent): float
    {
        if ($price < 0.0) {
            throw new InvalidArgumentException('Price cannot be negative.');
        }

        if ($discountPercent < 0 || $discountPercent > 100) {
            throw new InvalidArgumentException('Discount must be between 0 and 100.');
        }

        $discountMultiplier = 1 - ($discountPercent / 100);

        return round($price * $discountMultiplier, 2);
    }
}
