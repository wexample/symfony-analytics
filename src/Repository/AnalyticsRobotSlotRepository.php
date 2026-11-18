<?php

namespace Wexample\SymfonyAnalytics\Repository;

use DateTimeImmutable;
use Symfony\Component\Uid\Uuid;
use Wexample\SymfonyAnalytics\Entity\AnalyticsRobotSlot;
use Wexample\SymfonyHelpers\Repository\AbstractRepository;

/**
 * The pages each declared robot read, a sum per five minutes, for the day's
 * chart.
 *
 * @method AnalyticsRobotSlot saveNewAnalyticsRobotSlot(DateTimeImmutable $slot, string $robot, int $hits)
 */
class AnalyticsRobotSlotRepository extends AbstractRepository
{
    public const int SLOT_MINUTES = 5;

    private const string TABLE = 'analytics_robot_slot';

    private const string DATE_FORMAT = 'Y-m-d H:i:s';

    public static function getEntityClassName(): string
    {
        return AnalyticsRobotSlot::class;
    }

    public function createNewAnalyticsRobotSlot(DateTimeImmutable $slot, string $robot, int $hits): AnalyticsRobotSlot
    {
        return new AnalyticsRobotSlot($slot, $robot, $hits);
    }

    /**
     * The first minute of the five ones `at` falls in.
     */
    public static function getSlotOf(DateTimeImmutable $at): DateTimeImmutable
    {
        $minute = (int) $at->format('i');

        return $at->setTime((int) $at->format('H'), $minute - $minute % self::SLOT_MINUTES);
    }

    /**
     * One page more for the robot in the current five minutes, in one
     * statement, as its day counts it.
     */
    public function countHit(string $robot, DateTimeImmutable $at): void
    {
        $this->getEntityManager()->getConnection()->executeStatement(
            'INSERT INTO ' . self::TABLE . ' (id, slot, robot, hits) VALUES (:id, :slot, :robot, 1)'
            . ' ON CONFLICT (slot, robot) DO UPDATE SET hits = ' . self::TABLE . '.hits + 1',
            ['id' => (string) Uuid::v7(), 'slot' => self::getSlotOf($at)->format(self::DATE_FORMAT), 'robot' => $robot]
        );
    }

    /**
     * @return array<string, int> the pages per five minutes (`Y-m-d H:i`, its
     *                            first minute) from `from` to `to` excluded
     */
    public function countPerSlot(DateTimeImmutable $from, DateTimeImmutable $to): array
    {
        // The date written out and cut: the same SQL on PostgreSQL and SQLite.
        $slot = 'SUBSTR(CAST(slot AS VARCHAR(19)), 1, 16)';
        $rows = $this->getEntityManager()->getConnection()->fetchAllAssociative(
            'SELECT ' . $slot . ' AS slot, SUM(hits) AS views FROM ' . self::TABLE
            . ' WHERE slot >= :from AND slot < :to GROUP BY ' . $slot,
            ['from' => $from->format(self::DATE_FORMAT), 'to' => $to->format(self::DATE_FORMAT)]
        );

        return array_combine(array_column($rows, 'slot'), array_map('intval', array_column($rows, 'views')));
    }

    /**
     * @return array<string, int> the pages per robot from `from` to `to`
     *                            excluded, the busiest first
     */
    public function countPerRobot(DateTimeImmutable $from, DateTimeImmutable $to, int $limit): array
    {
        $rows = $this->getEntityManager()->getConnection()->fetchAllAssociative(
            'SELECT robot, SUM(hits) AS views FROM ' . self::TABLE
            . ' WHERE slot >= :from AND slot < :to GROUP BY robot ORDER BY views DESC LIMIT ' . $limit,
            ['from' => $from->format(self::DATE_FORMAT), 'to' => $to->format(self::DATE_FORMAT)]
        );

        return array_combine(array_column($rows, 'robot'), array_map('intval', array_column($rows, 'views')));
    }

    public function deleteBefore(DateTimeImmutable $date): int
    {
        return (int) $this->getEntityManager()->getConnection()->executeStatement(
            'DELETE FROM ' . self::TABLE . ' WHERE slot < :date',
            ['date' => $date->format(self::DATE_FORMAT)]
        );
    }
}
