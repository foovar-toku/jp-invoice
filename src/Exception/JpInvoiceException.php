<?php

declare(strict_types=1);

namespace Foovar\JpInvoice\Exception;

use RuntimeException;

/**
 * 本ライブラリが投げる例外の基底。
 *
 * 呼び出し側は catch (JpInvoiceException $e) で一括捕捉できる（要件定義書 §7）。
 */
abstract class JpInvoiceException extends RuntimeException
{
}
