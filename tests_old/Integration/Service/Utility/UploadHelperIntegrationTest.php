<?php

namespace App\Tests\Integration\Service\Utility;

use App\Shared\Infrastructure\FileStorage\UploadHelper;
use tests\Shared\Factory\ProductImageFactory;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Zenstruck\Foundry\Test\Factories;

class UploadHelperIntegrationTest extends KernelTestCase
{
    use Factories;

    private UploadHelper $uploadHelper;

    private string $appProductUploads;

    protected function setUp(): void
    {
        self::bootKernel();
        $this->uploadHelper = static::getContainer()->get(UploadHelper::class);
        $this->appProductUploads = static::getContainer()->getParameter('app.product_uploads');
    }

    public function testUploadFileSuccessfully(): void
    {
        $productImage = ProductImageFactory::createOne();

        $newFileName = $this->uploadHelper->uploadFile($productImage->getImageFile(), $this->appProductUploads);
        $this->assertStringContainsString('dummy-image-', $newFileName);

        // Clean up
        $result = $this->uploadHelper->deleteFile($this->appProductUploads.$newFileName);
        $this->assertTrue($result);
    }
}
