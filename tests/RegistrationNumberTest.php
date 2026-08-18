<?php

declare(strict_types=1);

namespace Foovar\JpInvoice\Tests;

use Foovar\JpInvoice\Exception\InvalidRegistrationNumberException;
use Foovar\JpInvoice\Validation\RegistrationNumber;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * 要件定義書 §8.3（T-20 〜 T-26）と §8.6（T-56 / T-57）。
 */
final class RegistrationNumberTest extends TestCase
{
    /** T-20 正常 */
    public function testT20Valid(): void
    {
        $number = RegistrationNumber::fromString('T1234567890123');

        self::assertSame('T1234567890123', $number->value);
        self::assertSame('1234567890123', $number->digits());
    }

    /** T-21 小文字は正規化して受理 */
    public function testT21Lowercase(): void
    {
        self::assertSame('T1234567890123', RegistrationNumber::fromString('t1234567890123')->value);
    }

    /** T-22 ハイフン入りは正規化して受理 */
    public function testT22Hyphenated(): void
    {
        self::assertSame('T1234567890123', RegistrationNumber::fromString('T1234-5678-90123')->value);
    }

    /** T-23 全角は正規化して受理 */
    public function testT23FullWidth(): void
    {
        self::assertSame('T1234567890123', RegistrationNumber::fromString('Ｔ１２３４５６７８９０１２３')->value);
    }

    /** 空白（半角・全角）も正規化する */
    public function testWhitespaceIsNormalized(): void
    {
        self::assertSame('T1234567890123', RegistrationNumber::fromString(' T 1234 5678 90123 ')->value);
        self::assertSame('T1234567890123', RegistrationNumber::fromString('Ｔ１２３４５６７８９０１２３　')->value);
    }

    /**
     * T-24 桁数不足 / T-25 接頭辞なし ほか、形式不正は例外。
     */
    #[DataProvider('malformedNumbers')]
    public function testT24T25Malformed(string $value): void
    {
        $this->expectException(InvalidRegistrationNumberException::class);
        RegistrationNumber::fromString($value);
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function malformedNumbers(): iterable
    {
        yield 'T-24 桁数不足' => ['T123456789012'];
        yield 'T-25 接頭辞なし' => ['1234567890123'];
        yield '桁数超過' => ['T12345678901234'];
        yield '空文字' => [''];
        yield '数字以外を含む' => ['T123456789012X'];
        yield '別の接頭辞' => ['A1234567890123'];
        yield 'T のみ' => ['T'];
    }

    /**
     * T-26 チェックディジット不一致でも**受理**する。
     *
     * 個人事業者の登録番号は法人番号ではなく、この規則に従わないため。
     */
    public function testT26CheckDigitMismatchIsAccepted(): void
    {
        $number = RegistrationNumber::fromString('T1234567890123');

        self::assertFalse($number->matchesCorporateNumberRule());
    }

    /**
     * T-56 国税庁 法人番号公表サイトの検算例。
     *
     * 基礎番号 700110005901 → 偶数桁の和 13、奇数桁の和 11
     *   13 × 2 + 11 = 37、37 mod 9 = 1、9 − 1 = 8 → 法人番号 8700110005901
     */
    public function testT56NtaCheckDigitExample(): void
    {
        self::assertSame(8, RegistrationNumber::calculateCheckDigit('700110005901'));

        $number = RegistrationNumber::fromString('T8700110005901');
        self::assertTrue($number->matchesCorporateNumberRule());
    }

    /** T-57 国税庁Q&A のサンプル番号は形式 OK・受理だが法人番号規則は満たさない */
    public function testT57NtaSampleNumberIsFormatValidButNotCorporate(): void
    {
        self::assertTrue(RegistrationNumber::isValid('T1234567890123'));
        self::assertFalse(RegistrationNumber::fromString('T1234567890123')->matchesCorporateNumberRule());
    }

    /**
     * チェックディジットは 0〜9 に収まる。剰余が 0 のとき 9 になる境界を固定する。
     */
    public function testCheckDigitBoundary(): void
    {
        // 全桁 0 → 総和 0 → 9 - 0 = 9
        self::assertSame(9, RegistrationNumber::calculateCheckDigit('000000000000'));

        for ($i = 0; $i < 1000; $i++) {
            $base = str_pad((string) ($i * 7919 % 1000000000000), 12, '0', STR_PAD_LEFT);
            $checkDigit = RegistrationNumber::calculateCheckDigit($base);
            self::assertGreaterThanOrEqual(0, $checkDigit);
            self::assertLessThanOrEqual(9, $checkDigit);
        }
    }
}
