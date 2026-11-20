<?php

namespace Wexample\SymfonyAnalytics\Entity\Traits\Manipulator;

use Wexample\SymfonyAnalytics\Entity\AnalyticsApiBreakdown;
use Wexample\SymfonyHelpers\Entity\Traits\Manipulator\EntityManipulatorTrait;

trait AnalyticsApiBreakdownManipulatorTrait
{
    use EntityManipulatorTrait;

    public static function getEntityClassName(): string
    {
        return AnalyticsApiBreakdown::class;
    }
}
