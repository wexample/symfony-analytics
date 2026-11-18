<?php

namespace Wexample\SymfonyAnalytics\Entity;

use DateTimeImmutable;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Wexample\SymfonyAnalytics\Repository\AnalyticsRobotSlotRepository;
use Wexample\SymfonyHelpers\Entity\AbstractEntity;

/**
 * The pages a declared robot read in five minutes: what the day's chart
 * draws of them, the days summing them up per route having no hour. Kept two
 * days, the history being the days'.
 */
#[ORM\Entity(repositoryClass: AnalyticsRobotSlotRepository::class)]
#[ORM\Table(name: 'analytics_robot_slot')]
#[ORM\UniqueConstraint(name: 'analytics_robot_slot_key', columns: ['slot', 'robot'])]
class AnalyticsRobotSlot extends AbstractEntity
{
    /** Its first minute. */
    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    private DateTimeImmutable $slot;

    #[ORM\Column(type: Types::STRING, length: 64)]
    private string $robot;

    #[ORM\Column(type: Types::INTEGER)]
    private int $hits;

    public function __construct(DateTimeImmutable $slot, string $robot, int $hits)
    {
        parent::__construct();

        $this->slot = $slot;
        $this->robot = $robot;
        $this->hits = $hits;
    }

    public function getSlot(): DateTimeImmutable
    {
        return $this->slot;
    }

    public function getRobot(): string
    {
        return $this->robot;
    }

    public function getHits(): int
    {
        return $this->hits;
    }
}
