<?php

namespace App\Purchasing\UI\Console\Utilities;

use App\Purchasing\Application\Command\SupplierProduct\ToggleSupplierProductStatus;
use App\Purchasing\Application\Handler\SupplierProduct\ToggleSupplierProductStatusHandler;
use App\Purchasing\Domain\Model\SupplierProduct\SupplierProduct;
use App\Purchasing\Domain\Repository\SupplierProductRepository;
use App\Shared\Application\FlusherInterface;
use App\Shared\Infrastructure\Security\DefaultUserAuthenticator;
use Symfony\Component\Console\Attribute\Argument;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:activate-supplier-products',
    description: 'Activate supplier products',
)]
readonly class activateSupplierProductsCommand
{
    public function __construct(
        private SupplierProductRepository $supplierProducts,
        private ToggleSupplierProductStatusHandler $supplierProductStatusHandler,
        private DefaultUserAuthenticator $defaultUserAuthenticator,
        private FlusherInterface $flusher,
    ) {
    }

    public function __invoke(
        InputInterface $input,
        OutputInterface $output,
        #[Argument(description: 'Number of supplier products to process')]
        int $productCount = 50,
    ): int {
        $io = new SymfonyStyle($input, $output);

        if ($productCount < 1) {
            $io->error('productCount must be > 0.');

            return Command::INVALID;
        }

        $io->section(sprintf('Activating up to %d supplier products', $productCount));

        $supplierProducts = $this->getSupplierProducts($productCount);
        if ($supplierProducts === []) {
            $io->note('No supplier products found.');

            return Command::SUCCESS;
        }

        $this->defaultUserAuthenticator->ensureAuthenticated();

        $progress = $io->createProgressBar(count($supplierProducts));
        $progress->start();

        $processed = 0;
        $processedItems = [];

        foreach ($supplierProducts as $supplierProduct) {
            if (!$supplierProduct instanceof SupplierProduct) {
                continue;
            }

            ($this->supplierProductStatusHandler)(
                new ToggleSupplierProductStatus($supplierProduct->getPublicId())
            );

            $processedItems[] = sprintf('%s : %d (%s)',
                $supplierProduct->getProductCode(),
                $supplierProduct->getProduct()->getId(),
                $supplierProduct->getSupplier()->getName(),
            );
            ++$processed;

            $progress->advance();
        }

        $this->flusher->flush();

        $progress->finish();
        $io->newLine(2);
        $io->success(sprintf('Processed %d items.', $processed));

        if ($processed > 0 && $output->isVerbose()) {
            $io->section('Processed Supplier products');
            $io->listing($processedItems);
        }

        return Command::SUCCESS;
    }

    public function getSupplierProducts(int $productCount): array
    {
        return $this->supplierProducts->findBy(['isActive' => false], null, $productCount);
    }
}
