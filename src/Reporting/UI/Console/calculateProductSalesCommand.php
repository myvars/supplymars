<?php

namespace App\Reporting\UI\Console;

use App\Reporting\Application\Handler\CalculateProductSalesHandler;
use Symfony\Component\Console\Attribute\Argument;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:calculate-product-sales',
    description: 'Calculate product sales',
)]
final readonly class calculateProductSalesCommand
{
    private const string DATE_FORMAT = 'Y-m-d';

    public function __construct(
        private CalculateProductSalesHandler $productSalesCalculator,
        private calculateProductSalesSummaryCommand $productSalesSummaryCommand,
    ) {
    }

    public function __invoke(
        InputInterface $input,
        OutputInterface $output,
        #[Argument(description: 'Days to process')]
        int $dayCount,
        #[Argument(description: 'Day offset to start processing')] int $dayOffset = 0,
    ): int {
        $io = new SymfonyStyle($input, $output);

        $io->section(sprintf('Calculating sales data for %d days, starting %d days ago',
            $dayCount,
            $dayOffset
        ));

        for ($day = 0; $day < $dayCount; ++$day) {
            $startDate = new \DateTime('-' . ($day + $dayOffset) . ' day')->format(self::DATE_FORMAT);
            $io->note(sprintf('Processing sales for %s', $startDate));

            $this->productSalesCalculator->process($startDate);
        }

        $io->success(sprintf('Processed sales data for %d days', $dayCount));

        if (0 === $dayOffset) {
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
