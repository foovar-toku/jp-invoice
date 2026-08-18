<?php

declare(strict_types=1);

/**
 * jp-invoice デモ。
 *
 * 「行ごとに丸めた場合（よくある誤り）」と「税率ごとに1回だけ丸めた場合（制度どおり）」を
 * 並べて表示する。それ以外の機能は載せない。
 */

require dirname(__DIR__) . '/autoload.php';

/** @var array{contact_email: ?string, github_url: ?string, published_on_packagist: bool} $config */
$config = require dirname(__DIR__) . '/config.php';

use Foovar\JpInvoice\Enum\PriceMode;
use Foovar\JpInvoice\Enum\RoundingMode;
use Foovar\JpInvoice\Enum\TaxRate;
use Foovar\JpInvoice\Exception\JpInvoiceException;
use Foovar\JpInvoice\Invoice;
use Foovar\JpInvoice\Issuer;
use Foovar\JpInvoice\LineItem;

const SAMPLE_LINES = [
    ['description' => '商品A', 'quantity' => '1', 'unitPrice' => '105', 'taxRate' => 'standard_10'],
    ['description' => '商品B', 'quantity' => '1', 'unitPrice' => '105', 'taxRate' => 'standard_10'],
    ['description' => '商品C', 'quantity' => '1', 'unitPrice' => '105', 'taxRate' => 'standard_10'],
    ['description' => '飲食料品（軽減税率）', 'quantity' => '3', 'unitPrice' => '105', 'taxRate' => 'reduced_8'],
];

/** @return list<array{description: string, quantity: string, unitPrice: string, taxRate: string}> */
function submittedLines(): array
{
    $raw = $_POST['lines'] ?? null;
    if (!is_array($raw)) {
        return SAMPLE_LINES;
    }

    $lines = [];
    foreach ($raw as $row) {
        if (!is_array($row)) {
            continue;
        }
        $unitPrice = trim((string) ($row['unitPrice'] ?? ''));
        $quantity = trim((string) ($row['quantity'] ?? ''));
        if ($unitPrice === '' && $quantity === '') {
            continue;
        }
        $lines[] = [
            'description' => trim((string) ($row['description'] ?? '')),
            'quantity' => $quantity === '' ? '1' : $quantity,
            'unitPrice' => $unitPrice === '' ? '0' : $unitPrice,
            'taxRate' => (string) ($row['taxRate'] ?? 'standard_10'),
        ];
    }

    return $lines === [] ? SAMPLE_LINES : $lines;
}

$roundingMode = RoundingMode::tryFrom((string) ($_POST['rounding'] ?? '')) ?? RoundingMode::FLOOR;
$priceMode = PriceMode::tryFrom((string) ($_POST['priceMode'] ?? '')) ?? PriceMode::TAX_EXCLUDED;
$lines = submittedLines();

$error = null;
$correct = null;      // 制度どおり（税率ごとに1回）
$perLineTax = 0;      // 行ごとに丸めた場合の税額合計
$perLineDetail = [];  // 行ごとの内訳

try {
    $issuer = new Issuer('デモ事業者', 'T8700110005901', $roundingMode);

    $invoice = new Invoice($issuer, priceMode: $priceMode);
    foreach ($lines as $row) {
        $invoice->addLine(new LineItem(
            $row['description'] === '' ? '（品名未入力）' : $row['description'],
            $row['quantity'],
            $row['unitPrice'],
            TaxRate::tryFrom($row['taxRate']) ?? TaxRate::STANDARD_10,
        ));
    }
    $correct = $invoice->calculate();

    // 「行ごとに丸めた場合」は、1行だけの請求書を作って足し合わせれば再現できる
    foreach ($invoice->lines() as $line) {
        $single = new Invoice($issuer, priceMode: $priceMode);
        $single->addLine($line);
        $tax = $single->calculate()->totalTaxAmount;
        $perLineTax += $tax;
        $perLineDetail[] = ['line' => $line, 'tax' => $tax];
    }
} catch (JpInvoiceException $e) {
    $error = $e->getMessage();
}

$difference = $correct === null ? 0 : $correct->totalTaxAmount - $perLineTax;

