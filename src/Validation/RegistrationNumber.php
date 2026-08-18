<?php

declare(strict_types=1);

namespace Foovar\JpInvoice\Validation;

use Foovar\JpInvoice\Exception\InvalidRegistrationNumberException;

/**
 * 適格請求書発行事業者の登録番号（記載事項1）。
 *
 * 形式は「T」+ 13桁の数字。法人の場合は 13桁が法人番号と一致する。
 *
 * === 重要 ===
 * **個人事業者の登録番号は法人番号ではなく、チェックディジット規則に従わない。**
 * したがって形式検証（エラー）とチェックディジット検証（警告）を分離してある:
 *
 *   RegistrationNumber::fromString('T…')      形式不正なら例外
 *   $number->matchesCorporateNumberRule()      法人番号として整合するか（false でも有効な登録番号）
 *
 * 実在性の確認（国税庁公表システムへの照会）はスコープ外（v2 でオプションモジュール化）。
 */
final class RegistrationNumber
{
    /** 全角英数字・各種ハイフン・空白の正規化表。mbstring に依存しないよう UTF-8 の文字列置換で行う */
    private const NORMALIZE_MAP = [
        'Ｔ' => 'T',
        'ｔ' => 'T',
        't' => 'T',
        '０' => '0',
        '１' => '1',
        '２' => '2',
        '３' => '3',
        '４' => '4',
        '５' => '5',
        '６' => '6',
        '７' => '7',
        '８' => '8',
        '９' => '9',
        '－' => '',
        'ー' => '',
        '‐' => '',
        '‑' => '',
        '–' => '',
        '—' => '',
        '−' => '',
        'ｰ' => '',
        '　' => '',
        '-' => '',
        ' ' => '',
        "\t" => '',
    ];

    /** T + 13桁の正規化済み文字列 */
    public readonly string $value;

    private function __construct(string $value)
    {
        $this->value = $value;
    }

    /**
     * 文字列から生成する。小文字・ハイフン・全角数字・空白は正規化して受け入れる。
     *
     * @throws InvalidRegistrationNumberException 形式が「T」+ 13桁でないとき
     */
    public static function fromString(string $value): self
    {
        $normalized = self::normalize($value);

        if (preg_match('/\AT[0-9]{13}\z/', $normalized) !== 1) {
            throw InvalidRegistrationNumberException::malformed($value);
        }

        return new self($normalized);
    }

    /**
     * 形式として妥当かどうかだけを返す（例外を投げない版）。
     */
    public static function isValid(string $value): bool
    {
        return preg_match('/\AT[0-9]{13}\z/', self::normalize($value)) === 1;
    }

    /**
     * 13桁の数字部分。
     */
    public function digits(): string
    {
        return substr($this->value, 1);
    }

    /**
     * 法人番号のチェックディジット規則に整合するか。
     *
     * 国税庁 法人番号公表サイト「チェックデジットの計算」:
     *   下12桁を P1..P12（最下位が P1）、Qn = 1（n が奇数）／2（n が偶数）として
     *   チェックディジット = 9 −（Σ Pn × Qn） mod 9
     *
     * 検算例: 基礎番号 700110005901 → 偶数桁の和 13、奇数桁の和 11、13×2+11=37、37 mod 9 = 1、
     *         9−1 = 8 → 法人番号 8700110005901
     *
     * **false でも登録番号として無効ではない。**個人事業者の登録番号は法人番号ではないため。
     */
    public function matchesCorporateNumberRule(): bool
    {
        $digits = $this->digits();
        $checkDigit = (int) $digits[0];

        return $checkDigit === self::calculateCheckDigit(substr($digits, 1));
    }

    /**
     * 12桁の基礎番号からチェックディジットを算出する。
     *
     * @param string $baseNumber 12桁の数字
     */
    public static function calculateCheckDigit(string $baseNumber): int
    {
        $sum = 0;
        $length = strlen($baseNumber);

        for ($n = 1; $n <= $length; $n++) {
            $digit = (int) $baseNumber[$length - $n]; // 最下位から n 桁目 = P_n
            $weight = $n % 2 === 1 ? 1 : 2;           // Q_n
            $sum += $digit * $weight;
        }

        return 9 - ($sum % 9);
    }

    private static function normalize(string $value): string
    {
        return strtr(trim($value), self::NORMALIZE_MAP);
    }

    public function __toString(): string
    {
        return $this->value;
    }
}
