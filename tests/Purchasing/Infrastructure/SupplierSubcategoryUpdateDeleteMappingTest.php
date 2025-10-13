<?php

namespace App\Tests\Purchasing\Infrastructure;

use App\Purchasing\Domain\Model\SupplierProduct\SupplierSubcategory;
use App\Tests\Shared\Factory\SupplierCategoryFactory;
use App\Tests\Shared\Factory\SupplierFactory;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

final class SupplierSubcategoryUpdateDeleteMappingTest extends KernelTestCase
{
    private EntityManagerInterface $em;

    protected function setUp(): void
    {
        self::bootKernel();
        $this->em = self::getContainer()->get(EntityManagerInterface::class);
    }

    public function testUpdateRoundTripPersistsChanges(): void
    {
        $supplierA = SupplierFactory::createOne();
        $supplierB = SupplierFactory::createOne();
        $categoryA = SupplierCategoryFactory::createOne(['supplier' => $supplierA]);
        $categoryB = SupplierCategoryFactory::createOne(['supplier' => $supplierB]);

        $subcategory = SupplierSubcategory::create(
            name: 'Before',
            supplier: $supplierA,
            supplierCategory: $categoryA,
        );
        $this->em->persist($subcategory);
        $this->em->flush();
        $id = $subcategory->getId();

        /** @var SupplierSubcategory $loaded */
        $loaded = $this->em->getRepository(SupplierSubcategory::class)->find($id);
        $loaded->update(
            name: 'After',
            supplier: $supplierB,
            supplierCategory: $categoryB,
        );

        $this->em->flush();
        $this->em->clear();

        /** @var SupplierSubcategory $reloaded */
        $reloaded = $this->em->getRepository(SupplierSubcategory::class)->find($id);
        self::assertSame('After', $reloaded->getName());
        self::assertSame($supplierB->getId(), $reloaded->getSupplier()?->getId());
        self::assertSame($categoryB->getId(), $reloaded->getSupplierCategory()?->getId());
    }

    public function testDeleteRemovesRow(): void
    {
        $supplier = SupplierFactory::createOne();
        $category = SupplierCategoryFactory::createOne(['supplier' => $supplier]);

        $subcategory = SupplierSubcategory::create(
            name: 'To Delete',
            supplier: $supplier,
            supplierCategory: $category,
        );
        $this->em->persist($subcategory);
        $this->em->flush();
        $id = $subcategory->getId();

        $this->em->remove($subcategory);
        $this->em->flush();
        $this->em->clear();

        self::assertNull($this->em->getRepository(SupplierSubcategory::class)->find($id));
    }
}
