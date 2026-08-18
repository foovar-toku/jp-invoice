<?php

declare(strict_types=1);

namespace Foovar\JpInvoice;

use Foovar\JpInvoice\Enum\TaxRate;

/**
 * 計算結果全体。イミュータブル。
 *
 * 帳票側はこのオブジェクトだけを受け取れば描画できる（要件定義書 §10）。
 */
final class CalculationResult
{
    public readonly int $totalTaxableBase;

    public readonly int $totalTaxAmount;

    public readonly int $grandTotal;

    /**
     * @param list<TaxSummary> $summaries 税率の降順で安定ソート済み
     */
    public function __construct(
        public readonly array $summaries,
    ) {
        $base = 0;
        $tax = 0;
        foreach ($summaries as $summary) {
            $base += $summary->taxableBase;
            $tax += $summary->taxAmount;
        }

        $this->totalTaxableBase = $base;
        $this->totalTaxAmount = $tax;
        $this->grandTotal = $base + $tax;
    }

    /**
     * 指定税率の集計を取り出す。該当がなければ null。
     */
    public function summaryFor(TaxRate $taxRate): ?TaxSummary
    {
        foreach ($this->summaries as $summary) {
            if ($summary->taxRate === $taxRate) {
                return $summary;
            }
        }

        return null;
    }
}
