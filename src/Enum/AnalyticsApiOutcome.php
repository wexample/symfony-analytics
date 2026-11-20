<?php

namespace Wexample\SymfonyAnalytics\Enum;

/**
 * How a call to the api ended, read from its status: answered, refused for
 * the caller's fault, stopped by a rate limit, or failed on our side.
 */
enum AnalyticsApiOutcome: string
{
    case OK = 'ok';

    case CLIENT_ERROR = 'client_error';

    case LIMITED = 'limited';

    case SERVER_ERROR = 'server_error';

    public static function fromStatus(int $status): self
    {
        return match (true) {
            429 === $status => self::LIMITED,
            $status >= 500 => self::SERVER_ERROR,
            $status >= 400 => self::CLIENT_ERROR,
            default => self::OK,
        };
    }

    /**
     * Its category of the charts' palette.
     */
    public function getColor(): string
    {
        return match ($this) {
            self::OK => 'mint',
            self::CLIENT_ERROR => 'sunflower',
            self::LIMITED => 'lavender',
            self::SERVER_ERROR => 'ruby',
        };
    }

    /**
     * The SQL condition on a `status` column its calls meet.
     */
    public function getCondition(): string
    {
        return match ($this) {
            self::OK => 'status < 400',
            self::CLIENT_ERROR => 'status >= 400 AND status < 500 AND status <> 429',
            self::LIMITED => 'status = 429',
            self::SERVER_ERROR => 'status >= 500',
        };
    }
}
