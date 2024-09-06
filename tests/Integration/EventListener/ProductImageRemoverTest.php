<?php

namespace App\Tests\Integration\EventListener;

use App\Entity\ProductImage;
use App\Service\Product\ActiveSourceCalculator;
use App\Service\Product\ProductPriceCalculator;
use App\Service\UploadHelper;
use App\Tests\Utilities\TestProduct;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Zenstruck\Foundry\Test\Factories;

class ProductImageRemoverTest extends KernelTestCase
{
    use Factories;

    private EntityManagerInterface $entityManager;

    private TestProduct $testProduct;

    protected function setUp(): void
    {
        $this->entityManager = static::getContainer()->get(EntityManagerInterface::class);
        $this->testProduct = new TestProduct(
            $this->entityManager,
            self::getContainer()->get(ActiveSourceCalculator::class),
            self::getContainer()->get(ProductPriceCalculator::class)
        );
    }

    public function testRemoveImage(): void
    {
        $product = $this->testProduct->create();

        $uploadHelper = self::getContainer()->get(UploadHelper::class);
        $appProductUploads = self::getContainer()->getParameter('app.product_uploads');
        $uploadDir = static::getContainer()->getParameter('kernel.project_dir') . '/public/' . $appProductUploads;

        $dummyImagePath = __DIR__ . '/../../Resources/dummy-image.jpg';
        $dummyImage = new UploadedFile($dummyImagePath, 'dummy-image.jpg', 'image/jpeg', null, true);

        $productImage = new ProductImage();
        $productImage
            ->setProduct($product)
            ->setPosition(1)
            ->setImageFile($dummyImage);

        $productImage->setImageName(
            $uploadHelper->uploadFile($productImage->getImageFile(), $appProductUploads)
        );

        $this->entityManager->persist($productImage);
        $this->entityManager->flush();

        $this->assertFileExists($uploadDir . $productImage->getImageName());

        // Now, remove the ProductImage to trigger preRemove and postRemove
        $this->entityManager->remove($productImage);
        $this->entityManager->flush();

        $this->assertFileDoesNotExist($uploadDir . $productImage->getImageName());
    }
}