<?php

namespace Wexample\SymfonyAnalytics\EventSubscriber;

use Psr\Cache\CacheItemPoolInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\TerminateEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Wexample\SymfonyAnalytics\Service\AnalyticsMaintenanceService;

/**
 * Maintains the audience without a scheduler: once a day, after a response
 * has left. The day is marked first, so two workers do not both run it.
 */
class AnalyticsMaintenanceSubscriber implements EventSubscriberInterface
{
    private const string CACHE_KEY = 'wexample_symfony_analytics.maintained';

    public function __construct(
        private readonly AnalyticsMaintenanceService $maintenance,
        private readonly CacheItemPoolInterface $cache,
        private readonly bool $autoMaintenance,
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [KernelEvents::TERMINATE => ['onTerminate', -1024]];
    }

    public function onTerminate(TerminateEvent $event): void
    {
        if (! $this->autoMaintenance || ! $event->isMainRequest()) {
            return;
        }

        $maintained = $this->cache->getItem(self::CACHE_KEY);

        if ($maintained->isHit()) {
            return;
        }

        $this->cache->save($maintained->set(true)->expiresAfter(86400));
        $this->maintenance->maintain();
    }
}
