<?php

namespace App\Pricing\Domain\Repository;

use App\Pricing\Domain\Model\VatRate\VatRate;
use App\Pricing\Domain\Model\VatRate\VatRateId;
use App\Pricing\Domain\Model\VatRate\VatRatePublicId;
use App\Pricing\Infrastructure\Persistence\Doctrine\VatRateDoctrineRepository;
use App\Shared\Infrastructure\Persistence\Search\FindByCriteriaInterface;
use Symfony\Component\DependencyInjection\Attribute\AsAlias;

#[AsAlias(VatRateDoctrineRepository::class)]
interface VatRateRepository extends FindByCriteriaInterface
{
    public function add(VatRate $vatRate): void;

    public function remove(VatRate $vatRate): void;

    public function get(VatRateId $id): ?VatRate;

    public function getByPublicId(VatRatePublicId $publicId): ?VatRate;
}
