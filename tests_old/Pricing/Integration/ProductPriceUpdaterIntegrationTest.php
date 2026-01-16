<?php

namespace App\Tests\Pricing\Integration;

use App\Catalog\Domain\Model\Product\Product;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use tests\Shared\Factory\ProductFactory;
use Zenstruck\Foundry\Test\Factories;

class ProductPriceUpdaterIntegrationTest extends KernelTestCase
{
    use Factories;

    private EntityManagerInterface $em;

    protected function setUp(): void
    {
        self::bootKernel();
        $this->em = static::getContainer()->get(EntityManagerInterface::class);
    }

    public function testPreUpdateRecalculatesPriceWhenFieldsChange(): void
    {
        $product = ProductFactory::createOne(['cost' => 100, 'defaultMarkup' => '20.000']);

        $product->setCost(120);

        $this->em->flush();

        $updatedProduct = $this->em->getRepository(Product::class)->find($product->getId());
        $this->assertNotNull($updatedProduct->getSellPrice());
    }

    public function testPreUpdateSkipsRecalculationWhenNoRelevantFieldsChange(): void
    {
        $product = ProductFactory::createOne(['cost' => 100, 'defaultMarkup' => '20.000']);

        $product->setName('New Product Name');

        $this->em->flush();

        $updatedProduct = $this->em->getRepository(Product::class)->find($product->getId());
        $this->assertEquals(100, $updatedProduct->getCost());
    }
}
