<?php

namespace Wexample\SymfonyAnalytics\Entity;

use Symfony\Component\Uid\Uuid;
use Wexample\Pseudocode\Attribute\PseudocodeExport;
use Wexample\SymfonyHelpers\Entity\AbstractEntity;

/**
 * One line of the calls to the api broken down by a dimension over a
 * period: a route, a client, a status… how many calls, how many refused,
 * stopped by a limit, failed, answered by the cache, and how long they took.
 *
 * Computed when asked for, never stored, as AnalyticsBreakdown is: it
 * travels the road of every list — normalizer, dto, generated
 * `AnalyticsApiBreakdown.ts` and its repository. Its id derives from what it
 * is the line of.
 */
#[PseudocodeExport(inherited: true)]
class AnalyticsApiBreakdown extends AbstractEntity
{
    public const string ID_NAMESPACE = '3d1f6a2c-8b4e-4f7a-9c0d-5e2b1a7f6c84';

    protected string $dimension;

    /** What the line is: a route, a client, a status; empty for the total. */
    protected string $dimensionKey;

    protected int $calls;

    /** Refused for the caller's fault: a 4xx but a 429. */
    protected int $clientErrors;

    /** Stopped by a rate limit: a 429. */
    protected int $limited;

    /** Failed on our side: a 5xx. */
    protected int $serverErrors;

    /** Answered by the api's cache. */
    protected int $cached;

    protected int $averageMs;

    protected int $maxMs;

    /** The callers, on the total line only; 0 elsewhere. */
    protected int $clients;

    public function __construct(
        string $dimension,
        string $dimensionKey,
        int $calls,
        int $clientErrors,
        int $limited,
        int $serverErrors,
        int $cached,
        int $averageMs,
        int $maxMs,
        int $clients,
        string $period,
    ) {
        parent::__construct();

        $this->dimension = $dimension;
        $this->dimensionKey = $dimensionKey;
        $this->calls = $calls;
        $this->clientErrors = $clientErrors;
        $this->limited = $limited;
        $this->serverErrors = $serverErrors;
        $this->cached = $cached;
        $this->averageMs = $averageMs;
        $this->maxMs = $maxMs;
        $this->clients = $clients;

        $this->setId(Uuid::v5(Uuid::fromString(self::ID_NAMESPACE), implode("\0", [$dimension, $dimensionKey, $period])));
    }

    public function getDimension(): string
    {
        return $this->dimension;
    }

    public function getDimensionKey(): string
    {
        return $this->dimensionKey;
    }

    public function getCalls(): int
    {
        return $this->calls;
    }

    public function getClientErrors(): int
    {
        return $this->clientErrors;
    }

    public function getLimited(): int
    {
        return $this->limited;
    }

    public function getServerErrors(): int
    {
        return $this->serverErrors;
    }

    public function getCached(): int
    {
        return $this->cached;
    }

    public function getAverageMs(): int
    {
        return $this->averageMs;
    }

    public function getMaxMs(): int
    {
        return $this->maxMs;
    }

    public function getClients(): int
    {
        return $this->clients;
    }
}
