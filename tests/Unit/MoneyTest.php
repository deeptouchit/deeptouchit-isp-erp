<?php

namespace Tests\Unit;

use App\Support\Money;
use Tests\TestCase;

class MoneyTest extends TestCase
{
    public function test_money_addition_uses_decimal_precision(): void
    {
        $total = Money::of('10.1250')->add(Money::of('0.0050'));

        $this->assertSame('10.1300', $total->decimal());
    }
}
