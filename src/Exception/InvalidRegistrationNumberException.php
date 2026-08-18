<?php

declare(strict_types=1);

namespace Foovar\JpInvoice\Exception;

/**
 * 登録番号の形式が不正なとき。
 *
 * 形式（T + 13桁）はエラー扱い。チェックディジット不一致は**警告レベル**とし、ここでは投げない。
 * 個人事業者の登録番号は法人番号ではなく、チェックディジット規則に従わないため
 * （要件定義書 §4.5）。
 */
final class InvalidRegistrationNumberException extends JpInvoiceException
{
    public static function malformed(string $value): self
    {
        return new self(sprintf(
            '登録番号は「T」+ 13桁の数字である必要があります: "%s"',
            $value,
        ));
    }
}
