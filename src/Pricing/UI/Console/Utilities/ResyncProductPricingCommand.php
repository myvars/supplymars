<?php

declare(strict_types=1);

namespace App\Pricing\UI\Console\Utilities;

use App\Catalog\Domain\Model\Product\Product;
use App\Catalog\Domain\Repository\ProductRepository;
use App\Shared\Domain\Service\Pricing\MarkupCalculator;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Attribute\Option;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * Brings every product's stored source, stock, cost and price back in step with its supplier products.
 * Before #106 was fixed, simulated stock and cost changes never reached the product.
 */
#[AsCommand(
    name: 'app:resync-product-pricing',
    description: "Recalculate every product's active source and price from its supplier products",
)]
final readonly class ResyncProductPricingCommand
{
    public function __construct(
        private ProductRepository $products,
        private MarkupCalculator $markupCalculator,
        private EntityManagerInterface $entityManager,
    ) {
    }

    public function __invoke(
        SymfonyStyle $io,
        OutputInterface $output,
        #[Option(description: 'Report what would change without saving')]
        bool $dryRun = false,
        #[Option(description: 'Products per flush')]
        int $batchSize = 200,
    ): int {
        if ($batchSize < 1) {
            $io->error('Batch size must be > 0.');

            return Command::INVALID;
        }

        $checked = 0;
        $changed = [];
        $failed = [];

        foreach ($this->products->iterateAll() as $product) {
            $before = $this->snapshot($product);

            try {
                $product->recalculateActiveSource($this->markupCalculator);
            } catch (\InvalidArgumentException $exception) {
                // The source may already be applied without its price: put the product back as it was.
                $this->entityManager->refresh($product);
                $failed[] = sprintf('#%d %s: %s', $product->getId(), $product->getName(), $exception->getMessage());
                ++$checked;
                continue;
            }

            $after = $this->snapshot($product);

            if ($before !== $after) {
                $changed[] = sprintf(
                    '#%d %s: %s → %s',
                    $product->getId(),
                    $product->getName(),
                    $this->describe($before),
                    $this->describe($after),
                );
            }

            if (++$checked % $batchSize === 0) {
                $this->endBatch($dryRun);
            }
        }

        $this->endBatch($dryRun);

        if ($output->isVerbose() && $changed !== []) {
            $io->listing($changed);
        }

        $summary = sprintf(
            '%sChecked %d products: %d %s.',
            $dryRun ? '[DRY RUN] ' : '',
            $checked,
            \count($changed),
            $dryRun ? 'would change' : 'changed',
        );

        if ($failed !== []) {
            $io->listing($failed);
            $io->error($summary . sprintf(' %d failed and were left as they were.', \count($failed)));

            return Command::FAILURE;
        }

        $io->success($summary);

        return Command::SUCCESS;
    }

    /**
     * A dry run never flushes, so clearing drops its changes.
     */
    private function endBatch(bool $dryRun): void
    {
        if (!$dryRun) {
            $this->entityManager->flush();
        }

        $this->entityManager->clear();
    }

    /**
     * @return array{source: ?int, stock: ?int, cost: ?string, leadTimeDays: ?int, markup: ?string, sellPrice: ?string, sellPriceIncVat: ?string}
     */
    private function snapshot(Product $product): array
    {
        return [
            'source' => $product->getActiveProductSource()?->getId(),
            'stock' => $product->getStock(),
            'cost' => $product->getCost(),
            'leadTimeDays' => $product->getLeadTimeDays(),
            'markup' => $product->getMarkup(),
            'sellPrice' => $product->getSellPrice(),
            'sellPriceIncVat' => $product->getSellPriceIncVat(),
        ];
    }

    /**
     * @param array{source: ?int, stock: ?int, cost: ?string, leadTimeDays: ?int, markup: ?string, sellPrice: ?string, sellPriceIncVat: ?string} $state
     */
    private function describe(array $state): string
    {
        return sprintf(
            'source %s, stock %d, lead time %d, cost %s, markup %s, price %s',
            $state['source'] ?? 'none',
            $state['stock'] ?? 0,
            $state['leadTimeDays'] ?? 0,
            $state['cost'] ?? '0.00',
            $state['markup'] ?? '0.000',
            $state['sellPriceIncVat'] ?? '0.00',
        );
    }
}
