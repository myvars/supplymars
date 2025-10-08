<?php

namespace App\Command\SalesProcessing;

use App\Service\Sales\ProductSalesSummaryCalculator;
use Symfony\Component\Console\Attribute\Argument;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:calculate-product-sales-summary',
    description: 'Calculate product sales summary',
)]
class calculateProductSalesSummaryCommand
{
    public function __construct(private readonly ProductSalesSummaryCalculator $productSalesSummaryCalculator)
    {
    }

    public function __invoke(
        InputInterface $input,
        OutputInterface $output,
        #[Argument(description: 'Rebuild full sales summary')] ?string $rebuild,
    ): int {
        $io = new SymfonyStyle($input, $output);
        $rebuild = (int) $rebuild;

        $io->info('Calculating product sales summary');

        $this->productSalesSummaryCalculator->process($rebuild);

        $io->success('Sales summary calculation completed');

        return Command::SUCCESS;
    }
}