function h(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}
?>
<!DOCTYPE html>
<html lang="ja">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>jp-invoice デモ — インボイスの端数処理は税率ごとに1回</title>
<meta name="description" content="適格請求書の消費税額を、行ごとに丸めた場合と税率ごとに1回丸めた場合で比較できるデモです。">
<style>
  :root {
    --bg:#f7f7f5; --panel:#fff; --fg:#1c1c1a; --muted:#6b6b66; --line:#e2e2dd;
    --accent:#b45309; --ok:#166534; --ng:#b91c1c; --code:#f3f3f0;
  }
  @media (prefers-color-scheme: dark) {
    :root {
      --bg:#16161a; --panel:#1e1e23; --fg:#eceae5; --muted:#9a978f; --line:#2e2e35;
      --accent:#e8a33d; --ok:#4ade80; --ng:#f87171; --code:#26262c;
    }
  }
  *{box-sizing:border-box}
  body{margin:0;background:var(--bg);color:var(--fg);line-height:1.8;
       font-family:-apple-system,BlinkMacSystemFont,"Hiragino Kaku Gothic ProN","Noto Sans JP","Yu Gothic",Meiryo,sans-serif}
  .wrap{max-width:860px;margin:0 auto;padding:32px 20px 64px}
  header{margin-bottom:28px}
  h1{font-size:24px;margin:0 0 8px;letter-spacing:.02em}
  .lead{color:var(--muted);margin:0;font-size:15px}
  section{background:var(--panel);border:1px solid var(--line);border-radius:12px;padding:24px;margin-bottom:20px}
  h2{font-size:17px;margin:0 0 16px}
  table{width:100%;border-collapse:collapse;font-size:14px}
  th,td{padding:8px 10px;border-bottom:1px solid var(--line);text-align:left}
  th{color:var(--muted);font-weight:600;font-size:13px}
  td.num,th.num{text-align:right;font-variant-numeric:tabular-nums}
  input[type=text]{width:100%;padding:6px 8px;border:1px solid var(--line);border-radius:6px;
                   background:var(--bg);color:var(--fg);font-size:14px}
  select{padding:6px 8px;border:1px solid var(--line);border-radius:6px;background:var(--bg);color:var(--fg);font-size:14px}
  button{margin-top:16px;padding:9px 22px;border:0;border-radius:8px;background:var(--accent);
         color:#fff;font-size:15px;font-weight:600;cursor:pointer}
  .opts{display:flex;gap:20px;flex-wrap:wrap;margin-top:16px;font-size:14px}
  .verdict{display:flex;gap:16px;flex-wrap:wrap;margin-bottom:18px}
  .card{flex:1 1 240px;border:1px solid var(--line);border-radius:10px;padding:16px}
  .card .amount{font-size:30px;font-variant-numeric:tabular-nums;margin:4px 0}
  .card.bad{border-color:var(--ng)} .card.bad .amount{color:var(--ng)}
  .card.good{border-color:var(--ok)} .card.good .amount{color:var(--ok)}
  .tag{font-size:12px;color:var(--muted)}
  .diff{font-size:15px;padding:12px 14px;border-radius:8px;background:var(--code)}
  pre{background:var(--code);padding:14px;border-radius:8px;overflow-x:auto;font-size:13px;line-height:1.6}
  code{font-family:ui-monospace,SFMono-Regular,Menlo,Consolas,monospace}
  blockquote{margin:0;padding:10px 14px;border-left:3px solid var(--accent);color:var(--muted);font-size:14px}
  .err{color:var(--ng);font-size:14px}
  footer{color:var(--muted);font-size:13px;margin-top:32px}
  a{color:var(--accent)}
  .scroll{overflow-x:auto}
</style>
</head>
<body>
<div class="wrap">

<header>
  <h1>インボイスの端数処理は「税率ごとに1回」</h1>
  <p class="lead">同じ明細でも、丸める場所を間違えると消費税額がズレます。実際に入力して確かめてください。</p>
</header>

<?php if ($error !== null): ?>
<section><p class="err">入力エラー: <?= h($error) ?></p></section>
<?php endif; ?>

<?php if ($correct !== null): ?>
<section>
  <h2>結果</h2>
  <div class="verdict">
    <div class="card bad">
      <div class="tag">行ごとに丸めて合計（よくある誤り）</div>
      <div class="amount"><?= number_format($perLineTax) ?> 円</div>
      <div class="tag">消費税額</div>
    </div>
    <div class="card good">
      <div class="tag">税率ごとに1回だけ丸める（制度どおり）</div>
      <div class="amount"><?= number_format($correct->totalTaxAmount) ?> 円</div>
      <div class="tag">消費税額</div>
    </div>
  </div>

  <p class="diff">
    <?php if ($difference === 0): ?>
      この明細では<strong>差が出ません</strong>。端数が出ない金額だと一致します。単価を 105 円のような
      端数の出る値にすると差が現れます。
    <?php else: ?>
      差額 <strong><?= number_format(abs($difference)) ?> 円</strong>。
      行ごとに丸める実装は、この請求書で消費税額を <?= $difference > 0 ? '過少' : '過大' ?>に計算しています。
    <?php endif; ?>
  </p>

  <div class="scroll">
  <table>
    <thead>
      <tr><th>税率</th><th class="num">対価の額</th><th class="num">消費税額（制度どおり）</th></tr>
    </thead>
    <tbody>
    <?php foreach ($correct->summaries as $s): ?>
      <tr>
        <td><?= h($s->taxRate->label()) ?></td>
        <td class="num"><?= number_format($s->taxableBase) ?> 円</td>
        <td class="num"><?= number_format($s->taxAmount) ?> 円</td>
      </tr>
    <?php endforeach; ?>
      <tr>
        <td><strong>合計</strong></td>
        <td class="num"><strong><?= number_format($correct->totalTaxableBase) ?> 円</strong></td>
        <td class="num"><strong><?= number_format($correct->totalTaxAmount) ?> 円</strong></td>
      </tr>
    </tbody>
  </table>
  </div>

  <p style="font-size:14px;margin-top:14px">請求総額（税込）: <strong><?= number_format($correct->grandTotal) ?> 円</strong></p>

  <details style="margin-top:16px">
    <summary style="cursor:pointer;font-size:14px">行ごとに丸めた場合の内訳を見る</summary>
    <div class="scroll">
    <table style="margin-top:12px">
      <thead><tr><th>品名</th><th class="num">数量</th><th class="num">単価</th><th>税率</th><th class="num">行ごとの税額</th></tr></thead>
      <tbody>
      <?php foreach ($perLineDetail as $row): ?>
        <tr>
          <td><?= h($row['line']->description) ?></td>
          <td class="num"><?= h($row['line']->quantity) ?></td>
          <td class="num"><?= h($row['line']->unitPrice) ?></td>
          <td><?= h($row['line']->taxRate->label()) ?></td>
          <td class="num"><?= number_format($row['tax']) ?> 円</td>
        </tr>
      <?php endforeach; ?>
        <tr><td colspan="4"><strong>合計</strong></td><td class="num"><strong><?= number_format($perLineTax) ?> 円</strong></td></tr>
      </tbody>
    </table>
    </div>
  </details>
</section>
<?php endif; ?>

<section>
  <h2>明細</h2>
  <form method="post">
    <div class="scroll">
    <table>
      <thead><tr><th>品名</th><th style="width:80px">数量</th><th style="width:110px">単価</th><th style="width:140px">税率</th></tr></thead>
      <tbody>
      <?php for ($i = 0; $i < max(6, count($lines)); $i++):
          $row = $lines[$i] ?? ['description' => '', 'quantity' => '', 'unitPrice' => '', 'taxRate' => 'standard_10']; ?>
        <tr>
          <td><input type="text" name="lines[<?= $i ?>][description]" value="<?= h($row['description']) ?>"></td>
          <td><input type="text" name="lines[<?= $i ?>][quantity]" value="<?= h($row['quantity']) ?>" inputmode="decimal"></td>
          <td><input type="text" name="lines[<?= $i ?>][unitPrice]" value="<?= h($row['unitPrice']) ?>" inputmode="decimal"></td>
          <td>
            <select name="lines[<?= $i ?>][taxRate]">
              <?php foreach (TaxRate::cases() as $rate): ?>
                <option value="<?= h($rate->value) ?>" <?= $row['taxRate'] === $rate->value ? 'selected' : '' ?>>
                  <?= h($rate->label()) ?>
                </option>
              <?php endforeach; ?>
            </select>
          </td>
        </tr>
      <?php endfor; ?>
      </tbody>
    </table>
    </div>

    <div class="opts">
      <label>単価の入力
        <select name="priceMode">
          <option value="tax_excluded" <?= $priceMode === PriceMode::TAX_EXCLUDED ? 'selected' : '' ?>>税抜</option>
          <option value="tax_included" <?= $priceMode === PriceMode::TAX_INCLUDED ? 'selected' : '' ?>>税込</option>
        </select>
      </label>
      <label>端数処理
        <select name="rounding">
          <option value="floor" <?= $roundingMode === RoundingMode::FLOOR ? 'selected' : '' ?>>切捨て</option>
          <option value="ceil" <?= $roundingMode === RoundingMode::CEIL ? 'selected' : '' ?>>切上げ</option>
          <option value="half_up" <?= $roundingMode === RoundingMode::HALF_UP ? 'selected' : '' ?>>四捨五入</option>
        </select>
      </label>
    </div>

    <button type="submit">計算する</button>
  </form>
</section>

<section>
  <h2>制度上の根拠</h2>
  <blockquote>
    適格請求書の記載事項である消費税額等に１円未満の端数が生じる場合は、一の適格請求書につき、
    税率ごとに１回の端数処理を行う必要があります（消令70の10、基通１−８−15）。<br>
    （注）一の適格請求書に記載されている個々の商品ごとに消費税額等を計算し、１円未満の端数処理を行い、
    その合計額を消費税額等として記載することは認められません。
  </blockquote>
  <p style="font-size:13px;color:var(--muted);margin-top:10px">
    出典: 国税庁「消費税の仕入税額控除制度における適格請求書等保存方式に関するQ&amp;A」問57（令和6年4月改訂）。
    切上げ・切捨て・四捨五入のいずれを採るかは事業者が選べますが、継続して適用する必要があります。
  </p>
</section>

<section>
  <h2>同じ計算をライブラリで</h2>
<?php if ($config['published_on_packagist']): ?>
  <pre><code>composer require foovar/jp-invoice</code></pre>
<?php endif; ?>
  <pre><code>use Foovar\JpInvoice\{Issuer, Invoice, LineItem};
use Foovar\JpInvoice\Enum\{TaxRate, RoundingMode, PriceMode};

$issuer  = new Issuer('自社', 'T1234567890123', RoundingMode::<?= h(strtoupper($roundingMode->value)) ?>);
$invoice = new Invoice($issuer, priceMode: PriceMode::<?= h(strtoupper($priceMode->value)) ?>);
<?php foreach ($lines as $row): ?>
$invoice-&gt;addLine(new LineItem('<?= h($row['description']) ?>', '<?= h($row['quantity']) ?>', '<?= h($row['unitPrice']) ?>', TaxRate::<?= h(strtoupper($row['taxRate'])) ?>));
<?php endforeach; ?>

$result = $invoice-&gt;calculate();
$result-&gt;totalTaxAmount; // <?= $correct?->totalTaxAmount ?? '' ?> (int)</code></pre>
  <p style="font-size:14px">
    PHP 8.2+ / 依存パッケージなし / MIT。金額は numeric-string と整数だけで扱い、float は一切使いません。
<?php if ($config['github_url'] !== null): ?>
    <a href="<?= h($config['github_url']) ?>">ソースコード（GitHub）</a>
<?php endif; ?>
  </p>
</section>

<?php if ($config['contact_email'] !== null): ?>
<section>
  <h2>帳票 PDF・Laravel 統合・業種別テンプレート</h2>
  <p style="font-size:14px">
    計算コアは MIT で無料公開しています。その上に載せる次のものは商用で提供しています。
  </p>
  <ul style="font-size:14px">
    <li><strong>帳票 PDF 生成</strong> — A4縦の日本様式、税率別内訳表、角印埋め込み、御中／様の自動判定</li>
    <li><strong>Laravel 統合</strong> — Service Provider、Blade コンポーネント、マイグレーション雛形</li>
    <li><strong>業種別テンプレート集</strong> — 駐車場業（簡易インボイス）、コールセンター、保守契約 ほか</li>
  </ul>
  <p style="font-size:14px">
    <a href="mailto:<?= h($config['contact_email']) ?>?subject=jp-invoice%20%E5%95%8F%E3%81%84%E5%90%88%E3%82%8F%E3%81%9B">
      <?= h($config['contact_email']) ?> までお問い合わせください
    </a>
  </p>
</section>
<?php endif; ?>

<footer>
  <p>
    本ページは消費税額の計算を体験するためのデモです。個別の取引における税務上の取扱いについては、
    税理士等の専門家にご確認ください。入力された内容は保存していません。
  </p>
</footer>

</div>
</body>
</html>
