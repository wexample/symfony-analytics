<?php

namespace Wexample\SymfonyAnalytics\Api\Dto;

use Wexample\SymfonyAnalytics\Entity\AnalyticsBreakdown;
use Wexample\SymfonyApi\Api\Dto\AbstractEntityDto;
use Wexample\SymfonyHelpers\Entity\AbstractEntity;

/**
 * A line of a breakdown as it goes on the wire: what it is the line of, and
 * its counts.
 */
class PublicAnalyticsBreakdownDto extends AbstractEntityDto
{
    public string $dimension;

    public string $dimensionKey;

    public int $visitors;

    public int $visits;

    public int $views;

    /**
     * @param AnalyticsBreakdown $entity
     */
    public static function fromEntity(AbstractEntity $entity): self
    {
        $dto = parent::fromEntity($entity);

        $dto->dimension = $entity->getDimension();
        $dto->dimensionKey = $entity->getDimensionKey();
        $dto->visitors = $entity->getVisitors();
        $dto->visits = $entity->getVisits();
        $dto->views = $entity->getViews();

        return $dto;
    }
}
