<?php

namespace Wexample\SymfonyAnalytics\Enum;

/**
 * What the audience is broken down by, each the column of a page viewed it
 * is read from. `total` is the whole period, one row; `robot` the robots by
 * name — a declared one — or by the reason it was caught — a suspected one.
 * A declared robot is summed up per robot, route and country only: the
 * other dimensions see the people and the suspected robots.
 */
enum AnalyticsDimension: string
{
    case TOTAL = 'total';

    case PAGE = 'page';

    // The pages by what draws them rather than by their address: one line for
    // a page whatever its language or its parameters.
    case ROUTE = 'route';

    case SOURCE = 'source';

    case REFERRER = 'referrer';

    case SEARCH_ENGINE = 'search_engine';

    case KEYWORD = 'keyword';

    case CAMPAIGN = 'campaign';

    case COUNTRY = 'country';

    case DEVICE = 'device';

    case ROBOT = 'robot';

    /**
     * The column of `analytics_page_view` its key is, null for the total.
     * `robot` and `route` are columns of `analytics_robot_day` too.
     */
    public function getColumn(): ?string
    {
        return match ($this) {
            self::TOTAL => null,
            self::ROBOT => 'robot',
            self::PAGE => 'path',
            self::ROUTE => 'route',
            self::SOURCE => 'source',
            self::REFERRER => 'referrer_host',
            self::SEARCH_ENGINE => 'search_engine',
            self::KEYWORD => 'keyword',
            self::CAMPAIGN => 'campaign',
            self::COUNTRY => 'country',
            self::DEVICE => 'device',
        };
    }
}
