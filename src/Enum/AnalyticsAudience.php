<?php

namespace Wexample\SymfonyAnalytics\Enum;

/**
 * Who read a page, as the recording tells them apart. A person and a
 * suspected robot leave a page viewed each (`analytics_page_view`, the
 * suspect with the reason it was caught); a declared robot, by far the most
 * pages, a sum a day per robot and route (`analytics_robot_day`).
 */
enum AnalyticsAudience: string
{
    case HUMAN = 'human';

    // A browser's user agent, a robot's behaviour or headers.
    case SUSPECT = 'suspect';

    // A robot by its own user agent.
    case DECLARED = 'declared';

    /**
     * Whether its pages are kept one by one, with a visitor and a visit.
     */
    public function hasPageViews(): bool
    {
        return self::DECLARED !== $this;
    }
}
