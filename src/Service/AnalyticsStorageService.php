<?php

namespace Wexample\SymfonyAnalytics\Service;

use Doctrine\DBAL\Platforms\PostgreSQLPlatform;
use Doctrine\ORM\EntityManagerInterface;

/**
 * What the audience weighs in the database: its tables with their indexes,
 * to see what the retention costs and tune it.
 */
class AnalyticsStorageService
{
    private const array TABLES = ['analytics_page_view', 'analytics_robot_day', 'analytics_robot_slot', 'analytics_monthly'];

    public function __construct(
        private readonly EntityManagerInterface $entityManager,
    ) {
    }

    /**
     * @return array<string, int>|null the bytes per table, null on a database
     *                                 that does not tell them (PostgreSQL does)
     */
    public function getSizes(): ?array
    {
        $connection = $this->entityManager->getConnection();

        if (! $connection->getDatabasePlatform() instanceof PostgreSQLPlatform) {
            return null;
        }

        $sizes = [];

        foreach (self::TABLES as $table) {
            $sizes[$table] = (int) $connection->fetchOne('SELECT pg_total_relation_size(:table)', ['table' => $table]);
        }

        return $sizes;
    }
}
