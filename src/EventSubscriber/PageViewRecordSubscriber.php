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
use Wexample\SymfonyAnalytics\Repository\AnalyticsRobotSlotRepository;
use Wexample\SymfonyAnalytics\Repository\PageViewRepository;
use Wexample\SymfonyAnalytics\Service\AnalyticsVisitorService;
use Wexample\SymfonyGeo\Interface\IpLocatorInterface;

/**
 * Counts a page once its response has left: a page a person loads — a GET
 * answered with HTML —, neither a program's request nor a fragment, nor the
 * visit of an account running the site (`excluded_roles`). Nothing runs in
 * the browser.
 *
 * A robot naming itself is counted apart, a sum a day per robot, route and country
 * (`robot_retention_months`, 0 to leave it out), and per five minutes for
 * the day's chart. A browser's user agent
 * reading as no person does — without a browser's headers, faster than
 * `robot_hourly_views` or `robot_minute_views` — is kept as a page viewed,
 * marked as a suspected robot, and so are the pages it read before that day:
 * a visitor is someone else the next day.
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
        private readonly AnalyticsRobotSlotRepository $robotSlots,
        private readonly AnalyticsVisitorService $visitors,
        private readonly Security $security,
        private readonly bool $enabled,
        private readonly array $excludedRoles,
        private readonly int $visitTimeout,
        private readonly int $robotHourlyViews,
        private readonly int $robotMinuteViews,
        private readonly int $robotRetentionMonths,
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
            if ($this->robotRetentionMonths > 0) {
                $this->robotDays->countHit(
                    $robot,
                    (string) $request->attributes->get('_route'),
                    (string) $this->ipLocator?->locateCountry((string) $request->getClientIp()),
                    $now
                );
                $this->robotSlots->countHit($robot, $now);
            }

            return;
        }

        foreach ($this->excludedRoles as $role) {
            if ($this->security->isGranted($role)) {
                return;
            }
        }

        $visitorId = $this->visitors->getVisitorId($request);
        $robot = $this->pageViews->findRobotOfVisitor($visitorId);

        if (null === $robot && null !== $robot = $this->getSuspectRobot($request, $visitorId, $now)) {
            $this->pageViews->markVisitorRobot($visitorId, $robot);
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
            $robot,
        );
    }

    /**
     * Why a browser's user agent is a robot's, null while it reads as a
     * person: the headers it sends, then how fast it reads.
     */
    private function getSuspectRobot(Request $request, string $visitorId, DateTimeImmutable $now): ?string
    {
        $headers = UserAgentHelper::getHeadersRobot(
            $request->headers->get('User-Agent'),
            $request->headers->get('Accept-Language'),
            $request->headers->get('Sec-Fetch-Mode'),
        );

        if (null !== $headers) {
            return $headers;
        }

        if ($this->robotMinuteViews > 0
            && $this->pageViews->countOfVisitorSince($visitorId, $now->modify('-1 minute')) >= $this->robotMinuteViews) {
            return UserAgentHelper::ROBOT_BURST;
        }

        if ($this->robotHourlyViews > 0
            && $this->pageViews->countOfVisitorSince($visitorId, $now->modify('-1 hour')) >= $this->robotHourlyViews) {
            return UserAgentHelper::ROBOT_CADENCE;
        }

        return null;
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
