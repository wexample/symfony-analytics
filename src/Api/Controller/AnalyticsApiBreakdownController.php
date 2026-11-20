<?php

namespace Wexample\SymfonyAnalytics\Api\Controller;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\Routing\Attribute\Route;
use Wexample\SymfonyAnalytics\Api\Dto\PublicAnalyticsApiBreakdownDto;
use Wexample\SymfonyAnalytics\Api\Normalizer\Entity\AnalyticsApiBreakdown\DefaultAnalyticsApiBreakdownNormalizer;
use Wexample\SymfonyAnalytics\Class\AnalyticsPeriod;
use Wexample\SymfonyAnalytics\Enum\AnalyticsApiDimension;
use Wexample\SymfonyAnalytics\Service\AnalyticsAccessService;
use Wexample\SymfonyAnalytics\Service\AnalyticsApiStatsService;
use Wexample\SymfonyApi\Api\Attribute\ApiAccess;
use Wexample\SymfonyApi\Api\Attribute\ApiResponseData;
use Wexample\SymfonyApi\Api\Attribute\QueryOption\LengthQueryOption;
use Wexample\SymfonyApi\Api\Attribute\QueryOption\PageQueryOption;
use Wexample\SymfonyApi\Api\Attribute\QueryOption\SortQueryOption;
use Wexample\SymfonyApi\Api\Attribute\QueryOption\StringQueryOption;
use Wexample\SymfonyApi\Api\Class\ApiResponse;
use Wexample\SymfonyApi\Api\Controller\AbstractApiController;
use Wexample\SymfonyHelpers\Controller\AbstractController;

/**
 * The calls to the api over a period broken down by a dimension —
 * `dimension`, one of AnalyticsApiDimension; `total` for the period as one
 * line —, sortable and paged: what a table of it reads. The holders of
 * `access_role` only.
 */
#[Route(path: '_api/analytics-api-breakdown/', name: 'api_analytics_api_breakdown_')]
#[ApiAccess(ApiAccess::CONTROLLER, "wexample_symfony_analytics.access_role, checked by AnalyticsAccessService")]
class AnalyticsApiBreakdownController extends AbstractApiController
{
    final public const string ROUTE_LIST = 'list';

    final public const string QUERY_OPTION_DIMENSION = 'dimension';

    final public const string QUERY_OPTION_FROM = 'from';

    final public const string QUERY_OPTION_TO = 'to';

    #[Route(path: 'list', name: self::ROUTE_LIST, methods: AbstractController::ROUTE_OPTIONS_METHOD_ONLY_GET, options: AbstractController::ROUTE_OPTIONS_ONLY_EXPOSE)]
    #[PageQueryOption]
    #[LengthQueryOption]
    #[SortQueryOption(allowed: ['calls', 'clientErrors', 'limited', 'serverErrors', 'cached', 'averageMs', 'maxMs', 'dimensionKey'], default: '-calls')]
    #[StringQueryOption(key: self::QUERY_OPTION_DIMENSION, default: 'total')]
    #[StringQueryOption(key: self::QUERY_OPTION_FROM, default: '')]
    #[StringQueryOption(key: self::QUERY_OPTION_TO, default: '')]
    #[ApiResponseData(PublicAnalyticsApiBreakdownDto::class, paginated: true)]
    public function list(
        Request $request,
        AnalyticsAccessService $access,
        AnalyticsApiStatsService $stats,
        DefaultAnalyticsApiBreakdownNormalizer $normalizer,
    ): ApiResponse {
        $access->denyUnlessGranted();

        $dimension = AnalyticsApiDimension::tryFrom((string) self::getQueryOptionValue($request, self::QUERY_OPTION_DIMENSION, 'total'))
            ?? throw new BadRequestHttpException('Unknown `dimension`.');
        $period = AnalyticsPeriod::fromQuery(
            (string) self::getQueryOptionValue($request, self::QUERY_OPTION_FROM, ''),
            (string) self::getQueryOptionValue($request, self::QUERY_OPTION_TO, '')
        );

        [$pagination, $page] = self::applyQueryOptionsToList($request, $stats->getBreakdown($dimension, $period));

        return self::apiResponsePaginated($pagination, $normalizer->normalizeCollection($page));
    }
}
