<?php

namespace App\Command;

use App\Service\Sales\ProductSalesCalculator;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use DateTime;

#[AsCommand(
    name: 'app:calculate-product-sales',
    description: 'Calculate product sales',
)]
class calculateProductSalesCommand extends Command
{
    private const DATE_FORMAT = 'Y-m-d';

    public function __construct(private readonly ProductSalesCalculator $productSalesCalculator)
    {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addArgument('dayCount', InputArgument::REQUIRED, 'Days to process')
            ->addArgument('dayOffset', InputArgument::OPTIONAL, 'Day offset to start processing');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $dayCount = (int)$input->getArgument('dayCount');
        $dayOffset = (int)$input->getArgument('dayOffset') ?: 0;

        $io->info(sprintf("Calculating sales data for %d days, starting %d days ago", $dayCount, $dayOffset));

        for ($day = 0; $day < $dayCount; $day++) {
            $startDate = (new DateTime('-' . ($day + $dayOffset) . ' day'))->format(self::DATE_FORMAT);
            $io->note(sprintf("Processing sales for %s", $startDate));

            $this->productSalesCalculator->process($startDate);
        }

        $io->success(sprintf("Processed sales data for %d days", $dayCount));

        $this->runTheProductSalesSummaryCommand($output);

        return Command::SUCCESS;
    }

    public function runTheProductSalesSummaryCommand(OutputInterface $output): void
    {
        $productSalesSummaryInput = new ArrayInput(['command' => 'app:calculate-product-sales-summary']);
        $this->getApplication()->doRun($productSalesSummaryInput, $output);
    }
}