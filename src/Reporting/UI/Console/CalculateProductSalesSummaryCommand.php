<?php

namespace App\Reporting\UI\Console;

use App\Reporting\Application\Handler\CalculateProductSalesSummaryHandler;
use App\Reporting\Domain\Metric\SalesDuration;
use Symfony\Component\Console\Attribute\Argument;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Attribute\Option;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:calculate-product-sales-summary',
    description: 'Calculate product sales summary',
)]
final readonly class CalculateProductSalesSummaryCommand
{
    public function __construct(private CalculateProductSalesSummaryHandler $productSalesSummaryCalculator)
    {
    }

    public function __invoke(
        InputInterface $input,
        OutputInterface $output,
        #[Argument(description: 'Rebuild full sales summary')]
        int $rebuild = 0,
        #[Option(description: 'Run without persisting changes')]
        bool $dryRun = false,
    ): int {
        $io = new SymfonyStyle($input, $output);

        $durations = SalesDuration::cases();

        $io->section(sprintf(
            '%sCalculating product sales summary for %d durations',
            $dryRun ? '[DRY RUN] ' : '',
            count($durations)
        ));

        $progress = $io->createProgressBar(count($durations));
        $progress->start();

        $processedDurations = [];
        foreach ($durations as $duration) {
            $processedDurations[] = $duration->value;
            $progress->advance();
        }

        $this->productSalesSummaryCalculator->process((bool) $rebuild, $dryRun);

        $progress->finish();
        $io->newLine(2);

        $io->success(sprintf(
            '%sProduct sales summary calculation completed',
            $dryRun ? '[DRY RUN] ' : ''
        ));

        if ($output->isVerbose()) {
            $io->section('Processed Durations');
            $io->listing($processedDurations);
        }

        return Command::SUCCESS;
    }
}
