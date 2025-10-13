<?php

namespace App\Tests\Catalog\Infrastructure;

use App\Catalog\Domain\Model\Category\Category;
use App\Shared\Domain\ValueObject\PriceModel;
use App\Tests\Shared\Factory\UserFactory;
use App\Tests\Shared\Factory\VatRateFactory;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

final class CategoryUpdateDeleteMappingTest extends KernelTestCase
{
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
            name: 'Before',
            owner: $owner,
            vatRate: $vatRate,
            defaultMarkup: '5.000',
            priceModel: PriceModel::DEFAULT,
            isActive: true,
        );

        $this->em->persist($category);
        $this->em->flush();
        $id = $category->getId();

        $loaded = $this->em->getRepository(Category::class)->find($id);

        $owner2 = UserFactory::new()->asStaff()->create();
        $vatRate2 = VatRateFactory::new()->withStandardRate()->create();

        $loaded->update(
            name: 'After',
            owner: $owner2,
            vatRate: $vatRate2,
            defaultMarkup: '7.500',
            priceModel: PriceModel::PRETTY_99,
            isActive: false,
        );

        $this->em->flush();
        $this->em->clear();

        $reloaded = $this->em->getRepository(Category::class)->find($id);
        self::assertSame('After', $reloaded->getName());
        self::assertSame('7.500', $reloaded->getDefaultMarkup());
        self::assertSame(PriceModel::PRETTY_99, $reloaded->getPriceModel());
        self::assertSame($owner2->getId(), $reloaded->getOwner()->getId());
        self::assertSame($vatRate2->getId(), $reloaded->getVatRate()->getId());
        self::assertFalse($reloaded->isActive());
    }

    public function testDeleteRemovesRow(): void
    {
        $owner = UserFactory::new()->asStaff()->create();
        $vatRate = VatRateFactory::new()->withStandardRate()->create();

        $category = Category::create(
            name: 'To Delete',
            owner: $owner,
            vatRate: $vatRate,
            defaultMarkup: '5.000',
            priceModel: PriceModel::DEFAULT,
            isActive: true,
        );

        $this->em->persist($category);
        $this->em->flush();
        $id = $category->getId();

        $this->em->remove($category);
        $this->em->flush();
        $this->em->clear();

        self::assertNull($this->em->getRepository(Category::class)->find($id));
    }
}
