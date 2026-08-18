<?php

declare(strict_types=1);

/**
 * デモの表示設定。
 *
 * 公開に合わせてここだけ書き換える。コード本体は触らない。
 */
return [
    // 商用パッケージの問い合わせ先。null の間は問い合わせセクションを表示しない
    // （宛先が決まっていないのに「お問い合わせください」と書かないため）
    'contact_email' => 'info@pijtokyo.jp',

    // GitHub リポジトリ URL。null の間はリンクを出さない
    'github_url' => 'https://github.com/foovar-toku/jp-invoice',

    // Packagist で公開済みか。true になったら composer require の案内を出す
    'published_on_packagist' => false,
];
