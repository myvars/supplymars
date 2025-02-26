<?php

namespace App\Service\Dashboard;

use App\Service\Dashboard\Report\ReportInterface;
use Symfony\Component\DependencyInjection\Attribute\AutowireLocator;
use Symfony\Contracts\Service\ServiceCollectionInterface;

final readonly class ReportHandler
{
    public function __construct(
        #[AutowireLocator(ReportInterface::class, indexAttribute: 'key')]
        private ServiceCollectionInterface $reports,
    ) {
    }

    public function build(string $name, object $dto): ?array
    {
        return $this->reports->get($name)->build($dto);
    }

    public function reports(): iterable
    {
        return array_keys($this->reports->getProvidedServices());
    }

    public function hasReport(?string $report): bool
    {
        return null !== $report && $this->reports->has($report);
    }
}
