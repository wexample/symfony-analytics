<?php

namespace Wexample\SymfonyAnalytics\Entity\Traits\Manipulator;

use Wexample\SymfonyAnalytics\Entity\AnalyticsBreakdown;
use Wexample\SymfonyHelpers\Entity\Traits\Manipulator\EntityManipulatorTrait;

trait AnalyticsBreakdownManipulatorTrait
{
    use EntityManipulatorTrait;

    public static function getEntityClassName(): string
    {
        return AnalyticsBreakdown::class;
    }
}
