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
 * 国税庁の公表資料に載っている計算例をそのまま受入条件にしたもの（要件定義書 §8.6）。
 *
 * 出典は各テストのコメントに記載。2026-08-17 に一次情報から採録（付録B）。
 */
final class NtaExampleTest extends TestCase
{
    private function invoice(PriceMode $priceMode): Invoice
    {
        return new Invoice(
            issuer: new Issuer('△△商事㈱', 'T1234567890123', RoundingMode::FLOOR),
            priceMode: $priceMode,
        );
    }

    /**
     * T-50 国税庁Q&A 問57 の記載例。
     *
     * 一定期間の取引をまとめた請求書。税込 100,000円のうち 10%対象 60,000円、8%対象 40,000円。
     *   10%対象: 60,000 × 10/110 ≒ 5,454円
     *    8%対象: 40,000 ×  8/108 ≒ 2,962円
     *   消費税 合計 8,416円
     */
    public function testT50Question57Example(): void
    {
        $invoice = $this->invoice(PriceMode::TAX_INCLUDED);
        $invoice->addLine(new LineItem('キッチンペーパー他', '1', '60000', TaxRate::STANDARD_10));
        $invoice->addLine(new LineItem('小麦粉・牛肉他', '1', '40000', TaxRate::REDUCED_8));

        $result = $invoice->calculate();

        self::assertSame(5454, $result->summaryFor(TaxRate::STANDARD_10)?->taxAmount);
        self::assertSame(2962, $result->summaryFor(TaxRate::REDUCED_8)?->taxAmount);
        self::assertSame(8416, $result->totalTaxAmount);
        self::assertSame(100000, $result->grandTotal);
    }

    /**
     * T-51 問57 参考① 媒介者交付特例。
     *
     * 自社の売上と他者の売上を税率ごとにまとめて1回端数処理できるケース。
     *   10%対象 計 23,894円 → 2,389円（2,389.4）
     *    8%対象 計 22,332円 → 1,786円（1,786.56）
     */
    public function testT51Question57AggregatedExample(): void
    {
        $invoice = $this->invoice(PriceMode::TAX_EXCLUDED);
        $invoice->addLine(new LineItem('自社売上', '1', '11345', TaxRate::STANDARD_10));
        $invoice->addLine(new LineItem('媒介者分', '1', '12549', TaxRate::STANDARD_10));
        $invoice->addLine(new LineItem('自社売上（軽減）', '1', '9987', TaxRate::REDUCED_8));
        $invoice->addLine(new LineItem('媒介者分（軽減）', '1', '12345', TaxRate::REDUCED_8));

        $result = $invoice->calculate();
        $standard = $result->summaryFor(TaxRate::STANDARD_10);
        $reduced = $result->summaryFor(TaxRate::REDUCED_8);

        self::assertNotNull($standard);
        self::assertNotNull($reduced);
        self::assertSame(23894, $standard->taxableBase);
        self::assertSame(2389, $standard->taxAmount);
        self::assertSame(22332, $reduced->taxableBase);
        self::assertSame(1786, $reduced->taxAmount);
    }

    /**
     * T-52 問57 参考② 代理交付。
     *
     * 代理交付では自社分と他社分をまとめて端数処理できず、それぞれ税率ごとに区分して処理する。
     *   当社分 11,345 → 1,134 / 9,987(8%) → 798
     *   ●社分 12,549 → 1,254 / 12,345(8%) → 987
     * まとめた場合の 2,389 / 1,786 とは一致しない（＝別々の Invoice として扱う必要がある）。
     */
    public function testT52Question57SeparateIssuersExample(): void
    {
        $own = $this->invoice(PriceMode::TAX_EXCLUDED);
        $own->addLine(new LineItem('当社分', '1', '11345', TaxRate::STANDARD_10));
        $own->addLine(new LineItem('当社分（軽減）', '1', '9987', TaxRate::REDUCED_8));

        $other = $this->invoice(PriceMode::TAX_EXCLUDED);
        $other->addLine(new LineItem('●社分', '1', '12549', TaxRate::STANDARD_10));
        $other->addLine(new LineItem('●社分（軽減）', '1', '12345', TaxRate::REDUCED_8));

        $ownStandard = $own->calculate()->summaryFor(TaxRate::STANDARD_10);
        $ownReduced = $own->calculate()->summaryFor(TaxRate::REDUCED_8);
        $otherStandard = $other->calculate()->summaryFor(TaxRate::STANDARD_10);
        $otherReduced = $other->calculate()->summaryFor(TaxRate::REDUCED_8);

        self::assertNotNull($ownStandard);
        self::assertNotNull($ownReduced);
        self::assertNotNull($otherStandard);
        self::assertNotNull($otherReduced);

        self::assertSame(1134, $ownStandard->taxAmount);
        self::assertSame(798, $ownReduced->taxAmount);
        self::assertSame(1254, $otherStandard->taxAmount);
        self::assertSame(987, $otherReduced->taxAmount);

        // まとめて端数処理した場合の値（2,389 / 1,786）と一致しないことを固定しておく
        self::assertNotSame(2389, $ownStandard->taxAmount + $otherStandard->taxAmount);
        self::assertNotSame(1786, $ownReduced->taxAmount + $otherReduced->taxAmount);
    }

    /**
     * T-53 問54 の記載例。
     *
     * 税込 131,200円。10%対象 88,000円 → 消費税 8,000円、8%対象 43,200円 → 3,200円。
     * いずれも割り切れるため、丸めモードに依存しない。
     */
    public function testT53Question54Example(): void
    {
        foreach (RoundingMode::cases() as $rounding) {
            $invoice = new Invoice(
                issuer: new Issuer('△△商事㈱', 'T1234567890123', $rounding),
                priceMode: PriceMode::TAX_INCLUDED,
            );
            $invoice->addLine(new LineItem('キッチンペーパー', '1', '88000', TaxRate::STANDARD_10));
            $invoice->addLine(new LineItem('小麦粉・牛肉', '1', '43200', TaxRate::REDUCED_8));

            $result = $invoice->calculate();

            self::assertSame(8000, $result->summaryFor(TaxRate::STANDARD_10)?->taxAmount, $rounding->value);
            self::assertSame(3200, $result->summaryFor(TaxRate::REDUCED_8)?->taxAmount, $rounding->value);
            self::assertSame(131200, $result->grandTotal, $rounding->value);
        }
    }
}
