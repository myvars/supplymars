<?php

namespace App\Tests\Integration\Service\Product;

use App\Factory\ProductFactory;
use App\Factory\ProductImageFactory;
use App\Service\Crud\Common\CrudOptions;
use App\Service\Product\ProductImageOrderer;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Zenstruck\Foundry\Test\Factories;

class ProductImageOrdererIntegrationTest extends KernelTestCase
{
    use Factories;

    private ProductImageOrderer $productImageOrderer;

    protected function setUp(): void
    {
        self::bootKernel();
        $entityManager = static::getContainer()->get(EntityManagerInterface::class);
        $this->productImageOrderer = new ProductImageOrderer($entityManager);
    }

    public function testHandleWithValidProduct(): void
    {
        $product = ProductFactory::createOne()->_real();
        $productImage1 = ProductImageFactory::createOne(['product' => $product, 'position' => 1])->_real();
        $productImage2 = ProductImageFactory::createOne(['product' => $product, 'position' => 2])->_real();

        $crudOptions = new CrudOptions();
        $crudOptions->setEntity($product);
        $crudOptions->setCrudActionContext(['orderedIds' => [$productImage2->getId(), $productImage1->getId()]]);

        $this->productImageOrderer->handle($crudOptions);

        $this->assertSame(2, $productImage1->getPosition());
        $this->assertSame(1, $productImage2->getPosition());
    }
}