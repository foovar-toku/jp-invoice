<?php

declare(strict_types=1);

namespace Foovar\JpInvoice\Support;

use Foovar\JpInvoice\CalculationResult;
use Foovar\JpInvoice\Enum\PriceMode;
use Foovar\JpInvoice\Enum\RoundingMode;
use Foovar\JpInvoice\Enum\TaxRate;
use Foovar\JpInvoice\LineItem;
use Foovar\JpInvoice\TaxSummary;

/**
 * 税率グループごとの集計と端数処理。
 *
 * === 本ライブラリの中核仕様 ===
 * 端数処理は「一の適格請求書につき、税率ごとに1回」だけ行う（消令70の10、基通1-8-15）。
 * 国税庁Q&A 問57（注）:
 *   「一の適格請求書に記載されている個々の商品ごとに消費税額等を計算し、1円未満の端数処理を行い、
 *     その合計額を消費税額等として記載することは認められません。」
 *
 * 適格請求書（Invoice）と適格返還請求書（CreditNote）で同じ処理を使う。
 * 返還インボイスも「税率ごとに区分した消費税額等」を記載するため、端数処理の考え方は同じ。
 *
 * @internal 公開 API ではない。後方互換の保証対象外。
 */
final class TaxCalculator
{
    private function __construct()
    {
    }

    /**
     * @param list<LineItem> $lines
     */
    public static function calculate(array $lines, PriceMode $priceMode, RoundingMode $rounding): CalculationResult
    {
        $summaries = [];
        foreach (self::groupByTaxRate($lines) as $group) {
            $summaries[] = $priceMode === PriceMode::TAX_EXCLUDED
                ? self::summarizeTaxExcluded($group['taxRate'], $group['amount'], $rounding)
                : self::summarizeTaxIncluded($group['taxRate'], $group['amount'], $rounding);
        }

        return new CalculationResult($summaries);
    }

    /**
     * 税率ごとに明細金額を合計する。ここでは丸めない。
     *
     * 並びは税率の降順（10% → 8% → 0%）で安定させる。
     *
     * @param list<LineItem> $lines
     * @return list<array{taxRate: TaxRate, amount: numeric-string}>
     */
    private static function groupByTaxRate(array $lines): array
    {
        /** @var array<string, numeric-string> $totals 税率 => 合計額 */
        $totals = [];

        foreach ($lines as $line) {
            $key = $line->taxRate->value;
            $totals[$key] = Decimal::add($totals[$key] ?? '0', $line->amount());
        }

        $groups = [];
        foreach (TaxRate::cases() as $taxRate) {
            if (isset($totals[$taxRate->value])) {
                $groups[] = ['taxRate' => $taxRate, 'amount' => $totals[$taxRate->value]];
            }
        }

        usort(
            $groups,
            static fn (array $a, array $b): int => $b['taxRate']->percent() <=> $a['taxRate']->percent(),
        );

        return $groups;
    }

    /**
     * 税抜入力モード（要件定義書 §5.1）。
     *
     * 対価の額を整数化してから税額を求め、その1回だけ端数処理する。
     *
     * @param numeric-string $amount
     */
    private static function summarizeTaxExcluded(TaxRate $taxRate, string $amount, RoundingMode $rounding): TaxSummary
    {
        $taxableBase = Decimal::toInt($amount, $rounding);
        $taxAmount = Decimal::toInt(
            Decimal::mul((string) $taxableBase, $taxRate->multiplier()),
            $rounding,
        );

        return new TaxSummary($taxRate, $taxableBase, $taxAmount);
    }

    /**
     * 税込入力モード（要件定義書 §5.2）。
     *
     * 税額 = 税込総額 × 10/110（8% なら 8/108）を1回だけ端数処理し、
     * 課税標準額は「税込総額 − 税額」で求める。
     * taxableBase を丸めて求めると taxableBase + taxAmount ≠ taxIncludedTotal になるため。
     *
     * @param numeric-string $amount
     */
    private static function summarizeTaxIncluded(TaxRate $taxRate, string $amount, RoundingMode $rounding): TaxSummary
    {
        $taxIncludedTotal = Decimal::toInt($amount, $rounding);

        $taxAmount = Decimal::toInt(
            Decimal::div(
                Decimal::mul((string) $taxIncludedTotal, $taxRate->taxIncludedNumerator()),
                $taxRate->taxIncludedDenominator(),
            ),
            $rounding,
        );

        return new TaxSummary($taxRate, $taxIncludedTotal - $taxAmount, $taxAmount);
    }
}
