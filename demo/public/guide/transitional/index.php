<?php
declare(strict_types=1);
require dirname(__DIR__, 3) . '/layout.php';
page_head(
    title: '免税事業者からの仕入れ 経過措置の控除割合 — 令和8年度改正後',
    description: '免税事業者等からの課税仕入れに係る経過措置の控除割合が、令和8年度税制改正で 80% → 70% → 50% → 30% → 0% の段階に変わりました。適用期間の一覧と、あわせて導入された1億円の控除限度額を整理します。',
    path: '/guide/transitional/',
    isArticle: true,
);
?>
<article>
<header>
  <h1>免税事業者からの仕入れ — 経過措置の控除割合</h1>
  <p class="lede">インボイス発行事業者でない相手からの課税仕入れでも、一定割合は控除できます。
  この割合が令和8年度税制改正で段階的なものに変わりました。</p>
</header>

<section>
  <h2>控除割合と適用期間</h2>
  <div class="scroll">
  <table>
    <thead><tr><th>課税仕入れを行った日</th><th class="num">控除割合</th></tr></thead>
    <tbody>
      <tr><td>2023-10-01 〜 2026-09-30（令和5年10月1日から3年間）</td><td class="num">80%</td></tr>
      <tr><td>2026-10-01 〜 2028-09-30（令和8年10月1日から2年間）</td><td class="num"><strong>70%</strong></td></tr>
      <tr><td>2028-10-01 〜 2030-09-30（令和10年10月1日から2年間）</td><td class="num">50%</td></tr>
      <tr><td>2030-10-01 〜 2031-09-30（令和12年10月1日から1年間）</td><td class="num">30%</td></tr>
      <tr><td>2031-10-01 〜（令和13年10月1日以降）</td><td class="num">0%（控除不可）</td></tr>
    </tbody>
  </table>
  </div>
  <p class="related">出典: 国税庁「令和８年度税制改正特集」（2026年8月17日確認）</p>
  <p>改正前は「令和8年10月1日から50%」でしたが、改正後は70%・50%・30%と段階を踏み、
  終了が令和13年9月30日まで延びています。<strong>二次情報には改正前の記載が残っているものが多い</strong>ので、
  実装するときは必ず一次情報で確認してください。</p>
</section>

<section>
  <h2>あわせて入った「1億円の控除限度額」</h2>
  <blockquote>
    一のインボイス発行事業者以外の者からの課税仕入れの合計額（税込み）が、その年又は事業年度で
    <strong>１億円</strong>（改正前：10億円）を超える場合には、その超えた部分の課税仕入れについて適用できません。
  </blockquote>
  <p>令和8年10月1日以後に開始する課税期間から適用されます。
  <strong>割合とは別枠の上限</strong>で、しかも年間・相手先ごとの累計で決まります。
  1枚の請求書だけを見て判定することはできないので、システムでは
  「相手先ごとの年間累計」を別に持つ必要があります。</p>
</section>

<section>
  <h2>実装での持ち方</h2>
  <p>割合は<strong>コードに直接書かず、期間と割合の表として外に出す</strong>ことを勧めます。
  制度は今後も変わります。テーブルの版（いつ時点の理解か）も一緒に持っておくと、
  «この計算はどの改正まで反映されているのか» を後から追えます。</p>
  <pre><code>use Foovar\JpInvoice\TransitionalMeasure;

$rate = TransitionalMeasure::rateFor(new DateTimeImmutable('2026-10-01'));
$rate-&gt;percent;         // 70
$rate-&gt;applyTo(10000);  // 7000

TransitionalMeasure::SCHEDULE_VERSION;  // '2026-08-17'</code></pre>
  <p>制度開始前など表の範囲外の日付は、0% を返すのではなく例外にしています。
  「控除できない（0%）」と「判定できない」は意味が違うためです。</p>
</section>

<div class="cta">
  <p><strong>経過措置の判定を含む計算ライブラリ</strong>（MIT・無料）</p>
  <p style="font-size:14px"><code>composer require foovar/jp-invoice</code> —
  <a href="https://github.com/foovar-toku/jp-invoice">GitHub</a>／
  <a href="/">デモ</a></p>
</div>

<p class="related">関連: <a href="/guide/rounding/">端数処理は税率ごとに1回</a>／
<a href="/guide/required-fields/">記載事項6項目</a>／
<a href="/guide/credit-note/">返還インボイス</a></p>
</article>
<?php page_foot(); ?>
