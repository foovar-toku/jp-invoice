<?php
declare(strict_types=1);
require dirname(__DIR__, 2) . '/layout.php';
page_head(
    title: 'インボイス制度の実装ガイド — 開発者向け',
    description: '適格請求書（インボイス）をシステムで扱うときに間違えやすい論点を、国税庁の公表資料を引きながら開発者向けに整理しています。端数処理、記載事項、返還インボイス、経過措置。',
    path: '/guide/',
);
?>
<header>
  <h1>インボイス制度の実装ガイド</h1>
  <p class="lead">請求書を発行するシステムを作るときに、実際に事故が起きる論点だけを集めました。
  すべて国税庁の公表資料を出典として明記しています。</p>
</header>

<section>
  <ul style="line-height:2.2">
    <li><a href="/guide/rounding/"><strong>消費税の端数処理は「税率ごとに1回」</strong></a><br>
      <span style="color:var(--muted);font-size:14px">明細行ごとに丸めると税額がズレます。最も多い実装ミス（国税庁Q&A 問57）</span></li>
    <li><a href="/guide/required-fields/"><strong>適格請求書の記載事項6項目チェックリスト</strong></a><br>
      <span style="color:var(--muted);font-size:14px">取引年月日と発行日は別物。簡易インボイスとの差分も（消法57の4、問54）</span></li>
    <li><a href="/guide/credit-note/"><strong>返還インボイスと1万円未満の交付義務免除</strong></a><br>
      <span style="color:var(--muted);font-size:14px">振込手数料の値引き処理。判定単位を税率ごとにすると誤ります（問28）</span></li>
    <li><a href="/guide/transitional/"><strong>免税事業者からの仕入れ — 経過措置の控除割合</strong></a><br>
      <span style="color:var(--muted);font-size:14px">令和8年度改正で 80% → 70% → 50% → 30% → 0% に。1億円の控除限度額も</span></li>
  </ul>
</section>

<section>
  <h2>ツール</h2>
  <p style="font-size:14px">
    <a href="/tools/registration-number/"><strong>登録番号チェッカー</strong></a> —
    <code>T</code>+13桁の形式と、法人番号のチェックディジットを計算過程つきで確認します<br>
    <a href="/"><strong>端数処理の比較</strong></a> —
    明細を入れて「行ごとに丸めた場合」と「税率ごとに1回」の差を見ます
  </p>
</section>

<section>
  <h2>技術記事</h2>
  <p style="font-size:14px">
    <a href="https://zenn.dev/foovar/articles/a2a1ddb4ef73f1">PHPでインボイスの消費税を計算したら1円ズレる — 端数処理は「税率ごとに1回」</a>（Zenn）<br>
    <span style="color:var(--muted)">実装でつまずいた点をまとめたものです。コード例つき。</span>
  </p>
</section>

<div class="cta">
  <p><strong>計算部分は PHP ライブラリとして無料公開しています。</strong></p>
  <p style="font-size:14px">
    <code>composer require foovar/jp-invoice</code> —
    MIT ライセンス／依存パッケージなし／国税庁Q&A の計算例で検証済み。
    <a href="/">ブラウザで動きを試す</a>
  </p>
</div>
<?php page_foot(); ?>
