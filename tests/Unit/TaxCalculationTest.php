<?php

namespace Tests\Unit;

use App\Services\AmountInWords;
use App\Services\TaxCalculationService;
use PHPUnit\Framework\TestCase;

class TaxCalculationTest extends TestCase
{
    public function test_sample_invoice_totals_match_srs(): void
    {
        $service = new TaxCalculationService;

        $result = $service->calculate([
            ['amount' => 429000, 'tax_rate' => 18, 'hsn_sac' => '84615011'],
            ['amount' => 8500, 'tax_rate' => 18, 'hsn_sac' => '998898'],
        ], '24', '27');

        $this->assertSame('igst', $result['tax_type']);
        $this->assertEquals(437500.00, $result['taxable_amount']);
        $this->assertEquals(78750.00, $result['igst_amount']);
        $this->assertEquals(516250.00, $result['grand_total']);
        $this->assertEquals(0.00, $result['round_off']);

        $words = AmountInWords::convert(516250);
        $this->assertStringContainsString('Five Lakh Sixteen Thousand Two Hundred Fifty', $words);
    }

    public function test_quotation_discount_net_price(): void
    {
        $service = new TaxCalculationService;
        $line = $service->lineAmount(1, 500000, 10);

        $this->assertEquals(500000.00, $line['gross']);
        $this->assertEquals(50000.00, $line['discount_amount']);
        $this->assertEquals(450000.00, $line['amount']);
    }
}
