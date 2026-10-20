<?php

namespace Wexample\SymfonyAnalytics\Service\ChartPoint;

use DateTimeImmutable;
use IntlDateFormatter;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Contracts\Translation\TranslatorInterface;
use Wexample\SymfonyAnalytics\Class\AnalyticsPeriod;
use Wexample\SymfonyAnalytics\Service\AnalyticsAccessService;
use Wexample\SymfonyAnalytics\Service\AnalyticsStatsService;
use Wexample\SymfonyCharts\Entity\ChartPoint;
use Wexample\SymfonyCharts\Helper\ChartPointHelper;
use Wexample\SymfonyCharts\Interface\ChartPointProviderInterface;

/**
 * Visitors, visits and pages viewed along the period: day by day, or month
 * by month over a long one.
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
        $series = $this->stats->getSeries($period);
        $formatter = new IntlDateFormatter($request->getLocale(), IntlDateFormatter::NONE, IntlDateFormatter::NONE, null, null, $period->isDaily() ? 'd MMM' : 'MMM y');
        $categories = array_map(
            static fn (string $key): string => (string) $formatter->format(new DateTimeImmutable(strlen($key) === 7 ? $key . '-01' : $key)),
            array_keys($series)
        );

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
