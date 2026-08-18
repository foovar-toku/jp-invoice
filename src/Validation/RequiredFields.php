<?php

declare(strict_types=1);

namespace Foovar\JpInvoice\Validation;

use Foovar\JpInvoice\CreditNote;
use Foovar\JpInvoice\Enum\InvoiceType;
use Foovar\JpInvoice\Exception\MissingRequiredFieldException;
use Foovar\JpInvoice\Invoice;

/**
 * 記載事項の充足検証（消法57の4）。
 *
 * レンダリングはしない。「その書類が法定の記載事項を満たせるだけの情報を持っているか」だけを見る。
 *
 * 適格請求書の記載事項:
 *   1 適格請求書発行事業者の氏名又は名称 及び 登録番号
 *   2 課税資産の譲渡等を行った年月日
 *   3 課税資産の譲渡等に係る資産又は役務の内容（軽減対象である場合はその旨）
 *   4 税率ごとに区分して合計した対価の額（税抜又は税込）及び適用税率
 *   5 税率ごとに区分した消費税額等
 *   6 書類の交付を受ける事業者の氏名又は名称
 *
 * 適格簡易請求書（消法57の4②）では 6 が不要、4・5 はいずれか一方の記載で足りる。
 * 4・5 は計算結果として常に保証されるため、ここでは 1・2・3・6 と明細の存在を見る。
 */
final class RequiredFields
{
    private function __construct()
    {
    }

    /**
     * @throws MissingRequiredFieldException
     */
    public static function assertInvoice(Invoice $invoice): void
    {
        self::assertIssuer($invoice->issuer->name, $invoice->issuer->registrationNumber);

        if ($invoice->transactionDate === null) {
            throw MissingRequiredFieldException::of('取引年月日（記載事項2）');
        }

        self::assertLines($invoice->lines());

        // 記載事項6。適格簡易請求書では不要
        if ($invoice->type !== InvoiceType::SIMPLIFIED
            && ($invoice->recipient === null || trim($invoice->recipient->name) === '')
        ) {
            throw MissingRequiredFieldException::of('交付を受ける事業者の氏名又は名称（記載事項6）');
        }
    }

    /**
     * 適格返還請求書の記載事項（消法57の4③）。
     *
     * 適格請求書との差は「対価の返還等の基となった取引の年月日」が必要な点。
     *
     * @throws MissingRequiredFieldException
     */
    public static function assertCreditNote(CreditNote $creditNote): void
    {
        self::assertIssuer($creditNote->issuer->name, $creditNote->issuer->registrationNumber);

        if ($creditNote->returnDate === null) {
            throw MissingRequiredFieldException::of('対価の返還等を行った年月日');
        }

        if ($creditNote->originalTransactionDate === null) {
            throw MissingRequiredFieldException::of('対価の返還等の基となった取引の年月日');
        }

        self::assertLines($creditNote->lines());
    }

    private static function assertIssuer(string $name, ?RegistrationNumber $registrationNumber): void
    {
        if (trim($name) === '') {
            throw MissingRequiredFieldException::of('発行事業者の氏名又は名称（記載事項1）');
        }

        if ($registrationNumber === null) {
            throw MissingRequiredFieldException::of('登録番号（記載事項1）');
        }
    }

    /**
     * @param list<\Foovar\JpInvoice\LineItem> $lines
     */
    private static function assertLines(array $lines): void
    {
        if ($lines === []) {
            throw MissingRequiredFieldException::of('取引内容（記載事項3）— 明細が1件もありません');
        }

        foreach ($lines as $index => $line) {
            if (trim($line->description) === '') {
                throw MissingRequiredFieldException::of(
                    sprintf('取引内容（記載事項3）— %d 行目の品名が空です', $index + 1),
                );
            }
        }
    }
}
