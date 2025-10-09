<?php

namespace App\Tests\Integration\Service\Product;

use App\Factory\ProductFactory;
use App\Service\Crud\Common\CrudOptions;
use App\Service\Product\ProductImageCreator;
use App\Service\Utility\UploadHelper;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Exception\ORMException;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Zenstruck\Foundry\Test\Factories;

class ProductImageCreatorIntegrationTest extends KernelTestCase
{
    use Factories;

    private ProductImageCreator $productImageCreator;

    private string $appProductUploads;

    private UploadHelper $uploadHelper;

    private EntityManagerInterface $entityManager;

    protected function setUp(): void
    {
        self::bootKernel();
        $this->entityManager = static::getContainer()->get(EntityManagerInterface::class);
        $validator = static::getContainer()->get(ValidatorInterface::class);
        $this->uploadHelper = static::getContainer()->get(UploadHelper::class);
        $this->appProductUploads = static::getContainer()->getParameter('app.product_uploads');
        $this->productImageCreator = new ProductImageCreator($this->entityManager, $validator, $this->uploadHelper, $this->appProductUploads);
    }

    public function testHandleWithValidProduct(): void
    {
        $product = ProductFactory::createOne();
        $imageFile = new UploadedFile(
            __DIR__ . '/../../../../tests/Resources/dummy-image.jpg',
            'dummy-image.jpg',
            'image/jpeg',
            null,
            true
        );

        $crudOptions = new CrudOptions();
        $crudOptions->setEntity($product);
        $crudOptions->setCrudActionContext(['imageFiles' => [$imageFile]]);

        $this->productImageCreator->handle($crudOptions);
        $this->entityManager->refresh($product);

        $productImages = $product->getProductImages();
        $this->assertCount(1, $productImages);
        $this->assertStringContainsString('dummy-image-', $productImages[0]->getImageName());

        // Clean up
        $result = $this->uploadHelper->deleteFile($this->appProductUploads.$productImages[0]->getImageName());
        $this->assertTrue($result);
    }
}
