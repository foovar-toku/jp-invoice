<?php

declare(strict_types=1);

namespace Foovar\JpInvoice;

/**
 * 交付を受ける事業者（記載事項6）。
 *
 * 適格簡易請求書では記載不要のため、Invoice では null を許容する。
 */
final class Recipient
{
    public function __construct(
        public readonly string $name,
    ) {
    }
}
