<?php
// README に載せたコード例が実際にその値を返すかを検証する。
declare(strict_types=1);
require __DIR__ . '/../vendor/autoload.php';

use Foovar\JpInvoice\{Issuer, Recipient, Invoice, LineItem, CreditNote, TransitionalMeasure};
use Foovar\JpInvoice\Enum\{TaxRate, RoundingMode, PriceMode, InvoiceType};
use Foovar\JpInvoice\Validation\RegistrationNumber;

$issuer = new Issuer('ピー・アイ・ジェイ株式会社', 'T1234567890123', RoundingMode::FLOOR);

$invoice = new Invoice(
    issuer: $issuer,
    recipient: new Recipient('株式会社サンプル'),
    transactionDate: new DateTimeImmutable('2026-08-17'),
    priceMode: PriceMode::TAX_EXCLUDED,
    type: InvoiceType::STANDARD,
);
$invoice->addLine(new LineItem('月極駐車場利用料（8月分）', '1', '15000', TaxRate::STANDARD_10));
$invoice->addLine(new LineItem('来客用飲料', '10', '105', TaxRate::REDUCED_8));
$invoice->validate();
$result = $invoice->calculate();

foreach ($result->summaries as $s) {
    printf("%s: 対価 %d 円 / 消費税 %d 円\n", $s->taxRate->label(), $s->taxableBase, $s->taxAmount);
}
printf("合計税額 %d 円 / 請求総額 %d 円\n", $result->totalTaxAmount, $result->grandTotal);

$inc = new Invoice($issuer, priceMode: PriceMode::TAX_INCLUDED);
$inc->addLine(new LineItem('商品', '1', '1000', TaxRate::STANDARD_10));
$s = $inc->calculate()->summaryFor(TaxRate::STANDARD_10);
printf("税込入力: tax=%d base=%d incl=%d\n", $s->taxAmount, $s->taxableBase, $s->taxIncludedTotal);

$n = RegistrationNumber::fromString('t1234-5678-90123');
printf("登録番号: %s / 法人番号規則=%s\n", $n->value, var_export($n->matchesCorporateNumberRule(), true));

$note = new CreditNote(
    issuer: $issuer,
    returnDate: new DateTimeImmutable('2026-08-17'),
    originalTransactionDate: new DateTimeImmutable('2026-07-31'),
);
$note->addLine(new LineItem('振込手数料相当額の売上値引', '1', '440', TaxRate::STANDARD_10));
printf("返還インボイス: 交付義務=%s\n", var_export($note->requiresIssuance(), true));

$rate = TransitionalMeasure::rateFor(new DateTimeImmutable('2026-10-01'));
printf("経過措置: %d%% / 10000円 → %d円 / SCHEDULE_VERSION=%s\n",
    $rate->percent, $rate->applyTo(10000), TransitionalMeasure::SCHEDULE_VERSION);
