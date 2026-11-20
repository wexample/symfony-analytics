<?php

namespace Wexample\SymfonyAnalytics\Service\ChartPoint;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Contracts\Translation\TranslatorInterface;
use Wexample\SymfonyAnalytics\Class\AnalyticsPeriod;
use Wexample\SymfonyAnalytics\Enum\AnalyticsApiOutcome;
use Wexample\SymfonyAnalytics\Helper\AnalyticsChartHelper;
use Wexample\SymfonyAnalytics\Service\AnalyticsAccessService;
use Wexample\SymfonyAnalytics\Service\AnalyticsApiStatsService;
use Wexample\SymfonyCharts\Helper\ChartPointHelper;
use Wexample\SymfonyCharts\Interface\ChartPointProviderInterface;

/**
 * The calls to the api along the period, a series per outcome to stack:
 * answered, refused, stopped by a limit, failed.
 */
class AnalyticsApiChartPointProvider implements ChartPointProviderInterface
{
    private const string DOMAIN = 'WexampleSymfonyAnalyticsBundle.common.analytics::api_outcome.';

    public function __construct(
        private readonly AnalyticsApiStatsService $stats,
        private readonly AnalyticsAccessService $access,
        private readonly TranslatorInterface $translator,
    ) {
    }

    public static function getChartName(): string
    {
        return 'analytics-api';
    }

    public function getPoints(Request $request): iterable
    {
        $this->access->denyUnlessGranted();

        $period = AnalyticsPeriod::fromQuery($request->query->get('from'), $request->query->get('to'));
        $series = $this->stats->getSeries($period);
        $categories = AnalyticsChartHelper::getCategories($period, array_keys($series), $request->getLocale());
        $points = [];

        foreach (AnalyticsApiOutcome::cases() as $outcome) {
            $points = [...$points, ...ChartPointHelper::seriesFromList(
                self::getChartName(),
                $outcome->value,
                $categories,
                array_column($series, $outcome->value),
                $this->translator->trans(self::DOMAIN . $outcome->value),
                $outcome->getColor()
            )];
        }

        return $points;
    }
}
