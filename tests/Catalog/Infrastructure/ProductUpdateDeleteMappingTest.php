<?php

namespace App\Tests\Catalog\Infrastructure;

use App\Catalog\Domain\Model\Product\Product;
use App\Shared\Domain\Service\Pricing\MarkupCalculator;
use App\Tests\Shared\Factory\CategoryFactory;
use App\Tests\Shared\Factory\ManufacturerFactory;
use App\Tests\Shared\Factory\SubcategoryFactory;
use App\Tests\Shared\Factory\SupplierProductFactory;
use App\Tests\Shared\Factory\UserFactory;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

final class ProductUpdateDeleteMappingTest extends KernelTestCase
{
    private EntityManagerInterface $em;

    private MarkupCalculator $markupCalculator;

    protected function setUp(): void
    {
        self::bootKernel();
        $this->em = self::getContainer()->get(EntityManagerInterface::class);
        $this->markupCalculator = self::getContainer()->get(MarkupCalculator::class);
    }

    public function testUpdateRoundTripPersistsChanges(): void
    {
        $owner = UserFactory::new()->asStaff()->create();
        $category = CategoryFactory::createOne();
        $subcategory = SubcategoryFactory::createOne(['category' => $category]);
        $manufacturer = ManufacturerFactory::createOne();
        $supplierProduct = SupplierProductFactory::createOne(['product' => null, 'cost' => '10.00']);

        $product = Product::create(
            name: 'Before',
            description: 'First desc',
            category: $category,
            subcategory: $subcategory,
            manufacturer: $manufacturer,
            mfrPartNumber: 'MFR-1',
            owner: $owner,
            isActive: true,
        );
        $product->addSupplierProduct($this->markupCalculator, $supplierProduct);

        $this->em->persist($product);
        $this->em->flush();

        $id = $product->getId();

        $loaded = $this->em->getRepository(Product::class)->find($id);

        $owner2 = UserFactory::new()->asStaff()->create();
        $category2 = CategoryFactory::createOne();
        $subcategory2 = SubcategoryFactory::createOne(['category' => $category2]);
        $manufacturer2 = ManufacturerFactory::createOne();

        $loaded->update(
            markupCalculator: $this->markupCalculator,
            name: 'After',
            description: 'Second desc',
            category: $category2,
            subcategory: $subcategory2,
            manufacturer: $manufacturer2,
            mfrPartNumber: 'MFR-2',
            owner: $owner2,
            isActive: false,
        );

        $this->em->flush();
        $this->em->clear();

        $reloaded = $this->em->getRepository(Product::class)->find($id);
        self::assertSame('After', $reloaded->getName());
        self::assertSame('Second desc', $reloaded->getDescription());
        self::assertSame('MFR-2', $reloaded->getMfrPartNumber());
        self::assertSame($owner2->getId(), $reloaded->getOwner()->getId());
        self::assertSame($category2->getId(), $reloaded->getCategory()->getId());
        self::assertSame($subcategory2->getId(), $reloaded->getSubcategory()->getId());
        self::assertSame($manufacturer2->getId(), $reloaded->getManufacturer()->getId());
        self::assertFalse($reloaded->isActive());
        self::assertSame('5.000', $reloaded->getMarkup());
        self::assertSame('10.50', $reloaded->getSellPrice());
        self::assertSame('12.60', $reloaded->getSellPriceIncVat());
    }

    public function testDeleteRemovesRow(): void
    {
        $owner = UserFactory::new()->asStaff()->create();
        $category = CategoryFactory::new()->create();
        $subcategory = SubcategoryFactory::new()->with(['category' => $category])->create();
        $manufacturer = ManufacturerFactory::new()->create();

        $product = Product::create(
            name: 'To Delete',
            description: 'Desc',
            category: $category,
            subcategory: $subcategory,
            manufacturer: $manufacturer,
            mfrPartNumber: 'DEL-1',
            owner: $owner,
            isActive: true,
        );

        $this->em->persist($product);
        $this->em->flush();

        $id = $product->getId();

        $this->em->remove($product);
        $this->em->flush();
        $this->em->clear();

        self::assertNull($this->em->getRepository(Product::class)->find($id));
    }
}
