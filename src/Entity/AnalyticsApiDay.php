<?php

namespace Wexample\SymfonyAnalytics\Entity;

use DateTimeImmutable;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Wexample\SymfonyAnalytics\Repository\AnalyticsApiDayRepository;
use Wexample\SymfonyHelpers\Entity\AbstractEntity;

/**
 * The calls to the api in a day, per route, client, status and cache
 * answer: how many, and how long they took. Programs, not readers: counted
 * apart from the audience, kept `api_retention_months`.
 */
#[ORM\Entity(repositoryClass: AnalyticsApiDayRepository::class)]
#[ORM\Table(name: 'analytics_api_day')]
#[ORM\UniqueConstraint(name: 'analytics_api_day_key', columns: ['day', 'route', 'client', 'status', 'cache'])]
class AnalyticsApiDay extends AbstractEntity
{
    #[ORM\Column(type: Types::DATE_IMMUTABLE)]
    private DateTimeImmutable $day;

    #[ORM\Column(type: Types::STRING, length: 255)]
    private string $route;

    #[ORM\Column(type: Types::STRING, length: 64)]
    private string $client;

    #[ORM\Column(type: Types::SMALLINT)]
    private int $status;

    #[ORM\Column(type: Types::STRING, length: 8, options: ['default' => ''])]
    private string $cache;

    #[ORM\Column(type: Types::INTEGER)]
    private int $calls;

    /** The milliseconds of all its calls together. */
    #[ORM\Column(type: Types::BIGINT)]
    private string $durationMs;

    /** The milliseconds of its longest call. */
    #[ORM\Column(type: Types::INTEGER)]
    private int $durationMaxMs;

    public function __construct(
        DateTimeImmutable $day,
        string $route,
        string $client,
        int $status,
        string $cache,
        int $calls,
        int $durationMs,
        int $durationMaxMs,
    ) {
        parent::__construct();

        $this->day = $day;
        $this->route = $route;
        $this->client = $client;
        $this->status = $status;
        $this->cache = $cache;
        $this->calls = $calls;
        $this->durationMs = (string) $durationMs;
        $this->durationMaxMs = $durationMaxMs;
    }

    public function getDay(): DateTimeImmutable
    {
        return $this->day;
    }

    public function getRoute(): string
    {
        return $this->route;
    }

    public function getClient(): string
    {
        return $this->client;
    }

    public function getStatus(): int
    {
        return $this->status;
    }

    public function getCache(): string
    {
        return $this->cache;
    }

    public function getCalls(): int
    {
        return $this->calls;
    }

    public function getDurationMs(): int
    {
        return (int) $this->durationMs;
    }

    public function getDurationMaxMs(): int
    {
        return $this->durationMaxMs;
    }
}
