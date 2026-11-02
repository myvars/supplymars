<?php

declare(strict_types=1);

namespace App\Tests\Shared\Infrastructure\FileStorage;

use App\Shared\Infrastructure\FileStorage\UploadHelper;
use League\Flysystem\Filesystem;
use League\Flysystem\Visibility;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\File\Exception\CannotWriteFileException;
use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\String\Slugger\SluggerInterface;
use Symfony\Component\String\UnicodeString;

final class UploadHelperTest extends TestCase
{
    private function helper(Filesystem $filesystem, bool $playgroundMode = false): UploadHelper
    {
        $slugger = $this->createStub(SluggerInterface::class);
        $slugger
            ->method('slug')
            ->willReturn(new UnicodeString('dummy-image'));

        return new UploadHelper($filesystem, $slugger, $playgroundMode);
    }

    public function testUploadFileWritesStreamWithPublicVisibility(): void
    {
        $filesystem = $this->createMock(Filesystem::class);

        $dummyImagePath = __DIR__ . '/../../../Shared/Resources/dummy-image.jpg';

        $file = $this->createMock(File::class);
        $file->method('getPathname')->willReturn($dummyImagePath);
        $file->method('getMimeType')->willReturn('image/jpeg');
        $file->method('guessExtension')->willReturn('jpg');
        $file->expects(self::once())
            ->method('getFilename')
            ->willReturn('dummy-image.jpg');

        $directory = 'images';

        $filesystem
            ->expects(self::once())
            ->method('writeStream')
            ->with(
                self::stringContains($directory . '/'),
                self::anything(),
                self::equalTo([
                    'visibility' => Visibility::PUBLIC,
                    'mimetype' => 'image/jpeg',
                ])
            );

        $newFileName = $this->helper($filesystem)->uploadFile($file, $directory);
        self::assertStringStartsWith('dummy-image-', $newFileName);
        self::assertStringEndsWith('.jpg', $newFileName);
    }

    public function testUploadFileTakesExtensionFromContentNotClientName(): void
    {
        $filesystem = $this->createMock(Filesystem::class);

        $file = new UploadedFile(__DIR__ . '/../../../Shared/Resources/dummy-image.jpg', 'evil.html', test: true);

        $filesystem
            ->expects(self::once())
            ->method('writeStream')
            ->with(self::stringEndsWith('.jpg'), self::anything(), self::equalTo([
                'visibility' => Visibility::PUBLIC,
                'mimetype' => 'image/jpeg',
            ]));

        $newFileName = $this->helper($filesystem)->uploadFile($file, 'images');

        self::assertStringEndsWith('.jpg', $newFileName);
    }

    public function testUploadFileDeletesExistingWhenPresent(): void
    {
        $filesystem = $this->createMock(Filesystem::class);

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

        $filesystem
            ->expects(self::once())
            ->method('writeStream')
            ->with(
                self::stringContains($directory . '/'),
                self::anything(),
                self::arrayHasKey('visibility')
            );

        $filesystem
            ->expects(self::once())
            ->method('fileExists')
            ->with($directory . '/' . $existingBasename)
            ->willReturn(true);

        $filesystem
            ->expects(self::once())
            ->method('delete')
            ->with($directory . '/' . $existingBasename);

        $this->helper($filesystem)->uploadFile($file, $directory, $existingBasename);
    }

    public function testDeleteFileDeletesWhenExists(): void
    {
        $filesystem = $this->createMock(Filesystem::class);

        $path = 'images/test.jpg';

        $filesystem
            ->expects(self::once())
            ->method('fileExists')
            ->with($path)
            ->willReturn(true);

        $filesystem
            ->expects(self::once())
            ->method('delete')
            ->with($path);

        $result = $this->helper($filesystem)->deleteFile($path);
        self::assertTrue($result);
    }

    public function testDeleteFileNoOpWhenMissing(): void
    {
        $filesystem = $this->createMock(Filesystem::class);

        $path = 'images/missing.jpg';

        $filesystem
            ->expects(self::once())
            ->method('fileExists')
            ->with($path)
            ->willReturn(false);

        $filesystem
            ->expects(self::never())
            ->method('delete');

        $result = $this->helper($filesystem)->deleteFile($path);
        self::assertTrue($result);
    }

    public function testUploadFileThrowsInPlaygroundMode(): void
    {
        $playgroundHelper = $this->helper($this->createStub(Filesystem::class), playgroundMode: true);

        $file = $this->createStub(File::class);

        $this->expectException(CannotWriteFileException::class);
        $this->expectExceptionMessageIsOrContains('playground mode');

        $playgroundHelper->uploadFile($file, 'images');
    }
}
