<?php

namespace App\Reporting\UI\Console;

use App\Reporting\Application\Handler\CalculateCustomerSalesSummaryHandler;
use Symfony\Component\Console\Attribute\Argument;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:calculate-customer-sales-summary',
    description: 'Calculate customer sales summary',
)]
final readonly class calculateCustomerSalesSummaryCommand
{
    public function __construct(private CalculateCustomerSalesSummaryHandler $customerSalesSummaryCalculator)
    {
    }

    public function __invoke(
        InputInterface $input,
        OutputInterface $output,
        #[Argument(description: 'Rebuild full sales summary')] int $rebuild = 0,
    ): int {
        $io = new SymfonyStyle($input, $output);

        $io->info('Calculating customer sales summary');

        $this->customerSalesSummaryCalculator->process((bool) $rebuild);

        $io->success('Customer sales summary calculation completed');

        return Command::SUCCESS;
    }
}
