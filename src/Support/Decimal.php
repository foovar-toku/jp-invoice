<?php

declare(strict_types=1);

namespace Foovar\JpInvoice\Support;

use Foovar\JpInvoice\Enum\RoundingMode;
use Foovar\JpInvoice\Exception\InvalidAmountException;

/**
 * BCMath による十進演算。
 *
 * 本ライブラリは float を一切使わない（要件定義書 付録A-2）。
 * (float) キャスト、round()、number_format() は使用禁止。金額は numeric-string と int だけで扱う。
 *
 * @internal 公開 API ではない。後方互換の保証対象外。
 */
final class Decimal
{
    /** 内部演算のスケール（小数桁数） */
    public const SCALE = 10;

    private function __construct()
    {
    }

    /**
     * numeric-string であることを検証して正規化する。負値は拒否する。
     *
     * ここを通った値だけが以降の bcmath 演算に入る。PHPStan は bcmath 関数に numeric-string を
     * 要求するので、型レベルで「検証していない文字列で計算する」経路を塞げる。
     *
     * @return numeric-string
     */
    public static function assertNonNegative(string $value, string $field): string
    {
        if (!is_numeric($value)) {
            throw InvalidAmountException::notNumeric($field, $value);
        }

        // 指数表記（1e3 等）は bcmath が解釈できないため弾く
        if (preg_match('/^[+-]?\d*\.?\d+$/', $value) !== 1) {
            throw InvalidAmountException::notNumeric($field, $value);
        }

        if (bccomp($value, '0', self::SCALE) < 0) {
            throw InvalidAmountException::negative($field, $value);
        }

        return $value;
    }

    /**
     * @param numeric-string $a
     * @param numeric-string $b
     * @return numeric-string
     */
    public static function add(string $a, string $b): string
    {
        return bcadd($a, $b, self::SCALE);
    }

    /**
     * @param numeric-string $a
     * @param numeric-string $b
     * @return numeric-string
     */
    public static function sub(string $a, string $b): string
    {
        return bcsub($a, $b, self::SCALE);
    }

    /**
     * @param numeric-string $a
     * @param numeric-string $b
     * @return numeric-string
     */
    public static function mul(string $a, string $b): string
    {
        return bcmul($a, $b, self::SCALE);
    }

    /**
     * @param numeric-string $a
     * @param numeric-string $b
     * @return numeric-string
     */
    public static function div(string $a, string $b): string
    {
        return bcdiv($a, $b, self::SCALE);
    }

    /**
     * 円未満を処理して整数（円）にする。
     *
     * 要件定義書 §5.3。負値は v1 では発生しない前提だが、防御的に例外を投げる。
     *
     * @param numeric-string $value
     */
    public static function toInt(string $value, RoundingMode $mode): int
    {
        if (bccomp($value, '0', self::SCALE) < 0) {
            throw InvalidAmountException::negative('端数処理の対象', $value);
        }

        $truncated = bcadd($value, '0', 0);

        $result = match ($mode) {
            RoundingMode::FLOOR => $truncated,
            RoundingMode::CEIL => self::hasFraction($value, $truncated)
                ? bcadd($truncated, '1', 0)
                : $truncated,
            RoundingMode::HALF_UP => bcadd(bcadd($value, '0.5', self::SCALE), '0', 0),
        };

        return (int) $result;
    }

    /**
     * @param numeric-string $value
     * @param numeric-string $truncated
     */
    private static function hasFraction(string $value, string $truncated): bool
    {
        return bccomp($value, $truncated, self::SCALE) !== 0;
    }
}
