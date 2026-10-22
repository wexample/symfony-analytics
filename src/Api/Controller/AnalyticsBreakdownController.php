<?php

namespace Wexample\SymfonyAnalytics\Api\Controller;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\Routing\Attribute\Route;
use Wexample\SymfonyAnalytics\Api\Dto\PublicAnalyticsBreakdownDto;
use Wexample\SymfonyAnalytics\Api\Normalizer\Entity\AnalyticsBreakdown\DefaultAnalyticsBreakdownNormalizer;
use Wexample\SymfonyAnalytics\Class\AnalyticsPeriod;
use Wexample\SymfonyAnalytics\Enum\AnalyticsDimension;
use Wexample\SymfonyAnalytics\Service\AnalyticsAccessService;
use Wexample\SymfonyAnalytics\Service\AnalyticsStatsService;
use Wexample\SymfonyApi\Api\Attribute\ApiResponseData;
use Wexample\SymfonyApi\Api\Attribute\QueryOption\LengthQueryOption;
use Wexample\SymfonyApi\Api\Attribute\QueryOption\PageQueryOption;
use Wexample\SymfonyApi\Api\Attribute\QueryOption\SortQueryOption;
use Wexample\SymfonyApi\Api\Attribute\QueryOption\StringQueryOption;
use Wexample\SymfonyApi\Api\Class\ApiResponse;
use Wexample\SymfonyApi\Api\Controller\AbstractApiController;
use Wexample\SymfonyHelpers\Controller\AbstractController;

/**
 * The audience of a period broken down by a dimension — `dimension`, one of
 * AnalyticsDimension; `total` for the period as one line —, sortable and
 * paged: what a table of it reads. The holders of `access_role` only.
 */
#[Route(path: 'api/analytics-breakdown/', name: 'api_analytics_breakdown_')]
class AnalyticsBreakdownController extends AbstractApiController
{
    final public const string ROUTE_LIST = 'list';

    final public const string QUERY_OPTION_DIMENSION = 'dimension';

    final public const string QUERY_OPTION_FROM = 'from';

    final public const string QUERY_OPTION_TO = 'to';

    #[Route(path: 'list', name: self::ROUTE_LIST, methods: AbstractController::ROUTE_OPTIONS_METHOD_ONLY_GET, options: AbstractController::ROUTE_OPTIONS_ONLY_EXPOSE)]
    #[PageQueryOption]
    #[LengthQueryOption]
    #[SortQueryOption(allowed: ['visits', 'visitors', 'views', 'dimensionKey'], default: '-visits')]
    #[StringQueryOption(key: self::QUERY_OPTION_DIMENSION, default: 'total')]
    #[StringQueryOption(key: self::QUERY_OPTION_FROM, default: '')]
    #[StringQueryOption(key: self::QUERY_OPTION_TO, default: '')]
    #[ApiResponseData(PublicAnalyticsBreakdownDto::class, paginated: true)]
    public function list(
        Request $request,
        AnalyticsAccessService $access,
        AnalyticsStatsService $stats,
        DefaultAnalyticsBreakdownNormalizer $normalizer,
    ): ApiResponse {
        $access->denyUnlessGranted();

        $dimension = AnalyticsDimension::tryFrom((string) self::getQueryOptionValue($request, self::QUERY_OPTION_DIMENSION, 'total'))
            ?? throw new BadRequestHttpException('Unknown `dimension`.');
        $period = AnalyticsPeriod::fromQuery(
            (string) self::getQueryOptionValue($request, self::QUERY_OPTION_FROM, ''),
            (string) self::getQueryOptionValue($request, self::QUERY_OPTION_TO, '')
        );

        [$pagination, $page] = self::applyQueryOptionsToList($request, $stats->getBreakdown($dimension, $period));

        return self::apiResponsePaginated($pagination, $normalizer->normalizeCollection($page));
    }
}
