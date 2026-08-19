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

?>
<?php
require dirname(__DIR__) . '/layout.php';

page_head(
    title: 'インボイスの端数処理は税率ごとに1回 — jp-invoice デモ',
    description: '適格請求書の消費税額を、行ごとに丸めた場合と税率ごとに1回丸めた場合で比較できるデモです。国税庁Q&A 問57 の根拠つき。PHP ライブラリ（MIT）も公開しています。',
    path: '/',
);
?>
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
    <a href="https://zenn.dev/foovar/articles/a2a1ddb4ef73f1">解説記事（Zenn）</a>
    ／ <a href="/guide/">実装ガイド</a>
<?php if ($config['github_url'] !== null): ?>
    <a href="<?= h($config['github_url']) ?>">ソースコード（GitHub）</a>
<?php endif; ?>
  </p>
</section>

<?php if ($config['contact_email'] !== null): ?>
<section>
  <h2>帳票 PDF 生成パッケージ（商用）</h2>
  <p style="font-size:14px">
    計算コアは MIT で無料公開しています。その上に載せる帳票 PDF の生成は商用で提供しています。
  </p>
  <ul style="font-size:14px">
    <li>A4縦の日本様式。<strong>記載事項6項目</strong>をレイアウトに織り込み済み</li>
    <li>税率ごとの内訳表、軽減税率の注記、ロゴ・角印、振込先、自動改ページ</li>
    <li><strong>適格返還請求書</strong>にも対応（元取引年月日の記載、返還額の内訳）</li>
    <li>日本語フォントを埋め込み。環境依存で文字が消えない</li>
  </ul>

  <p style="font-size:14px">
    <a href="/sample-invoice.pdf">▶ 出力見本（PDF）</a>
    ／ 下記の導入事例もご覧いただけます
  </p>

  <h3 style="font-size:15px;margin:20px 0 8px">導入事例: 駐車場運営会社の請求書一式</h3>
  <p style="font-size:14px;color:var(--muted)">
    月極（適格請求書）とコインパーキング（適格簡易請求書）が同居する駐車場業を想定して、
    実運用に近いデータで出力したものです。すべて同じコードから生成しています。
  </p>
  <ul style="font-size:14px;line-height:2">
    <li><a href="/case-study/01-corporate.pdf">法人の月極契約</a> — 複数区画＋管理費。宛名は「御中」を自動判定</li>
    <li><a href="/case-study/02-reduced-rate.pdf">軽減税率が混在する請求</a> — 8%対象に ※ と脚注、税率ごとの内訳</li>
    <li><a href="/case-study/03-individual.pdf">個人契約（税込入力）</a> — 宛名は「様」を自動判定</li>
    <li><a href="/case-study/04-simplified-receipt.pdf">コインパーキングの領収書</a> — 適格簡易請求書。宛名なしで成立</li>
    <li><a href="/case-study/05-multipage.pdf">明細48行の請求書</a> — 自動改ページ、表見出しの再描画、ページ番号</li>
    <li><a href="/case-study/06-credit-note.pdf">適格返還請求書</a> — 解約の日割返金。元取引年月日を記載</li>
  </ul>

<?php
    /** @var array<string, array{label: string, price: ?int, url: ?string, note: string}> $links */
    $links = $config['payment_links'];
    $buyable = array_filter($links, static fn (array $l): bool => $l['url'] !== null && $l['price'] !== null);
?>
<?php if ($buyable !== []): ?>
  <h3 style="font-size:15px;margin:24px 0 10px">ご購入</h3>
  <div class="scroll">
  <table>
    <thead><tr><th>プラン</th><th class="num">価格（税込）</th><th></th></tr></thead>
    <tbody>
    <?php foreach ($buyable as $link): ?>
      <tr>
        <td><?= h($link['label']) ?><br>
          <span style="color:var(--muted);font-size:13px"><?= h($link['note']) ?></span></td>
        <td class="num"><?= number_format((int) $link['price']) ?> 円</td>
        <td><a href="<?= h((string) $link['url']) ?>"
               style="display:inline-block;padding:7px 16px;background:var(--accent);color:#fff;border-radius:6px;text-decoration:none">購入する</a></td>
      </tr>
    <?php endforeach; ?>
    </tbody>
  </table>
  </div>
  <p style="font-size:13px;color:var(--muted);margin-top:10px">
    決済は Stripe で行われます。決済後、<strong>3営業日以内</strong>に受け取り方法をメールでご案内します。
    お支払いに対する適格請求書（登録番号 <?= h($config['company']['registration_number']) ?>）を PDF でお送りします。
    <a href="/legal/">特定商取引法に基づく表記</a>
  </p>
<?php endif; ?>

  <h3 style="font-size:15px;margin:22px 0 8px">開発中</h3>
  <p style="font-size:14px;color:var(--muted)">
    Laravel 統合（Service Provider・Blade コンポーネント）と業種別テンプレート集は開発中です。
    ご要望があればお知らせください。優先順位の判断材料にします。
  </p>

  <p style="font-size:14px">
    お問い合わせ・お見積り:
    <a href="mailto:<?= h($config['contact_email']) ?>?subject=jp-invoice-pdf%20%E3%81%AB%E3%81%A4%E3%81%84%E3%81%A6">
      <?= h($config['contact_email']) ?>
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
<?php page_foot(); ?>
