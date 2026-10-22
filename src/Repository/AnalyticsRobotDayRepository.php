<?php

namespace Wexample\SymfonyAnalytics\Repository;

use DateTimeImmutable;
use Symfony\Component\Uid\Uuid;
use Wexample\SymfonyAnalytics\Entity\AnalyticsRobotDay;
use Wexample\SymfonyHelpers\Repository\AbstractRepository;

/**
 * The pages each robot read, a sum a day.
 *
 * @method AnalyticsRobotDay saveNewAnalyticsRobotDay(DateTimeImmutable $day, string $robot, int $hits)
 */
class AnalyticsRobotDayRepository extends AbstractRepository
{
    private const string TABLE = 'analytics_robot_day';

    public static function getEntityClassName(): string
    {
        return AnalyticsRobotDay::class;
    }

    public function createNewAnalyticsRobotDay(DateTimeImmutable $day, string $robot, int $hits): AnalyticsRobotDay
    {
        return new AnalyticsRobotDay($day, $robot, $hits);
    }

    /**
     * One page more for the robot today, in one statement: a crawler reading
     * several pages at once adds them all, none lost between a read and a
     * write.
     */
    public function countHit(string $robot, DateTimeImmutable $at): void
    {
        $this->getEntityManager()->getConnection()->executeStatement(
            'INSERT INTO ' . self::TABLE . ' (id, day, robot, hits) VALUES (:id, :day, :robot, 1)'
            . ' ON CONFLICT (day, robot) DO UPDATE SET hits = ' . self::TABLE . '.hits + 1',
            ['id' => (string) Uuid::v7(), 'day' => $at->format('Y-m-d'), 'robot' => $robot]
        );
    }

    /**
     * @return array<string, int> the pages read per robot from `from` to `to`
     *                            excluded, the busiest first
     */
    public function countPerRobot(DateTimeImmutable $from, DateTimeImmutable $to, int $limit): array
    {
        $rows = $this->getEntityManager()->getConnection()->fetchAllAssociative(
            'SELECT robot, SUM(hits) AS hits FROM ' . self::TABLE
            . ' WHERE day >= :from AND day < :to GROUP BY robot ORDER BY hits DESC LIMIT ' . $limit,
            ['from' => $from->format('Y-m-d'), 'to' => $to->format('Y-m-d')]
        );

        return array_combine(array_column($rows, 'robot'), array_map('intval', array_column($rows, 'hits')));
    }
}
