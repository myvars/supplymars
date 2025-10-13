<?php

namespace App\Tests\Unit\Service\Utility;

use App\Shared\Infrastructure\FileStorage\UploadHelper;
use League\Flysystem\Filesystem;
use League\Flysystem\Visibility;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\String\Slugger\SluggerInterface;
use Symfony\Component\String\UnicodeString;

class UploadHelperTest extends TestCase
{
    public MockObject $sluggerMock;

    private MockObject $filesystemMock;

    private UploadHelper $uploadHelper;

    protected function setUp(): void
    {
        $this->filesystemMock = $this->createMock(Filesystem::class);
        $this->sluggerMock = $this->createMock(SluggerInterface::class);
        $this->sluggerMock
            ->method('slug')
            ->willReturn(new UnicodeString('dummy-image'));
        $this->uploadHelper = new UploadHelper($this->filesystemMock, $this->sluggerMock);
    }

    public function testUploadFile(): void
    {
        $dummyImagePath = __DIR__ . '/../../../Shared/Resources/dummy-image.jpg';

        $fileMock = $this->createMock(File::class);
        $fileMock->method('getPathname')->willReturn($dummyImagePath);
        $fileMock->method('getMimeType')->willReturn('image/jpeg');
        $fileMock->method('guessExtension')->willReturn('jpg');

        $directory = 'test_dir';
        $originalFileName = 'dummy-image.jpg';

        $fileMock->expects($this->once())->method('getFilename')->willReturn($originalFileName);
        $this->filesystemMock->expects($this->once())
            ->method('writeStream')
            ->with(
                $this->stringContains($directory),
                $this->anything(),
                $this->equalTo([
                    'visibility' => Visibility::PUBLIC,
                    'mime-type' => 'image/jpeg',
                ])
            );

        $newFileName = $this->uploadHelper->uploadFile($fileMock, $directory);
        $this->assertStringContainsString('dummy-image-', $newFileName);
    }

    public function testUploadFileWithExistingFilename(): void
    {
        $dummyImagePath = __DIR__ . '/../../../Shared/Resources/dummy-image.jpg';

        $fileMock = $this->createMock(File::class);
        $fileMock->method('getPathname')->willReturn($dummyImagePath);
        $fileMock->method('getMimeType')->willReturn('image/jpeg');
        $fileMock->method('guessExtension')->willReturn('jpg');

        $directory = 'test_dir';
        $originalFileName = 'dummy-image.jpg';

        $fileMock->expects($this->once())->method('getFilename')->willReturn($originalFileName);
        $this->filesystemMock->expects($this->once())
            ->method('writeStream')
            ->with(
                $this->stringContains($directory),
                $this->anything(),
                $this->equalTo([
                    'visibility' => Visibility::PUBLIC,
                    'mime-type' => 'image/jpeg',
                ])
            );

        $existingFilename = 'test_dir/test.jpg';
        $this->uploadHelper->uploadFile($fileMock, $directory, $existingFilename);

        $this->filesystemMock->expects($this->once())->method('delete')->with($existingFilename);

        $result = $this->uploadHelper->deleteFile($existingFilename);

        $this->assertTrue($result);
    }

    public function testDeleteFile(): void
    {
        $path = 'test_dir/test.jpg';
        $this->filesystemMock->expects($this->once())->method('delete')->with($path);

        $result = $this->uploadHelper->deleteFile($path);

        $this->assertTrue($result);
    }

    public function testGetPublicFilePath(): void
    {
        $path = 'test_dir/test.jpg';
        $expectedUrl = 'https://example.com/test_dir/test.jpg';
        $this->filesystemMock->expects($this->once())->method('publicUrl')->with($path)->willReturn($expectedUrl);

        $url = $this->uploadHelper->getPublicFilePath($path);

        $this->assertEquals($expectedUrl, $url);
    }
}
