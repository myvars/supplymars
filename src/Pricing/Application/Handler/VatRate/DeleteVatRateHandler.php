<?php

namespace App\Pricing\Application\Handler\VatRate;

use App\Pricing\Application\Command\VatRate\DeleteVatRate;
use App\Pricing\Domain\Model\VatRate\VatRate;
use App\Pricing\Domain\Repository\VatRateRepository;
use App\Shared\Application\FlusherInterface;
use App\Shared\Application\Result;

final readonly class DeleteVatRateHandler
{
    public function __construct(
        private VatRateRepository $vatRates,
        private FlusherInterface $flusher,
    ) {
    }

    public function __invoke(DeleteVatRate $command): Result
    {
        $vatRate = $this->vatRates->getByPublicId($command->id);
        if (!$vatRate instanceof VatRate) {
            return Result::fail('VAT rate not found.');
        }

        $this->vatRates->remove($vatRate);
        $this->flusher->flush();

        return Result::ok('VAT rate deleted');
    }
}
