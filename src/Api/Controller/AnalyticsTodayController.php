<?php

namespace Wexample\SymfonyAnalytics\Api\Controller;

use Symfony\Component\Routing\Attribute\Route;
use Wexample\SymfonyAnalytics\Service\AnalyticsAccessService;
use Wexample\SymfonyAnalytics\Service\AnalyticsTodayService;
use Wexample\SymfonyApi\Api\Class\ApiResponse;
use Wexample\SymfonyApi\Api\Controller\AbstractApiController;
use Wexample\SymfonyHelpers\Controller\AbstractController;

/**
 * What is going on in the site now (AnalyticsTodayService::summarize()), for
 * a page that reads it again while it is open. The holders of `access_role`
 * only.
 */
#[Route(path: '_api/analytics-today/', name: 'api_analytics_today_')]
class AnalyticsTodayController extends AbstractApiController
{
    final public const string ROUTE_SUMMARY = 'summary';

    #[Route(path: self::ROUTE_SUMMARY, name: self::ROUTE_SUMMARY, methods: AbstractController::ROUTE_OPTIONS_METHOD_ONLY_GET, options: AbstractController::ROUTE_OPTIONS_ONLY_EXPOSE)]
    public function summary(AnalyticsAccessService $access, AnalyticsTodayService $today): ApiResponse
    {
        $access->denyUnlessGranted();

        return self::apiResponseSuccess(data: $today->summarize());
    }
}
