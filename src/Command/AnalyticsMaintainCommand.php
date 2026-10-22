<?php

namespace Wexample\SymfonyAnalytics\Command;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Wexample\SymfonyAnalytics\Service\AnalyticsMaintenanceService;

#[AsCommand(name: 'analytics:maintain', description: 'Sums the finished months up and deletes the pages viewed past the retention')]
class AnalyticsMaintainCommand extends Command
{
    public function __construct(
        private readonly AnalyticsMaintenanceService $maintenance,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $result = $this->maintenance->maintain();

        new SymfonyStyle($input, $output)->success(sprintf('%d month(s) summed up, %d page view(s) deleted.', $result['months'], $result['deleted']));

        return Command::SUCCESS;
    }
}
