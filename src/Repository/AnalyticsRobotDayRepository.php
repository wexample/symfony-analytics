<?php

namespace Wexample\SymfonyAnalytics\Repository;

use DateTimeImmutable;
use Symfony\Component\Uid\Uuid;
use Wexample\SymfonyAnalytics\Entity\AnalyticsRobotDay;
use Wexample\SymfonyAnalytics\Enum\AnalyticsAudience;
use Wexample\SymfonyAnalytics\Interface\AnalyticsCountsInterface;
use Wexample\SymfonyHelpers\Repository\AbstractRepository;

/**
 * The pages each declared robot read, a sum a day per route. A robot has
 * neither visitor nor visit: its counts are pages alone.
 *
 * @method AnalyticsRobotDay saveNewAnalyticsRobotDay(DateTimeImmutable $day, string $robot, string $route, int $hits)
 */
class AnalyticsRobotDayRepository extends AbstractRepository implements AnalyticsCountsInterface
{
    private const string TABLE = 'analytics_robot_day';

    private const string DATE_FORMAT = 'Y-m-d';

    /** The columns a robot's day is broken down by. */
    private const array COLUMNS = ['robot', 'route'];

    public static function getEntityClassName(): string
    {
        return AnalyticsRobotDay::class;
    }

    public function createNewAnalyticsRobotDay(DateTimeImmutable $day, string $robot, string $route, int $hits): AnalyticsRobotDay
    {
        return new AnalyticsRobotDay($day, $robot, $route, $hits);
    }

    public function supportsAudience(AnalyticsAudience $audience): bool
    {
        return AnalyticsAudience::DECLARED === $audience;
    }

    /**
     * One page more for the robot today on the route, in one statement: a
     * crawler reading several pages at once adds them all, none lost between
     * a read and a write.
     */
    public function countHit(string $robot, string $route, DateTimeImmutable $at): void
    {
        $this->getEntityManager()->getConnection()->executeStatement(
            'INSERT INTO ' . self::TABLE . ' (id, day, robot, route, hits) VALUES (:id, :day, :robot, :route, 1)'
            . ' ON CONFLICT (day, robot, route) DO UPDATE SET hits = ' . self::TABLE . '.hits + 1',
            ['id' => (string) Uuid::v7(), 'day' => $at->format(self::DATE_FORMAT), 'robot' => $robot, 'route' => mb_substr($route, 0, 255)]
        );
    }

    public function findOldestDate(): ?DateTimeImmutable
    {
        $oldest = $this->getEntityManager()->getConnection()->fetchOne('SELECT MIN(day) FROM ' . self::TABLE);

        return $oldest ? new DateTimeImmutable($oldest) : null;
    }

    public function countTotals(AnalyticsAudience $audience, DateTimeImmutable $from, DateTimeImmutable $to): array
    {
        return self::views((int) $this->getEntityManager()->getConnection()->fetchOne(
            'SELECT COALESCE(SUM(hits), 0) FROM ' . self::TABLE . ' WHERE day >= :from AND day < :to',
            $this->bounds($from, $to)
        ));
    }

    public function countPerBucket(AnalyticsAudience $audience, DateTimeImmutable $from, DateTimeImmutable $to, bool $daily): array
    {
        // The date written out and cut: the same SQL on PostgreSQL and SQLite.
        $bucket = 'SUBSTR(CAST(day AS VARCHAR(10)), 1, ' . ($daily ? 10 : 7) . ')';
        $rows = $this->getEntityManager()->getConnection()->fetchAllAssociative(
            'SELECT ' . $bucket . ' AS bucket, SUM(hits) AS views FROM ' . self::TABLE
            . ' WHERE day >= :from AND day < :to GROUP BY ' . $bucket . ' ORDER BY bucket',
            $this->bounds($from, $to)
        );

        $buckets = [];

        foreach ($rows as $row) {
            $buckets[$row['bucket']] = self::views((int) $row['views']);
        }

        return $buckets;
    }

    /**
     * Empty for a dimension a robot's day does not have: its pages, its
     * countries… are not kept.
     */
    public function countPerValue(AnalyticsAudience $audience, string $column, DateTimeImmutable $from, DateTimeImmutable $to, int $limit): array
    {
        if (! in_array($column, self::COLUMNS, true)) {
            return [];
        }

        $rows = $this->getEntityManager()->getConnection()->fetchAllAssociative(
            'SELECT ' . $column . ' AS dimension_key, SUM(hits) AS views FROM ' . self::TABLE
            . ' WHERE day >= :from AND day < :to AND ' . $column . " <> ''"
            . ' GROUP BY ' . $column . ' ORDER BY views DESC LIMIT ' . $limit,
            $this->bounds($from, $to)
        );

        $values = [];

        foreach ($rows as $row) {
            $values[(string) $row['dimension_key']] = self::views((int) $row['views']);
        }

        return $values;
    }

    public function deleteBefore(AnalyticsAudience $audience, DateTimeImmutable $date): int
    {
        return (int) $this->getEntityManager()->getConnection()->executeStatement(
            'DELETE FROM ' . self::TABLE . ' WHERE day < :date',
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
    private static function views(int $views): array
    {
        return ['visitors' => 0, 'visits' => 0, 'views' => $views];
    }
}
