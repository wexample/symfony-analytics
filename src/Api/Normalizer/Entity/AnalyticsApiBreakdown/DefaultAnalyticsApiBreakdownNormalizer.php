<?php

namespace Wexample\SymfonyAnalytics\Api\Normalizer\Entity\AnalyticsApiBreakdown;

use ArrayObject;
use Wexample\SymfonyAnalytics\Api\Dto\PublicAnalyticsApiBreakdownDto;
use Wexample\SymfonyAnalytics\Entity\AnalyticsApiBreakdown;
use Wexample\SymfonyAnalytics\Entity\Traits\Manipulator\AnalyticsApiBreakdownManipulatorTrait;
use Wexample\SymfonyHelpers\Entity\AbstractEntity;
use Wexample\SymfonyHelpers\Interface\NormalizableDataInterface;
use Wexample\SymfonyHelpers\Normalizer\AbstractEntityNormalizer;

class DefaultAnalyticsApiBreakdownNormalizer extends AbstractEntityNormalizer
{
    use AnalyticsApiBreakdownManipulatorTrait;

    public function normalizeEntity(
        AnalyticsApiBreakdown|AbstractEntity $entity,
        ?string $format = null,
        array $context = []
    ): array|string|int|float|bool|ArrayObject|NormalizableDataInterface|null {
        return PublicAnalyticsApiBreakdownDto::fromEntity($entity);
    }
}
