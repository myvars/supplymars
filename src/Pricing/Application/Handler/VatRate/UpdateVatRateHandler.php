<?php

namespace App\Pricing\Application\Handler\VatRate;

use App\Pricing\Application\Command\VatRate\UpdateVatRate;
use App\Pricing\Domain\Model\VatRate\VatRate;
use App\Pricing\Domain\Repository\VatRateRepository;
use App\Shared\Application\FlusherInterface;
use App\Shared\Application\Result;
use Symfony\Component\Validator\Validator\ValidatorInterface;

final readonly class UpdateVatRateHandler
{
    public function __construct(
        private VatRateRepository $vatRates,
        private FlusherInterface $flusher,
        private ValidatorInterface $validator,
    ) {
    }

    public function __invoke(UpdateVatRate $command): Result
    {
        $vatRate = $this->vatRates->getByPublicId($command->id);
        if (!$vatRate instanceof VatRate) {
            return Result::fail('VAT rate not found.');
        }

        $vatRate->update(
            name: $command->name,
            rate: $command->rate,
        );

        $errors = $this->validator->validate($vatRate);
        if (count($errors) > 0) {
            return Result::fail((string) $errors);
        }

        $this->flusher->flush();

        return Result::ok('VAT rate updated');
    }
}
