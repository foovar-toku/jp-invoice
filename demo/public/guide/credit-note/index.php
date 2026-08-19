<?php
declare(strict_types=1);
require dirname(__DIR__, 3) . '/layout.php';
page_head(
    title: '返還インボイスと1万円未満の交付義務免除 — 振込手数料の値引き処理',
    description: '適格返還請求書（返還インボイス）の記載事項と、税込1万円未満で交付義務が免除されるルールを整理しました。判定は「請求や債権の単位ごと」であり、適用税率ごとではありません（国税庁Q&A 問28）。',
    path: '/guide/credit-note/',
    isArticle: true,
);
?>
<article>
<header>
  <h1>返還インボイスと「1万円未満」の交付義務免除</h1>
  <p class="lede">値引き・返品・割戻しをしたら返還インボイスが要る、が原則です。
  ただし税込1万円未満なら免除されます。この判定単位を間違えている実装をよく見ます。</p>
</header>

<section>
  <h2>記載事項（消法57の4③）</h2>
  <ol>
    <li>発行事業者の氏名又は名称及び登録番号</li>
    <li>対価の返還等を行う年月日</li>
    <li><strong>対価の返還等の基となった取引の年月日</strong> ← 適格請求書には無い項目</li>
    <li>対価の返還等の取引内容（軽減税率対象である旨）</li>
    <li>税率ごとに区分して合計した対価の返還等の金額</li>
    <li>税率ごとに区分した消費税額等 又は 適用税率</li>
  </ol>
  <p>3番目が曲者です。元の取引がいつのものかを保持していないシステムでは、後から書けません。
  <strong>返金レコードに元取引日を持たせる</strong>設計にしておいてください。</p>
</section>

<section>
  <h2>1万円未満は交付義務が免除される</h2>
  <p>売上に係る対価の返還等に係る<strong>税込価額が1万円未満</strong>であれば、
  返還インボイスの交付義務は免除されます（消法57の4③、消令70の9③二）。
  買手が振込手数料相当額を差し引いて振り込み、売手がそれを売上値引として処理する場合が典型です。</p>

  <h3>判定単位を間違えないこと</h3>
  <blockquote>
    この１万円かどうかの判定は、値引き等の金額に標準税率が適用されたものと軽減税率が適用されたものが
    含まれている場合であったとしても、<strong>適用税率ごとの値引き等の金額により判定するものではなく</strong>、
    返還した金額や値引き等の対象となる請求や債権の単位ごとの減額金額により判定することとなります。
  </blockquote>
  <p class="related">出典: 国税庁Q&amp;A 問28（令和5年10月改訂）／基通1-8-17</p>

  <div class="scroll">
  <table>
    <thead><tr><th>ケース</th><th class="num">税込金額</th><th>交付義務</th></tr></thead>
    <tbody>
      <tr><td>50万円の請求に対し、振込手数料相当額を値引き</td><td class="num">440 円</td><td style="color:var(--ok)">免除される</td></tr>
      <tr><td>40万円の請求に対し、1商品100円のリベートを後日支払（200商品）</td><td class="num">20,000 円</td><td style="color:var(--ng)">免除されない</td></tr>
      <tr><td>8%対象 6,000円 + 10%対象 5,000円 の返品</td><td class="num">11,000 円</td><td style="color:var(--ng)">免除されない</td></tr>
    </tbody>
  </table>
  </div>
  <p>3つ目が事故のもとです。税率ごとに見ると両方1万円未満ですが、判定は<strong>全体の税込合計</strong>で行います。
  税率グループごとに判定する実装は、交付すべき書類を出さないことになります。</p>
</section>

<section>
  <h2>実装例</h2>
  <pre><code>use Foovar\JpInvoice\CreditNote;

$note = new CreditNote(
    issuer: $issuer,
    returnDate: new DateTimeImmutable('2026-09-15'),
    originalTransactionDate: new DateTimeImmutable('2026-08-31'), // 記載事項3
);
$note-&gt;addLine(new LineItem('振込手数料相当額の売上値引', '1', '440', TaxRate::STANDARD_10));

$note-&gt;requiresIssuance();   // false（税込1万円未満）
$note-&gt;taxIncludedTotal();   // 440</code></pre>
  <p>免除される場合でも、交付して差し支えありません。実務では「出しておく」運用も多いです。</p>
</section>

<div class="cta">
  <p><strong>返還インボイスの判定と計算に対応</strong>（MIT・無料）</p>
  <p style="font-size:14px"><code>composer require foovar/jp-invoice</code> —
  <a href="https://github.com/foovar-toku/jp-invoice">GitHub</a>／
  PDF 帳票の出力サンプルは <a href="/">トップページ</a>にあります</p>
</div>

<p class="related">関連: <a href="/guide/rounding/">端数処理は税率ごとに1回</a>／
<a href="/guide/required-fields/">記載事項6項目</a></p>
</article>
<?php page_foot(); ?>
