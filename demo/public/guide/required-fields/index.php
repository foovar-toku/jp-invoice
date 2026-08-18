<?php
declare(strict_types=1);
require dirname(__DIR__, 3) . '/layout.php';
page_head(
    title: '適格請求書の記載事項6項目チェックリスト — 実装者向け',
    description: '適格請求書に必要な6つの記載事項（消法57の4①）を、システム実装の観点で整理しました。取引年月日と発行日の違い、軽減税率対象である旨の表し方、適格簡易請求書との差分まで。',
    path: '/guide/required-fields/',
    isArticle: true,
);
?>
<article>
<header>
  <h1>適格請求書の記載事項6項目チェックリスト</h1>
  <p class="lede">帳票のレイアウトを決める前に確認する項目です。1つでも欠けると、
  受け取った側が仕入税額控除を受けられません。</p>
</header>

<section>
  <h2>6項目（消費税法第57条の4第1項）</h2>
  <div class="scroll">
  <table>
    <thead><tr><th>#</th><th>記載事項</th><th>実装上の注意</th></tr></thead>
    <tbody>
      <tr><td>1</td><td>発行事業者の氏名又は名称 <strong>及び登録番号</strong></td><td>登録番号は <code>T</code> + 13桁。これが無いと区分記載請求書等として扱われる</td></tr>
      <tr><td>2</td><td>課税資産の譲渡等を行った<strong>年月日</strong></td><td><strong>発行日ではない</strong>。両方載せるなら別項目として印字する</td></tr>
      <tr><td>3</td><td>取引内容（<strong>軽減税率対象である旨</strong>）</td><td>※印＋脚注が一般的。品名だけでは足りない</td></tr>
      <tr><td>4</td><td>税率ごとに区分して合計した対価の額 <strong>及び適用税率</strong></td><td>税抜・税込どちらでもよい。10%と8%を分けて出す</td></tr>
      <tr><td>5</td><td>税率ごとに区分した<strong>消費税額等</strong></td><td>端数処理は税率ごとに1回（→ <a href="/guide/rounding/">詳細</a>）</td></tr>
      <tr><td>6</td><td>交付を受ける事業者の氏名又は名称</td><td>適格簡易請求書では不要</td></tr>
    </tbody>
  </table>
  </div>
  <p class="related">出典: 消費税法第57条の4第1項／国税庁Q&amp;A 問54（令和6年4月改訂）</p>
</section>

<section>
  <h2>とくに間違えやすい2点</h2>

  <h3>「取引年月日」を発行日で代用してしまう</h3>
  <p>記載事項2は<strong>課税資産の譲渡等を行った年月日</strong>です。月末締めの請求書で
  «発行日: 9月1日» とだけ書いてあると、この項目を満たしません。
  一定期間をまとめる場合は「8月1日〜8月31日」のように課税期間を示す形でも構いません。</p>

  <h3>軽減税率対象である旨が抜ける</h3>
  <p>「飲食料品だから見れば分かる」では足りません。※印などの記号を付け、
  «※は軽減税率対象品目» と脚注を入れるのが実務上の定型です。</p>
</section>

<section>
  <h2>適格簡易請求書との差分</h2>
  <p>小売業、飲食店業、写真業、旅行業、タクシー業、<strong>駐車場業</strong>など、
  不特定かつ多数の者に対する取引を行う事業者は、適格簡易請求書を交付できます（消法57の4②）。</p>
  <ul>
    <li>記載事項6（交付を受ける事業者の氏名又は名称）が<strong>不要</strong></li>
    <li>記載事項4・5について、「適用税率」と「税率ごとに区分した消費税額等」は<strong>いずれか一方</strong>で足りる</li>
  </ul>
  <p>コインパーキングの領収書がこれに当たります。1つの事業者が月極（適格請求書）と
  時間貸し（適格簡易請求書）の両方を発行することは珍しくありません。</p>
</section>

<section>
  <h2>実装での検証</h2>
  <pre><code>$invoice-&gt;validate();   // 記載事項が欠けていれば MissingRequiredFieldException
$result = $invoice-&gt;calculate();</code></pre>
  <p>検証と計算は分けておくと、「下書き保存では検証しない」といった運用に対応しやすくなります。</p>
</section>

<div class="cta">
  <p><strong>記載事項の充足検証つきの計算ライブラリ</strong>（MIT・無料）</p>
  <p style="font-size:14px"><code>composer require foovar/jp-invoice</code> —
  <a href="https://github.com/foovar-toku/jp-invoice">GitHub</a> ／
  <a href="/">デモ</a> ／ <a href="/sample-invoice.pdf">PDF 帳票の見本</a>もあります</p>
</div>

<p class="related">関連: <a href="/guide/rounding/">端数処理は税率ごとに1回</a>／
<a href="/guide/transitional/">経過措置の控除割合</a></p>
</article>
<?php page_foot(); ?>
