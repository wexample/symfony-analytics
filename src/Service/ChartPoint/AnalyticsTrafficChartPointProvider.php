<?php

namespace Wexample\SymfonyAnalytics\Service\ChartPoint;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Contracts\Translation\TranslatorInterface;
use Wexample\SymfonyAnalytics\Class\AnalyticsPeriod;
use Wexample\SymfonyAnalytics\Enum\AnalyticsAudience;
use Wexample\SymfonyAnalytics\Helper\AnalyticsChartHelper;
use Wexample\SymfonyAnalytics\Service\AnalyticsAccessService;
use Wexample\SymfonyAnalytics\Service\AnalyticsStatsService;
use Wexample\SymfonyCharts\Helper\ChartPointHelper;
use Wexample\SymfonyCharts\Interface\ChartPointProviderInterface;

/**
 * The people's visitors, visits and pages viewed along the period: day by
 * day, or month by month over a long one.
 */
class AnalyticsTrafficChartPointProvider implements ChartPointProviderInterface
{
    private const string DOMAIN = 'WexampleSymfonyAnalyticsBundle.common.analytics::metric.';

    public function __construct(
        private readonly AnalyticsStatsService $stats,
        private readonly AnalyticsAccessService $access,
        private readonly TranslatorInterface $translator,
    ) {
    }

    public static function getChartName(): string
    {
        return 'analytics-traffic';
    }

    public function getPoints(Request $request): iterable
    {
        $this->access->denyUnlessGranted();

        $period = AnalyticsPeriod::fromQuery($request->query->get('from'), $request->query->get('to'));
        $series = $this->stats->getSeries($period, [AnalyticsAudience::HUMAN]);
        $categories = AnalyticsChartHelper::getCategories($period, array_keys($series), $request->getLocale());

        $points = [];

        foreach (['visitors' => null, 'visits' => 'mint', 'views' => 'plum'] as $metric => $color) {
            $points = [...$points, ...ChartPointHelper::seriesFromList(
                self::getChartName(),
                $metric,
                $categories,
                array_column($series, $metric),
                $this->translator->trans(self::DOMAIN . $metric),
                $color
            )];
        }

        return $points;
    }
}
