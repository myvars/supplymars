<?php

namespace App\Tests\Unit\Service;


use App\Service\UploadHelper;
use League\Flysystem\Filesystem;
use League\Flysystem\Visibility;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\File\File;

class UploadHelperTest extends TestCase
{
    private Filesystem $filesystemMock;
    private UploadHelper $uploadHelper;

    protected function setUp(): void
    {
        $this->filesystemMock = $this->createMock(Filesystem::class);
        $this->uploadHelper = new UploadHelper($this->filesystemMock);
    }

    public function testUploadFile(): void
    {
        $dummyImagePath = __DIR__ . '/../../Resources/dummy-image.jpg';

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

        $this->uploadHelper->uploadFile($fileMock, $directory);
    }

    public function testUploadFileWithExistingFilename(): void
    {
        $dummyImagePath = __DIR__ . '/../../Resources/dummy-image.jpg';

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