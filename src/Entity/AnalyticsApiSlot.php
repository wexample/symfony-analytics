<?php

namespace Wexample\SymfonyAnalytics\Entity;

use DateTimeImmutable;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Wexample\SymfonyAnalytics\Repository\AnalyticsApiSlotRepository;
use Wexample\SymfonyHelpers\Entity\AbstractEntity;

/**
 * The calls to the api in five minutes per status: what the day's chart
 * draws of them. Kept two days, the history being the days'.
 */
#[ORM\Entity(repositoryClass: AnalyticsApiSlotRepository::class)]
#[ORM\Table(name: 'analytics_api_slot')]
#[ORM\UniqueConstraint(name: 'analytics_api_slot_key', columns: ['slot', 'status'])]
class AnalyticsApiSlot extends AbstractEntity
{
    /** Its first minute. */
    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    private DateTimeImmutable $slot;

    #[ORM\Column(type: Types::SMALLINT)]
    private int $status;

    #[ORM\Column(type: Types::INTEGER)]
    private int $calls;

    public function __construct(DateTimeImmutable $slot, int $status, int $calls)
    {
        parent::__construct();

        $this->slot = $slot;
        $this->status = $status;
        $this->calls = $calls;
    }

    public function getSlot(): DateTimeImmutable
    {
        return $this->slot;
    }

    public function getStatus(): int
    {
        return $this->status;
    }

    public function getCalls(): int
    {
        return $this->calls;
    }
}
