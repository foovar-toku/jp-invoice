<?php

declare(strict_types=1);

namespace Foovar\JpInvoice\Tests;

use Foovar\JpInvoice\Enum\PriceMode;
use Foovar\JpInvoice\Enum\RoundingMode;
use Foovar\JpInvoice\Enum\TaxRate;
use Foovar\JpInvoice\Invoice;
use Foovar\JpInvoice\Issuer;
use Foovar\JpInvoice\LineItem;
use PHPUnit\Framework\TestCase;

/**
 * 要件定義書 §8.1 の受入条件（T-01 〜 T-07）。
 *
 * これらは仕様そのものであり、1件でも落ちたら実装が誤っている。
 */
final class CalculationTest extends TestCase
{
    private function invoice(RoundingMode $rounding, PriceMode $priceMode = PriceMode::TAX_EXCLUDED): Invoice
    {
        return new Invoice(
            issuer: new Issuer('テスト事業者', 'T1234567890123', $rounding),
            priceMode: $priceMode,
        );
    }

    /**
     * T-01 行単位丸めとの差異検出。
     *
     * 税抜105円 × 3行、10%、切捨て。
     * 行ごとに丸めると 10 + 10 + 10 = 30 になるが、制度上は 315 × 10% = 31.5 → 31。
     * ここが 30 を返したら実装が誤っている（要件定義書 付録A-1）。
     */
    public function testT01LineLevelRoundingIsForbidden(): void
    {
        $invoice = $this->invoice(RoundingMode::FLOOR);
        for ($i = 0; $i < 3; $i++) {
            $invoice->addLine(new LineItem('商品', '1', '105', TaxRate::STANDARD_10));
        }

        $result = $invoice->calculate();
        $summary = $result->summaryFor(TaxRate::STANDARD_10);

        self::assertNotNull($summary);
        self::assertSame(315, $summary->taxableBase);
        self::assertSame(31, $summary->taxAmount, '行単位で丸めた 30 になってはならない');
        self::assertSame(346, $result->grandTotal);
    }

    /** T-02 同上 CEIL → 31.5 は切り上げて 32 */
    public function testT02Ceil(): void
    {
        $invoice = $this->invoice(RoundingMode::CEIL);
        for ($i = 0; $i < 3; $i++) {
            $invoice->addLine(new LineItem('商品', '1', '105', TaxRate::STANDARD_10));
        }

        self::assertSame(32, $invoice->calculate()->totalTaxAmount);
    }

    /** T-03 同上 HALF_UP → 31.5 は四捨五入して 32 */
    public function testT03HalfUp(): void
    {
        $invoice = $this->invoice(RoundingMode::HALF_UP);
        for ($i = 0; $i < 3; $i++) {
            $invoice->addLine(new LineItem('商品', '1', '105', TaxRate::STANDARD_10));
        }

        self::assertSame(32, $invoice->calculate()->totalTaxAmount);
    }

    /**
     * T-04 税率混在。
     *
     * 10%対象 105円×2 → 210 / 21、8%対象 105円×2 → 210 / 16（16.8 切捨て）。合計税額 37。
     * 税率グループを跨いで合算してから端数処理してはならない。
     */
    public function testT04MixedRates(): void
    {
        $invoice = $this->invoice(RoundingMode::FLOOR);
        $invoice->addLine(new LineItem('日用品', '2', '105', TaxRate::STANDARD_10));
        $invoice->addLine(new LineItem('飲食料品', '2', '105', TaxRate::REDUCED_8));

        $result = $invoice->calculate();
        $standard = $result->summaryFor(TaxRate::STANDARD_10);
        $reduced = $result->summaryFor(TaxRate::REDUCED_8);

        self::assertNotNull($standard);
        self::assertNotNull($reduced);
        self::assertSame(210, $standard->taxableBase);
        self::assertSame(21, $standard->taxAmount);
        self::assertSame(210, $reduced->taxableBase);
        self::assertSame(16, $reduced->taxAmount);
        self::assertSame(37, $result->totalTaxAmount);

        // 記載事項4・5のため、税率ごとに区分されていること（T-34）
        self::assertCount(2, $result->summaries);
        // 税率の降順
        self::assertSame(TaxRate::STANDARD_10, $result->summaries[0]->taxRate);
        self::assertSame(TaxRate::REDUCED_8, $result->summaries[1]->taxRate);
    }

    /**
     * T-05 税込入力。
     *
     * 税込1,000円、10%、切捨て → 1000 × 10/110 = 90.909… → 90、課税標準額 910。
     */
    public function testT05TaxIncluded(): void
    {
        $invoice = $this->invoice(RoundingMode::FLOOR, PriceMode::TAX_INCLUDED);
        $invoice->addLine(new LineItem('商品', '1', '1000', TaxRate::STANDARD_10));

        $summary = $invoice->calculate()->summaryFor(TaxRate::STANDARD_10);

        self::assertNotNull($summary);
        self::assertSame(90, $summary->taxAmount);
        self::assertSame(910, $summary->taxableBase);
        self::assertSame(1000, $summary->taxIncludedTotal);
    }

    /**
     * T-06 税込入力の整合性。
     *
     * 税込モードでは常に taxableBase + taxAmount === taxIncludedTotal でなければならない。
     * 課税標準額を丸めて求める実装だとここが崩れる。
     *
     * @param non-empty-string $amount
     */
    #[\PHPUnit\Framework\Attributes\DataProvider('taxIncludedAmounts')]
    public function testT06TaxIncludedConsistency(string $amount, TaxRate $taxRate, RoundingMode $rounding): void
    {
        $invoice = $this->invoice($rounding, PriceMode::TAX_INCLUDED);
        $invoice->addLine(new LineItem('商品', '1', $amount, $taxRate));

        $summary = $invoice->calculate()->summaryFor($taxRate);

        self::assertNotNull($summary);
        self::assertSame(
            $summary->taxIncludedTotal,
            $summary->taxableBase + $summary->taxAmount,
            sprintf('税込 %s 円 / %s / %s で整合しない', $amount, $taxRate->value, $rounding->value),
        );
        self::assertSame((int) $amount, $summary->taxIncludedTotal);
    }

    /**
     * @return iterable<string, array{string, TaxRate, RoundingMode}>
     */
    public static function taxIncludedAmounts(): iterable
    {
        $amounts = ['1', '3', '7', '99', '100', '101', '999', '1000', '1001', '12345', '99999', '1000000'];
        foreach ($amounts as $amount) {
            foreach ([TaxRate::STANDARD_10, TaxRate::REDUCED_8, TaxRate::EXEMPT_0] as $rate) {
                foreach (RoundingMode::cases() as $rounding) {
                    yield sprintf('%s円 %s %s', $amount, $rate->value, $rounding->value)
                        => [$amount, $rate, $rounding];
                }
            }
        }
    }

    /**
     * T-07 小数数量。
     *
     * 数量 0.5、単価 3,333円、10%、切捨て → 対価の額 1666.5 → 1666、税額 166.6 → 166。
     */
    public function testT07FractionalQuantity(): void
    {
        $invoice = $this->invoice(RoundingMode::FLOOR);
        $invoice->addLine(new LineItem('作業（0.5時間）', '0.5', '3333', TaxRate::STANDARD_10));

        $summary = $invoice->calculate()->summaryFor(TaxRate::STANDARD_10);

        self::assertNotNull($summary);
        self::assertSame(1666, $summary->taxableBase);
        self::assertSame(166, $summary->taxAmount);
    }
}
