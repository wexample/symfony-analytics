<?php

namespace Wexample\SymfonyAnalytics\Repository;

use DateTimeImmutable;
use Doctrine\DBAL\Types\Types;
use Wexample\SymfonyAnalytics\Entity\AnalyticsMonthly;
use Wexample\SymfonyAnalytics\Enum\AnalyticsAudience;
use Wexample\SymfonyAnalytics\Enum\AnalyticsDimension;
use Wexample\SymfonyHelpers\Repository\AbstractRepository;

/**
 * The months summed up, read where the pages viewed are gone.
 *
 * @method AnalyticsMonthly saveNewAnalyticsMonthly(DateTimeImmutable $month, AnalyticsAudience $audience, AnalyticsDimension $dimension, string $dimensionKey, int $visitors, int $visits, int $views)
 */
class AnalyticsMonthlyRepository extends AbstractRepository
{
    public static function getEntityClassName(): string
    {
        return AnalyticsMonthly::class;
    }

    public function createNewAnalyticsMonthly(
        DateTimeImmutable $month,
        AnalyticsAudience $audience,
        AnalyticsDimension $dimension,
        string $dimensionKey,
        int $visitors,
        int $visits,
        int $views,
    ): AnalyticsMonthly {
        return new AnalyticsMonthly($month, $audience, $dimension, $dimensionKey, $visitors, $visits, $views);
    }

    /**
     * Whether the audience's month is summed up: its total line is written
     * whatever it counts, zero included.
     */
    public function hasMonth(AnalyticsAudience $audience, DateTimeImmutable $month): bool
    {
        return null !== $this->findOneBy(['month' => $month, 'audience' => $audience, 'dimension' => AnalyticsDimension::TOTAL]);
    }

    /**
     * The lines of an audience's dimension for the months from `from` to `to`
     * excluded.
     *
     * @return list<AnalyticsMonthly>
     */
    public function findForDimension(AnalyticsAudience $audience, AnalyticsDimension $dimension, DateTimeImmutable $from, DateTimeImmutable $to): array
    {
        return $this->createQueryBuilder('monthly')
            ->andWhere('monthly.audience = :audience')
            ->andWhere('monthly.dimension = :dimension')
            ->setParameter('audience', $audience)
            ->andWhere('monthly.month >= :from')
            ->andWhere('monthly.month < :to')
            ->setParameter('dimension', $dimension)
            ->setParameter('from', $from, Types::DATE_IMMUTABLE)
            ->setParameter('to', $to, Types::DATE_IMMUTABLE)
            ->orderBy('monthly.month', self::SORT_ASC)
            ->getQuery()
            ->getResult();
    }
}
