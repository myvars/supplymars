<?php

namespace App\Customer\Domain\Repository;

use App\Customer\Domain\Model\Address\Address;
use App\Customer\Domain\Model\Address\AddressId;
use App\Customer\Domain\Model\Address\AddressPublicId;
use App\Customer\Infrastructure\Persistence\Doctrine\AddressDoctrineRepository;
use Symfony\Component\DependencyInjection\Attribute\AsAlias;

#[AsAlias(AddressDoctrineRepository::class)]
interface AddressRepository
{
    public function add(Address $address): void;
    public function remove(Address $address): void;
    public function get(AddressId $id): ?Address;
    public function getByPublicId(AddressPublicId $publicId): ?Address;
}
