<?php

declare(strict_types=1);

namespace Foovar\JpInvoice\Enum;

/**
 * 明細の単価をどちらで保持しているか。
 *
 * 計算経路が分かれるため（要件定義書 §5.1 / §5.2）、両方を同じテストで検証すること。
 * 税込モードでは taxableBase を丸めるのではなく、税込総額から税額を減算して求める。
 */
enum PriceMode: string
{
    /** 単価は税抜（本体価格） */
    case TAX_EXCLUDED = 'tax_excluded';

    /** 単価は税込 */
    case TAX_INCLUDED = 'tax_included';
}
