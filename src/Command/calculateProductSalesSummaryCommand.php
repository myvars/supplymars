<?php

namespace App\Command;

use App\Service\Sales\ProductSalesSummarizer;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:calculate-product-sales-summary',
    description: 'Calculate product sales summary',
)]
class calculateProductSalesSummaryCommand extends Command
{
    public function __construct(private readonly ProductSalesSummarizer $productSalesSummarizer)
    {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addArgument('rebuild', InputArgument::OPTIONAL, 'Rebuild full sales summary');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $rebuild = (int)$input->getArgument('rebuild');

        $io->info("Calculating sales summary");

        $this->productSalesSummarizer->summarize($rebuild);

        $io->success("Sales summary calculation completed");

        return Command::SUCCESS;
    }
}