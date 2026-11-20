<?php

namespace Wexample\SymfonyAnalytics\Service;

use DateTimeImmutable;
use Wexample\SymfonyAnalytics\Entity\PageView;
use Wexample\SymfonyAnalytics\Enum\AnalyticsAudience;
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

    /** Within these minutes, a visitor is at the site. */
    public const int ACTIVE_MINUTES = 30;

    public function __construct(
        private readonly PageViewRepository $pageViews,
        private readonly AnalyticsRobotSlotRepository $robotSlots,
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
     * The people who read a page within the last ACTIVE_MINUTES.
     */
    public function countActiveVisitors(): int
    {
        $now = new DateTimeImmutable();

        return $this->pageViews->countTotals(AnalyticsAudience::HUMAN, $now->modify('-' . self::ACTIVE_MINUTES . ' minutes'), $now->modify('+1 minute'))['visitors'];
    }

    /**
     * The people's pages within the last sixty minutes, and yesterday over
     * the same ones: whether this hour is an ordinary one. Sliding, as the
     * visitors at the site are: whoever is at it has a page counted here.
     *
     * @return array{now: int, yesterday: int}
     */
    public function compareHour(): array
    {
        $to = new DateTimeImmutable('+1 minute');
        $from = $to->modify('-61 minutes');

        return [
            'now' => $this->pageViews->countTotals(AnalyticsAudience::HUMAN, $from, $to)['views'],
            'yesterday' => $this->pageViews->countTotals(AnalyticsAudience::HUMAN, $from->modify('-1 day'), $to->modify('-1 day'))['views'],
        ];
    }

    /**
     * The robot that read the most within the last hour, declared or
     * suspected — a suspected one by the reason it was caught —, null when
     * none read anything.
     *
     * @return array{robot: string, suspect: bool, views: int}|null
     */
    public function findBusiestRobot(): ?array
    {
        $now = new DateTimeImmutable();
        $from = $now->modify('-1 hour');
        $to = $now->modify('+1 minute');
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
     * @return array{active: int, active_minutes: int, hour: array{now: int, yesterday: int}, busiest: array{robot: string, suspect: bool, views: int}|null, latest: list<array{viewed_at: string, path: string, source: string, country: string|null}>}
     */
    public function summarize(int $latest = 10): array
    {
        return [
            'active' => $this->countActiveVisitors(),
            'active_minutes' => self::ACTIVE_MINUTES,
            'hour' => $this->compareHour(),
            'busiest' => $this->findBusiestRobot(),
            'latest' => array_map(static fn (PageView $view): array => [
                'viewed_at' => $view->getViewedAt()->format(DATE_ATOM),
                'path' => $view->getPath(),
                'source' => $view->getOrigin()->source->value,
                'country' => $view->getCountry(),
            ], $this->findLatestPages($latest)),
        ];
    }

    /**
     * @return list<PageView> the people's latest pages, the newest first
     */
    public function findLatestPages(int $limit): array
    {
        return $this->pageViews->findLatest(AnalyticsAudience::HUMAN, $limit);
    }

    private static function floor(DateTimeImmutable $at, int $minutes): DateTimeImmutable
    {
        $minute = (int) $at->format('i');

        return $at->setTime((int) $at->format('H'), $minute - $minute % $minutes);
    }
}
