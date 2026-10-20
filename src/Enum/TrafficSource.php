<?php

namespace Wexample\SymfonyAnalytics\Enum;

/**
 * Where a visit comes from, as its first page tells.
 */
enum TrafficSource: string
{
    /** Typed, bookmarked, or sent from somewhere that says nothing. */
    case DIRECT = 'direct';

    case SEARCH = 'search';

    case SOCIAL = 'social';

    /** An assistant answering with a link: ChatGPT, Perplexity, Claude… */
    case AI = 'ai';

    /** Another site linking here. */
    case REFERRAL = 'referral';

    /** A link carrying `utm_*` parameters, from nowhere else above. */
    case CAMPAIGN = 'campaign';
}
