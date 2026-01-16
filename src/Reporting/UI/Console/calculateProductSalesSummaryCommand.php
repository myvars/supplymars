<?php

namespace App\Reporting\UI\Console;

use App\Reporting\Application\Handler\CalculateProductSalesSummaryHandler;
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
final readonly class calculateProductSalesSummaryCommand
{
    public function __construct(private CalculateProductSalesSummaryHandler $productSalesSummaryCalculator)
    {
    }

    public function __invoke(
        InputInterface $input,
        OutputInterface $output,
        #[Argument(description: 'Rebuild full sales summary')]
        int $rebuild = 0,
    ): int {
        $io = new SymfonyStyle($input, $output);

        $io->info('Calculating product sales summary');

        $this->productSalesSummaryCalculator->process((bool) $rebuild);

        $io->success('Sales summary calculation completed');

        return Command::SUCCESS;
    }
}
