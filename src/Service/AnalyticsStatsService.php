<?php

namespace Wexample\SymfonyAnalytics\Service;

use DateTimeImmutable;
use Wexample\SymfonyAnalytics\Class\AnalyticsPeriod;
use Wexample\SymfonyAnalytics\Entity\AnalyticsBreakdown;
use Wexample\SymfonyAnalytics\Entity\AnalyticsMonthly;
use Wexample\SymfonyAnalytics\Enum\AnalyticsAudience;
use Wexample\SymfonyAnalytics\Enum\AnalyticsDimension;
use Wexample\SymfonyAnalytics\Interface\AnalyticsCountsInterface;
use Wexample\SymfonyAnalytics\Repository\AnalyticsMonthlyRepository;
use Wexample\SymfonyAnalytics\Repository\AnalyticsRobotDayRepository;
use Wexample\SymfonyAnalytics\Repository\PageViewRepository;

/**
 * The audience of a period: its totals, its course, its breakdowns, for the
 * audiences asked — the people, the robots, or both. Each audience is read
 * day by day where it is still kept, and from its months summed up before
 * that, its own retention apart: a part of the period reaching back there is
 * told month by month.
 */
class AnalyticsStatsService
{
    /** The lines a breakdown returns at most: a table pages through them. */
    public const int BREAKDOWN_LIMIT = 500;

    public function __construct(
        private readonly PageViewRepository $pageViews,
        private readonly AnalyticsRobotDayRepository $robotDays,
        private readonly AnalyticsMonthlyRepository $monthlies,
        private readonly int $retentionMonths,
        private readonly int $suspectRetentionMonths,
        private readonly int $robotRetentionMonths,
    ) {
    }

    /**
     * The months an audience is kept day by day, before only its months
     * summed up are.
     */
    public function getRetentionMonths(AnalyticsAudience $audience): int
    {
        return match ($audience) {
            AnalyticsAudience::HUMAN => $this->retentionMonths,
            AnalyticsAudience::SUSPECT => $this->suspectRetentionMonths,
            AnalyticsAudience::DECLARED => $this->robotRetentionMonths,
        };
    }

    /**
     * The first day an audience is still kept day by day.
     */
    public function getHorizon(AnalyticsAudience $audience): DateTimeImmutable
    {
        return new DateTimeImmutable('first day of this month midnight')->modify('-' . $this->getRetentionMonths($audience) . ' months');
    }

    public function getCounts(AnalyticsAudience $audience): AnalyticsCountsInterface
    {
        return $this->pageViews->supportsAudience($audience) ? $this->pageViews : $this->robotDays;
    }

    /**
     * @param list<AnalyticsAudience> $audiences
     * @return array{visitors: int, visits: int, views: int}
     */
    public function getTotals(AnalyticsPeriod $period, array $audiences): array
    {
        $totals = self::zero();

        foreach ($audiences as $audience) {
            $totals = self::add($totals, $this->getCounts($audience)->countTotals($audience, ...$this->kept($audience, $period)));

            foreach ($this->summedUp($audience, AnalyticsDimension::TOTAL, $period) as $monthly) {
                $totals = self::add($totals, $monthly);
            }
        }

        return $totals;
    }

    /**
     * The counts per day, or per month over a long period, every day or month
     * of the period present — a gap is a zero.
     *
     * @param list<AnalyticsAudience> $audiences
     * @return array<string, array{visitors: int, visits: int, views: int}> `Y-m-d` or `Y-m`
     */
    public function getSeries(AnalyticsPeriod $period, array $audiences): array
    {
        $daily = $period->isDaily();
        $format = $daily ? 'Y-m-d' : 'Y-m';
        $series = [];

        for ($date = $period->from; $date < $period->to; $date = $date->modify($daily ? '+1 day' : 'first day of next month')) {
            $series[$date->format($format)] = self::zero();
        }

        foreach ($audiences as $audience) {
            foreach ($this->summedUp($audience, AnalyticsDimension::TOTAL, $period) as $monthly) {
                $key = $monthly->getMonth()->format($format);

                if (isset($series[$key])) {
                    $series[$key] = self::add($series[$key], $monthly);
                }
            }

            foreach ($this->getCounts($audience)->countPerBucket($audience, ...[...$this->kept($audience, $period), $daily]) as $key => $counts) {
                if (isset($series[$key])) {
                    $series[$key] = self::add($series[$key], $counts);
                }
            }
        }

        return $series;
    }

