<?php

namespace Wexample\SymfonyAnalytics\Service\ChartPoint;

use DateTimeImmutable;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Contracts\Translation\TranslatorInterface;
use Wexample\SymfonyAnalytics\Enum\AnalyticsAudience;
use Wexample\SymfonyAnalytics\Service\AnalyticsAccessService;
use Wexample\SymfonyAnalytics\Service\AnalyticsTodayService;
use Wexample\SymfonyCharts\Helper\ChartPointHelper;
use Wexample\SymfonyCharts\Interface\ChartPointProviderInterface;

/**
 * Every page read over the last hours, a few minutes per bar, a series per
 * audience to stack: what is going on now. Its `scope` is the window, `24h`
 * by default or `2h`, whatever the period of the page.
 */
class AnalyticsTodayChartPointProvider implements ChartPointProviderInterface
{
    private const string DOMAIN = 'WexampleSymfonyAnalyticsBundle.common.analytics::audience.';

    public function __construct(
        private readonly AnalyticsTodayService $today,
        private readonly AnalyticsAccessService $access,
        private readonly TranslatorInterface $translator,
    ) {
    }

    public static function getChartName(): string
    {
        return 'analytics-today';
    }

    public function getPoints(Request $request): iterable
    {
        $this->access->denyUnlessGranted();

        $hours = (int) rtrim((string) ($request->query->get('scope') ?: '24h'), 'h');

        if (! isset(AnalyticsTodayService::WINDOWS[$hours])) {
            throw new BadRequestHttpException('`scope` is one of 24h, 2h.');
        }

        $series = $this->today->getSeries($hours);
        $categories = array_map(static fn (string $slot): string => new DateTimeImmutable($slot)->format('H:i'), array_keys($series));
        $points = [];

        foreach (AnalyticsAudience::cases() as $audience) {
            $points = [...$points, ...ChartPointHelper::seriesFromList(
                self::getChartName(),
                $audience->value,
                $categories,
                array_column($series, $audience->value),
                $this->translator->trans(self::DOMAIN . $audience->value),
                $audience->getColor()
            )];
        }

        return $points;
    }
}
