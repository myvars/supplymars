<?php

namespace App\Reporting\UI\Console;

use App\Reporting\Application\Handler\CalculateProductSalesHandler;
use Symfony\Component\Console\Attribute\Argument;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Attribute\Option;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:calculate-product-sales',
    description: 'Calculate product sales',
)]
final readonly class CalculateProductSalesCommand
{
    private const string DATE_FORMAT = 'Y-m-d';

    public function __construct(
        private CalculateProductSalesHandler $productSalesCalculator,
        private CalculateProductSalesSummaryCommand $productSalesSummaryCommand,
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
            '%sCalculating product sales data for %d days, starting %d days ago',
            $dryRun ? '[DRY RUN] ' : '',
            $dayCount,
            $dayOffset
        ));

        $progress = $io->createProgressBar($dayCount);
        $progress->start();

        $processedDates = [];
        for ($day = 0; $day < $dayCount; ++$day) {
            $startDate = new \DateTime('-' . ($day + $dayOffset) . ' day')->format(self::DATE_FORMAT);

            $this->productSalesCalculator->process($startDate, $dryRun);
            $processedDates[] = $startDate;
            $progress->advance();
        }

        $progress->finish();
        $io->newLine(2);

        $io->success(sprintf(
            '%sProcessed product sales data for %d days',
            $dryRun ? '[DRY RUN] ' : '',
            $dayCount
        ));

        if ($output->isVerbose()) {
            $io->section('Processed Dates');
            $io->listing($processedDates);
        }

        if ($dayOffset === 0 && !$skipSummary && !$dryRun) {
            $this->runTheProductSalesSummaryCommand($output);
        }

        return Command::SUCCESS;
    }

    public function runTheProductSalesSummaryCommand(OutputInterface $output): void
    {
        $input = new ArrayInput([]);
        $this->productSalesSummaryCommand->__invoke($input, $output, 0);
    }
}
