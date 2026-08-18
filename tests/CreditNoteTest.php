<?php

declare(strict_types=1);

namespace Foovar\JpInvoice\Tests;

use DateTimeImmutable;
use Foovar\JpInvoice\CreditNote;
use Foovar\JpInvoice\Enum\TaxRate;
use Foovar\JpInvoice\Exception\MissingRequiredFieldException;
use Foovar\JpInvoice\Issuer;
use Foovar\JpInvoice\LineItem;
use Foovar\JpInvoice\Recipient;
use PHPUnit\Framework\TestCase;

/**
 * 要件定義書 §8.5（T-40 〜 T-43）と §8.6（T-54 / T-55）返還インボイス。
 */
final class CreditNoteTest extends TestCase
{
    private function creditNote(?DateTimeImmutable $originalDate = new DateTimeImmutable('2026-07-31')): CreditNote
    {
        return new CreditNote(
            issuer: new Issuer('ピー・アイ・ジェイ株式会社', 'T1234567890123'),
            recipient: new Recipient('株式会社サンプル'),
            returnDate: new DateTimeImmutable('2026-08-17'),
            originalTransactionDate: $originalDate,
        );
    }

    /** T-40 税込 10,000円 → 交付義務あり */
    public function testT40RequiresIssuance(): void
    {
        $note = $this->creditNote();
        $note->addLine(new LineItem('返品', '1', '10000', TaxRate::STANDARD_10));

        self::assertTrue($note->requiresIssuance());
        self::assertSame(10000, $note->taxIncludedTotal());
    }

    /** T-41 税込 9,999円 → 交付義務なし */
    public function testT41DoesNotRequireIssuance(): void
    {
        $note = $this->creditNote();
        $note->addLine(new LineItem('値引', '1', '9999', TaxRate::STANDARD_10));

        self::assertFalse($note->requiresIssuance());
    }

    /** T-42 境界。免除条件は「1万円未満」なので、ちょうど 10,000円 は交付義務あり */
    public function testT42Boundary(): void
    {
        $under = $this->creditNote();
        $under->addLine(new LineItem('値引', '1', '9999', TaxRate::STANDARD_10));
        self::assertFalse($under->requiresIssuance());

        $exact = $this->creditNote();
        $exact->addLine(new LineItem('値引', '1', '10000', TaxRate::STANDARD_10));
        self::assertTrue($exact->requiresIssuance());
    }

    /** T-43 元取引日が未設定なら例外（記載事項3） */
    public function testT43OriginalTransactionDateIsRequired(): void
    {
        $note = $this->creditNote(null);
        $note->addLine(new LineItem('値引', '1', '10000', TaxRate::STANDARD_10));

        $this->expectException(MissingRequiredFieldException::class);
        $note->validate();
    }

    /** 記載事項が揃っていれば検証を通る */
    public function testValidCreditNotePasses(): void
    {
        $note = $this->creditNote();
        $note->addLine(new LineItem('返品（8月分）', '1', '10000', TaxRate::STANDARD_10));

        $note->validate();
        self::assertNotNull($note->originalTransactionDate);
    }

    /**
     * T-54 国税庁Q&A 問28 例①。
     *
     * 500,000円の請求に対し、買手が振込手数料相当額 440円 を減額して 499,560円 を支払。
     * 売手は 440円 を対価の返還等として処理する → 1万円未満なので交付義務は免除される。
     */
    public function testT54Question28TransferFee(): void
    {
        $note = $this->creditNote();
        $note->addLine(new LineItem('振込手数料相当額の売上値引', '1', '440', TaxRate::STANDARD_10));

        self::assertFalse($note->requiresIssuance());
        self::assertSame(440, $note->taxIncludedTotal());
    }

    /**
     * T-55 国税庁Q&A 問28 例②。
     *
     * 400,000円の請求に関し、1商品当たり100円のリベートを後日支払（合計 20,000円）。
     * → 1万円以上なので交付義務は免除されない。
     *
     * **明細1行あたり 100円で判定してはならない。**問28（注）のとおり、判定は
     * 「請求や債権の単位ごとに減額した金額」で行う（適用税率ごとでもない）。
     */
    public function testT55Question28Rebate(): void
    {
        $note = $this->creditNote();
        for ($i = 0; $i < 200; $i++) {
            $note->addLine(new LineItem('リベート（1商品100円）', '1', '100', TaxRate::STANDARD_10));
        }

        self::assertSame(20000, $note->taxIncludedTotal());
        self::assertTrue($note->requiresIssuance(), '1行100円で判定して免除にしてはならない');
    }

    /**
     * 税率が混在していても、判定は全体の税込合計で行う（問28（注））。
     *
     * 8%対象 6,000円 + 10%対象 5,000円 = 11,000円。
     * 税率ごとに判定するとどちらも1万円未満で「免除」になってしまうが、正しくは交付義務あり。
     */
    public function testMixedRatesAreJudgedTogether(): void
    {
        $note = $this->creditNote();
        $note->addLine(new LineItem('飲食料品の返品', '1', '6000', TaxRate::REDUCED_8));
        $note->addLine(new LineItem('日用品の返品', '1', '5000', TaxRate::STANDARD_10));

        self::assertSame(11000, $note->taxIncludedTotal());
        self::assertTrue($note->requiresIssuance(), '税率グループごとに判定してはならない');
    }

    /** 返還インボイスも税率ごとに区分した消費税額等を持つ（記載事項5・6） */
    public function testTaxIsSummarizedPerRate(): void
    {
        $note = $this->creditNote();
        $note->addLine(new LineItem('飲食料品の返品', '1', '10800', TaxRate::REDUCED_8));
        $note->addLine(new LineItem('日用品の返品', '1', '11000', TaxRate::STANDARD_10));

        $result = $note->calculate();

        self::assertSame(1000, $result->summaryFor(TaxRate::STANDARD_10)?->taxAmount);
        self::assertSame(800, $result->summaryFor(TaxRate::REDUCED_8)?->taxAmount);
        self::assertSame(21800, $result->grandTotal);
    }
}
