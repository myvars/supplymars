<?php

namespace App\Tests\Integration\EventListener\DoctrineEvents;

use App\Entity\Product;
use App\Factory\ProductFactory;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Zenstruck\Foundry\Test\Factories;

class ProductPriceUpdaterIntegrationTest extends KernelTestCase
{
    use Factories;

    private EntityManagerInterface $entityManager;

    protected function setUp(): void
    {
        self::bootKernel();
        $this->entityManager = static::getContainer()->get(EntityManagerInterface::class);
    }

    public function testPreUpdateRecalculatesPriceWhenFieldsChange(): void
    {
        $product = ProductFactory::createOne(['cost' => 100, 'defaultMarkup' => '20.000'])->_real();

        $product->setCost(120);

        $this->entityManager->flush();

        $updatedProduct = $this->entityManager->getRepository(Product::class)->find($product->getId());
        $this->assertNotNull($updatedProduct->getSellPrice());
    }

    public function testPreUpdateSkipsRecalculationWhenNoRelevantFieldsChange(): void
    {
        $product = ProductFactory::createOne(['cost' => 100, 'defaultMarkup' => '20.000'])->_real();

        $product->setName('New Product Name');

        $this->entityManager->flush();

        $updatedProduct = $this->entityManager->getRepository(Product::class)->find($product->getId());
        $this->assertEquals(100, $updatedProduct->getCost());
    }
}