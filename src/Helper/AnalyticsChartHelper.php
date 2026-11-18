<?php

namespace Wexample\SymfonyAnalytics\Helper;

use DateTimeImmutable;
use IntlDateFormatter;
use Wexample\SymfonyAnalytics\Class\AnalyticsPeriod;

/**
 * What the charts of the audience share: the days or months they run along,
 * written in the reader's language.
 */
final class AnalyticsChartHelper
{
    /**
     * @param list<string> $keys the buckets of AnalyticsStatsService::getSeries(), `Y-m-d` or `Y-m`
     * @return list<string> `d MMM` along a short period, `MMM y` along a long one
     */
    public static function getCategories(AnalyticsPeriod $period, array $keys, string $locale): array
    {
        $formatter = new IntlDateFormatter($locale, IntlDateFormatter::NONE, IntlDateFormatter::NONE, null, null, $period->isDaily() ? 'd MMM' : 'MMM y');

        return array_map(
            static fn (string $key): string => (string) $formatter->format(new DateTimeImmutable(strlen($key) === 7 ? $key . '-01' : $key)),
            $keys
        );
    }
}
