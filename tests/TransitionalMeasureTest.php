<?php

declare(strict_types=1);

namespace Foovar\JpInvoice\Tests;

use DateTimeImmutable;
use DateTimeZone;
use Foovar\JpInvoice\Enum\RoundingMode;
use Foovar\JpInvoice\Exception\UnsupportedDateException;
use Foovar\JpInvoice\TransitionalMeasure;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * 要件定義書 §8.2（T-10 〜 T-14）経過措置。
 *
 * 期待値は国税庁「令和８年度税制改正特集」で確認済み（要件定義書 付録B-1）。
 */
final class TransitionalMeasureTest extends TestCase
{
    /**
     * T-10 〜 T-14 境界日の判定。
     */
    #[DataProvider('boundaries')]
    public function testBoundaries(string $date, int $expectedPercent): void
    {
        self::assertSame(
            $expectedPercent,
            TransitionalMeasure::rateFor(new DateTimeImmutable($date))->percent,
            $date,
        );
    }

    /**
     * @return iterable<string, array{string, int}>
     */
    public static function boundaries(): iterable
    {
        yield 'T-10 制度開始日' => ['2023-10-01', 80];
        yield 'T-10 改正前最終日' => ['2026-09-30', 80];
        yield 'T-11 改正日' => ['2026-10-01', 70];
        yield 'T-11 70%期間の最終日' => ['2028-09-30', 70];
        yield 'T-12 50%開始' => ['2028-10-01', 50];
        yield 'T-12 50%最終日' => ['2030-09-30', 50];
        yield '30%開始' => ['2030-10-01', 30];
        yield '30%最終日' => ['2031-09-30', 30];
        yield 'T-14 終了後' => ['2031-10-01', 0];
        yield 'T-14 さらに先' => ['2040-01-01', 0];
    }

    /** T-13 制度開始前は例外。黙って 0% を返さない */
    public function testT13BeforeScheduleThrows(): void
    {
        $this->expectException(UnsupportedDateException::class);
        TransitionalMeasure::rateFor(new DateTimeImmutable('2023-09-30'));
    }

    /** テーブルは SCHEDULE_VERSION で「いつ時点の理解か」を示す（要件定義書 §3.5.2） */
    public function testScheduleVersionIsExposed(): void
    {
        self::assertSame('2026-08-17', TransitionalMeasure::SCHEDULE_VERSION);
        self::assertSame('2023-10-01', TransitionalMeasure::scheduleStartsOn());
    }

    /** テーブルに隙間や重なりが無いこと（改正で書き換えたときの事故防止） */
    public function testScheduleIsContiguous(): void
    {
        $periods = TransitionalMeasure::schedule();
        $count = count($periods);

        for ($i = 0; $i < $count - 1; $i++) {
            $to = $periods[$i]['to'];
            self::assertNotNull($to, '最終期間以外に to が null の期間がある');

            $expectedNextFrom = (new DateTimeImmutable($to))->modify('+1 day')->format('Y-m-d');
            self::assertSame(
                $expectedNextFrom,
                $periods[$i + 1]['from'],
                sprintf('期間 %d と %d の間に隙間または重なりがある', $i, $i + 1),
            );
        }

        self::assertNull($periods[$count - 1]['to'], '最終期間は上限なしであること');
    }

    /** 控除できる金額。80% なら 1,000 円の税額に対し 800 円 */
    public function testApplyTo(): void
    {
        $rate = TransitionalMeasure::rateFor(new DateTimeImmutable('2026-08-17'));

        self::assertSame(80, $rate->percent);
        self::assertSame('0.8000000000', $rate->multiplier());
        self::assertSame(800, $rate->applyTo(1000));
        self::assertFalse($rate->isZero());

        // 端数は事業者が選んだ方法で処理する
        self::assertSame(80, $rate->applyTo(101));                        // 80.8 切捨て
        self::assertSame(81, $rate->applyTo(101, RoundingMode::CEIL));    // 切上げ
        self::assertSame(81, $rate->applyTo(101, RoundingMode::HALF_UP)); // 四捨五入
    }

    /** 控除不可期間 */
    public function testZeroRate(): void
    {
        $rate = TransitionalMeasure::rateFor(new DateTimeImmutable('2031-10-01'));

        self::assertTrue($rate->isZero());
        self::assertSame(0, $rate->applyTo(999999));
    }

    /**
     * 日付のみで判定し、時刻・タイムゾーンに依存しないこと（要件定義書 §7）。
     */
    public function testTimezoneIndependence(): void
    {
        $tokyo = new DateTimeImmutable('2026-09-30 23:59:59', new DateTimeZone('Asia/Tokyo'));
        $utc = new DateTimeImmutable('2026-09-30 00:00:00', new DateTimeZone('UTC'));

        self::assertSame(80, TransitionalMeasure::rateFor($tokyo)->percent);
        self::assertSame(80, TransitionalMeasure::rateFor($utc)->percent);
    }
}
