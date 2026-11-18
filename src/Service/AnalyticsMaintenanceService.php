<?php

namespace Wexample\SymfonyAnalytics\Service;

use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;
use Wexample\SymfonyAnalytics\Enum\AnalyticsAudience;
use Wexample\SymfonyAnalytics\Enum\AnalyticsDimension;
use Wexample\SymfonyAnalytics\Repository\AnalyticsMonthlyRepository;

/**
 * Sums every finished month up, per audience and dimension, as soon as it is
 * over — not when it expires: a month is the same a year later —, then
 * deletes what each audience keeps day by day past its own retention. What
 * is left of an old month is its totals and its first lines per dimension.
 */
class AnalyticsMaintenanceService
{
    /** The lines kept per month and dimension: the long tail of pages is not history. */
    private const int MONTHLY_LINES = 1000;

    public function __construct(
        private readonly AnalyticsMonthlyRepository $monthlies,
        private readonly AnalyticsStatsService $stats,
        private readonly EntityManagerInterface $entityManager,
    ) {
    }

    /**
     * @return array{months: int, deleted: int} the months summed up, the
     *                                          lines deleted, every audience
     *                                          together
     */
    public function maintain(): array
    {
        $months = 0;
        $deleted = 0;
        $current = new DateTimeImmutable('first day of this month midnight');

        foreach (AnalyticsAudience::cases() as $audience) {
            $counts = $this->stats->getCounts($audience);
            $oldest = $counts->findOldestDate();

            for ($month = $oldest?->modify('first day of this month midnight'); null !== $month && $month < $current; $month = $month->modify('+1 month')) {
                if (! $this->monthlies->hasMonth($audience, $month)) {
                    $this->sumUp($audience, $month);
                    ++$months;
                }
            }

            $deleted += $counts->deleteBefore($audience, $this->stats->getHorizon($audience));
        }

        return ['months' => $months, 'deleted' => $deleted];
    }

    private function sumUp(AnalyticsAudience $audience, DateTimeImmutable $month): void
    {
        $counts = $this->stats->getCounts($audience);
        $end = $month->modify('+1 month');

        foreach (AnalyticsDimension::cases() as $dimension) {
            $lines = AnalyticsDimension::TOTAL === $dimension
                ? ['' => $counts->countTotals($audience, $month, $end)]
                : $counts->countPerValue($audience, $dimension->getColumn(), $month, $end, self::MONTHLY_LINES);

            foreach ($lines as $key => $line) {
                $this->entityManager->persist($this->monthlies->createNewAnalyticsMonthly(
                    $month,
                    $audience,
                    $dimension,
                    (string) $key,
                    $line['visitors'],
                    $line['visits'],
                    $line['views']
                ));
            }
        }

        $this->entityManager->flush();
    }
}
