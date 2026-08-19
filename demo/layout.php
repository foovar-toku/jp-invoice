<?php

declare(strict_types=1);

/**
 * デモサイト共通のレイアウト。
 *
 * 各ページは page_head() / page_foot() を呼ぶだけ。meta・OGP・構造化データ・ナビを一箇所で持つ。
 */

const SITE_ORIGIN = 'https://invoice.pij.systems';
const SITE_NAME = 'jp-invoice';

if (!function_exists('h')) {
    function h(string $value): string
    {
        return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }
}

/**
 * @param string $path      サイト内の絶対パス（canonical と OGP に使う）
 * @param bool   $isArticle 解説記事なら true（構造化データの型が変わる）
 */
function page_head(string $title, string $description, string $path, bool $isArticle = false, bool $noindex = false): void
{
    $canonical = SITE_ORIGIN . $path;
    $ogImage = SITE_ORIGIN . '/ogp.png';

    $jsonLd = $isArticle
        ? [
            '@context' => 'https://schema.org',
            '@type' => 'TechArticle',
            'headline' => $title,
            'description' => $description,
            'url' => $canonical,
            'image' => $ogImage,
            'inLanguage' => 'ja',
            'author' => ['@type' => 'Organization', 'name' => 'ピー・アイ・ジェイ株式会社'],
        ]
        : [
            '@context' => 'https://schema.org',
            '@type' => 'SoftwareApplication',
            'name' => 'jp-invoice',
            'description' => $description,
            'url' => $canonical,
            'image' => $ogImage,
            'applicationCategory' => 'DeveloperApplication',
            'operatingSystem' => 'PHP 8.2+',
            'inLanguage' => 'ja',
            'license' => 'https://opensource.org/licenses/MIT',
            'offers' => ['@type' => 'Offer', 'price' => '0', 'priceCurrency' => 'JPY'],
        ];

    echo '<!DOCTYPE html>', "\n";
    ?>
<html lang="ja">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= h($title) ?></title>
<meta name="description" content="<?= h($description) ?>">
<?php if ($noindex): ?><meta name="robots" content="noindex, nofollow">
<?php endif; ?>
<link rel="canonical" href="<?= h($canonical) ?>">
<meta property="og:type" content="<?= $isArticle ? 'article' : 'website' ?>">
<meta property="og:site_name" content="<?= h(SITE_NAME) ?>">
<meta property="og:title" content="<?= h($title) ?>">
<meta property="og:description" content="<?= h($description) ?>">
<meta property="og:url" content="<?= h($canonical) ?>">
<meta property="og:image" content="<?= h($ogImage) ?>">
<meta property="og:locale" content="ja_JP">
<meta name="twitter:card" content="summary_large_image">
<meta name="twitter:title" content="<?= h($title) ?>">
<meta name="twitter:description" content="<?= h($description) ?>">
<meta name="twitter:image" content="<?= h($ogImage) ?>">
<link rel="icon" href="/favicon.ico" sizes="any">
<link rel="apple-touch-icon" href="/apple-touch-icon.png">
<link rel="stylesheet" href="/style.css">
<script type="application/ld+json"><?= json_encode($jsonLd, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) ?></script>
</head>
<body>
<div class="wrap">
<nav class="site">
  <a href="/">デモで試す</a>
  <a href="/guide/">解説</a>
  <a href="/tools/registration-number/">登録番号チェッカー</a>
  <a href="https://github.com/foovar-toku/jp-invoice">GitHub</a>
  <a href="https://packagist.org/packages/foovar/jp-invoice">Packagist</a>
</nav>
    <?php
}

function page_foot(): void
{
    ?>
<footer style="margin-top:40px">
  <p style="font-size:13px;color:var(--muted)">
    本サイトは消費税額の計算を体験するための資料です。個別の取引における税務上の取扱いについては、
    税理士等の専門家にご確認ください。制度の内容は国税庁の公表資料に基づいていますが、
    最新の情報は必ず一次情報でご確認ください。<br>
    運営: ピー・アイ・ジェイ株式会社 ／ お問い合わせ: info@pijtokyo.jp ／
    <a href="/legal/">特定商取引法に基づく表記</a>
  </p>
</footer>
</div>
</body>
</html>
    <?php
}
