<?php

namespace Wexample\SymfonyAnalytics\Api\Dto;

use Wexample\SymfonyAnalytics\Entity\AnalyticsApiBreakdown;
use Wexample\SymfonyApi\Api\Dto\AbstractEntityDto;
use Wexample\SymfonyHelpers\Entity\AbstractEntity;

/**
 * A line of the api's calls as it goes on the wire: what it is the line of,
 * and its counts.
 */
class PublicAnalyticsApiBreakdownDto extends AbstractEntityDto
{
    public string $dimension;

    public string $dimensionKey;

    public int $calls;

    public int $clientErrors;

    public int $limited;

    public int $serverErrors;

    public int $cached;

    public int $averageMs;

    public int $maxMs;

    public int $clients;

    /**
     * @param AnalyticsApiBreakdown $entity
     */
    public static function fromEntity(AbstractEntity $entity): self
    {
        $dto = parent::fromEntity($entity);

        $dto->dimension = $entity->getDimension();
        $dto->dimensionKey = $entity->getDimensionKey();
        $dto->calls = $entity->getCalls();
        $dto->clientErrors = $entity->getClientErrors();
        $dto->limited = $entity->getLimited();
        $dto->serverErrors = $entity->getServerErrors();
        $dto->cached = $entity->getCached();
        $dto->averageMs = $entity->getAverageMs();
        $dto->maxMs = $entity->getMaxMs();
        $dto->clients = $entity->getClients();

        return $dto;
    }
}
