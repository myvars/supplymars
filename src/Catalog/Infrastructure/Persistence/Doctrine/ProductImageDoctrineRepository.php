<?php

namespace App\Catalog\Infrastructure\Persistence\Doctrine;

use App\Catalog\Domain\Model\Product\Product;
use App\Catalog\Domain\Model\ProductImage\ProductImage;
use App\Catalog\Domain\Model\ProductImage\productImageId;
use App\Catalog\Domain\Model\ProductImage\ProductImagePublicId;
use App\Catalog\Domain\Repository\ProductImageRepository;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\NoResultException;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<ProductImage>
 *
 * @method ProductImage|null find($id, $lockMode = null, $lockVersion = null)
 * @method ProductImage|null findOneBy(array $criteria, array $orderBy = null)
 * @method ProductImage[]    findAll()
 * @method ProductImage[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class ProductImageDoctrineRepository extends ServiceEntityRepository implements ProductImageRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ProductImage::class);
    }

    public function add(ProductImage $productImage): void
    {
        $this->getEntityManager()->persist($productImage);
    }

    public function remove(ProductImage $productImage): void
    {
        $this->getEntityManager()->remove($productImage);
    }

    public function get(productImageId $id): ?ProductImage
    {
        return $this->find($id->value());
    }

    public function getByPublicId(ProductImagePublicId $publicId): ?ProductImage
    {
        return $this->findOneBy(['publicId' => $publicId->value()]);
    }

    /**
     * @throws NonUniqueResultException
     * @throws NoResultException
     */
    public function getNextPositionForProduct(Product $product): int
    {
        $query = $this->createQueryBuilder('pi')
            ->select('MAX(pi.position)')
            ->where('pi.product = :product')
            ->setParameter('product', $product)
            ->getQuery();

        $maxPosition = $query->getSingleScalarResult();

        return (int) $maxPosition + 1;
    }
}
