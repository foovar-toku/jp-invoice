<?php

declare(strict_types=1);

/**
 * デモ用の最小 PSR-4 オートローダ。
 *
 * 本ライブラリは依存パッケージがゼロなので、デモを動かすのに composer install は要らない。
 * サーバ側に vendor/ を置かずに済むよう、ここで src/ を直接読む。
 */
spl_autoload_register(static function (string $class): void {
    $prefix = 'Foovar\\JpInvoice\\';
    if (!str_starts_with($class, $prefix)) {
        return;
    }

    $relative = str_replace('\\', '/', substr($class, strlen($prefix)));
    $file = dirname(__DIR__) . '/src/' . $relative . '.php';

    if (is_file($file)) {
        require $file;
    }
});
