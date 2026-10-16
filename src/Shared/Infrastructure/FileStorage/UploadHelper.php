<?php

namespace App\Shared\Infrastructure\FileStorage;

use League\Flysystem\FilesystemException;
use League\Flysystem\FilesystemOperator;
use League\Flysystem\Visibility;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\DependencyInjection\Attribute\Target;
use Symfony\Component\HttpFoundation\File\Exception\CannotWriteFileException;
use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\String\Slugger\SluggerInterface;

final readonly class UploadHelper
{
    public function __construct(
        #[Target('products_fsFilesystem')]
        private FilesystemOperator $uploadFilesystem,
        private SluggerInterface $slugger,
        #[Autowire('%env(bool:PLAYGROUND_MODE)%')]
        private bool $playgroundMode = false,
    ) {
    }

    /**
     * Stores the file as it is. Validate it first with a File or Image constraint that has an extensions
     * allowlist: the stored extension follows the detected content, so unchecked HTML is stored as .html.
     */
    public function uploadFile(File $file, ?string $directory, ?string $existingFilename = null): string
    {
        if ($this->playgroundMode) {
            throw new CannotWriteFileException('File uploads are disabled in playground mode.');
        }

        $originalFileName = $this->getOriginalFileName($file);
        $newFileName = $this->getNewFilename($file, $originalFileName);

        $this->doUpload($file, $directory, $newFileName, true);

        if (null !== $existingFilename) {
            $this->deleteFile($this->createFilePath($directory, $existingFilename));
        }

        return $newFileName;
    }

    public function deleteFile(string $path): bool
    {
        try {
            if ($this->uploadFilesystem->fileExists($path)) {
                $this->uploadFilesystem->delete($path);
            }

            return true;
        } catch (FilesystemException) {
            return false;
        }
    }

    private function doUpload(File $file, ?string $directory, string $fileName, bool $isPublic = true): void
    {
        $path = $this->createFilePath($directory, $fileName);
        $stream = fopen($file->getPathname(), 'r');

        if ($stream === false) {
            throw new CannotWriteFileException('Unable to open file stream for upload.');
        }

        try {
            $this->uploadFilesystem->writeStream($path, $stream, [
                'visibility' => $isPublic ? Visibility::PUBLIC : Visibility::PRIVATE,
                // The key Flysystem's S3 adapter sends as the object's Content-Type
                'mimetype' => $file->getMimeType(),
            ]);
        } catch (FilesystemException $filesystemException) {
            throw new CannotWriteFileException($filesystemException->getMessage(), $filesystemException->getCode(), $filesystemException);
        } finally {
            if (is_resource($stream)) {
                fclose($stream);
            }
        }
    }

    private function getOriginalFileName(File $file): string
    {
        return $file instanceof UploadedFile
            ? $file->getClientOriginalName()
            : $file->getFilename();
    }

    private function getNewFilename(File $file, string $originalName): string
    {
        $base = pathinfo($originalName, PATHINFO_FILENAME) ?: 'file';
        $slug = (string) $this->slugger->slug($base)->lower();

        // From the detected content, never the client's filename: the extension decides how the file is served.
        $ext = $file->guessExtension() ?: 'bin';

        $random = bin2hex(random_bytes(8));

        return sprintf('%s-%s.%s', $slug, $random, $ext);
    }

    private function createFilePath(?string $directory, string $fileName): string
    {
        return null === $directory || '' === $directory || '0' === $directory ? $fileName : $directory . '/' . $fileName;
    }
}
