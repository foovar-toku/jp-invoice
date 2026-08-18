<?php

declare(strict_types=1);

namespace Foovar\JpInvoice\Tests;

use DateTimeImmutable;
use Foovar\JpInvoice\Enum\InvoiceType;
use Foovar\JpInvoice\Enum\TaxRate;
use Foovar\JpInvoice\Exception\JpInvoiceException;
use Foovar\JpInvoice\Exception\MissingRequiredFieldException;
use Foovar\JpInvoice\Invoice;
use Foovar\JpInvoice\Issuer;
use Foovar\JpInvoice\LineItem;
use Foovar\JpInvoice\Recipient;
use PHPUnit\Framework\TestCase;

/**
 * 要件定義書 §8.4（T-30 〜 T-34）記載事項の充足検証。
 */
final class ValidationTest extends TestCase
{
    private function issuer(string $registrationNumber = 'T1234567890123'): Issuer
    {
        return new Issuer('ピー・アイ・ジェイ株式会社', $registrationNumber);
    }

    private function line(): LineItem
    {
        return new LineItem('月極駐車場利用料（8月分）', '1', '15000', TaxRate::STANDARD_10);
    }

    /** T-30 標準インボイスで宛名なし → 例外 */
    public function testT30StandardWithoutRecipient(): void
    {
        $invoice = new Invoice(
            issuer: $this->issuer(),
            transactionDate: new DateTimeImmutable('2026-08-17'),
        );
        $invoice->addLine($this->line());

        $this->expectException(MissingRequiredFieldException::class);
        $invoice->validate();
    }

    /** T-31 簡易インボイスで宛名なし → 検証通過（駐車場業は簡易インボイスを交付できる） */
    public function testT31SimplifiedWithoutRecipient(): void
    {
        $invoice = new Invoice(
            issuer: $this->issuer(),
            transactionDate: new DateTimeImmutable('2026-08-17'),
            type: InvoiceType::SIMPLIFIED,
        );
        $invoice->addLine($this->line());

        $invoice->validate();

        self::assertTrue($invoice->isValid());
        self::assertTrue($invoice->isSimplified());
    }

    /** T-32 登録番号なし → 例外 */
    public function testT32WithoutRegistrationNumber(): void
    {
        $invoice = new Invoice(
            issuer: new Issuer('ピー・アイ・ジェイ株式会社'),
            recipient: new Recipient('株式会社サンプル'),
            transactionDate: new DateTimeImmutable('2026-08-17'),
        );
        $invoice->addLine($this->line());

        self::assertNull($invoice->issuer->registrationNumber);
        $this->expectException(MissingRequiredFieldException::class);
        $invoice->validate();
    }

    /** T-33 明細ゼロ件 → 例外 */
    public function testT33WithoutLines(): void
    {
        $invoice = new Invoice(
            issuer: $this->issuer(),
            recipient: new Recipient('株式会社サンプル'),
            transactionDate: new DateTimeImmutable('2026-08-17'),
        );

        $this->expectException(MissingRequiredFieldException::class);
        $invoice->validate();
    }

    /** 取引年月日なし → 例外（記載事項2） */
    public function testWithoutTransactionDate(): void
    {
        $invoice = new Invoice(
            issuer: $this->issuer(),
            recipient: new Recipient('株式会社サンプル'),
        );
        $invoice->addLine($this->line());

        $this->expectException(MissingRequiredFieldException::class);
        $invoice->validate();
    }

    /** 事業者名が空 → 例外（記載事項1） */
    public function testWithoutIssuerName(): void
    {
        $invoice = new Invoice(
            issuer: new Issuer('   ', 'T1234567890123'),
            recipient: new Recipient('株式会社サンプル'),
            transactionDate: new DateTimeImmutable('2026-08-17'),
        );
        $invoice->addLine($this->line());

        $this->expectException(MissingRequiredFieldException::class);
        $invoice->validate();
    }

    /** 品名が空 → 例外（記載事項3） */
    public function testWithoutLineDescription(): void
    {
        $invoice = new Invoice(
            issuer: $this->issuer(),
            recipient: new Recipient('株式会社サンプル'),
            transactionDate: new DateTimeImmutable('2026-08-17'),
        );
        $invoice->addLine(new LineItem('', '1', '15000', TaxRate::STANDARD_10));

        $this->expectException(MissingRequiredFieldException::class);
        $invoice->validate();
    }

    /**
     * T-34 軽減税率対象を含む場合、計算結果に 8% グループが分離されていること（記載事項3・4・5）。
     */
    public function testT34ReducedRateIsSeparated(): void
    {
        $invoice = new Invoice(
            issuer: $this->issuer(),
            recipient: new Recipient('株式会社サンプル'),
            transactionDate: new DateTimeImmutable('2026-08-17'),
        );
        $invoice->addLine($this->line());
        $invoice->addLine(new LineItem('来客用飲料', '10', '105', TaxRate::REDUCED_8));

        $invoice->validate();
        $result = $invoice->calculate();

        self::assertCount(2, $result->summaries);
        self::assertNotNull($result->summaryFor(TaxRate::REDUCED_8));
        self::assertTrue($invoice->lines()[1]->isReducedRate());
        self::assertFalse($invoice->lines()[0]->isReducedRate());
    }

    /** 例外はすべて JpInvoiceException で一括捕捉できる（要件定義書 §7） */
    public function testAllExceptionsShareBaseClass(): void
    {
        $invoice = new Invoice(issuer: $this->issuer());

        try {
            $invoice->validate();
            self::fail('例外が投げられていない');
        } catch (JpInvoiceException $e) {
            self::assertInstanceOf(MissingRequiredFieldException::class, $e);
        }
    }
}
