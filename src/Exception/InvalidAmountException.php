<?php

declare(strict_types=1);

namespace Foovar\JpInvoice\Exception;

/**
 * 金額・数量が不正なとき。
 *
 * numeric-string でない、負値である、など。値引は CreditNote で表現するため、
 * 明細の負値は受け付けない（要件定義書 §4.2）。
 */
final class InvalidAmountException extends JpInvoiceException
{
    public static function notNumeric(string $field, string $value): self
    {
        return new self(sprintf('%sは数値文字列である必要があります: "%s"', $field, $value));
    }

    public static function negative(string $field, string $value): self
    {
        return new self(sprintf('%sに負値は指定できません（値引は CreditNote で表現する）: "%s"', $field, $value));
    }
}
