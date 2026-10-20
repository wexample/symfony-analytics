<?php

namespace Wexample\SymfonyAnalytics\Service\ChartPoint;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Contracts\Translation\TranslatorInterface;
use Wexample\SymfonyAnalytics\Class\AnalyticsPeriod;
use Wexample\SymfonyAnalytics\Entity\AnalyticsBreakdown;
use Wexample\SymfonyAnalytics\Enum\AnalyticsDimension;
use Wexample\SymfonyAnalytics\Service\AnalyticsAccessService;
use Wexample\SymfonyAnalytics\Service\AnalyticsStatsService;
use Wexample\SymfonyCharts\Helper\ChartPointHelper;
use Wexample\SymfonyCharts\Interface\ChartPointProviderInterface;

/**
 * The visits of the period per country, for a world map: the categories are
 * ISO 3166-1 alpha-2 codes, which the map names in the page's language.
 */
class AnalyticsCountriesChartPointProvider implements ChartPointProviderInterface
{
    public function __construct(
        private readonly AnalyticsStatsService $stats,
        private readonly AnalyticsAccessService $access,
        private readonly TranslatorInterface $translator,
    ) {
    }

    public static function getChartName(): string
    {
        return 'analytics-countries';
    }

    public function getPoints(Request $request): iterable
    {
        $this->access->denyUnlessGranted();

        $lines = $this->stats->getBreakdown(
            AnalyticsDimension::COUNTRY,
            AnalyticsPeriod::fromQuery($request->query->get('from'), $request->query->get('to'))
        );

        return ChartPointHelper::seriesFromList(
            self::getChartName(),
            'visits',
            array_map(static fn (AnalyticsBreakdown $line): string => $line->getDimensionKey(), $lines),
            array_map(static fn (AnalyticsBreakdown $line): int => $line->getVisits(), $lines),
            $this->translator->trans('WexampleSymfonyAnalyticsBundle.common.analytics::metric.visits'),
        );
    }
}
