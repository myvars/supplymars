<?php

namespace App\Reporting\UI\Console;

use App\Reporting\Application\Handler\CalculateOrderSalesHandler;
use Symfony\Component\Console\Attribute\Argument;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Attribute\Option;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:calculate-order-sales',
    description: 'Calculate order sales',
)]
final readonly class CalculateOrderSalesCommand
{
    private const string DATE_FORMAT = 'Y-m-d';

    public function __construct(
        private CalculateOrderSalesHandler $orderSalesCalculator,
        private CalculateOrderSalesSummaryCommand $orderSalesSummaryCommand,
    ) {
    }

    public function __invoke(
        InputInterface $input,
        OutputInterface $output,
        #[Argument(description: 'Days to process')]
        int $dayCount,
        #[Argument(description: 'Day offset to start processing')]
        int $dayOffset = 0,
        #[Option(description: 'Run without persisting changes')]
        bool $dryRun = false,
        #[Option(description: 'Skip running the summary command')]
        bool $skipSummary = false,
    ): int {
        $io = new SymfonyStyle($input, $output);

        if ($dayCount < 1) {
            $io->error('dayCount must be > 0');

            return Command::INVALID;
        }

        $io->section(sprintf(
            '%sCalculating sales data for %d days, starting %d days ago',
            $dryRun ? '[DRY RUN] ' : '',
            $dayCount,
            $dayOffset
        ));

        $progress = $io->createProgressBar($dayCount);
        $progress->start();

        $processedDates = [];
        for ($day = 0; $day < $dayCount; ++$day) {
            $startDate = new \DateTime('-' . ($day + $dayOffset) . ' day')->format(self::DATE_FORMAT);

            $this->orderSalesCalculator->process($startDate, $dryRun);
            $processedDates[] = $startDate;
            $progress->advance();
        }

        $progress->finish();
        $io->newLine(2);

        $io->success(sprintf(
            '%sProcessed sales data for %d days',
            $dryRun ? '[DRY RUN] ' : '',
            $dayCount
        ));

        if ($output->isVerbose()) {
            $io->section('Processed Dates');
            $io->listing($processedDates);
        }

        if ($dayOffset === 0 && !$skipSummary && !$dryRun) {
            $this->runTheOrderSalesSummaryCommand($output);
        }

        return Command::SUCCESS;
    }

    public function runTheOrderSalesSummaryCommand(OutputInterface $output): void
    {
        $input = new ArrayInput([]);
        $this->orderSalesSummaryCommand->__invoke($input, $output, 0);
    }
}
