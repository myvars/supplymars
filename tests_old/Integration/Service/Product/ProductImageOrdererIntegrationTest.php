<?php

namespace App\Tests\Integration\Service\Product;

use App\Service\Crud\Common\CrudContext;
use App\Service\Product\ProductImageOrderer;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use tests\Shared\Factory\ProductFactory;
use tests\Shared\Factory\ProductImageFactory;
use Zenstruck\Foundry\Test\Factories;

class ProductImageOrdererIntegrationTest extends KernelTestCase
{
    use Factories;

    private ProductImageOrderer $productImageOrderer;

    protected function setUp(): void
    {
        self::bootKernel();
        $em = static::getContainer()->get(EntityManagerInterface::class);
        $this->productImageOrderer = new ProductImageOrderer($em);
    }

    public function testHandleWithValidProduct(): void
    {
        $product = ProductFactory::createOne();
        $productImage1 = ProductImageFactory::createOne(['product' => $product, 'position' => 1]);
        $productImage2 = ProductImageFactory::createOne(['product' => $product, 'position' => 2]);

        $context = new CrudContext();
        $context->setEntity($product);
        $context->setCrudHandlerContext(['orderedIds' => [$productImage2->getId(), $productImage1->getId()]]);

        ($this->productImageOrderer)($context);

        $this->assertSame(2, $productImage1->getPosition());
        $this->assertSame(1, $productImage2->getPosition());
    }
}
