<?php

namespace Wexample\SymfonyAnalytics\Entity;

use DateTimeImmutable;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Wexample\SymfonyAnalytics\Repository\AnalyticsRobotDayRepository;
use Wexample\SymfonyHelpers\Entity\AbstractEntity;

/**
 * The pages a declared robot read in a day on a route: counted rather than
 * left out, so that the audience says who else reads the site — the AI
 * crawlers first — and what they read. A robot leaves no visit, its pages one
 * sum a day per route, kept `robot_retention_months` and then by the month.
 * The route is empty on the days recorded before it was.
 */
#[ORM\Entity(repositoryClass: AnalyticsRobotDayRepository::class)]
#[ORM\Table(name: 'analytics_robot_day')]
#[ORM\UniqueConstraint(name: 'analytics_robot_day_key', columns: ['day', 'robot', 'route'])]
class AnalyticsRobotDay extends AbstractEntity
{
    #[ORM\Column(type: Types::DATE_IMMUTABLE)]
    private DateTimeImmutable $day;

    /** Its name (UserAgentHelper::getRobot()); `cadence` on the days before suspects were kept apart. */
    #[ORM\Column(type: Types::STRING, length: 64)]
    private string $robot;

    #[ORM\Column(type: Types::STRING, length: 255, options: ['default' => ''])]
    private string $route;

    #[ORM\Column(type: Types::INTEGER)]
    private int $hits;

    public function __construct(DateTimeImmutable $day, string $robot, string $route, int $hits)
    {
        parent::__construct();

        $this->day = $day;
        $this->robot = $robot;
        $this->route = $route;
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

    public function getRoute(): string
    {
        return $this->route;
    }

    public function getHits(): int
    {
        return $this->hits;
    }
}
