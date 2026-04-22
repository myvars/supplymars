<?php

namespace App\Tests\Catalog\Infrastructure;

use App\Catalog\Domain\Model\Category\Category;
use App\Catalog\Domain\Model\Subcategory\Subcategory;
use App\Shared\Domain\ValueObject\PriceModel;
use App\Tests\Shared\Factory\UserFactory;
use App\Tests\Shared\Factory\VatRateFactory;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Zenstruck\Foundry\Test\Factories;

final class SubcategoryUpdateDeleteMappingTest extends KernelTestCase
{
    use Factories;

    private EntityManagerInterface $em;

    protected function setUp(): void
    {
        self::bootKernel();
        $this->em = self::getContainer()->get(EntityManagerInterface::class);
    }

    public function testUpdateRoundTripPersistsChanges(): void
    {
        $owner = UserFactory::new()->asStaff()->create();
        $vatRate = VatRateFactory::new()->withStandardRate()->create();

        $category = Category::create(
            name: 'Category',
            owner: $owner,
            vatRate: $vatRate,
            defaultMarkup: '5.000',
            priceModel: PriceModel::DEFAULT,
            isActive: true,
        );
        $this->em->persist($category);

        $subcategory = Subcategory::create(
            name: 'Before',
            category: $category,
            owner: $owner,
            defaultMarkup: '0.000',
            priceModel: PriceModel::NONE,
            isActive: true,
        );

        $this->em->persist($subcategory);
        $this->em->flush();

        $id = $subcategory->getId();

        $reloaded = $this->em->getRepository(Subcategory::class)->find($id);

        $newOwner = UserFactory::new()->asStaff()->create();

        $newCategory = Category::create(
            name: 'New Category',
            owner: $owner,
            vatRate: $vatRate,
            defaultMarkup: '6.000',
            priceModel: PriceModel::PRETTY_99,
            isActive: true,
        );
        $this->em->persist($newCategory);

        $reloaded->update(
            name: 'After',
            category: $newCategory,
            owner: $newOwner,
            defaultMarkup: '3.250',
            priceModel: PriceModel::PRETTY_99,
            isActive: false,
        );

        $this->em->flush();
        $this->em->clear();

        /** @var Subcategory $reloaded */
        $reloaded = $this->em->getRepository(Subcategory::class)->find($id);
        self::assertSame('After', $reloaded->getName());
        self::assertSame('3.250', $reloaded->getDefaultMarkup());
        self::assertSame(PriceModel::PRETTY_99, $reloaded->getPriceModel());
        self::assertSame($newOwner->getId(), $reloaded->getOwner()?->getId());
        self::assertSame($newCategory->getId(), $reloaded->getCategory()->getId());
        self::assertFalse($reloaded->isActive());
    }

    public function testDeleteRemovesRow(): void
    {
        $owner = UserFactory::new()->asStaff()->create();
        $vatRate = VatRateFactory::new()->withStandardRate()->create();

        $category = Category::create(
            name: 'Parent',
            owner: $owner,
            vatRate: $vatRate,
            defaultMarkup: '5.000',
            priceModel: PriceModel::DEFAULT,
            isActive: true,
        );
        $this->em->persist($category);

        $subcategory = Subcategory::create(
            name: 'To Delete',
            category: $category,
            owner: $owner,
            defaultMarkup: '1.000',
            priceModel: PriceModel::NONE,
            isActive: true,
        );

        $this->em->persist($subcategory);
        $this->em->flush();

        $id = $subcategory->getId();

        $this->em->remove($subcategory);
        $this->em->flush();
        $this->em->clear();

        self::assertNull($this->em->getRepository(Subcategory::class)->find($id));
    }
}
