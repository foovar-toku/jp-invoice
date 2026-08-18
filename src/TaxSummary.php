<?php

declare(strict_types=1);

namespace Foovar\JpInvoice;

use Foovar\JpInvoice\Enum\TaxRate;

/**
 * 税率グループごとの集計結果（記載事項4・5）。
 *
 * すべて円単位の整数。taxAmount は端数処理済みで、この1回が制度上唯一の端数処理となる。
 */
final class TaxSummary
{
    public readonly int $taxIncludedTotal;

    /**
     * @param int $taxableBase 税率ごとに区分して合計した対価の額（税抜）
     * @param int $taxAmount   税率ごとに区分した消費税額等（端数処理済み）
     */
    public function __construct(
        public readonly TaxRate $taxRate,
        public readonly int $taxableBase,
        public readonly int $taxAmount,
    ) {
        $this->taxIncludedTotal = $taxableBase + $taxAmount;
    }
}
