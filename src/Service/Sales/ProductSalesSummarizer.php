<?php

namespace App\Service\Sales;

use App\Service\Sales\Duration\DurationInterface;
use Symfony\Component\DependencyInjection\Attribute\AutowireLocator;
use Symfony\Contracts\Service\ServiceCollectionInterface;

class ProductSalesSummarizer
{
    private array $salesTypes = ['product', 'category', 'manufacturer', 'subcategory', 'supplier'];

    public function __construct(
        #[AutowireLocator(DurationInterface::class, indexAttribute: 'key')]
        private readonly ServiceCollectionInterface $durations,
        private readonly ProductSalesSummaryProcessor $processor
    ) {
    }

    public function summarize(bool $rebuild = false): void
    {
        foreach ($this->durations as $durationType => $duration) {
            foreach ($this->salesTypes as $salesType) {
                $this->processor->process(
                    $salesType,
                    $durationType,
                    $duration->getStartDate($rebuild),
                    $duration->getDateString()
                );
            }
        }
    }
}