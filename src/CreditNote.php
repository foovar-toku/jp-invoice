<?php

declare(strict_types=1);

namespace Foovar\JpInvoice;

use DateTimeImmutable;
use Foovar\JpInvoice\Enum\PriceMode;
use Foovar\JpInvoice\Exception\MissingRequiredFieldException;
use Foovar\JpInvoice\Support\TaxCalculator;
use Foovar\JpInvoice\Validation\RequiredFields;

/**
 * 適格返還請求書（返還インボイス）。
 *
 * 売上に係る対価の返還等（返品・値引き・割戻し・販売奨励金等）を行う場合に交付する（消法57の4③）。
 *
 * 記載事項:
 *   1 発行事業者の氏名又は名称及び登録番号
 *   2 対価の返還等を行う年月日
 *   3 **対価の返還等の基となった取引の年月日**（適格請求書には無い項目）
 *   4 対価の返還等の取引内容（軽減対象である場合はその旨）
 *   5 税率ごとに区分して合計した対価の返還等の金額
 *   6 税率ごとに区分した消費税額等 又は 適用税率
 *
 * 明細の金額は「返還する額」を正の数で持つ。Invoice 側で負値を禁止しているのはこのため
 * （値引きは CreditNote で表現する）。
 */
final class CreditNote
{
    /** 交付義務が免除される金額の境界。「税込1万円未満」なので 10,000 は免除されない */
    public const ISSUANCE_THRESHOLD = 10000;

    /** @var list<LineItem> */
    private array $lines = [];

    /**
     * @param DateTimeImmutable|null $returnDate              対価の返還等を行った年月日（記載事項2）
     * @param DateTimeImmutable|null $originalTransactionDate 基となった取引の年月日（記載事項3・必須）
     */
    public function __construct(
        public readonly Issuer $issuer,
        public readonly ?Recipient $recipient = null,
        public readonly ?DateTimeImmutable $returnDate = null,
        public readonly ?DateTimeImmutable $originalTransactionDate = null,
        public readonly PriceMode $priceMode = PriceMode::TAX_INCLUDED,
    ) {
    }

    public function addLine(LineItem $line): self
    {
        $this->lines[] = $line;

        return $this;
    }

    /**
     * @return list<LineItem>
     */
    public function lines(): array
    {
        return $this->lines;
    }

    /**
     * @throws MissingRequiredFieldException
     */
    public function validate(): void
    {
        RequiredFields::assertCreditNote($this);
    }

    /**
     * 税率ごとの返還額と消費税額。端数処理は適格請求書と同じく税率ごとに1回。
     */
    public function calculate(): CalculationResult
    {
        return TaxCalculator::calculate($this->lines, $this->priceMode, $this->issuer->roundingMode);
    }

    /**
     * 交付義務があるか。
     *
     * 売上に係る対価の返還等に係る**税込価額が1万円未満**であれば交付義務は免除される
     * （消法57の4③、消令70の9③二）。
     *
     * === 判定単位（国税庁Q&A 問28） ===
     * 「返還した金額や値引き等の対象となる請求や債権の単位ごとに減額した金額」で判定する（基通1-8-17）。
     * （注）「適用税率ごとの値引き等の金額により判定するものではなく」— つまり
     * **税率グループごとに判定してはならない**。この返還インボイス全体の税込合計で判定する。
     *
     * 例（問28）:
     *   500,000円の請求に対し振込手数料相当額 440円 を減額 → 免除される
     *   400,000円の請求に対し1商品100円のリベート、合計 20,000円 → 免除されない
     */
    public function requiresIssuance(): bool
    {
        return $this->taxIncludedTotal() >= self::ISSUANCE_THRESHOLD;
    }

    /**
     * 対価の返還等に係る税込価額の合計。
     */
    public function taxIncludedTotal(): int
    {
        return $this->calculate()->grandTotal;
    }
}
