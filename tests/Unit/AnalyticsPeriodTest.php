<?php

namespace Wexample\SymfonyAnalytics\Tests\Unit;

use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Wexample\SymfonyAnalytics\Class\AnalyticsPeriod;

class AnalyticsPeriodTest extends TestCase
{
    public function testTheLastThirtyDaysByDefault(): void
    {
        $period = AnalyticsPeriod::fromQuery(null, null);

        $this->assertSame(30, (int) $period->from->diff($period->to)->format('%a'));
        $this->assertTrue($period->isDaily());
    }

    public function testAYearIsReadMonthByMonth(): void
    {
        $this->assertFalse(AnalyticsPeriod::fromQuery('2026-01-01', '2027-01-01')->isDaily());
    }

    public function testABadPeriodIsRefused(): void
    {
        foreach ([['2026-13-01', '2026-12-01'], ['2026-10-02', '2026-10-01'], ['2026-10-01', null]] as [$from, $to]) {
            try {
                AnalyticsPeriod::fromQuery($from, $to);
                $this->fail(sprintf('%s → %s was taken.', $from, $to));
            } catch (BadRequestHttpException) {
                $this->addToAssertionCount(1);
            }
        }
    }
}
