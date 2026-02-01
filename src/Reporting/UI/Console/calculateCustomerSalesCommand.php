<?php

namespace App\Reporting\UI\Console;

use App\Reporting\Application\Handler\CalculateCustomerSalesHandler;
use Symfony\Component\Console\Attribute\Argument;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:calculate-customer-sales',
    description: 'Calculate customer sales',
)]
final readonly class calculateCustomerSalesCommand
{
    private const string DATE_FORMAT = 'Y-m-d';

    public function __construct(
        private CalculateCustomerSalesHandler $customerSalesCalculator,
        private calculateCustomerSalesSummaryCommand $customerSalesSummaryCommand,
    ) {
    }

    public function __invoke(
        InputInterface $input,
        OutputInterface $output,
        #[Argument(description: 'Days to process')]
        int $dayCount,
        #[Argument(description: 'Day offset to start processing')]
        int $dayOffset = 0,
    ): int {
        $io = new SymfonyStyle($input, $output);

        $io->section(sprintf('Calculating customer sales data for %d days, starting %d days ago',
            $dayCount,
            $dayOffset
        ));

        for ($day = 0; $day < $dayCount; ++$day) {
            $startDate = new \DateTime('-' . ($day + $dayOffset) . ' day')->format(self::DATE_FORMAT);
            $io->note(sprintf('Processing customer sales for %s', $startDate));

            $this->customerSalesCalculator->process($startDate);
        }

        $io->success(sprintf('Processed customer sales data for %d days', $dayCount));

        if (0 === $dayOffset) {
            $this->runTheCustomerSalesSummaryCommand($output);
        }

        return Command::SUCCESS;
    }

    public function runTheCustomerSalesSummaryCommand(OutputInterface $output): void
    {
        $input = new ArrayInput([]);
        $this->customerSalesSummaryCommand->__invoke($input, $output, 0);
    }
}
