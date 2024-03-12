<?php

namespace App\Service;

use Gedmo\Sluggable\Util\Urlizer;
use League\Flysystem\Filesystem;
use League\Flysystem\FilesystemException;
use League\Flysystem\Visibility;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\File\Exception\CannotWriteFileException;
use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\HttpFoundation\File\UploadedFile;

readonly class UploadHelper
{
    public function __construct(
        #[Autowire(service: 'oneup_flysystem.products_fs_filesystem')]
        private Filesystem $uploadFilesystem)
    {
    }

    public function uploadFile(File $file, ?string $directory, ?string $existingFilename=null): string
    {
        $originalFileName = $this->getOriginalFileName($file);
        $newFileName = $this->getNewFilename($file, $originalFileName);
        $this->doUpload($file, $directory, $newFileName, true);

        if (null !== $existingFilename) {
            $path = $this->createFilePath($directory, $existingFilename);
            $this->deleteFile($path);
        }

        return $newFileName;
    }

    public function deleteFile(string $path): ?bool
    {
        try {
            $this->uploadFilesystem->delete($path);

            return true;
        } catch (FilesystemException $exception) {
            return false;
        }
    }

    public function getPublicFilePath(string $path): string
    {
        return $this->uploadFilesystem->publicUrl($path);
    }

    private function doUpload(File $file, $directory, $fileName, bool $isPublic=true): void
    {
        $path = $this->createFilePath($directory, $fileName);
        $stream = fopen($file->getPathname(), 'r');

        try {
            $this->uploadFilesystem->writeStream($path, $stream, [
                'visibility' => $isPublic ? Visibility::PUBLIC : Visibility::PRIVATE,
                'mime-type' => $file->getMimeType(),
            ]);
        } catch (FilesystemException $e) {
            throw new CannotWriteFileException($e->getMessage());
        }

        if (is_resource($stream)) {
            fclose($stream);
        }
    }

    private function getOriginalFileName(File $file): string
    {
        if ($file instanceof UploadedFile) {
            return $file->getClientOriginalName();
        }

        return $file->getFilename();
    }

    private function getNewFilename(File $file, $fileName): string
    {
        return Urlizer::urlize(pathinfo($fileName, PATHINFO_FILENAME))
            . '-' . uniqid() . '.' . $file->guessExtension();
    }

    private function createFilePath(?string $directory, string $fileName): string
    {
        return !empty($directory) ? $directory . '/' . $fileName : $fileName;
    }
}