<?php

namespace Wexample\SymfonyAnalytics\Service;

use Symfony\Component\HttpFoundation\Request;

/**
 * Who a visitor is, without knowing them: a hash of their address and their
 * browser under the kernel secret and the day. Nothing reversible is kept,
 * nothing is left on their machine, and tomorrow they are someone else.
 */
class AnalyticsVisitorService
{
    public function __construct(
        private readonly string $secret,
    ) {
    }

    public function getVisitorId(Request $request): string
    {
        return substr(hash_hmac(
            'sha256',
            implode('|', [date('Y-m-d'), (string) $request->getClientIp(), (string) $request->headers->get('User-Agent')]),
            $this->secret
        ), 0, 32);
    }
}
