<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Services\DiscountCalculator;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

final class ExampleTest extends TestCase
{
    private DiscountCalculator $discountCalculator;

    protected function setUp(): void
    {
        parent::setUp();

        $this->discountCalculator = new DiscountCalculator;
    }

    public function test_it_calculates_a_discount(): void
    {
        $resultPrice = $this->discountCalculator->calculate(200.0, 25);

        self::assertSame(150.0, $resultPrice);
    }

    public function test_it_allows_a_full_discount(): void
    {
        $resultPrice = $this->discountCalculator->calculate(99.99, 100);

        self::assertSame(0.0, $resultPrice);
    }

    public function test_it_rejects_a_negative_price(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Price cannot be negative.');

        $this->discountCalculator->calculate(-1.0, 10);
    }

    public function test_it_rejects_an_invalid_discount(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Discount must be between 0 and 100.');

        $this->discountCalculator->calculate(100.0, 101);
    }
}
