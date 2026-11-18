<?php

namespace Wexample\SymfonyAnalytics\Interface;

use DateTimeImmutable;
use Wexample\SymfonyAnalytics\Enum\AnalyticsAudience;

/**
 * Where the counts of an audience are kept day by day, before its months are
 * summed up: the pages viewed for the people and the suspected robots, the
 * days of the declared ones. Each period runs from `from` to `to` excluded.
 */
interface AnalyticsCountsInterface
{
    public function supportsAudience(AnalyticsAudience $audience): bool;

    public function findOldestDate(): ?DateTimeImmutable;

    /**
     * @return array{visitors: int, visits: int, views: int}
     */
    public function countTotals(AnalyticsAudience $audience, DateTimeImmutable $from, DateTimeImmutable $to): array;

    /**
     * The counts per day (`Y-m-d`) or per month (`Y-m`), the oldest first.
     *
     * @return array<string, array{visitors: int, visits: int, views: int}>
     */
    public function countPerBucket(AnalyticsAudience $audience, DateTimeImmutable $from, DateTimeImmutable $to, bool $daily): array;

    /**
     * The counts per value of a column, the most visited first; a line
     * without a value there is left out.
     *
     * @param string $column one of AnalyticsDimension::getColumn(), never a visitor's input
     * @return array<string, array{visitors: int, visits: int, views: int}>
     */
    public function countPerValue(AnalyticsAudience $audience, string $column, DateTimeImmutable $from, DateTimeImmutable $to, int $limit): array;

    /**
     * @return int the lines deleted
     */
    public function deleteBefore(AnalyticsAudience $audience, DateTimeImmutable $date): int;
}
