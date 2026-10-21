<?php

namespace Wexample\SymfonyAnalytics\Service;

use DateTimeImmutable;
use Wexample\SymfonyAnalytics\Class\AnalyticsPeriod;
use Wexample\SymfonyAnalytics\Entity\AnalyticsBreakdown;
use Wexample\SymfonyAnalytics\Entity\AnalyticsMonthly;
use Wexample\SymfonyAnalytics\Enum\AnalyticsDimension;
use Wexample\SymfonyAnalytics\Repository\AnalyticsMonthlyRepository;
use Wexample\SymfonyAnalytics\Repository\AnalyticsRobotDayRepository;
use Wexample\SymfonyAnalytics\Repository\PageViewRepository;

/**
 * The audience of a period: its totals, its course, its breakdowns. Read from
 * the pages viewed where they are kept, and from the months summed up before
 * that (`retention_months`), which a part of the period reaching back there
 * is told month by month.
 */
class AnalyticsStatsService
{
    /** The lines a breakdown returns at most: a table pages through them. */
    public const int BREAKDOWN_LIMIT = 500;

    public function __construct(
        private readonly PageViewRepository $pageViews,
        private readonly AnalyticsMonthlyRepository $monthlies,
        private readonly AnalyticsRobotDayRepository $robotDays,
        private readonly int $retentionMonths,
    ) {
    }

    /**
     * The first day whose pages viewed are still kept.
     */
    public function getHorizon(): DateTimeImmutable
    {
        return new DateTimeImmutable('first day of this month midnight')->modify('-' . $this->retentionMonths . ' months');
    }

    /**
     * @return array{visitors: int, visits: int, views: int}
     */
    public function getTotals(AnalyticsPeriod $period): array
    {
        $totals = $this->pageViews->countTotals(...$this->kept($period));

        foreach ($this->summedUp(AnalyticsDimension::TOTAL, $period) as $monthly) {
            $totals = self::add($totals, $monthly);
        }

        return $totals;
    }

    /**
     * The counts per day, or per month over a long period, every day or month
     * of the period present — a gap is a zero.
     *
     * @return array<string, array{visitors: int, visits: int, views: int}> `Y-m-d` or `Y-m`
     */
    public function getSeries(AnalyticsPeriod $period): array
    {
        $daily = $period->isDaily();
        $format = $daily ? 'Y-m-d' : 'Y-m';
        $series = [];

        for ($date = $period->from; $date < $period->to; $date = $date->modify($daily ? '+1 day' : 'first day of next month')) {
            $series[$date->format($format)] = ['visitors' => 0, 'visits' => 0, 'views' => 0];
        }

        foreach ($this->summedUp(AnalyticsDimension::TOTAL, $period) as $monthly) {
            $key = $monthly->getMonth()->format($format);

            if (isset($series[$key])) {
                $series[$key] = self::add($series[$key], $monthly);
            }
        }

        foreach ($this->pageViews->countPerBucket(...[...$this->kept($period), $daily]) as $key => $counts) {
            if (isset($series[$key])) {
                $series[$key] = self::add($series[$key], $counts);
            }
        }

        return $series;
    }

    /**
     * @return list<AnalyticsBreakdown> the most visited first
     */
    public function getBreakdown(AnalyticsDimension $dimension, AnalyticsPeriod $period): array
    {
        if (AnalyticsDimension::TOTAL === $dimension) {
            $totals = $this->getTotals($period);

            return [new AnalyticsBreakdown($dimension->value, '', $totals['visitors'], $totals['visits'], $totals['views'], $period->getKey())];
        }

        // A robot has neither visitor nor visit: its pages alone.
        if (AnalyticsDimension::ROBOT === $dimension) {
            $breakdown = [];

            foreach ($this->robotDays->countPerRobot($period->from, $period->to, self::BREAKDOWN_LIMIT) as $robot => $hits) {
                $breakdown[] = new AnalyticsBreakdown($dimension->value, $robot, 0, 0, $hits, $period->getKey());
            }

            return $breakdown;
        }

        $lines = $this->pageViews->countPerValue($dimension->getColumn(), ...[...$this->kept($period), self::BREAKDOWN_LIMIT]);

        foreach ($this->summedUp($dimension, $period) as $monthly) {
            $key = $monthly->getDimensionKey();
            $lines[$key] = self::add($lines[$key] ?? ['visitors' => 0, 'visits' => 0, 'views' => 0], $monthly);
        }

        uasort($lines, static fn (array $a, array $b): int => [$b['visits'], $b['views']] <=> [$a['visits'], $a['views']]);

        $breakdown = [];

        foreach (array_slice($lines, 0, self::BREAKDOWN_LIMIT, true) as $key => $counts) {
            $breakdown[] = new AnalyticsBreakdown($dimension->value, (string) $key, $counts['visitors'], $counts['visits'], $counts['views'], $period->getKey());
        }

        return $breakdown;
    }

    /**
     * The part of the period whose pages viewed are kept.
     *
     * @return array{0: DateTimeImmutable, 1: DateTimeImmutable}
     */
    private function kept(AnalyticsPeriod $period): array
    {
        return [max($period->from, $this->getHorizon()), $period->to];
    }

    /**
     * The months summed up within the part of the period before the horizon.
     *
     * @return list<AnalyticsMonthly>
     */
    private function summedUp(AnalyticsDimension $dimension, AnalyticsPeriod $period): array
    {
        $horizon = $this->getHorizon();

        if ($period->from >= $horizon) {
            return [];
        }

        return $this->monthlies->findForDimension(
            $dimension,
            $period->from->modify('first day of this month'),
            min($period->to, $horizon)
        );
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
