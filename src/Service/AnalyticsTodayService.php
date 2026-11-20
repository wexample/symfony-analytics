<?php

namespace Wexample\SymfonyAnalytics\Service;

use DateTimeImmutable;
use Wexample\SymfonyAnalytics\Entity\PageView;
use Wexample\SymfonyAnalytics\Enum\AnalyticsApiOutcome;
use Wexample\SymfonyAnalytics\Enum\AnalyticsAudience;
use Wexample\SymfonyAnalytics\Repository\AnalyticsApiSlotRepository;
use Wexample\SymfonyAnalytics\Repository\AnalyticsRobotSlotRepository;
use Wexample\SymfonyAnalytics\Repository\PageViewRepository;

/**
 * What is going on in the site now: the pages of the last hours a few
 * minutes at a time, per audience, the visitors at it, the last pages read.
 * Read from the pages viewed and the robots' five minutes, never from the
 * days.
 */
class AnalyticsTodayService
{
    /** The hours a chart of the day runs over, and the minutes of each of its bars. */
    public const array WINDOWS = [24 => 15, 2 => 5];

    /** What "now" covers, one window for all it tells: the visitors at the site, their pages, the busiest robot. */
    public const int NOW_MINUTES = 60;

    public function __construct(
        private readonly PageViewRepository $pageViews,
        private readonly AnalyticsRobotSlotRepository $robotSlots,
        private readonly AnalyticsApiSlotRepository $apiSlots,
        private readonly int $apiRetentionMonths,
    ) {
    }

    /**
     * The pages per audience in each slot of the last hours, the current
     * slot last.
     *
     * @param int $hours one of the WINDOWS
     * @return array<string, array<string, int>> per slot (`Y-m-d H:i`, its first
     *                                           minute), the pages per audience
     */
    public function getSeries(int $hours): array
    {
        $minutes = self::WINDOWS[$hours];
        $end = self::floor(new DateTimeImmutable(), $minutes)->modify('+' . $minutes . ' minutes');
        $start = $end->modify('-' . $hours . ' hours');
        $zero = array_fill_keys(array_map(static fn (AnalyticsAudience $audience): string => $audience->value, AnalyticsAudience::cases()), 0);
        $series = [];

        for ($slot = $start; $slot < $end; $slot = $slot->modify('+' . $minutes . ' minutes')) {
            $series[$slot->format('Y-m-d H:i')] = $zero;
        }

        foreach (AnalyticsAudience::cases() as $audience) {
            $counts = $audience->hasPageViews()
                ? $this->pageViews->countPerMinute($audience, $start, $end)
                : $this->robotSlots->countPerSlot($start, $end);

            foreach ($counts as $minute => $views) {
                $key = self::floor(new DateTimeImmutable($minute), $minutes)->format('Y-m-d H:i');

                if (isset($series[$key])) {
                    $series[$key][$audience->value] += $views;
                }
            }
        }

        return $series;
    }

    /**
     * The people who read a page within the last NOW_MINUTES.
     */
    public function countActiveVisitors(): int
    {
        return $this->pageViews->countTotals(AnalyticsAudience::HUMAN, ...self::now())['visitors'];
    }

    /**
     * The people's pages within the last NOW_MINUTES, and yesterday over the
     * same ones: whether this hour is an ordinary one.
     *
     * @return array{now: int, yesterday: int}
     */
    public function compareHour(): array
    {
        [$from, $to] = self::now();

        return [
            'now' => $this->pageViews->countTotals(AnalyticsAudience::HUMAN, $from, $to)['views'],
            'yesterday' => $this->pageViews->countTotals(AnalyticsAudience::HUMAN, $from->modify('-1 day'), $to->modify('-1 day'))['views'],
        ];
    }

    /**
     * The robot that read the most within the last NOW_MINUTES, declared or
     * suspected — a suspected one by the reason it was caught —, null when
     * none read anything.
     *
     * @return array{robot: string, suspect: bool, views: int}|null
     */
    public function findBusiestRobot(): ?array
    {
        [$from, $to] = self::now();
        $busiest = null;

        foreach ($this->robotSlots->countPerRobot($from, $to, 1) as $robot => $views) {
            $busiest = ['robot' => $robot, 'suspect' => false, 'views' => $views];
        }

        foreach ($this->pageViews->countPerValue(AnalyticsAudience::SUSPECT, 'robot', $from, $to, 1) as $robot => $counts) {
            if ($counts['views'] > ($busiest['views'] ?? 0)) {
                $busiest = ['robot' => $robot, 'suspect' => true, 'views' => $counts['views']];
            }
        }

        return $busiest;
    }

    /**
     * What is going on now, whole, as the page shows it and the api answers
     * it — the same shape for its first drawing and each reading after: the
     * visitors at the site, this hour beside yesterday's, the busiest robot,
     * the people's latest pages.
     *
     * @return array{active: int, active_minutes: int, hour: array{now: int, yesterday: int}, busiest: array{robot: string, suspect: bool, views: int}|null, api: array<string, int>|null, latest: list<array{viewed_at: string, path: string, source: string, country: string|null}>}
     */
    public function summarize(int $latest = 10): array
    {
        return [
            'active' => $this->countActiveVisitors(),
            'active_minutes' => self::NOW_MINUTES,
            'hour' => $this->compareHour(),
            'busiest' => $this->findBusiestRobot(),
            'api' => $this->countApiCalls(),
            'latest' => array_map(static fn (PageView $view): array => [
                'viewed_at' => $view->getViewedAt()->format(DATE_ATOM),
                'path' => $view->getPath(),
                'source' => $view->getOrigin()->source->value,
                'country' => $view->getCountry(),
            ], $this->findLatestPages($latest)),
        ];
    }

    /**
     * The calls to the api within the last NOW_MINUTES, all of them and per
     * AnalyticsApiOutcome; null where the api is not counted.
     *
     * @return array<string, int>|null `calls`, then the calls per outcome
     */
    public function countApiCalls(): ?array
    {
        if ($this->apiRetentionMonths < 1) {
            return null;
        }

        $calls = ['calls' => 0] + array_fill_keys(array_map(static fn (AnalyticsApiOutcome $outcome): string => $outcome->value, AnalyticsApiOutcome::cases()), 0);

        foreach ($this->apiSlots->countPerSlot(...self::now()) as $outcomes) {
            foreach ($outcomes as $outcome => $count) {
                $calls[$outcome] += $count;
                $calls['calls'] += $count;
            }
        }

        return $calls;
    }

    /**
     * @return list<PageView> the people's latest pages, the newest first
     */
    public function findLatestPages(int $limit): array
    {
        return $this->pageViews->findLatest(AnalyticsAudience::HUMAN, $limit);
    }

    /**
     * The last NOW_MINUTES, up to the minute under way included.
     *
     * @return array{0: DateTimeImmutable, 1: DateTimeImmutable}
     */
    private static function now(): array
    {
        $to = new DateTimeImmutable('+1 minute');

        return [$to->modify('-' . (self::NOW_MINUTES + 1) . ' minutes'), $to];
    }

    private static function floor(DateTimeImmutable $at, int $minutes): DateTimeImmutable
    {
        $minute = (int) $at->format('i');

        return $at->setTime((int) $at->format('H'), $minute - $minute % $minutes);
    }
}
