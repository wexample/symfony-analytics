<?php

namespace Wexample\SymfonyAnalytics\Repository;

use DateTimeImmutable;
use Symfony\Component\Uid\Uuid;
use Wexample\SymfonyAnalytics\Entity\AnalyticsApiDay;
use Wexample\SymfonyAnalytics\Enum\AnalyticsApiOutcome;
use Wexample\SymfonyHelpers\Repository\AbstractRepository;

/**
 * The calls to the api, a sum a day per route, client, status and cache
 * answer, and what they add up to.
 *
 * @method AnalyticsApiDay saveNewAnalyticsApiDay(DateTimeImmutable $day, string $route, string $client, int $status, string $cache, int $calls, int $durationMs, int $durationMaxMs)
 */
class AnalyticsApiDayRepository extends AbstractRepository
{
    private const string TABLE = 'analytics_api_day';

    private const string DATE_FORMAT = 'Y-m-d';

    /** The columns the calls are broken down by. */
    private const array COLUMNS = ['route', 'client', 'status', 'cache'];

    private const string COUNTS = 'SUM(calls) AS calls,'
        . " SUM(CASE WHEN status >= 400 AND status < 500 AND status <> 429 THEN calls ELSE 0 END) AS client_errors,"
        . ' SUM(CASE WHEN status = 429 THEN calls ELSE 0 END) AS limited,'
        . ' SUM(CASE WHEN status >= 500 THEN calls ELSE 0 END) AS server_errors,'
        . " SUM(CASE WHEN cache = 'hit' THEN calls ELSE 0 END) AS cached,"
        . ' SUM(duration_ms) AS duration_ms, MAX(duration_max_ms) AS duration_max_ms';

    public static function getEntityClassName(): string
    {
        return AnalyticsApiDay::class;
    }

    public function createNewAnalyticsApiDay(
        DateTimeImmutable $day,
        string $route,
        string $client,
        int $status,
        string $cache,
        int $calls,
        int $durationMs,
        int $durationMaxMs,
    ): AnalyticsApiDay {
        return new AnalyticsApiDay($day, $route, $client, $status, $cache, $calls, $durationMs, $durationMaxMs);
    }

    /**
     * One call more, in one statement: calls at once add up, none lost
     * between a read and a write.
     */
    public function countCall(DateTimeImmutable $at, string $route, string $client, int $status, string $cache, int $durationMs): void
    {
        $this->getEntityManager()->getConnection()->executeStatement(
            'INSERT INTO ' . self::TABLE . ' (id, day, route, client, status, cache, calls, duration_ms, duration_max_ms)'
            . ' VALUES (:id, :day, :route, :client, :status, :cache, 1, :duration, :duration)'
            . ' ON CONFLICT (day, route, client, status, cache) DO UPDATE SET calls = ' . self::TABLE . '.calls + 1,'
            . ' duration_ms = ' . self::TABLE . '.duration_ms + :duration,'
            . ' duration_max_ms = CASE WHEN ' . self::TABLE . '.duration_max_ms < :duration THEN :duration ELSE ' . self::TABLE . '.duration_max_ms END',
            [
                'id' => (string) Uuid::v7(),
                'day' => $at->format(self::DATE_FORMAT),
                'route' => mb_substr($route, 0, 255),
                'client' => mb_substr($client, 0, 64),
                'status' => $status,
                'cache' => $cache,
                'duration' => $durationMs,
            ]
        );
    }

    /**
     * @return array{calls: int, client_errors: int, limited: int, server_errors: int, cached: int, duration_ms: int, duration_max_ms: int, clients: int}
     */
    public function countTotals(DateTimeImmutable $from, DateTimeImmutable $to): array
    {
        $row = $this->getEntityManager()->getConnection()->fetchAssociative(
            'SELECT ' . self::COUNTS . ', COUNT(DISTINCT client) AS clients FROM ' . self::TABLE . ' WHERE day >= :from AND day < :to',
            $this->bounds($from, $to)
        ) ?: [];

        return self::counts($row) + ['clients' => (int) ($row['clients'] ?? 0)];
    }

    /**
     * The calls per day (`Y-m-d`) or per month (`Y-m`) and outcome.
     *
     * @return array<string, array<string, int>> per bucket, the calls per AnalyticsApiOutcome
     */
    public function countPerBucket(DateTimeImmutable $from, DateTimeImmutable $to, bool $daily): array
    {
        // The date written out and cut: the same SQL on PostgreSQL and SQLite.
        $bucket = 'SUBSTR(CAST(day AS VARCHAR(10)), 1, ' . ($daily ? 10 : 7) . ')';
        $outcomes = implode(', ', array_map(
            static fn (AnalyticsApiOutcome $outcome): string => 'SUM(CASE WHEN ' . $outcome->getCondition() . ' THEN calls ELSE 0 END) AS ' . $outcome->value,
            AnalyticsApiOutcome::cases()
        ));
        $rows = $this->getEntityManager()->getConnection()->fetchAllAssociative(
            'SELECT ' . $bucket . ' AS bucket, ' . $outcomes . ' FROM ' . self::TABLE
            . ' WHERE day >= :from AND day < :to GROUP BY ' . $bucket . ' ORDER BY bucket',
            $this->bounds($from, $to)
        );

        $buckets = [];

        foreach ($rows as $row) {
            foreach (AnalyticsApiOutcome::cases() as $outcome) {
                $buckets[$row['bucket']][$outcome->value] = (int) $row[$outcome->value];
            }
        }

        return $buckets;
    }

    /**
     * The counts per value of a column, the most called first.
     *
     * @param string $column one of AnalyticsApiDimension::getColumn(), never a caller's input
     * @return array<string, array{calls: int, client_errors: int, limited: int, server_errors: int, cached: int, duration_ms: int, duration_max_ms: int}>
     */
    public function countPerValue(string $column, DateTimeImmutable $from, DateTimeImmutable $to, int $limit): array
    {
        if (! in_array($column, self::COLUMNS, true)) {
            return [];
        }

        $rows = $this->getEntityManager()->getConnection()->fetchAllAssociative(
            'SELECT ' . $column . ' AS dimension_key, ' . self::COUNTS . ' FROM ' . self::TABLE
            . ' WHERE day >= :from AND day < :to GROUP BY ' . $column . ' ORDER BY calls DESC LIMIT ' . $limit,
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
     * @return array{calls: int, client_errors: int, limited: int, server_errors: int, cached: int, duration_ms: int, duration_max_ms: int}
     */
    private static function counts(array $row): array
    {
        return [
            'calls' => (int) ($row['calls'] ?? 0),
            'client_errors' => (int) ($row['client_errors'] ?? 0),
            'limited' => (int) ($row['limited'] ?? 0),
            'server_errors' => (int) ($row['server_errors'] ?? 0),
            'cached' => (int) ($row['cached'] ?? 0),
            'duration_ms' => (int) ($row['duration_ms'] ?? 0),
            'duration_max_ms' => (int) ($row['duration_max_ms'] ?? 0),
        ];
    }
}
