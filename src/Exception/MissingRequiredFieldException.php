<?php

declare(strict_types=1);

namespace Foovar\JpInvoice\Exception;

/**
 * 法定の記載事項が欠けているとき（消法57の4）。
 */
final class MissingRequiredFieldException extends JpInvoiceException
{
    public static function of(string $field): self
    {
        return new self(sprintf('記載事項が不足しています: %s', $field));
    }
}