    /**
     * @param list<AnalyticsAudience> $audiences
     * @return list<AnalyticsBreakdown> the most visited first, the most read
     *                                  when no line has a visit
     */
    public function getBreakdown(AnalyticsDimension $dimension, AnalyticsPeriod $period, array $audiences): array
    {
        if (AnalyticsDimension::TOTAL === $dimension) {
            $totals = $this->getTotals($period, $audiences);

            return [new AnalyticsBreakdown($dimension->value, '', $totals['visitors'], $totals['visits'], $totals['views'], $period->getKey())];
        }

        $lines = [];

        foreach ($audiences as $audience) {
            // A person is no robot.
            if (AnalyticsDimension::ROBOT === $dimension && AnalyticsAudience::HUMAN === $audience) {
                continue;
            }

            foreach ($this->getCounts($audience)->countPerValue($audience, $dimension->getColumn(), ...[...$this->kept($audience, $period), self::BREAKDOWN_LIMIT]) as $key => $counts) {
                $lines[$key] = self::add($lines[$key] ?? self::zero(), $counts);
            }

            foreach ($this->summedUp($audience, $dimension, $period) as $monthly) {
                $key = $monthly->getDimensionKey();
                $lines[$key] = self::add($lines[$key] ?? self::zero(), $monthly);
            }
        }

        uasort($lines, static fn (array $a, array $b): int => [$b['visits'], $b['views']] <=> [$a['visits'], $a['views']]);

        $breakdown = [];

        foreach (array_slice($lines, 0, self::BREAKDOWN_LIMIT, true) as $key => $counts) {
            $breakdown[] = new AnalyticsBreakdown($dimension->value, (string) $key, $counts['visitors'], $counts['visits'], $counts['views'], $period->getKey());
        }

        return $breakdown;
    }

    /**
     * The part of the period an audience is still kept day by day.
     *
     * @return array{0: DateTimeImmutable, 1: DateTimeImmutable}
     */
    private function kept(AnalyticsAudience $audience, AnalyticsPeriod $period): array
    {
        return [max($period->from, $this->getHorizon($audience)), $period->to];
    }

    /**
     * The audience's months summed up within the part of the period before
     * its horizon.
     *
     * @return list<AnalyticsMonthly>
     */
    private function summedUp(AnalyticsAudience $audience, AnalyticsDimension $dimension, AnalyticsPeriod $period): array
    {
        $horizon = $this->getHorizon($audience);

        if ($period->from >= $horizon) {
            return [];
        }

        return $this->monthlies->findForDimension(
            $audience,
            $dimension,
            $period->from->modify('first day of this month'),
            min($period->to, $horizon)
        );
    }

    /**
     * @return array{visitors: int, visits: int, views: int}
     */
    private static function zero(): array
    {
        return ['visitors' => 0, 'visits' => 0, 'views' => 0];
    }

    /**
     * @param array{visitors: int, visits: int, views: int} $counts
     * @param AnalyticsMonthly|array{visitors: int, visits: int, views: int} $more
     * @return array{visitors: int, visits: int, views: int}
     */
    private static function add(array $counts, AnalyticsMonthly|array $more): array
    {
        $more = $more instanceof AnalyticsMonthly
            ? ['visitors' => $more->getVisitors(), 'visits' => $more->getVisits(), 'views' => $more->getViews()]
            : $more;

        return [
            'visitors' => $counts['visitors'] + $more['visitors'],
            'visits' => $counts['visits'] + $more['visits'],
            'views' => $counts['views'] + $more['views'],
        ];
    }
}
