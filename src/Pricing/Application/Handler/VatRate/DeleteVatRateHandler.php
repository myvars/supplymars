<?php

namespace App\Pricing\Application\Handler\VatRate;

use App\Pricing\Application\Command\VatRate\DeleteVatRate;
use App\Pricing\Domain\Model\VatRate\VatRate;
use App\Pricing\Domain\Repository\VatRateRepository;
use App\Shared\Application\FlusherInterface;
use App\Shared\Application\Result;
use Symfony\Bundle\SecurityBundle\Security;

final readonly class DeleteVatRateHandler
{
    public function __construct(
        private VatRateRepository $vatRates,
        private FlusherInterface $flusher,
        private Security $security,
    ) {
    }

    public function __invoke(DeleteVatRate $command): Result
    {
        if (!$this->security->isGranted('ROLE_SUPER_ADMIN')) {
            return Result::fail('Deleting is disabled for this user.');
        }

        $vatRate = $this->vatRates->getByPublicId($command->id);
        if (!$vatRate instanceof VatRate) {
            return Result::fail('VAT rate not found.');
        }

        $this->vatRates->remove($vatRate);
        $this->flusher->flush();

        return Result::ok(message: 'VAT rate deleted');
    }
}
