<?php

namespace Wexample\SymfonyAnalytics\EventSubscriber;

use DateTimeImmutable;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\TerminateEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Uid\Uuid;
use Wexample\SymfonyAnalytics\Helper\TrafficOriginHelper;
use Wexample\SymfonyAnalytics\Helper\UserAgentHelper;
use Wexample\SymfonyAnalytics\Repository\AnalyticsRobotDayRepository;
use Wexample\SymfonyAnalytics\Repository\PageViewRepository;
use Wexample\SymfonyAnalytics\Service\AnalyticsVisitorService;
use Wexample\SymfonyGeo\Interface\IpLocatorInterface;

/**
 * Counts a page once its response has left: a page a person loads — a GET
 * answered with HTML —, neither a program's request nor a fragment, nor the
 * visit of an account running the site (`excluded_roles`). Nothing runs in
 * the browser.
 *
 * A robot's page is counted apart, a sum a day per robot: one its user agent
 * names, or a visitor reading faster than `robot_hourly_views` — a crawler
 * dressed as a browser.
 *
 * A page carries on the visit of the visitor's previous one when it is
 * recent enough, taking its origin; otherwise it opens a visit, whose origin
 * its referrer and its `utm_*` say.
 */
class PageViewRecordSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private readonly PageViewRepository $pageViews,
        private readonly AnalyticsRobotDayRepository $robotDays,
        private readonly AnalyticsVisitorService $visitors,
        private readonly Security $security,
        private readonly bool $enabled,
        private readonly array $excludedRoles,
        private readonly int $visitTimeout,
        private readonly int $robotHourlyViews,
        // Absent without a package locating addresses (symfony-remote-db-ip): no country.
        private readonly ?IpLocatorInterface $ipLocator = null,
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [KernelEvents::TERMINATE => 'onTerminate'];
    }

    public function onTerminate(TerminateEvent $event): void
    {
        $request = $event->getRequest();

        if (! $this->enabled || ! $event->isMainRequest() || ! $this->isPageRead($request, $event->getResponse())) {
            return;
        }

        $now = new DateTimeImmutable();
        $userAgent = $request->headers->get('User-Agent');

        if (null !== $robot = UserAgentHelper::getRobot($userAgent)) {
            $this->robotDays->countHit($robot, $now);

            return;
        }

        foreach ($this->excludedRoles as $role) {
            if ($this->security->isGranted($role)) {
                return;
            }
        }

        $visitorId = $this->visitors->getVisitorId($request);

        if ($this->robotHourlyViews > 0
            && $this->pageViews->countOfVisitorSince($visitorId, $now->modify('-1 hour')) >= $this->robotHourlyViews) {
            $this->robotDays->countHit(UserAgentHelper::ROBOT_CADENCE, $now);

            return;
        }

        $previous = $this->pageViews->findLatestOfVisitorSince($visitorId, $now->modify('-' . $this->visitTimeout . ' minutes'));

        $this->pageViews->saveNewPageView(
            $visitorId,
            $previous?->getVisitId() ?? (string) Uuid::v4(),
            $now,
            $request->getPathInfo(),
            $request->attributes->get('_route'),
            $request->getLocale(),
            $previous?->getOrigin() ?? TrafficOriginHelper::read($request->headers->get('Referer'), $request->query->all(), $request->getHost()),
            $this->ipLocator?->locateCountry((string) $request->getClientIp()),
            UserAgentHelper::getDevice($userAgent),
        );
    }

    private function isPageRead(Request $request, Response $response): bool
    {
        $purpose = $request->headers->get('Sec-Purpose') ?? $request->headers->get('Purpose');

        return $request->isMethod(Request::METHOD_GET)
            && $response->isSuccessful()
            && str_contains((string) $response->headers->get('Content-Type'), 'text/html')
            && ! $request->isXmlHttpRequest()
            && ! str_starts_with($request->getPathInfo(), '/_')
            && ! str_contains((string) $purpose, 'prefetch');
    }
}
