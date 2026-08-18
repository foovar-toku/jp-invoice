<?php
declare(strict_types=1);
require dirname(__DIR__, 3) . '/layout.php';
page_head(
    title: 'インボイスの消費税端数処理は「税率ごとに1回」— 行ごとに丸めると誤り',
    description: '適格請求書の消費税額の端数処理は、一の請求書につき税率ごとに1回だけ行います。明細行ごとに丸めて合計するのは認められません（消令70の10、基通1-8-15、国税庁Q&A 問57）。具体的な計算例とPHPの実装例つき。',
    path: '/guide/rounding/',
    isArticle: true,
);
?>
<article>
<header>
  <h1>消費税の端数処理は「税率ごとに1回」</h1>
  <p class="lede">請求書システムで最も多い実装ミスです。行ごとに丸めても、合計してから丸めても、
  どちらも1円単位で違う額になります。制度が求めるのは<strong>税率ごとに1回だけ</strong>です。</p>
</header>

<section>
  <h2>何が問題になるのか</h2>
  <p>税抜105円の商品を3行、税率10%、切捨てで計算してみます。</p>

  <div class="scroll">
  <table>
    <thead><tr><th>計算方法</th><th class="num">消費税額</th><th>制度上の可否</th></tr></thead>
    <tbody>
      <tr><td>行ごとに 105 × 10% = 10.5 → 10 円、それを3行合計</td><td class="num">30 円</td><td style="color:var(--ng)">認められない</td></tr>
      <tr><td>税率ごとに合計 315 円 × 10% = 31.5 → 31 円</td><td class="num"><strong>31 円</strong></td><td style="color:var(--ok)">正しい</td></tr>
    </tbody>
  </table>
  </div>

  <p>差は1円ですが、明細が増えるほど開きます。しかも<strong>買手の仕入税額控除の額に影響する</strong>ため、
  «自社の帳簿では合っているのに取引先と1円合わない» という形で表面化します。</p>
</section>

<section>
  <h2>根拠（国税庁Q&A 問57）</h2>
  <blockquote>
    適格請求書の記載事項である消費税額等に１円未満の端数が生じる場合は、一の適格請求書につき、
    税率ごとに１回の端数処理を行う必要があります（消令70の10、基通１−８−15）。<br>
    なお、切上げ、切捨て、四捨五入などの端数処理の方法については、任意の方法とすることができます。<br>
    （注）一の適格請求書に記載されている個々の商品ごとに消費税額等を計算し、１円未満の端数処理を行い、
    その合計額を消費税額等として記載することは<strong>認められません</strong>。
  </blockquote>
  <p class="related">出典: 国税庁「消費税の仕入税額控除制度における適格請求書等保存方式に関するQ&amp;A」問57（令和6年4月改訂）／
  消費税法施行令第70条の10、消費税法基本通達1-8-15</p>
</section>

<section>
  <h2>実装するときの3つのルール</h2>
  <h3>1. 丸めの単位は「税率グループ」</h3>
  <p>10%対象と8%対象は別々に合計し、それぞれ1回ずつ端数処理します。
  税率をまたいで合計してから丸めるのも誤りです。</p>

  <h3>2. 丸め方は選べるが、継続適用する</h3>
  <p>切上げ・切捨て・四捨五入のどれを採るかは事業者が決められます。
  ただし請求書ごとに変えてよいという意味ではありません。実装上は
  <strong>事業者の設定として持ち、請求書単位で上書きできないようにする</strong>のが安全です。</p>

  <h3>3. 浮動小数点を使わない</h3>
  <p><code>0.1</code> は二進数で正確に表現できません。金額計算に <code>float</code> を使うと、
  丸めの直前で <code>31.499999...</code> のような値になり、四捨五入の結果が変わることがあります。
  PHP なら BCMath、文字列と整数で扱ってください。</p>
</section>

<section>
  <h2>PHP での実装例</h2>
  <pre><code>use Foovar\JpInvoice\{Issuer, Invoice, LineItem};
use Foovar\JpInvoice\Enum\{TaxRate, RoundingMode};

$issuer  = new Issuer('自社', 'T1234567890123', RoundingMode::FLOOR);
$invoice = new Invoice($issuer);

$invoice-&gt;addLine(new LineItem('商品A', '1', '105', TaxRate::STANDARD_10));
$invoice-&gt;addLine(new LineItem('商品B', '1', '105', TaxRate::STANDARD_10));
$invoice-&gt;addLine(new LineItem('商品C', '1', '105', TaxRate::STANDARD_10));

$result = $invoice-&gt;calculate();
$result-&gt;totalTaxAmount;   // 31（行ごとに丸めた 30 ではない）</code></pre>
  <p><a href="/">ブラウザ上のデモ</a>で、自分の明細を入れて差を確認できます。</p>
</section>

<div class="cta">
  <p><strong>foovar/jp-invoice</strong> — インボイス対応の消費税計算ライブラリ（MIT・無料）</p>
  <p style="font-size:14px"><code>composer require foovar/jp-invoice</code><br>
  PHP 8.2+／依存パッケージなし／float 不使用／国税庁Q&amp;A の計算例をテストに採録。
  <a href="https://github.com/foovar-toku/jp-invoice">GitHub</a></p>
</div>

<p class="related">
  この論点を実装者向けにまとめた記事もあります:
  <a href="https://zenn.dev/foovar/articles/a2a1ddb4ef73f1">PHPでインボイスの消費税を計算したら1円ズレる</a>（Zenn）<br>
  関連: <a href="/guide/required-fields/">適格請求書の記載事項6項目</a>／
  <a href="/guide/credit-note/">返還インボイスと1万円未満の免除</a>
</p>
</article>
<?php page_foot(); ?>
