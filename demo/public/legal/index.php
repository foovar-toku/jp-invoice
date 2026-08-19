<?php

declare(strict_types=1);

require dirname(__DIR__, 2) . '/layout.php';

/** @var array<string, mixed> $config */
$config = require dirname(__DIR__, 2) . '/config.php';
/** @var array<string, string> $company */
$company = $config['company'];

/** @var array<string, array{label: string, price: ?int, url: ?string, note: string}> $links */
$links = $config['payment_links'];
$hasPrices = false;
foreach ($links as $link) {
    if ($link['price'] !== null) {
        $hasPrices = true;
    }
}

page_head(
    title: '特定商取引法に基づく表記 — jp-invoice-pdf',
    description: 'ピー・アイ・ジェイ株式会社が販売する jp-invoice-pdf（適格請求書のPDF帳票生成パッケージ）に関する、特定商取引法に基づく表記です。',
    path: '/legal/',
);
?>
<header>
  <h1>特定商取引法に基づく表記</h1>
</header>

<section>
  <div class="scroll">
  <table>
    <tbody>
      <tr><th style="width:180px">販売事業者</th><td><?= h($company['name']) ?></td></tr>
      <tr><th>代表者</th><td><?= h($company['representative']) ?></td></tr>
      <tr><th>所在地</th><td><?= h($company['address']) ?></td></tr>
      <tr><th>電話番号</th><td><?= h($company['tel']) ?><br>
        <span style="color:var(--muted);font-size:13px">お問い合わせはメールでお願いいたします。お電話の場合は折り返しとなることがあります。</span></td></tr>
      <tr><th>メールアドレス</th><td><?= h($company['email']) ?></td></tr>
      <tr><th>登録番号（インボイス）</th><td><?= h($company['registration_number']) ?></td></tr>
      <tr><th>販売する商品</th><td>ソフトウェアパッケージ「jp-invoice-pdf」の利用許諾、および関連する導入支援役務</td></tr>
      <tr><th>販売価格</th>
        <td>
        <?php if ($hasPrices): ?>
          <ul style="margin:0;padding-left:1.2em">
          <?php foreach ($links as $link): ?>
            <?php if ($link['price'] !== null): ?>
              <li><?= h($link['label']) ?>: <strong><?= number_format($link['price']) ?> 円</strong>（税込）</li>
            <?php endif; ?>
          <?php endforeach; ?>
          </ul>
        <?php else: ?>
          個別のお見積りによります。お問い合わせください。
        <?php endif; ?>
        </td></tr>
      <tr><th>商品代金以外の必要料金</th><td>インターネット接続に要する通信費、銀行振込の場合の振込手数料はお客様のご負担となります。</td></tr>
      <tr><th>支払方法</th><td>クレジットカード決済（Stripe）または銀行振込</td></tr>
      <tr><th>支払時期</th><td>クレジットカード決済: ご注文時に決済されます。<br>銀行振込: 請求書発行日の翌月末日までにお支払いください。</td></tr>
      <tr><th>引渡時期</th>
        <td>決済確認後、<strong>3営業日以内</strong>にダウンロード方法（リポジトリへのアクセス手順）をメールでご案内します。
        導入支援については別途、個別に日程を調整します。</td></tr>
      <tr><th>引渡方法</th><td>電子的な方法（Composer によるダウンロード）でのご提供となります。物理媒体の送付は行いません。</td></tr>
      <tr><th>返品・キャンセル</th>
        <td><strong>デジタル商品の性質上、提供開始後の返品・返金はお受けできません。</strong><br>
        提供前であればキャンセルを承ります。ソフトウェアに当社の責に帰すべき重大な不具合があり、
        相当の期間内に修正できない場合は、個別に返金のご相談に応じます。</td></tr>
      <tr><th>動作環境</th><td>PHP 8.2 以上、<code>ext-bcmath</code>。背景が透過した PNG の角印を使用する場合は <code>ext-gd</code> または <code>ext-imagick</code>。<br>
        ご購入前に<a href="/">デモ</a>と<a href="/sample-invoice.pdf">出力見本</a>で仕様をご確認ください。</td></tr>
    </tbody>
  </table>
  </div>
</section>

<section>
  <h2>免責</h2>
  <p style="font-size:14px">
    本ソフトウェアは帳票の作成および消費税額の計算を補助するものであり、税務上の判断または助言を
    提供するものではありません。個別の取引における消費税の取扱い、および交付する書類が法令の要件を
    満たすか否かの最終的な判断は、税理士等の専門家にご確認ください。
  </p>
</section>
<?php page_foot(); ?>
