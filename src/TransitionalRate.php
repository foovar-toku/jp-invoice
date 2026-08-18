<?php

declare(strict_types=1);

namespace Foovar\JpInvoice;

use Foovar\JpInvoice\Enum\RoundingMode;
use Foovar\JpInvoice\Support\Decimal;

/**
 * 経過措置の控除割合。TransitionalMeasure::rateFor() が返す値オブジェクト。
 *
 * 要件定義書 §3.5.2 では戻り値を Rate と書いているが、TaxRate（適用税率）と紛らわしいため
 * TransitionalRate という名前にした。意味は同じ。
 */
final class TransitionalRate
{
    /**
     * @param int         $percent  控除割合（80, 70, 50, 30, 0）
     * @param string      $from     この割合が適用される期間の開始日（閉区間）
     * @param string|null $to       終了日（閉区間）。null は上限なし
     */
    public function __construct(
        public readonly int $percent,
        public readonly string $from,
        public readonly ?string $to,
    ) {
    }

    /**
     * 乗じる係数。80% なら '0.80'。
     *
     * @return numeric-string
     */
    public function multiplier(): string
    {
        /** @var numeric-string $result */
        $result = bcdiv((string) $this->percent, '100', Decimal::SCALE);

        return $result;
    }

    /**
     * 控除できる金額を求める。
     *
     * 端数処理の方法は請求書の税額計算と同じく事業者が選択する。既定は切捨て。
     *
     * **注意**: 経過措置には割合とは別に控除限度額（年間・相手先ごと税込1億円）がある。
     * その判定は本ライブラリの責任範囲外（TransitionalMeasure のクラスコメント参照）。
     */
    public function applyTo(int $taxAmount, RoundingMode $rounding = RoundingMode::FLOOR): int
    {
        return Decimal::toInt(Decimal::mul((string) $taxAmount, $this->multiplier()), $rounding);
    }

    /**
     * 控除できない（経過措置が終了している）か。
     */
    public function isZero(): bool
    {
        return $this->percent === 0;
    }
}
