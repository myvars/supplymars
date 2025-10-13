<?php

namespace App\Tests\Catalog\Application\Handler\Product;

use App\Catalog\Application\Command\Product\UpdateProduct;
use App\Catalog\Application\Handler\Product\UpdateProductHandler;
use App\Catalog\Domain\Model\Category\CategoryId;
use App\Catalog\Domain\Model\Manufacturer\ManufacturerId;
use App\Catalog\Domain\Model\Subcategory\SubcategoryId;
use App\Catalog\Domain\Repository\ProductRepository;
use App\Tests\Shared\Factory\ProductFactory;
use App\Tests\Shared\Factory\UserFactory;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Zenstruck\Foundry\Test\Factories;

class UpdateProductHandlerTest extends KernelTestCase
{
    use Factories;

    private UpdateProductHandler $handler;
    private ProductRepository $products;

    protected function setUp(): void
    {
        self::bootKernel();
        $this->handler = self::getContainer()->get(UpdateProductHandler::class);
        $this->products = self::getContainer()->get(ProductRepository::class);
    }

    public function testHandleUpdatesProduct(): void
    {
        $product = ProductFactory::new()->withActiveSource()->create();
        $owner = UserFactory::new()->asStaff()->create();

        $command = new UpdateProduct(
            id: $product->getPublicId(),
            name: 'Updated Product Name',
            description: 'Updated description',
            categoryId: CategoryId::fromInt($product->getCategory()->getId()),
            subcategoryId: SubcategoryId::fromInt($product->getSubcategory()->getId()),
            manufacturerId: ManufacturerId::fromInt($product->getManufacturer()->getId()),
            mfrPartNumber: 'UPD-1234',
            ownerId: $owner->getId(),
            isActive: $product->isActive(),
        );

        $result = ($this->handler)($command);

        self::assertTrue($result->ok);

        $persisted = $this->products->getByPublicId($product->getPublicId());
        self::assertSame('Updated Product Name', $persisted->getName());
        self::assertSame('UPD-1234', $persisted->getMfrPartNumber());
        self::assertSame($owner->getId(), $persisted->getOwner()?->getId());
    }
}
