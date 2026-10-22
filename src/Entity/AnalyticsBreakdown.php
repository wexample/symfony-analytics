<?php

namespace Wexample\SymfonyAnalytics\Entity;

use Symfony\Component\Uid\Uuid;
use Wexample\Pseudocode\Attribute\PseudocodeExport;
use Wexample\SymfonyHelpers\Entity\AbstractEntity;

/**
 * One line of the audience broken down by a dimension over a period: a page,
 * a source, a country… and how many visitors, visits and pages it counts.
 *
 * Computed when asked for, never stored — no `#[ORM\Entity]` —, so that it
 * travels the road every list of the stack travels: normalizer, dto,
 * generated `AnalyticsBreakdown.ts` and its repository, which the tables of
 * symfony-analytics-ds read. Its id derives from what it is the line of.
 */
#[PseudocodeExport(inherited: true)]
class AnalyticsBreakdown extends AbstractEntity
{
    public const string ID_NAMESPACE = 'b6f0b0f4-2d8e-4c55-9a6e-7c0f3e1d5a21';

    protected string $dimension;

    /** What the line is: a path, a source, a country code; empty for the total. */
    protected string $dimensionKey;

    protected int $visitors;

    protected int $visits;

    protected int $views;

    public function __construct(
        string $dimension,
        string $dimensionKey,
        int $visitors,
        int $visits,
        int $views,
        string $period,
    ) {
        parent::__construct();

        $this->dimension = $dimension;
        $this->dimensionKey = $dimensionKey;
        $this->visitors = $visitors;
        $this->visits = $visits;
        $this->views = $views;

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

    public function getVisitors(): int
    {
        return $this->visitors;
    }

    public function getVisits(): int
    {
        return $this->visits;
    }

    public function getViews(): int
    {
        return $this->views;
    }
}
