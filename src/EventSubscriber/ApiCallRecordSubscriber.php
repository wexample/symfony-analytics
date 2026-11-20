<?php

namespace Wexample\SymfonyAnalytics\EventSubscriber;

use DateTimeImmutable;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\Event\TerminateEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Wexample\SymfonyAnalytics\Repository\AnalyticsApiDayRepository;
use Wexample\SymfonyAnalytics\Repository\AnalyticsApiSlotRepository;
use Wexample\SymfonyAnalytics\Service\AnalyticsVisitorService;
use Wexample\SymfonyApi\Security\AbstractApiTokenHandler;

/**
 * Counts a call to the api once its response has left: a request under one
 * of `api_paths` answered with anything but a page, outside
 * `api_excluded_routes` — the probes of its health. A sum a day per route,
 * client, status and cache answer (`api_retention_months`, 0 to leave the
 * api out), with how long it took, and per five minutes for the day's
 * chart.
 *
 * The route is the tool an MCP agent called when symfony-api-mcp says so
 * (`_mcp_tool`), `mcp:<tool>`. The client is the token that signed the
 * call, by its hint; an anonymous caller is a hash of its address and user
 * agent under the day, as a visitor is. The cache answer is what the api's
 * cache says (`_api_cache`: hit or miss).
 */
class ApiCallRecordSubscriber implements EventSubscriberInterface
{
    // Set by symfony-api-mcp on the request of a tool call, read without depending on it.
    public const string REQUEST_ATTRIBUTE_MCP_TOOL = '_mcp_tool';

    // Set by what caches the api's answers: `hit` or `miss`.
    public const string REQUEST_ATTRIBUTE_CACHE = '_api_cache';

    private const string REQUEST_ATTRIBUTE_STARTED = '_analytics_api_started';

    private const string REQUEST_ATTRIBUTE_DURATION = '_analytics_api_duration';

    public function __construct(
        private readonly AnalyticsApiDayRepository $apiDays,
        private readonly AnalyticsApiSlotRepository $apiSlots,
        private readonly AnalyticsVisitorService $visitors,
        private readonly bool $enabled,
        private readonly array $apiPaths,
        private readonly array $apiExcludedRoutes,
        private readonly int $apiRetentionMonths,
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            // Before the firewall, so that a call it refuses is timed too.
            KernelEvents::REQUEST => ['onRequest', 4096],
            KernelEvents::RESPONSE => ['onResponse', -4096],
            KernelEvents::TERMINATE => 'onTerminate',
        ];
    }

    public function onRequest(RequestEvent $event): void
    {
        if ($event->isMainRequest() && $this->isApiCall($event->getRequest())) {
            $event->getRequest()->attributes->set(self::REQUEST_ATTRIBUTE_STARTED, hrtime(true));
        }
    }

    public function onResponse(ResponseEvent $event): void
    {
        $started = $event->getRequest()->attributes->get(self::REQUEST_ATTRIBUTE_STARTED);

        if ($event->isMainRequest() && null !== $started) {
            $event->getRequest()->attributes->set(self::REQUEST_ATTRIBUTE_DURATION, intdiv(hrtime(true) - $started, 1_000_000));
        }
    }

    public function onTerminate(TerminateEvent $event): void
    {
        $request = $event->getRequest();
        $duration = $request->attributes->get(self::REQUEST_ATTRIBUTE_DURATION);

        // A page under the api's path — its documentation — is a page, counted as one.
        if (null === $duration || str_contains((string) $event->getResponse()->headers->get('Content-Type'), 'text/html')) {
            return;
        }

        if (in_array($request->attributes->get('_route'), $this->apiExcludedRoutes, true)) {
            return;
        }

        $now = new DateTimeImmutable();
        $status = $event->getResponse()->getStatusCode();
        $tool = $request->attributes->get(self::REQUEST_ATTRIBUTE_MCP_TOOL);

        $this->apiDays->countCall(
            $now,
            null !== $tool ? 'mcp:' . $tool : (string) ($request->attributes->get('_route') ?? $request->getPathInfo()),
            $request->attributes->get(AbstractApiTokenHandler::REQUEST_ATTRIBUTE_HINT) ?? 'anonymous:' . substr($this->visitors->getVisitorId($request), 0, 8),
            $status,
            (string) $request->attributes->get(self::REQUEST_ATTRIBUTE_CACHE, ''),
            $duration
        );
        $this->apiSlots->countCall($status, $now);
    }

    private function isApiCall(Request $request): bool
    {
        if (! $this->enabled || $this->apiRetentionMonths < 1) {
            return false;
        }

        foreach ($this->apiPaths as $pattern) {
            if (1 === preg_match('#' . $pattern . '#', $request->getPathInfo())) {
                return true;
            }
        }

        return false;
    }
}
