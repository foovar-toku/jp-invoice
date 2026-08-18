<?php

declare(strict_types=1);

namespace Foovar\JpInvoice;

use DateTimeImmutable;
use Foovar\JpInvoice\Exception\UnsupportedDateException;

/**
 * 免税事業者等からの課税仕入れに係る経過措置（激変緩和措置）。
 *
 * 制度改正が繰り返される前提で、期間と割合の対応を**データテーブルとして外出し**してある。
 * 法改正時に SCHEDULE と SCHEDULE_VERSION を書き換えるだけで追随できること。
 * ロジック側に割合を直接書かないこと（要件定義書 §3.5.2、付録A-3）。
 *
 * === 控除限度額（1億円）について ===
 * 令和8年度税制改正で、割合とは別に金額の上限が入った。国税庁「令和８年度税制改正特集」:
 *   「一のインボイス発行事業者以外の者からの課税仕入れの合計額（税込み）が、その年又は
 *     事業年度で1億円（改正前：10億円）を超える場合には、その超えた部分の課税仕入れに
 *     ついて適用できません。」（令和8年10月1日以後に開始する課税期間から）
 *
 * これは**年間・相手先ごとの累計**で決まる。1枚の請求書しか見ない本ライブラリでは判定できないため、
 * rateFor() は割合しか返さない。**限度額の判定は利用側の責任**である。
 *
 * @todo 1億円の判定単位の細部（「その年又は事業年度」の区切り、相手先の同一性判定）を
 *       国税庁Q&A で要確認。税込みで判定する点は確認済み
 * @todo 改正後スケジュールの根拠条文（附則の条番号）を確認する。特集ページには条番号の記載がない
 */
final class TransitionalMeasure
{
    /**
     * このテーブルが「いつ時点の理解に基づくか」。
     *
     * 利用者はこの値を見て、自分が把握している制度改正まで反映されているかを判断する。
     */
    public const SCHEDULE_VERSION = '2026-08-17';

    /**
     * 出典: 国税庁「令和８年度税制改正特集」
     * https://www.nta.go.jp/taxes/shiraberu/zeimokubetsu/shohi/keigenzeiritsu/invoice-review/index.htm
     *
     * 境界日は閉区間（from <= 課税仕入れを行った日 <= to）。to が null は上限なし。
     *
     * @var list<array{from: string, to: string|null, percent: int}>
     */
    public const SCHEDULE = [
        ['from' => '2023-10-01', 'to' => '2026-09-30', 'percent' => 80], // 令和5年10月1日から3年間
        ['from' => '2026-10-01', 'to' => '2028-09-30', 'percent' => 70], // 令和8年10月1日から2年間
        ['from' => '2028-10-01', 'to' => '2030-09-30', 'percent' => 50], // 令和10年10月1日から2年間
        ['from' => '2030-10-01', 'to' => '2031-09-30', 'percent' => 30], // 令和12年10月1日から1年間
        ['from' => '2031-10-01', 'to' => null, 'percent' => 0],          // 令和13年10月1日以降は控除不可
    ];

    private function __construct()
    {
    }

    /**
     * 課税仕入れを行った日に対応する控除割合を返す。
     *
     * 判定は日付のみで行い、時刻・タイムゾーンには依存しない（要件定義書 §7）。
     * 期をまたぐ取引について「いつの課税仕入れか」を決めるのは利用側の責任。
     *
     * @throws UnsupportedDateException テーブルの範囲外（制度開始前など）のとき
     */
    public static function rateFor(DateTimeImmutable $transactionDate): TransitionalRate
    {
        $date = $transactionDate->format('Y-m-d');

        foreach (self::SCHEDULE as $period) {
            if ($date >= $period['from'] && ($period['to'] === null || $date <= $period['to'])) {
                return new TransitionalRate($period['percent'], $period['from'], $period['to']);
            }
        }

        // 黙って 0% を返さない。0% は「控除不可」という制度上の意味を持つため区別する
        throw UnsupportedDateException::outOfSchedule($date, self::SCHEDULE_VERSION);
    }

    /**
     * 期間テーブルそのもの。改正差分の確認や、テーブルの健全性検査に使う。
     *
     * @return list<array{from: string, to: string|null, percent: int}>
     */
    public static function schedule(): array
    {
        return self::SCHEDULE;
    }

    /**
     * テーブルが対象としている最初の日（制度開始日）。
     */
    public static function scheduleStartsOn(): string
    {
        return self::SCHEDULE[0]['from'];
    }
}
