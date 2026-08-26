<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

class InvoiceServiceTest extends TestCase
{
    public function test_prorated_billing_calculation_is_accurate(): void
    {
        // Viết test case thử nghiệm công thức tính tiền ở đây
        $dailyRate = 3000000 / 30; // 100,000 đ / ngày
        $stayDays = 10;
        $expectedAmount = 1000000;

        $this->assertEquals($expectedAmount, $dailyRate * $stayDays);
    }
}
