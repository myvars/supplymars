<?php

namespace App\Tests\Integration\EventListener\DoctrineEvents;

use App\Entity\ProductImage;
use App\Factory\SupplierProductFactory;
use App\Service\Utility\UploadHelper;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Zenstruck\Foundry\Test\Factories;

class ProductImageRemoverIntegrationTest extends KernelTestCase
{
    use Factories;

    private EntityManagerInterface $entityManager;

    protected function setUp(): void
    {
        $this->entityManager = static::getContainer()->get(EntityManagerInterface::class);
    }

    public function testRemoveImage(): void
    {
        $supplierProduct = SupplierProductFactory::createOne();
        $product = $supplierProduct->getProduct();

        $uploadHelper = self::getContainer()->get(UploadHelper::class);
        $appProductUploads = self::getContainer()->getParameter('app.product_uploads');
        $uploadDir = static::getContainer()->getParameter('kernel.project_dir') . '/public/' . $appProductUploads;

        $dummyImagePath = __DIR__ . '/../../../Resources/dummy-image.jpg';
        $dummyImage = new UploadedFile($dummyImagePath, 'dummy-image.jpg', 'image/jpeg', null, true);

        $productImage = ProductImage::createFromUploadedFile($product, $dummyImage, 1);

        $productImage->updateImageName(
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