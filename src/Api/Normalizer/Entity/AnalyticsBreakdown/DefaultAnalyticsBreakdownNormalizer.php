<?php

namespace Wexample\SymfonyAnalytics\Api\Normalizer\Entity\AnalyticsBreakdown;

use ArrayObject;
use Wexample\SymfonyAnalytics\Api\Dto\PublicAnalyticsBreakdownDto;
use Wexample\SymfonyAnalytics\Entity\AnalyticsBreakdown;
use Wexample\SymfonyAnalytics\Entity\Traits\Manipulator\AnalyticsBreakdownManipulatorTrait;
use Wexample\SymfonyHelpers\Entity\AbstractEntity;
use Wexample\SymfonyHelpers\Interface\NormalizableDataInterface;
use Wexample\SymfonyHelpers\Normalizer\AbstractEntityNormalizer;

class DefaultAnalyticsBreakdownNormalizer extends AbstractEntityNormalizer
{
    use AnalyticsBreakdownManipulatorTrait;

    public function normalizeEntity(
        AnalyticsBreakdown|AbstractEntity $entity,
        ?string $format = null,
        array $context = []
    ): array|string|int|float|bool|ArrayObject|NormalizableDataInterface|null {
        return PublicAnalyticsBreakdownDto::fromEntity($entity);
    }
}
