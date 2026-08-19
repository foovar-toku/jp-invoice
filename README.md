# jp-invoice

[![CI](https://github.com/foovar-toku/jp-invoice/actions/workflows/ci.yml/badge.svg)](https://github.com/foovar-toku/jp-invoice/actions/workflows/ci.yml)
[![Latest Version](https://img.shields.io/packagist/v/foovar/jp-invoice.svg)](https://packagist.org/packages/foovar/jp-invoice)
[![Downloads](https://img.shields.io/packagist/dt/foovar/jp-invoice.svg)](https://packagist.org/packages/foovar/jp-invoice)
[![License](https://img.shields.io/badge/license-MIT-blue.svg)](LICENSE)
[![PHP](https://img.shields.io/badge/php-8.2%20%7C%208.3%20%7C%208.4-777bb4.svg)](composer.json)

日本の**適格請求書等保存方式（インボイス制度）**に準拠した消費税額計算ライブラリ。

端数処理を「**一の適格請求書につき、税率ごとに1回**」で行います（消令70の10、基通1-8-15）。
明細行ごとに丸めてしまう実装ミスを、構造的に起こせないようにするのが目的です。

[English](#english) | MIT License | PHP 8.2+ / 依存パッケージなし（`ext-bcmath` のみ）

**▶ ブラウザで試す: https://invoice.pij.systems/** — 行ごとに丸めた場合との差が並んで出ます
**▶ 解説記事: PHPでインボイスの消費税を計算したら1円ズレる**（[Zenn](https://zenn.dev/foovar/articles/a2a1ddb4ef73f1) / [Qiita](https://qiita.com/foovar/items/732af8e33c6292894e39)）

---

## なぜ必要か

同じ請求書でも、丸める場所を間違えると税額がズレます。

| 税抜 105 円 × 3 行、10%、切捨て | 消費税額 |
|---|---|
| 行ごとに丸めて合計（**制度違反**） | 30 円 |
| 税率ごとに1回だけ丸める（**正しい**） | **31 円** |

国税庁Q&A 問57（注）:

> 一の適格請求書に記載されている個々の商品ごとに消費税額等を計算し、１円未満の端数処理を行い、
> その合計額を消費税額等として記載することは**認められません**。

本ライブラリは、この 31 円を返します。

## インストール

```bash
composer require foovar/jp-invoice
```

## 使い方

```php
use Foovar\JpInvoice\{Issuer, Recipient, Invoice, LineItem};
use Foovar\JpInvoice\Enum\{TaxRate, RoundingMode, PriceMode, InvoiceType};

$issuer = new Issuer(
    name: 'ピー・アイ・ジェイ株式会社',
    registrationNumber: 'T1234567890123',
    roundingMode: RoundingMode::FLOOR, // 自社の運用に合わせて明示指定してください
);

$invoice = new Invoice(
    issuer: $issuer,
    recipient: new Recipient('株式会社サンプル'),
    transactionDate: new DateTimeImmutable('2026-08-17'),
    priceMode: PriceMode::TAX_EXCLUDED,
    type: InvoiceType::STANDARD,
);

$invoice->addLine(new LineItem(
    description: '月極駐車場利用料（8月分）',
    quantity: '1',
    unitPrice: '15000',
    taxRate: TaxRate::STANDARD_10,
));
$invoice->addLine(new LineItem('来客用飲料', '10', '105', TaxRate::REDUCED_8));

$invoice->validate();            // 記載事項の充足検証（不足があれば例外）
$result = $invoice->calculate(); // 計算

$result->totalTaxAmount; // int
$result->grandTotal;     // int

foreach ($result->summaries as $s) {
    printf("%s: 対価 %d 円 / 消費税 %d 円\n", $s->taxRate->label(), $s->taxableBase, $s->taxAmount);
}
// 10%対象: 対価 15000 円 / 消費税 1500 円
//  8%対象: 対価 1050 円 / 消費税 84 円
```

金額は必ず `int`（円）で返ります。数量・単価は **numeric-string** で渡してください
（`float` は 1 円のズレを生むため、ライブラリ内部でも一切使いません）。

### 税込入力

```php
$invoice = new Invoice($issuer, priceMode: PriceMode::TAX_INCLUDED);
$invoice->addLine(new LineItem('商品', '1', '1000', TaxRate::STANDARD_10));

$s = $invoice->calculate()->summaryFor(TaxRate::STANDARD_10);
$s->taxAmount;        // 90  (1000 × 10/110 = 90.909…)
$s->taxableBase;      // 910
$s->taxIncludedTotal; // 1000  ← 常に taxableBase + taxAmount と一致します
```

### 適格簡易請求書

小売業・飲食店業・写真業・旅行業・タクシー業・駐車場業など、不特定多数を相手とする取引で交付できます。
交付先の氏名又は名称が不要になります。

```php
$invoice = new Invoice($issuer, transactionDate: $date, type: InvoiceType::SIMPLIFIED);
// 宛名なしでも validate() を通ります
```

### 登録番号

```php
use Foovar\JpInvoice\Validation\RegistrationNumber;

$number = RegistrationNumber::fromString('t1234-5678-90123'); // 小文字・ハイフン・全角は正規化
$number->value;                        // 'T1234567890123'
$number->matchesCorporateNumberRule(); // false
```

`matchesCorporateNumberRule()` は**法人番号のチェックディジット規則に整合するか**を返すだけです。
**個人事業者の登録番号は法人番号ではないため、この規則に従いません。** `false` でも登録番号として
無効とは限らないので、形式検証（例外）とは分けて扱ってください。

### 適格返還請求書（返還インボイス）

```php
use Foovar\JpInvoice\CreditNote;

$note = new CreditNote(
    issuer: $issuer,
    returnDate: new DateTimeImmutable('2026-08-17'),
    originalTransactionDate: new DateTimeImmutable('2026-07-31'), // 基となった取引の年月日（必須）
);
$note->addLine(new LineItem('振込手数料相当額の売上値引', '1', '440', TaxRate::STANDARD_10));

$note->requiresIssuance(); // false（税込1万円未満は交付義務が免除される）
```

判定は**返還インボイス全体の税込合計**で行います。国税庁Q&A 問28（注）のとおり、
適用税率ごとの値引額で判定するものではありません。

### 経過措置（免税事業者等からの課税仕入れ）

```php
use Foovar\JpInvoice\TransitionalMeasure;

$rate = TransitionalMeasure::rateFor(new DateTimeImmutable('2026-10-01'));
$rate->percent;         // 70
$rate->applyTo(10000);  // 7000

TransitionalMeasure::SCHEDULE_VERSION; // '2026-08-17'（このテーブルがいつ時点の理解か）
```

| 課税仕入れを行った日 | 控除割合 |
|---|---|
| 2023-10-01 〜 2026-09-30 | 80% |
| 2026-10-01 〜 2028-09-30 | 70% |
| 2028-10-01 〜 2030-09-30 | 50% |
| 2030-10-01 〜 2031-09-30 | 30% |
| 2031-10-01 〜 | 0%（控除不可） |

範囲外の日付（制度開始前など）は `UnsupportedDateException` になります。黙って 0% は返しません。

> **⚠️ 控除限度額（1億円）は本ライブラリでは判定できません。**
> 令和8年度税制改正により、一のインボイス発行事業者以外の者からの課税仕入れの合計額（税込み）が
> その年又は事業年度で 1 億円を超える場合、超えた部分にこの経過措置は適用されません。
> **年間・相手先ごとの累計**で決まるため、1 枚の請求書しか見ない本ライブラリの責任範囲外です。
> 判定は利用側で行ってください。

## 品質保証

- **国税庁Q&A の計算例をそのままテストにしています**（問54 / 問57 / 問28、法人番号公表サイトの検算例）
- PHP **8.2 / 8.3 / 8.4** で全テスト green
- **PHPStan level max** エラーゼロ
- `float` は使用禁止。CI で `src/` への混入を検査（`bin/no-float`）
- 依存パッケージ **ゼロ**。フレームワーク非依存

## やらないこと（スコープ外）

- 帳票（PDF）のレンダリング（→ [商用パッケージ](#商用パッケージ)）
- 申告書の作成、納付税額の確定計算
- 簡易課税制度、2割特例、3割特例
- 電子帳簿保存法の保存要件
- 会計仕訳の生成
- 国税庁公表システムへの実在照会（登録番号が実在するかの確認）

## 商用パッケージ

計算コアは MIT で無料です。その上に載せる次のものは商用で提供しています。

| パッケージ | 内容 |
|---|---|
| **帳票 PDF 生成** | A4縦の日本様式、税率別内訳表、軽減税率の注記、角印、御中/様の自動判定、自動改ページ。日本語フォント埋め込み |
| Laravel 統合 | Service Provider、Blade コンポーネント、マイグレーション雛形（開発中） |
| 業種別テンプレート集 | 駐車場業（簡易インボイス）、コールセンター、保守契約 ほか（開発中） |

**出力見本と導入事例**: <https://invoice.pij.systems/>（ページ下部）
お問い合わせ: info@pijtokyo.jp

## 開発

```bash
./bin/test        # PHP 8.2 / 8.3 / 8.4 でテスト（Docker）
./bin/no-float    # src/ への float 混入検査
```

## 免責

本ライブラリは消費税額の計算を補助するものです。個別の取引における税務上の取扱いについては、
**税理士等の専門家にご確認ください**。制度改正への追随は利用者の責任において行ってください。

## 関連記事・ツール

- PHPでインボイスの消費税を計算したら1円ズレる — 端数処理は「税率ごとに1回」（[Zenn](https://zenn.dev/foovar/articles/a2a1ddb4ef73f1) / [Qiita](https://qiita.com/foovar/items/732af8e33c6292894e39)）
- [インボイス制度の実装ガイド](https://invoice.pij.systems/guide/) — 端数処理／記載事項6項目／返還インボイス／経過措置
- [登録番号チェッカー](https://invoice.pij.systems/tools/registration-number/) — `T`+13桁の形式と法人番号チェックディジットを計算過程つきで確認

## 参考資料

- 消費税法 第57条の4（適格請求書発行事業者の義務）
- 消費税法施行令 第70条の9第3項第2号、第70条の10
- 消費税法基本通達 1-8-15、1-8-17
- 国税庁「消費税の仕入税額控除制度における適格請求書等保存方式に関するQ&A」
- 国税庁「令和８年度税制改正特集」
- 国税庁 法人番号公表サイト「チェックデジットの計算」

---

<a id="english"></a>

## English

**jp-invoice** — a consumption tax calculator for Japan's qualified invoice system (インボイス制度).

Japanese tax law requires the tax amount on an invoice to be rounded **once per tax rate per invoice** —
not per line item. Rounding each line separately is a compliance error that quietly changes the total.

```
Three lines of ¥105 (excl. tax), 10%, round down:
  per-line rounding  → ¥30   (non-compliant)
  per-rate rounding  → ¥31   (correct — what this library returns)
```

- PHP 8.2+ / zero dependencies (`ext-bcmath` only) / framework-agnostic
- No floating point anywhere — amounts are numeric-strings and integers, all arithmetic via BCMath
- Handles tax-exclusive and tax-inclusive input, simplified invoices, credit notes (返還インボイス),
  registration number validation, and the transitional deduction schedule for purchases from
  tax-exempt businesses
- Test cases are taken directly from the National Tax Agency's official Q&A examples

```php
$invoice = new Invoice($issuer, priceMode: PriceMode::TAX_EXCLUDED);
$invoice->addLine(new LineItem('Parking fee', '1', '15000', TaxRate::STANDARD_10));
$result = $invoice->calculate();
$result->totalTaxAmount; // int (yen)
```

**Disclaimer**: this library assists with calculation only. It does not provide tax advice.
Consult a licensed tax accountant for how the rules apply to your transactions, and keep the
transitional-measure table up to date yourself.
