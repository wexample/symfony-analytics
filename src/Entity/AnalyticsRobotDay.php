<?php

namespace Wexample\SymfonyAnalytics\Entity;

use DateTimeImmutable;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Wexample\SymfonyAnalytics\Repository\AnalyticsRobotDayRepository;
use Wexample\SymfonyHelpers\Entity\AbstractEntity;

/**
 * The pages a robot read in a day: counted rather than left out, so that the
 * audience says who else reads the site — the AI crawlers first. A robot
 * leaves no visit, its pages one sum a day; kept as long as the months.
 */
#[ORM\Entity(repositoryClass: AnalyticsRobotDayRepository::class)]
#[ORM\Table(name: 'analytics_robot_day')]
#[ORM\UniqueConstraint(name: 'analytics_robot_day_key', columns: ['day', 'robot'])]
class AnalyticsRobotDay extends AbstractEntity
{
    #[ORM\Column(type: Types::DATE_IMMUTABLE)]
    private DateTimeImmutable $day;

    /** Its name (UserAgentHelper::getRobot()), or `cadence`. */
    #[ORM\Column(type: Types::STRING, length: 64)]
    private string $robot;

    #[ORM\Column(type: Types::INTEGER)]
    private int $hits;

    public function __construct(DateTimeImmutable $day, string $robot, int $hits)
    {
        parent::__construct();

        $this->day = $day;
        $this->robot = $robot;
        $this->hits = $hits;
    }

    public function getDay(): DateTimeImmutable
    {
        return $this->day;
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
