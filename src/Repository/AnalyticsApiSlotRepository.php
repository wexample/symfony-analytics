<?php

namespace Wexample\SymfonyAnalytics\Repository;

use DateTimeImmutable;
use Symfony\Component\Uid\Uuid;
use Wexample\SymfonyAnalytics\Entity\AnalyticsApiSlot;
use Wexample\SymfonyAnalytics\Enum\AnalyticsApiOutcome;
use Wexample\SymfonyHelpers\Repository\AbstractRepository;

/**
 * The calls to the api, a sum per five minutes and status, for the day's
 * chart.
 *
 * @method AnalyticsApiSlot saveNewAnalyticsApiSlot(DateTimeImmutable $slot, int $status, int $calls)
 */
class AnalyticsApiSlotRepository extends AbstractRepository
{
    private const string TABLE = 'analytics_api_slot';

    private const string DATE_FORMAT = 'Y-m-d H:i:s';

    public static function getEntityClassName(): string
    {
        return AnalyticsApiSlot::class;
    }

    public function createNewAnalyticsApiSlot(DateTimeImmutable $slot, int $status, int $calls): AnalyticsApiSlot
    {
        return new AnalyticsApiSlot($slot, $status, $calls);
    }

    public function countCall(int $status, DateTimeImmutable $at): void
    {
        $this->getEntityManager()->getConnection()->executeStatement(
            'INSERT INTO ' . self::TABLE . ' (id, slot, status, calls) VALUES (:id, :slot, :status, 1)'
            . ' ON CONFLICT (slot, status) DO UPDATE SET calls = ' . self::TABLE . '.calls + 1',
            ['id' => (string) Uuid::v7(), 'slot' => AnalyticsRobotSlotRepository::getSlotOf($at)->format(self::DATE_FORMAT), 'status' => $status]
        );
    }

    /**
     * @return array<string, array<string, int>> per five minutes (`Y-m-d H:i`,
     *                                           its first minute), the calls
     *                                           per AnalyticsApiOutcome
     */
    public function countPerSlot(DateTimeImmutable $from, DateTimeImmutable $to): array
    {
        // The date written out and cut: the same SQL on PostgreSQL and SQLite.
        $slot = 'SUBSTR(CAST(slot AS VARCHAR(19)), 1, 16)';
        $rows = $this->getEntityManager()->getConnection()->fetchAllAssociative(
            'SELECT ' . $slot . ' AS slot, status, SUM(calls) AS calls FROM ' . self::TABLE
            . ' WHERE slot >= :from AND slot < :to GROUP BY ' . $slot . ', status',
            ['from' => $from->format(self::DATE_FORMAT), 'to' => $to->format(self::DATE_FORMAT)]
        );

        $slots = [];

        foreach ($rows as $row) {
            $outcome = AnalyticsApiOutcome::fromStatus((int) $row['status'])->value;
            $slots[$row['slot']][$outcome] = ($slots[$row['slot']][$outcome] ?? 0) + (int) $row['calls'];
        }

        return $slots;
    }

    public function deleteBefore(DateTimeImmutable $date): int
    {
        return (int) $this->getEntityManager()->getConnection()->executeStatement(
            'DELETE FROM ' . self::TABLE . ' WHERE slot < :date',
            ['date' => $date->format(self::DATE_FORMAT)]
        );
    }
}
