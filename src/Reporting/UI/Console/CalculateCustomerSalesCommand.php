<?php

namespace App\Reporting\UI\Console;

use App\Reporting\Application\Handler\CalculateCustomerSalesHandler;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Attribute\Argument;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Attribute\Option;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:calculate-customer-sales',
    description: 'Calculate customer sales',
)]
final readonly class CalculateCustomerSalesCommand
{
    private const string DATE_FORMAT = 'Y-m-d';

    public function __construct(
        private CalculateCustomerSalesHandler $customerSalesCalculator,
        private CalculateCustomerSalesSummaryCommand $customerSalesSummaryCommand,
        private LoggerInterface $logger,
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
            '%sCalculating customer sales data for %d days, starting %d days ago',
            $dryRun ? '[DRY RUN] ' : '',
            $dayCount,
            $dayOffset
        ));

        $progress = $io->createProgressBar($dayCount);
        $progress->start();

        $processedDates = [];
        $failed = 0;
        for ($day = 0; $day < $dayCount; ++$day) {
            $startDate = new \DateTime('-' . ($day + $dayOffset) . ' day')->format(self::DATE_FORMAT);

            try {
                $this->customerSalesCalculator->process($startDate, $dryRun);
                $processedDates[] = $startDate;
            } catch (\Throwable $throwable) {
                ++$failed;
                $this->logger->error('Failed to calculate customer sales for {date}', [
                    'date' => $startDate,
                    'error' => $throwable->getMessage(),
                ]);
            }

            $progress->advance();
        }

        $progress->finish();
        $io->newLine(2);

        if ($failed > 0) {
            $io->warning(sprintf('%d day(s) failed — see logs for details.', $failed));
        }

        $io->success(sprintf(
            '%sProcessed customer sales data for %d days',
            $dryRun ? '[DRY RUN] ' : '',
            $dayCount
        ));

        if ($output->isVerbose()) {
            $io->section('Processed Dates');
            $io->listing($processedDates);
        }

        if ($dayOffset === 0 && !$skipSummary && !$dryRun) {
            $this->runTheCustomerSalesSummaryCommand($output);
        }

        return $failed > 0 ? Command::FAILURE : Command::SUCCESS;
    }

    public function runTheCustomerSalesSummaryCommand(OutputInterface $output): void
    {
        $input = new ArrayInput([]);
        $this->customerSalesSummaryCommand->__invoke($input, $output, 0);
    }
}
