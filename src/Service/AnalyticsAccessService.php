<?php

namespace Wexample\SymfonyAnalytics\Service;

use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

/**
 * Who reads the audience: the holders of `access_role`.
 */
class AnalyticsAccessService
{
    public function __construct(
        private readonly Security $security,
        private readonly string $accessRole,
    ) {
    }

    public function getAccessRole(): string
    {
        return $this->accessRole;
    }

    /**
     * @throws AccessDeniedException for anyone else
     */
    public function denyUnlessGranted(): void
    {
        if (! $this->security->isGranted($this->accessRole)) {
            throw new AccessDeniedException();
        }
    }
}
