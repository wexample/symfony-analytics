<?php

namespace Wexample\SymfonyAnalytics\Repository;

use DateTimeImmutable;
use Wexample\SymfonyAnalytics\Class\TrafficOrigin;
use Wexample\SymfonyAnalytics\Entity\PageView;
use Wexample\SymfonyAnalytics\Enum\DeviceType;
use Wexample\SymfonyHelpers\Repository\AbstractRepository;

/**
 * The pages viewed, and what they add up to: counted in the database, a
 * visitor and a visit once each however many pages they read.
 *
 * @method PageView saveNewPageView(string $visitorId, string $visitId, DateTimeImmutable $viewedAt, string $path, ?string $route, ?string $locale, TrafficOrigin $origin, ?string $country, DeviceType $device)
 */
class PageViewRepository extends AbstractRepository
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
    ): PageView {
        return new PageView($visitorId, $visitId, $viewedAt, $path, $route, $locale, $origin, $country, $device);
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

    public function findOldestDate(): ?DateTimeImmutable
    {
        $oldest = $this->getEntityManager()->getConnection()->fetchOne('SELECT MIN(viewed_at) FROM ' . self::TABLE);

        return $oldest ? new DateTimeImmutable($oldest) : null;
    }

    /**
     * @return array{visitors: int, visits: int, views: int}
     */
    public function countTotals(DateTimeImmutable $from, DateTimeImmutable $to): array
    {
        $row = $this->getEntityManager()->getConnection()->fetchAssociative(
            'SELECT ' . self::COUNTS . ' FROM ' . self::TABLE . ' WHERE viewed_at >= :from AND viewed_at < :to',
            $this->bounds($from, $to)
        ) ?: [];

        return self::counts($row);
    }

    /**
     * The counts per day (`Y-m-d`) or per month (`Y-m`), the oldest first.
     *
     * @return array<string, array{visitors: int, visits: int, views: int}>
     */
    public function countPerBucket(DateTimeImmutable $from, DateTimeImmutable $to, bool $daily): array
    {
        // The date written out and cut: the same SQL on PostgreSQL and SQLite.
        $bucket = 'SUBSTR(CAST(viewed_at AS VARCHAR(19)), 1, ' . ($daily ? 10 : 7) . ')';
        $rows = $this->getEntityManager()->getConnection()->fetchAllAssociative(
            'SELECT ' . $bucket . ' AS bucket, ' . self::COUNTS . ' FROM ' . self::TABLE
            . ' WHERE viewed_at >= :from AND viewed_at < :to GROUP BY ' . $bucket . ' ORDER BY bucket',
            $this->bounds($from, $to)
        );

        $buckets = [];

        foreach ($rows as $row) {
            $buckets[$row['bucket']] = self::counts($row);
        }

        return $buckets;
    }

    /**
     * The counts per value of a column, the most visited first; a page viewed
     * without a value there — no referrer, no country — is left out.
     *
     * @param string $column one of AnalyticsDimension::getColumn(), never a visitor's input
     * @return array<string, array{visitors: int, visits: int, views: int}>
     */
    public function countPerValue(string $column, DateTimeImmutable $from, DateTimeImmutable $to, int $limit): array
    {
        $rows = $this->getEntityManager()->getConnection()->fetchAllAssociative(
            'SELECT ' . $column . ' AS dimension_key, ' . self::COUNTS . ' FROM ' . self::TABLE
            . ' WHERE viewed_at >= :from AND viewed_at < :to AND ' . $column . ' IS NOT NULL'
            . ' GROUP BY ' . $column . ' ORDER BY visits DESC, views DESC LIMIT ' . $limit,
            $this->bounds($from, $to)
        );

        $values = [];

        foreach ($rows as $row) {
            $values[(string) $row['dimension_key']] = self::counts($row);
        }

        return $values;
    }

    public function deleteBefore(DateTimeImmutable $date): int
    {
        return (int) $this->getEntityManager()->getConnection()->executeStatement(
            'DELETE FROM ' . self::TABLE . ' WHERE viewed_at < :date',
            ['date' => $date->format(self::DATE_FORMAT)]
        );
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
