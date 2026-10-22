<?php

namespace Wexample\SymfonyAnalytics\Service;

use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;
use Wexample\SymfonyAnalytics\Enum\AnalyticsDimension;
use Wexample\SymfonyAnalytics\Repository\AnalyticsMonthlyRepository;
use Wexample\SymfonyAnalytics\Repository\PageViewRepository;

/**
 * Sums every finished month up, per dimension, as soon as it is over — not
 * when it expires: a month is the same a year later —, then deletes the pages
 * viewed older than `retention_months`. What is left of an old month is its
 * totals and its first lines per dimension.
 */
class AnalyticsMaintenanceService
{
    /** The lines kept per month and dimension: the long tail of pages is not history. */
    private const int MONTHLY_LINES = 1000;

    public function __construct(
        private readonly PageViewRepository $pageViews,
        private readonly AnalyticsMonthlyRepository $monthlies,
        private readonly AnalyticsStatsService $stats,
        private readonly EntityManagerInterface $entityManager,
    ) {
    }

    /**
     * @return array{months: int, deleted: int} the months summed up, the pages deleted
     */
    public function maintain(): array
    {
        $months = 0;
        $oldest = $this->pageViews->findOldestDate();
        $current = new DateTimeImmutable('first day of this month midnight');

        for ($month = $oldest?->modify('first day of this month midnight'); null !== $month && $month < $current; $month = $month->modify('+1 month')) {
            if (! $this->monthlies->hasMonth($month)) {
                $this->sumUp($month);
                ++$months;
            }
        }

        return ['months' => $months, 'deleted' => $this->pageViews->deleteBefore($this->stats->getHorizon())];
    }

    private function sumUp(DateTimeImmutable $month): void
    {
        $end = $month->modify('+1 month');

        foreach (AnalyticsDimension::cases() as $dimension) {
            // Summed up a day at a time already.
            if (AnalyticsDimension::ROBOT === $dimension) {
                continue;
            }

            $lines = AnalyticsDimension::TOTAL === $dimension
                ? ['' => $this->pageViews->countTotals($month, $end)]
                : $this->pageViews->countPerValue($dimension->getColumn(), $month, $end, self::MONTHLY_LINES);

            foreach ($lines as $key => $counts) {
                $this->entityManager->persist($this->monthlies->createNewAnalyticsMonthly(
                    $month,
                    $dimension,
                    (string) $key,
                    $counts['visitors'],
                    $counts['visits'],
                    $counts['views']
                ));
            }
        }

        $this->entityManager->flush();
    }
}
