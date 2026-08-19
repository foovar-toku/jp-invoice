<?php

declare(strict_types=1);

require dirname(__DIR__, 2) . '/layout.php';

/** @var array<string, mixed> $config */
$config = require dirname(__DIR__, 2) . '/config.php';
/** @var array<string, string> $company */
$company = $config['company'];

page_head(
    title: 'ご購入ありがとうございます — jp-invoice-pdf',
    description: 'jp-invoice-pdf をご購入いただいた方向けの、受け取り手順のご案内です。',
    path: '/thanks/',
    noindex: true,   // 購入者だけが見るページなので検索対象にしない
);
?>
<header>
  <h1>ご購入ありがとうございます</h1>
  <p class="lead">決済が完了しました。受け取りまでの流れをご案内します。</p>
</header>

<section>
  <h2>1. 公開鍵をお送りください</h2>
  <p>パッケージは Composer 経由でお渡しします。配布用リポジトリへの読み取り専用アクセスを
  発行しますので、<strong>SSH の公開鍵</strong>を下記までお送りください。</p>
  <pre><code># 鍵が無い場合はデプロイ用に新しく作成してください
ssh-keygen -t ed25519 -C "jp-invoice-pdf" -f ~/.ssh/jp_invoice_pdf
cat ~/.ssh/jp_invoice_pdf.pub   # ← この内容をお送りください</code></pre>
  <p>送付先: <strong><?= h($company['email']) ?></strong>（件名に「jp-invoice-pdf 公開鍵」とご記入ください）</p>
  <p style="font-size:14px;color:var(--muted)">秘密鍵は絶対に送らないでください。必要なのは <code>.pub</code> の方だけです。</p>
</section>

<section>
  <h2>2. 当社でアクセスを発行します</h2>
  <p><strong>3営業日以内</strong>に読み取り専用の鍵を登録し、完了をメールでご連絡します。
  お急ぎの場合はその旨をご記入ください。</p>
</section>

<section>
  <h2>3. Composer で導入</h2>
  <p>ご案内後、お手元の <code>composer.json</code> に次を追記して <code>composer update</code> を実行してください。</p>
  <pre><code>{
  "repositories": [
    { "type": "vcs", "url": "git@github.com:foovar-toku/jp-invoice-pdf.git" }
  ],
  "require": {
    "foovar/jp-invoice-pdf": "^1.0"
  }
}</code></pre>
  <p>計算コア <code>foovar/jp-invoice</code>（MIT）は Packagist から自動で入ります。
  使い方は同梱の README をご覧ください。</p>
</section>

<section>
  <h2>4. 適格請求書をお送りします</h2>
  <p>お支払いに対する適格請求書（登録番号 <?= h($company['registration_number']) ?>）を、
  ご登録のメールアドレス宛に PDF でお送りします。</p>
  <p style="font-size:14px;color:var(--muted)">
    この請求書は、本パッケージ自身で生成しています。出力の実物としてもご確認いただけます。
  </p>
</section>

<div class="cta">
  <p><strong>ご不明な点は <?= h($company['email']) ?> までご連絡ください。</strong></p>
  <p style="font-size:14px">
    導入でお困りの場合は、組み込み実装の支援も承っています。
    <a href="/legal/">特定商取引法に基づく表記</a>
  </p>
</div>
<?php page_foot(); ?>
