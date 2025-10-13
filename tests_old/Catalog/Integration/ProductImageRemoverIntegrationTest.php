<?php

namespace App\Tests\Catalog\Integration;

use App\Catalog\Domain\Model\ProductImage\ProductImage;
use App\Shared\Infrastructure\FileStorage\UploadHelper;
use Doctrine\ORM\EntityManagerInterface;
use tests\Shared\Factory\SupplierProductFactory;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Zenstruck\Foundry\Test\Factories;

class ProductImageRemoverIntegrationTest extends KernelTestCase
{
    use Factories;

    private EntityManagerInterface $em;

    protected function setUp(): void
    {
        $this->em = static::getContainer()->get(EntityManagerInterface::class);
    }

    public function testRemoveImage(): void
    {
        $supplierProduct = SupplierProductFactory::createOne();
        $product = $supplierProduct->getProduct();

        $uploadHelper = self::getContainer()->get(UploadHelper::class);
        $appProductUploads = self::getContainer()->getParameter('app.product_uploads');
        $uploadDir = static::getContainer()->getParameter('kernel.project_dir') . '/public/' . $appProductUploads;

        $dummyImagePath = __DIR__ . '/../../Shared/Resources/dummy-image.jpg';
        $dummyImage = new UploadedFile($dummyImagePath, 'dummy-image.jpg', 'image/jpeg', null, true);

        $productImage = ProductImage::createFromUploadedFile($product, $dummyImage, 1);

        $productImage->changeImageName(
            $uploadHelper->uploadFile($productImage->getImageFile(), $appProductUploads)
        );

        $this->em->persist($productImage);
        $this->em->flush();

        $this->assertFileExists($uploadDir . $productImage->getImageName());

        // Now, remove the ProductImage to trigger preRemove and postRemove
        $this->em->remove($productImage);
        $this->em->flush();

        $this->assertFileDoesNotExist($uploadDir . $productImage->getImageName());
    }
}
