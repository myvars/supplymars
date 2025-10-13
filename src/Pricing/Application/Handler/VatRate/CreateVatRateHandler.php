<?php

namespace App\Pricing\Application\Handler\VatRate;

use App\Pricing\Application\Command\VatRate\CreateVatRate;
use App\Pricing\Domain\Model\VatRate\VatRate;
use App\Pricing\Domain\Model\VatRate\VatRateId;
use App\Pricing\Domain\Repository\VatRateRepository;
use App\Shared\Application\FlusherInterface;
use App\Shared\Application\Result;
use Symfony\Component\Validator\Validator\ValidatorInterface;

final readonly class CreateVatRateHandler
{
    public function __construct(
        private VatRateRepository $vatRates,
        private FlusherInterface $flusher,
        private ValidatorInterface $validator,
    ) {
    }

    public function __invoke(CreateVatRate $command): Result
    {
        $vatRate = VatRate::create(
            name: $command->name,
            rate: $command->rate,
        );

        $errors = $this->validator->validate($vatRate);
        if (count($errors) > 0) {
            return Result::fail((string) $errors);
        }

        $this->vatRates->add($vatRate);
        $this->flusher->flush();

        return Result::ok('VAT rate created', VatRateId::fromInt($vatRate->getId()));
    }
}
