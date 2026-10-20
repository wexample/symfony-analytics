<?php

namespace Wexample\SymfonyAnalytics\Entity;

use DateTimeImmutable;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Wexample\SymfonyAnalytics\Enum\AnalyticsDimension;
use Wexample\SymfonyAnalytics\Repository\AnalyticsMonthlyRepository;
use Wexample\SymfonyHelpers\Entity\AbstractEntity;

/**
 * A finished month, summed up per dimension: what is left of the audience
 * once its pages viewed are past `retention_months` and deleted. Visitors are
 * the month's own, which months added together count twice over.
 */
#[ORM\Entity(repositoryClass: AnalyticsMonthlyRepository::class)]
#[ORM\Table(name: 'analytics_monthly')]
#[ORM\UniqueConstraint(name: 'analytics_monthly_key', columns: ['month', 'dimension', 'dimension_key'])]
class AnalyticsMonthly extends AbstractEntity
{
    /** Its first day. */
    #[ORM\Column(type: Types::DATE_IMMUTABLE)]
    private DateTimeImmutable $month;

    #[ORM\Column(type: Types::STRING, length: 32, enumType: AnalyticsDimension::class)]
    private AnalyticsDimension $dimension;

    /** Empty for the total. */
    #[ORM\Column(type: Types::STRING, length: 255)]
    private string $dimensionKey;

    #[ORM\Column(type: Types::INTEGER)]
    private int $visitors;

    #[ORM\Column(type: Types::INTEGER)]
    private int $visits;

    #[ORM\Column(type: Types::INTEGER)]
    private int $views;

    public function __construct(
        DateTimeImmutable $month,
        AnalyticsDimension $dimension,
        string $dimensionKey,
        int $visitors,
        int $visits,
        int $views,
    ) {
        parent::__construct();

        $this->month = $month;
        $this->dimension = $dimension;
        $this->dimensionKey = $dimensionKey;
        $this->visitors = $visitors;
        $this->visits = $visits;
        $this->views = $views;
    }

    public function getMonth(): DateTimeImmutable
    {
        return $this->month;
    }

    public function getDimension(): AnalyticsDimension
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
