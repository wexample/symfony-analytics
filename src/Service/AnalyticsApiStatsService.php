<?php

namespace Wexample\SymfonyAnalytics\Service;

use DateTimeImmutable;
use Wexample\SymfonyAnalytics\Class\AnalyticsPeriod;
use Wexample\SymfonyAnalytics\Entity\AnalyticsApiBreakdown;
use Wexample\SymfonyAnalytics\Enum\AnalyticsApiDimension;
use Wexample\SymfonyAnalytics\Enum\AnalyticsApiOutcome;
use Wexample\SymfonyAnalytics\Repository\AnalyticsApiDayRepository;

/**
 * The calls to the api over a period: their totals, their course per
 * outcome, their breakdowns. Read from the days, kept
 * `api_retention_months`.
 */
class AnalyticsApiStatsService
{
    /** The lines a breakdown returns at most: a table pages through them. */
    public const int BREAKDOWN_LIMIT = 500;

    public function __construct(
        private readonly AnalyticsApiDayRepository $apiDays,
        private readonly int $apiRetentionMonths,
    ) {
    }

    public function isEnabled(): bool
    {
        return $this->apiRetentionMonths > 0;
    }

    public function getRetentionMonths(): int
    {
        return $this->apiRetentionMonths;
    }

    /**
     * The first day the calls are still kept.
     */
    public function getHorizon(): DateTimeImmutable
    {
        return new DateTimeImmutable('first day of this month midnight')->modify('-' . $this->apiRetentionMonths . ' months');
    }

    /**
     * The calls per day, or per month over a long period, and outcome, every
     * day or month of the period present — a gap is a zero.
     *
     * @return array<string, array<string, int>> `Y-m-d` or `Y-m`, the calls per AnalyticsApiOutcome
     */
    public function getSeries(AnalyticsPeriod $period): array
    {
        $daily = $period->isDaily();
        $format = $daily ? 'Y-m-d' : 'Y-m';
        $zero = array_fill_keys(array_map(static fn (AnalyticsApiOutcome $outcome): string => $outcome->value, AnalyticsApiOutcome::cases()), 0);
        $series = [];

        for ($date = $period->from; $date < $period->to; $date = $date->modify($daily ? '+1 day' : 'first day of next month')) {
            $series[$date->format($format)] = $zero;
        }

        foreach ($this->apiDays->countPerBucket($period->from, $period->to, $daily) as $key => $calls) {
            if (isset($series[$key])) {
                $series[$key] = $calls;
            }
        }

        return $series;
    }

    /**
     * @return list<AnalyticsApiBreakdown> the most called first
     */
    public function getBreakdown(AnalyticsApiDimension $dimension, AnalyticsPeriod $period): array
    {
        if (AnalyticsApiDimension::TOTAL === $dimension) {
            $totals = $this->apiDays->countTotals($period->from, $period->to);

            return [self::line($dimension, '', $totals, $totals['clients'], $period)];
        }

        $lines = [];

        foreach ($this->apiDays->countPerValue($dimension->getColumn(), $period->from, $period->to, self::BREAKDOWN_LIMIT) as $key => $counts) {
            $lines[] = self::line($dimension, (string) $key, $counts, 0, $period);
        }

        return $lines;
    }

    /**
     * @param array{calls: int, client_errors: int, limited: int, server_errors: int, cached: int, duration_ms: int, duration_max_ms: int} $counts
     */
    private static function line(AnalyticsApiDimension $dimension, string $key, array $counts, int $clients, AnalyticsPeriod $period): AnalyticsApiBreakdown
    {
        return new AnalyticsApiBreakdown(
            $dimension->value,
            $key,
            $counts['calls'],
            $counts['client_errors'],
            $counts['limited'],
            $counts['server_errors'],
            $counts['cached'],
            $counts['calls'] > 0 ? intdiv($counts['duration_ms'], $counts['calls']) : 0,
            $counts['duration_max_ms'],
            $clients,
            $period->getKey()
        );
    }
}
