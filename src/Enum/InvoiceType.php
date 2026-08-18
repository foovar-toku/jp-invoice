<?php

declare(strict_types=1);

namespace Foovar\JpInvoice\Enum;

/**
 * 交付する書類の種別。
 *
 * 適格簡易請求書は、小売業・飲食店業・写真業・旅行業・タクシー業・駐車場業など、
 * 不特定かつ多数の者に対する取引を行う事業者が交付できる（消法57の4②）。
 * 交付を受ける事業者の氏名又は名称の記載が不要になる。
 */
enum InvoiceType: string
{
    /** 適格請求書 */
    case STANDARD = 'standard';

    /** 適格簡易請求書 */
    case SIMPLIFIED = 'simplified';
}
