<?php

namespace App\Tests\Integration\Service\Product;

use App\Service\Crud\Common\CrudContext;
use App\Service\Product\ProductImageCreator;
use App\Shared\Infrastructure\FileStorage\UploadHelper;
use Doctrine\ORM\EntityManagerInterface;
use tests\Shared\Factory\ProductFactory;
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

    private EntityManagerInterface $em;

    protected function setUp(): void
    {
        self::bootKernel();
        $this->em = static::getContainer()->get(EntityManagerInterface::class);
        $validator = static::getContainer()->get(ValidatorInterface::class);
        $this->uploadHelper = static::getContainer()->get(UploadHelper::class);
        $this->appProductUploads = static::getContainer()->getParameter('app.product_uploads');
        $this->productImageCreator = new ProductImageCreator($this->em, $validator, $this->uploadHelper, $this->appProductUploads);
    }

    public function testHandleWithValidProduct(): void
    {
        $product = ProductFactory::createOne();
        $imageFile = new UploadedFile(
            __DIR__ . '/../../../../tests/Shared/Resources/dummy-image.jpg',
            'dummy-image.jpg',
            'image/jpeg',
            null,
            true
        );

        $context = new CrudContext();
        $context->setEntity($product);
        $context->setCrudHandlerContext(['imageFiles' => [$imageFile]]);

        ($this->productImageCreator)($context);
        $this->em->refresh($product);

        $productImages = $product->getProductImages();
        $this->assertCount(1, $productImages);
        $this->assertStringContainsString('dummy-image-', $productImages[0]->getImageName());

        // Clean up
        $result = $this->uploadHelper->deleteFile($this->appProductUploads.$productImages[0]->getImageName());
        $this->assertTrue($result);
    }
}
