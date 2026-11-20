<?php

namespace Wexample\SymfonyAnalytics\Enum;

/**
 * What the api's calls are broken down by, each a column of
 * `analytics_api_day`; `total` is the whole period, one row.
 */
enum AnalyticsApiDimension: string
{
    case TOTAL = 'total';

    // The route called, or `mcp:<tool>` for a tool an MCP agent called.
    case ROUTE = 'route';

    // The token that signed the call, by its hint, or an anonymous caller by
    // a hash of its address and user agent under the day.
    case CLIENT = 'client';

    case STATUS = 'status';

    // `hit` or `miss` where the api's cache answered or was asked, empty otherwise.
    case CACHE = 'cache';

    public function getColumn(): ?string
    {
        return self::TOTAL === $this ? null : $this->value;
    }
}
