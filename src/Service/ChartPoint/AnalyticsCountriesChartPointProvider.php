<?php

namespace Wexample\SymfonyAnalytics\Service\ChartPoint;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Contracts\Translation\TranslatorInterface;
use Wexample\SymfonyAnalytics\Class\AnalyticsPeriod;
use Wexample\SymfonyAnalytics\Entity\AnalyticsBreakdown;
use Wexample\SymfonyAnalytics\Enum\AnalyticsAudienceView;
use Wexample\SymfonyAnalytics\Enum\AnalyticsDimension;
use Wexample\SymfonyAnalytics\Service\AnalyticsAccessService;
use Wexample\SymfonyAnalytics\Service\AnalyticsStatsService;
use Wexample\SymfonyCharts\Helper\ChartPointHelper;
use Wexample\SymfonyCharts\Interface\ChartPointProviderInterface;

/**
 * What the period read per country, for a world map, of the audience its
 * `scope` says (AnalyticsAudienceView, `humans` by default): the people's
 * visits, or the pages read once robots are in — a declared robot has no
 * visit. The categories are ISO 3166-1 alpha-2 codes, which the map names in
 * the page's language.
 */
class AnalyticsCountriesChartPointProvider implements ChartPointProviderInterface
{
    private const string METRIC = 'WexampleSymfonyAnalyticsBundle.common.analytics::metric.';

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

        $view = AnalyticsAudienceView::tryFrom((string) ($request->query->get('scope') ?: AnalyticsAudienceView::HUMANS->value))
            ?? throw new BadRequestHttpException('`scope` is one of humans, robots, all.');
        $metric = AnalyticsAudienceView::HUMANS === $view ? 'visits' : 'views';

        $lines = $this->stats->getBreakdown(
            AnalyticsDimension::COUNTRY,
            AnalyticsPeriod::fromQuery($request->query->get('from'), $request->query->get('to')),
            $view->getAudiences()
        );

        return ChartPointHelper::seriesFromList(
            self::getChartName(),
            $metric,
            array_map(static fn (AnalyticsBreakdown $line): string => $line->getDimensionKey(), $lines),
            array_map(static fn (AnalyticsBreakdown $line): int => 'visits' === $metric ? $line->getVisits() : $line->getViews(), $lines),
            $this->translator->trans(self::METRIC . $metric),
        );
    }
}
