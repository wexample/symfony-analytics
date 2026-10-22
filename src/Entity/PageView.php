<?php

namespace Wexample\SymfonyAnalytics\Entity;

use DateTimeImmutable;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Wexample\SymfonyAnalytics\Class\TrafficOrigin;
use Wexample\SymfonyAnalytics\Enum\DeviceType;
use Wexample\SymfonyAnalytics\Enum\TrafficSource;
use Wexample\SymfonyAnalytics\Repository\PageViewRepository;
use Wexample\SymfonyHelpers\Entity\AbstractEntity;

/**
 * One page a person read: when, which, from where they came to the site,
 * from which country, on which kind of device.
 *
 * Nobody is named: the visitor is a hash of the address and the browser
 * under a salt of the day, so the same person is another visitor the next
 * day, and the address is never kept — no cookie, nothing to consent to.
 * A visit is the run of pages a visitor reads without a pause longer than
 * `visit_timeout`; every page of it carries the origin of its first one.
 */
#[ORM\Entity(repositoryClass: PageViewRepository::class)]
#[ORM\Table(name: 'analytics_page_view')]
#[ORM\Index(name: 'analytics_page_view_viewed_at', columns: ['viewed_at'])]
#[ORM\Index(name: 'analytics_page_view_visitor', columns: ['visitor_id', 'viewed_at'])]
class PageView extends AbstractEntity
{
    #[ORM\Column(type: Types::STRING, length: 32)]
    private string $visitorId;

    #[ORM\Column(type: Types::STRING, length: 36)]
    private string $visitId;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    private DateTimeImmutable $viewedAt;

    #[ORM\Column(type: Types::STRING, length: 255)]
    private string $path;

    #[ORM\Column(type: Types::STRING, length: 255, nullable: true)]
    private ?string $route;

    #[ORM\Column(type: Types::STRING, length: 16, nullable: true)]
    private ?string $locale;

    #[ORM\Column(type: Types::STRING, length: 16, enumType: TrafficSource::class)]
    private TrafficSource $source;

    #[ORM\Column(type: Types::STRING, length: 255, nullable: true)]
    private ?string $referrerHost;

    #[ORM\Column(type: Types::STRING, length: 64, nullable: true)]
    private ?string $searchEngine;

    #[ORM\Column(type: Types::STRING, length: 255, nullable: true)]
    private ?string $keyword;

    #[ORM\Column(type: Types::STRING, length: 255, nullable: true)]
    private ?string $campaign;

    /** ISO 3166-1 alpha-2; null where the address placed nowhere. */
    #[ORM\Column(type: Types::STRING, length: 2, nullable: true)]
    private ?string $country;

    #[ORM\Column(type: Types::STRING, length: 16, enumType: DeviceType::class)]
    private DeviceType $device;

    public function __construct(
        string $visitorId,
        string $visitId,
        DateTimeImmutable $viewedAt,
        string $path,
        ?string $route,
        ?string $locale,
        TrafficOrigin $origin,
        ?string $country,
        DeviceType $device,
    ) {
        parent::__construct();

        $this->visitorId = $visitorId;
        $this->visitId = $visitId;
        $this->viewedAt = $viewedAt;
        $this->path = mb_substr($path, 0, 255);
        $this->route = $route;
        $this->locale = $locale;
        $this->source = $origin->source;
        $this->referrerHost = $origin->referrerHost;
        $this->searchEngine = $origin->searchEngine;
        $this->keyword = $origin->keyword;
        $this->campaign = $origin->campaign;
        $this->country = $country;
        $this->device = $device;
    }

    public function getVisitorId(): string
    {
        return $this->visitorId;
    }

    public function getVisitId(): string
    {
        return $this->visitId;
    }

    public function getViewedAt(): DateTimeImmutable
    {
        return $this->viewedAt;
    }

    public function getPath(): string
    {
        return $this->path;
    }

    public function getRoute(): ?string
    {
        return $this->route;
    }

    public function getLocale(): ?string
    {
        return $this->locale;
    }

    /**
     * The origin of the visit it belongs to.
     */
    public function getOrigin(): TrafficOrigin
    {
        return new TrafficOrigin($this->source, $this->referrerHost, $this->searchEngine, $this->keyword, $this->campaign);
    }

    public function getCountry(): ?string
    {
        return $this->country;
    }

    public function getDevice(): DeviceType
    {
        return $this->device;
    }
}
