<?php

declare(strict_types=1);

namespace Foovar\JpInvoice\Exception;

/**
 * 経過措置のテーブルが定義していない日付を渡されたとき。
 *
 * 黙って 0% を返すと「控除できない」と誤認させるため、必ず例外にする（要件定義書 §3.5.2）。
 */
final class UnsupportedDateException extends JpInvoiceException
{
    public static function outOfSchedule(string $date, string $scheduleVersion): self
    {
        return new self(sprintf(
            '経過措置のテーブル（SCHEDULE_VERSION: %s）が対象としていない日付です: %s',
            $scheduleVersion,
            $date,
        ));
    }
}
