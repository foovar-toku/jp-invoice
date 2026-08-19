<?php

declare(strict_types=1);

/**
 * デモ／販売ページの設定。
 *
 * 公開に合わせてここだけ書き換える。コード本体は触らない。
 */
return [
    // 商用パッケージの問い合わせ先
    'contact_email' => 'info@pijtokyo.jp',

    // GitHub リポジトリ URL
    'github_url' => 'https://github.com/foovar-toku/jp-invoice',

    // Packagist で公開済みか
    'published_on_packagist' => true,

    // 販売事業者の情報（特定商取引法に基づく表記に使用）
    'company' => [
        'name' => 'ピー・アイ・ジェイ株式会社',
        'representative' => '中川 宏',
        'address' => '〒164-0012 東京都中野区本町4-21-8-2D',
        'tel' => '03-6454-1154',
        'email' => 'info@pijtokyo.jp',
        'registration_number' => 'T4010901043365',
    ],

    /*
     * Stripe Payment Link の URL。
     *
     * null の間は購入ボタンを出さず、問い合わせ導線のままにする
     *（買えないボタンを置かないため）。ダッシュボードで作成したら URL を入れる。
     *
     * price は税込の表示価格（円）。Stripe 側の金額と必ず一致させること。
     */
    'payment_links' => [
        'license' => [
            'label' => '帳票 PDF パッケージ（買い切り）',
            'price' => null,
            'url' => null,
            'note' => '1社1プロダクトへの組み込み。台数制限なし。初年度のアップデート受領権を含む',
        ],
        'renewal' => [
            'label' => 'アップデート受領権の更新（2年目以降・年額）',
            'price' => null,
            'url' => null,
            'note' => '制度改正への追随とメールサポート。更新しなくても購入時点のバージョンは永続利用できる',
        ],
        'onboarding' => [
            'label' => '導入支援（組み込み実装）',
            'price' => null,
            'url' => null,
            'note' => '既存システムへの組み込みを実装します。内容により個別見積',
        ],
    ],
];
