<?php

namespace App\Command\SalesProcessing;

use App\Service\Sales\OrderSalesCalculator;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:calculate-order-sales',
    description: 'Calculate order sales',
)]
class calculateOrderSalesCommand extends Command
{
    private const string DATE_FORMAT = 'Y-m-d';

    public function __construct(private readonly OrderSalesCalculator $orderSalesCalculator)
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
        $dayCount = (int) $input->getArgument('dayCount');
        $dayOffset = (int) $input->getArgument('dayOffset') ?: 0;

        $io->info(sprintf('Calculating sales data for %d days, starting %d days ago', $dayCount, $dayOffset));

        for ($day = 0; $day < $dayCount; ++$day) {
            $startDate = (new \DateTime('-'.($day + $dayOffset).' day'))->format(self::DATE_FORMAT);
            $io->note(sprintf('Processing sales for %s', $startDate));

            $this->orderSalesCalculator->process($startDate);
        }

        $io->success(sprintf('Processed sales data for %d days', $dayCount));

        if (0 === $dayOffset) {
            $this->runTheOrderSalesSummaryCommand($output);
        }

        return Command::SUCCESS;
    }

    public function runTheOrderSalesSummaryCommand(OutputInterface $output): void
    {
        $orderSalesSummaryInput = new ArrayInput(['command' => 'app:calculate-order-sales-summary']);
        $this->getApplication()->doRun($orderSalesSummaryInput, $output);
    }
}
