<?php

namespace Wexample\SymfonyAnalytics\Repository;

use DateTimeImmutable;
use Wexample\SymfonyAnalytics\Class\TrafficOrigin;
use Wexample\SymfonyAnalytics\Entity\PageView;
use Wexample\SymfonyAnalytics\Enum\AnalyticsAudience;
use Wexample\SymfonyAnalytics\Enum\DeviceType;
use Wexample\SymfonyAnalytics\Interface\AnalyticsCountsInterface;
use Wexample\SymfonyHelpers\Repository\AbstractRepository;

/**
 * The pages viewed, and what they add up to: counted in the database, a
 * visitor and a visit once each however many pages they read. The people's
 * and the suspected robots', told apart by `robot`.
 *
 * @method PageView saveNewPageView(string $visitorId, string $visitId, DateTimeImmutable $viewedAt, string $path, ?string $route, ?string $locale, TrafficOrigin $origin, ?string $country, DeviceType $device, ?string $robot)
 */
class PageViewRepository extends AbstractRepository implements AnalyticsCountsInterface
{
    private const string TABLE = 'analytics_page_view';

    private const string DATE_FORMAT = 'Y-m-d H:i:s';

    private const string COUNTS = 'COUNT(DISTINCT visitor_id) AS visitors, COUNT(DISTINCT visit_id) AS visits, COUNT(*) AS views';

    public static function getEntityClassName(): string
    {
        return PageView::class;
    }

    public function createNewPageView(
        string $visitorId,
        string $visitId,
        DateTimeImmutable $viewedAt,
        string $path,
        ?string $route,
        ?string $locale,
        TrafficOrigin $origin,
        ?string $country,
        DeviceType $device,
        ?string $robot,
    ): PageView {
        return new PageView($visitorId, $visitId, $viewedAt, $path, $route, $locale, $origin, $country, $device, $robot);
    }

    public function supportsAudience(AnalyticsAudience $audience): bool
    {
        return $audience->hasPageViews();
    }

    /**
     * The visitor's latest page since then: the visit it may carry on.
     */
    public function findLatestOfVisitorSince(string $visitorId, DateTimeImmutable $since): ?PageView
    {
        return $this->createQueryBuilder('page_view')
            ->andWhere('page_view.visitorId = :visitor')
            ->andWhere('page_view.viewedAt >= :since')
            ->setParameter('visitor', $visitorId)
            ->setParameter('since', $since)
            ->orderBy('page_view.viewedAt', self::SORT_DESC)
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * How many pages the visitor read since then: how fast it reads.
     */
    public function countOfVisitorSince(string $visitorId, DateTimeImmutable $since): int
    {
        return (int) $this->getEntityManager()->getConnection()->fetchOne(
            'SELECT COUNT(*) FROM ' . self::TABLE . ' WHERE visitor_id = :visitor AND viewed_at >= :since',
            ['visitor' => $visitorId, 'since' => $since->format(self::DATE_FORMAT)]
        );
    }

    /**
     * Why the visitor was caught as a robot today, null while it reads as a
     * person: a visitor is someone else the next day.
     */
    public function findRobotOfVisitor(string $visitorId): ?string
    {
        $robot = $this->getEntityManager()->getConnection()->fetchOne(
            'SELECT robot FROM ' . self::TABLE . ' WHERE visitor_id = :visitor AND robot IS NOT NULL LIMIT 1',
            ['visitor' => $visitorId]
        );

        return false === $robot ? null : $robot;
    }

    /**
     * The visitor's pages read as a person's turned into a robot's: the ones
     * before it crossed the line it was caught at.
     */
    public function markVisitorRobot(string $visitorId, string $robot): void
    {
        $this->getEntityManager()->getConnection()->executeStatement(
            'UPDATE ' . self::TABLE . ' SET robot = :robot WHERE visitor_id = :visitor AND robot IS NULL',
            ['robot' => $robot, 'visitor' => $visitorId]
        );
    }

    public function findOldestDate(): ?DateTimeImmutable
    {
        $oldest = $this->getEntityManager()->getConnection()->fetchOne('SELECT MIN(viewed_at) FROM ' . self::TABLE);

        return $oldest ? new DateTimeImmutable($oldest) : null;
    }

    public function countTotals(AnalyticsAudience $audience, DateTimeImmutable $from, DateTimeImmutable $to): array
    {
        $row = $this->getEntityManager()->getConnection()->fetchAssociative(
            'SELECT ' . self::COUNTS . ' FROM ' . self::TABLE . ' WHERE viewed_at >= :from AND viewed_at < :to AND ' . self::audienceCondition($audience),
            $this->bounds($from, $to)
        ) ?: [];

        return self::counts($row);
    }

    public function countPerBucket(AnalyticsAudience $audience, DateTimeImmutable $from, DateTimeImmutable $to, bool $daily): array
    {
        // The date written out and cut: the same SQL on PostgreSQL and SQLite.
        $bucket = 'SUBSTR(CAST(viewed_at AS VARCHAR(19)), 1, ' . ($daily ? 10 : 7) . ')';
        $rows = $this->getEntityManager()->getConnection()->fetchAllAssociative(
            'SELECT ' . $bucket . ' AS bucket, ' . self::COUNTS . ' FROM ' . self::TABLE
            . ' WHERE viewed_at >= :from AND viewed_at < :to AND ' . self::audienceCondition($audience)
            . ' GROUP BY ' . $bucket . ' ORDER BY bucket',
            $this->bounds($from, $to)
        );

        $buckets = [];

        foreach ($rows as $row) {
            $buckets[$row['bucket']] = self::counts($row);
        }

        return $buckets;
    }

    public function countPerValue(AnalyticsAudience $audience, string $column, DateTimeImmutable $from, DateTimeImmutable $to, int $limit): array
    {
        $rows = $this->getEntityManager()->getConnection()->fetchAllAssociative(
            'SELECT ' . $column . ' AS dimension_key, ' . self::COUNTS . ' FROM ' . self::TABLE
            . ' WHERE viewed_at >= :from AND viewed_at < :to AND ' . $column . ' IS NOT NULL AND ' . self::audienceCondition($audience)
            . ' GROUP BY ' . $column . ' ORDER BY visits DESC, views DESC LIMIT ' . $limit,
            $this->bounds($from, $to)
        );

        $values = [];

        foreach ($rows as $row) {
            $values[(string) $row['dimension_key']] = self::counts($row);
        }

        return $values;
    }

    public function deleteBefore(AnalyticsAudience $audience, DateTimeImmutable $date): int
    {
        return (int) $this->getEntityManager()->getConnection()->executeStatement(
            'DELETE FROM ' . self::TABLE . ' WHERE viewed_at < :date AND ' . self::audienceCondition($audience),
            ['date' => $date->format(self::DATE_FORMAT)]
        );
    }

    private static function audienceCondition(AnalyticsAudience $audience): string
    {
        return AnalyticsAudience::HUMAN === $audience ? 'robot IS NULL' : 'robot IS NOT NULL';
    }

    /**
     * @return array{from: string, to: string}
     */
    private function bounds(DateTimeImmutable $from, DateTimeImmutable $to): array
    {
        return ['from' => $from->format(self::DATE_FORMAT), 'to' => $to->format(self::DATE_FORMAT)];
    }

    /**
     * @return array{visitors: int, visits: int, views: int}
     */
    private static function counts(array $row): array
    {
        return [
            'visitors' => (int) ($row['visitors'] ?? 0),
            'visits' => (int) ($row['visits'] ?? 0),
            'views' => (int) ($row['views'] ?? 0),
        ];
    }
}
