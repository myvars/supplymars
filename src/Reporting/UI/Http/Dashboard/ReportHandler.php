<?php

namespace App\Reporting\UI\Http\Dashboard;

use App\Reporting\UI\Http\Dashboard\Report\ReportInterface;
use Symfony\Component\DependencyInjection\Attribute\AutowireLocator;
use Symfony\Contracts\Service\ServiceCollectionInterface;

final readonly class ReportHandler
{
    /**
     * @param ServiceCollectionInterface<ReportInterface> $reports
     */
    public function __construct(
        #[AutowireLocator(ReportInterface::class, indexAttribute: 'key')]
        private ServiceCollectionInterface $reports,
    ) {
    }

    /**
     * @return array<string, mixed>|null
     */
    public function build(string $name, object $dto): ?array
    {
        return $this->reports->get($name)->build($dto);
    }

    /**
     * @return array<int, string>
     */
    public function reports(): iterable
    {
        return array_keys($this->reports->getProvidedServices());
    }

    public function hasReport(?string $report): bool
    {
        return null !== $report && $this->reports->has($report);
    }
}
