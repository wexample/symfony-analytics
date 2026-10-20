<?php

namespace Wexample\SymfonyAnalytics\Enum;

/**
 * What the audience is broken down by, each the column of a page viewed it
 * is read from. `total` is the whole period, one row.
 */
enum AnalyticsDimension: string
{
    case TOTAL = 'total';

    case PAGE = 'page';

    case SOURCE = 'source';

    case REFERRER = 'referrer';

    case SEARCH_ENGINE = 'search_engine';

    case KEYWORD = 'keyword';

    case CAMPAIGN = 'campaign';

    case COUNTRY = 'country';

    case DEVICE = 'device';

    /**
     * The column of `analytics_page_view` its key is, null for the total.
     */
    public function getColumn(): ?string
    {
        return match ($this) {
            self::TOTAL => null,
            self::PAGE => 'path',
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
