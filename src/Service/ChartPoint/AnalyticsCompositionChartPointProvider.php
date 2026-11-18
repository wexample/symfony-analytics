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
 * Every page read along the period, whoever read it: the people's, the
 * suspected robots', the declared robots' — the raw traffic and what the
 * audience keeps of it, a series per audience, to stack.
 */
class AnalyticsCompositionChartPointProvider implements ChartPointProviderInterface
{
    private const string DOMAIN = 'WexampleSymfonyAnalyticsBundle.common.analytics::audience.';

    private const array COLORS = [
        'human' => 'mint',
        'suspect' => 'sunflower',
        'declared' => 'plum',
    ];

    public function __construct(
        private readonly AnalyticsStatsService $stats,
        private readonly AnalyticsAccessService $access,
        private readonly TranslatorInterface $translator,
    ) {
    }

    public static function getChartName(): string
    {
        return 'analytics-composition';
    }

    public function getPoints(Request $request): iterable
    {
        $this->access->denyUnlessGranted();

        $period = AnalyticsPeriod::fromQuery($request->query->get('from'), $request->query->get('to'));
        $points = [];
        $categories = null;

        foreach (AnalyticsAudience::cases() as $audience) {
            $series = $this->stats->getSeries($period, [$audience]);
            $categories ??= AnalyticsChartHelper::getCategories($period, array_keys($series), $request->getLocale());

            $points = [...$points, ...ChartPointHelper::seriesFromList(
                self::getChartName(),
                $audience->value,
                $categories,
                array_column($series, 'views'),
                $this->translator->trans(self::DOMAIN . $audience->value),
                self::COLORS[$audience->value]
            )];
        }

        return $points;
    }
}
