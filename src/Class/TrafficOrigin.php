<?php

namespace Wexample\SymfonyAnalytics\Class;

use Wexample\SymfonyAnalytics\Enum\TrafficSource;

/**
 * Where a visit comes from, read from its first request: the kind of source,
 * the site it left, and what a search engine or a campaign says of it.
 */
final readonly class TrafficOrigin
{
    public function __construct(
        public TrafficSource $source,
        public ?string $referrerHost = null,
        public ?string $searchEngine = null,
        public ?string $keyword = null,
        public ?string $campaign = null,
    ) {
    }
}
