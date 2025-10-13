<?php

namespace App\Customer\Infrastructure\Persistence\Doctrine;

use App\Customer\Domain\Model\Address\Address;
use App\Customer\Domain\Model\Address\AddressId;
use App\Customer\Domain\Model\Address\AddressPublicId;
use App\Customer\Domain\Model\User\User;
use App\Customer\Domain\Repository\AddressRepository;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Address>
 *
 * @method Address|null find($id, $lockMode = null, $lockVersion = null)
 * @method Address|null findOneBy(array $criteria, array $orderBy = null)
 * @method Address[] findAll()
 * @method Address[] findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class AddressDoctrineRepository extends ServiceEntityRepository implements AddressRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Address::class);
    }

    public function add(Address $address): void
    {
        $this->getEntityManager()->persist($address);
    }

    public function remove(Address $address): void
    {
        $this->getEntityManager()->remove($address);
    }

    public function get(AddressId $id): ?Address
    {
        return $this->find($id->value());
    }

    public function getByPublicId(AddressPublicId $publicId): ?Address
    {
        return $this->findOneBy(['publicId' => $publicId->value()]);
    }

    public function findDefaultBillingAddress(User $customer): ?Address
    {
        return $this->findOneBy(['customer' => $customer, 'isDefaultBillingAddress' => true]);
    }

    public function findDefaultShippingAddress(User $customer): ?Address
    {
        return $this->findOneBy(['customer' => $customer, 'isDefaultShippingAddress' => true]);
    }
}
