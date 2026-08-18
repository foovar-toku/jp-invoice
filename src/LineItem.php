<?php

declare(strict_types=1);

namespace Foovar\JpInvoice;

use Foovar\JpInvoice\Enum\TaxRate;
use Foovar\JpInvoice\Support\Decimal;

/**
 * 明細行。
 *
 * 数量・単価は numeric-string で保持する。float は使わない（要件定義書 §4.2、付録A-2）。
 * 単価が税抜か税込かは Invoice の PriceMode が決める。行ごとには持たない。
 */
final class LineItem
{
    /** @var numeric-string */
    public readonly string $quantity;

    /** @var numeric-string */
    public readonly string $unitPrice;

    /**
     * @param string $description 品名・取引内容（記載事項3）
     * @param string $quantity    数量。小数可（0.5 時間 等）。負値は不可
     * @param string $unitPrice   単価。小数可。負値は不可（値引は CreditNote で表現する）
     */
    public function __construct(
        public readonly string $description,
        string $quantity,
        string $unitPrice,
        public readonly TaxRate $taxRate,
        public readonly ?string $note = null,
    ) {
        $this->quantity = Decimal::assertNonNegative($quantity, '数量');
        $this->unitPrice = Decimal::assertNonNegative($unitPrice, '単価');
    }

    /**
     * この行の金額（数量 × 単価）。丸めない。
     *
     * 行単位で丸めると制度違反になる（消令70の10、基通1-8-15）。丸めは税率グループごとに1回。
     *
     * @return numeric-string
     */
    public function amount(): string
    {
        return Decimal::mul($this->quantity, $this->unitPrice);
    }

    /**
     * 軽減税率対象である旨（記載事項3）。taxRate から導出する。
     */
    public function isReducedRate(): bool
    {
        return $this->taxRate->isReducedRate();
    }
}
