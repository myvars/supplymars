<?php

namespace App\Command\SalesProcessing;

use App\Service\Sales\OrderSalesSummaryCalculator;
use Symfony\Component\Console\Attribute\Argument;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:calculate-order-sales-summary',
    description: 'Calculate order sales summary',
)]
class calculateOrderSalesSummaryCommand
{
    public function __construct(private readonly OrderSalesSummaryCalculator $orderSalesSummaryCalculator)
    {
    }

    public function __invoke(
        InputInterface $input,
        OutputInterface $output,
        #[Argument(description: 'Rebuild full sales summary')] ?string $rebuild,
    ): int {
        $io = new SymfonyStyle($input, $output);
        $rebuild = (int) $rebuild;

        $io->info('Calculating order sales summary');

        $this->orderSalesSummaryCalculator->process($rebuild);

        $io->success('Sales summary calculation completed');

        return Command::SUCCESS;
    }
}
