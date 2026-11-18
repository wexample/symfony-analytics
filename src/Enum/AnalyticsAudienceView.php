<?php

namespace Wexample\SymfonyAnalytics\Enum;

/**
 * Which audiences a reading adds up: the people, the robots — suspected and
 * declared —, or everything that read the site, the raw traffic.
 */
enum AnalyticsAudienceView: string
{
    case HUMANS = 'humans';

    case ROBOTS = 'robots';

    case ALL = 'all';

    /**
     * @return list<AnalyticsAudience>
     */
    public function getAudiences(): array
    {
        return match ($this) {
            self::HUMANS => [AnalyticsAudience::HUMAN],
            self::ROBOTS => [AnalyticsAudience::SUSPECT, AnalyticsAudience::DECLARED],
            self::ALL => AnalyticsAudience::cases(),
        };
    }
}
