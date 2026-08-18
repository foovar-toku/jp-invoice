<?php

declare(strict_types=1);

namespace Foovar\JpInvoice;

use DateTimeImmutable;
use Foovar\JpInvoice\Enum\InvoiceType;
use Foovar\JpInvoice\Enum\PriceMode;
use Foovar\JpInvoice\Exception\MissingRequiredFieldException;
use Foovar\JpInvoice\Support\TaxCalculator;
use Foovar\JpInvoice\Validation\RequiredFields;

/**
 * 適格請求書 / 適格簡易請求書。
 *
 * 端数処理は「一の適格請求書につき、税率ごとに1回」だけ行う（消令70の10、基通1-8-15）。
 * 実処理は Support\TaxCalculator にある。
 *
 * calculate() は副作用を持たず、同じ入力に対し常に同じ結果を返す。
 * validate() とは分離してある（検証を通さずに計算だけしたいケースがあるため）。
 *
 * 本ライブラリは消費税額の計算を補助するものです。個別の取引における税務上の取扱いについては、
 * 税理士等の専門家にご確認ください。制度改正への追随は利用者の責任において行ってください。
 */
final class Invoice
{
    /** @var list<LineItem> */
    private array $lines = [];

    public function __construct(
        public readonly Issuer $issuer,
        public readonly ?Recipient $recipient = null,
        public readonly ?DateTimeImmutable $transactionDate = null,
        public readonly PriceMode $priceMode = PriceMode::TAX_EXCLUDED,
        public readonly InvoiceType $type = InvoiceType::STANDARD,
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
     * 記載事項の充足検証（消法57の4）。
     *
     * @throws MissingRequiredFieldException 記載事項が欠けているとき
     */
    public function validate(): void
    {
        RequiredFields::assertInvoice($this);
    }

    /**
     * 記載事項を満たしているかを bool で返す（例外を投げない版）。
     */
    public function isValid(): bool
    {
        try {
            $this->validate();
        } catch (MissingRequiredFieldException) {
            return false;
        }

        return true;
    }

    /**
     * 税額を計算する。
     */
    public function calculate(): CalculationResult
    {
        return TaxCalculator::calculate($this->lines, $this->priceMode, $this->issuer->roundingMode);
    }

    /**
     * 適格簡易請求書か（消法57の4②）。
     *
     * 小売業・飲食店業・写真業・旅行業・タクシー業・駐車場業など、不特定かつ多数の者に
     * 対する取引を行う事業者が交付できる。交付先の氏名又は名称の記載が不要になる。
     */
    public function isSimplified(): bool
    {
        return $this->type === InvoiceType::SIMPLIFIED;
    }
}
