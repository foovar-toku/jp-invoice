<?php

declare(strict_types=1);

namespace Foovar\JpInvoice\Enum;

/**
 * 適用税率。
 *
 * 任意の税率値は受け付けない（要件定義書 §3.3）。旧税率（5%、旧8%）は対象外。
 * 将来の税率追加は case の追加とここのメソッド分岐で吸収する。
 *
 * 国税・地方消費税の内訳（10% = 7.8% + 2.2%、8% = 6.24% + 1.76%）は申告用途のため v2 で扱う。
 */
enum TaxRate: string
{
    /** 標準税率 10% */
    case STANDARD_10 = 'standard_10';

    /** 軽減税率 8%（飲食料品、定期購読の新聞等） */
    case REDUCED_8 = 'reduced_8';

    /** 0%（免税取引・不課税・非課税。どれに当たるかの区分は利用側の責任） */
    case EXEMPT_0 = 'exempt_0';

    /**
     * 税率のパーセント表記。表示と並び順に使う。
     */
    public function percent(): int
    {
        return match ($this) {
            self::STANDARD_10 => 10,
            self::REDUCED_8 => 8,
            self::EXEMPT_0 => 0,
        };
    }

    /**
     * 税抜金額に乗じる係数。numeric-string で返す（float を経由しない）。
     *
     * @return numeric-string
     */
    public function multiplier(): string
    {
        return match ($this) {
            self::STANDARD_10 => '0.10',
            self::REDUCED_8 => '0.08',
            self::EXEMPT_0 => '0',
        };
    }

    /**
     * 税込金額から税額を割り戻す分数の分子。10% なら 10（= 10/110）。
     *
     * @return numeric-string
     */
    public function taxIncludedNumerator(): string
    {
        return match ($this) {
            self::STANDARD_10 => '10',
            self::REDUCED_8 => '8',
            self::EXEMPT_0 => '0',
        };
    }

    /**
     * 税込金額から税額を割り戻す分数の分母。10% なら 110（= 10/110）。
     *
     * @return numeric-string
     */
    public function taxIncludedDenominator(): string
    {
        return match ($this) {
            self::STANDARD_10 => '110',
            self::REDUCED_8 => '108',
            self::EXEMPT_0 => '100',
        };
    }

    /**
     * 軽減税率対象か。
     *
     * 適格請求書の記載事項3「軽減税率対象である旨」に対応する（消法57の4①三）。
     */
    public function isReducedRate(): bool
    {
        return $this === self::REDUCED_8;
    }

    /**
     * 表示用のラベル。「10%対象」「8%対象」の形。
     */
    public function label(): string
    {
        return $this->percent() . '%対象';
    }
}
