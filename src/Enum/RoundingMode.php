<?php

declare(strict_types=1);

namespace Foovar\JpInvoice\Enum;

/**
 * 端数処理の方法。
 *
 * 国税庁「インボイス制度に関するQ&A」問57:
 *   「切上げ、切捨て、四捨五入などの端数処理の方法については、任意の方法とすることができます。」
 *
 * ただし選択した方法は継続適用する。そのため本ライブラリでは Invoice ではなく
 * Issuer（発行事業者）が保持する（要件定義書 §3.4）。
 */
enum RoundingMode: string
{
    /** 切捨て。既定値だが、自社の運用に合わせて明示指定すること */
    case FLOOR = 'floor';

    /** 切上げ */
    case CEIL = 'ceil';

    /** 四捨五入（0.5 は切り上げ） */
    case HALF_UP = 'half_up';
}
