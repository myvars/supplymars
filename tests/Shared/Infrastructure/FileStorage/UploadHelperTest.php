<?php

namespace App\Tests\Shared\Infrastructure\FileStorage;

use App\Shared\Infrastructure\FileStorage\UploadHelper;
use League\Flysystem\Filesystem;
use League\Flysystem\Visibility;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\String\Slugger\SluggerInterface;
use Symfony\Component\String\UnicodeString;

final class UploadHelperTest extends TestCase
{
    private Filesystem $filesystem;
    private SluggerInterface $slugger;
    private UploadHelper $helper;

    protected function setUp(): void
    {
        $this->filesystem = $this->createMock(Filesystem::class);

        $this->slugger = $this->createStub(SluggerInterface::class);
        $this->slugger
            ->method('slug')
            ->willReturn(new UnicodeString('dummy-image'));

        $this->helper = new UploadHelper($this->filesystem, $this->slugger);
    }

    private function stubFile(string $path, string $mimeType, string $extension, string $filename): File
    {
        $file = $this->createStub(File::class);
        $file->method('getPathname')->willReturn($path);
        $file->method('getMimeType')->willReturn($mimeType);
        $file->method('guessExtension')->willReturn($extension);

        // when interaction is asserted, use expects\(\) on a dedicated mock in that test
        return $file;
    }

    public function testUploadFileWritesStreamWithPublicVisibility(): void
    {
        $dummyImagePath = __DIR__ . '/../../../Shared/Resources/dummy-image.jpg';

        $file = $this->createMock(File::class);
        $file->method('getPathname')->willReturn($dummyImagePath);
        $file->method('getMimeType')->willReturn('image/jpeg');
        $file->method('guessExtension')->willReturn('jpg');
        $file->expects(self::once())
            ->method('getFilename')
            ->willReturn('dummy-image.jpg');

        $directory = 'images';

        $this->filesystem
            ->expects(self::once())
            ->method('writeStream')
            ->with(
                self::stringContains($directory . '/'),
                self::anything(),
                self::equalTo([
                    'visibility' => Visibility::PUBLIC,
                    'mime-type' => 'image/jpeg',
                ])
            );

        $newFileName = $this->helper->uploadFile($file, $directory);
        self::assertStringStartsWith('dummy-image-', $newFileName);
        self::assertStringEndsWith('.jpg', $newFileName);
    }

    public function testUploadFileDeletesExistingWhenPresent(): void
    {
        $dummyImagePath = __DIR__ . '/../../../Shared/Resources/dummy-image.jpg';

        $file = $this->createMock(File::class);
        $file->method('getPathname')->willReturn($dummyImagePath);
        $file->method('getMimeType')->willReturn('image/jpeg');
        $file->method('guessExtension')->willReturn('jpg');
        $file->expects(self::once())
            ->method('getFilename')
            ->willReturn('dummy-image.jpg');

        $directory = 'images';
        $existingBasename = 'old.jpg';

        $this->filesystem
            ->expects(self::once())
            ->method('writeStream')
            ->with(
                self::stringContains($directory . '/'),
                self::anything(),
                self::arrayHasKey('visibility')
            );

        $this->filesystem
            ->expects(self::once())
            ->method('fileExists')
            ->with($directory . '/' . $existingBasename)
            ->willReturn(true);

        $this->filesystem
            ->expects(self::once())
            ->method('delete')
            ->with($directory . '/' . $existingBasename);

        $this->helper->uploadFile($file, $directory, $existingBasename);
    }

    public function testDeleteFileDeletesWhenExists(): void
    {
        $path = 'images/test.jpg';

        $this->filesystem
            ->expects(self::once())
            ->method('fileExists')
            ->with($path)
            ->willReturn(true);

        $this->filesystem
            ->expects(self::once())
            ->method('delete')
            ->with($path);

        $result = $this->helper->deleteFile($path);
        self::assertTrue($result);
    }

    public function testDeleteFileNoOpWhenMissing(): void
    {
        $path = 'images/missing.jpg';

        $this->filesystem
            ->expects(self::once())
            ->method('fileExists')
            ->with($path)
            ->willReturn(false);

        $this->filesystem
            ->expects(self::never())
            ->method('delete');

        $result = $this->helper->deleteFile($path);
        self::assertTrue($result);
    }

    public function testGetPublicFilePath(): void
    {
        $path = 'images/test.jpg';
        $expected = 'https://cdn.example.com/images/test.jpg';

        $this->filesystem
            ->expects(self::once())
            ->method('publicUrl')
            ->with($path)
            ->willReturn($expected);

        self::assertSame($expected, $this->helper->getPublicFilePath($path));
    }
}
