<?php

namespace App\Service\Utility;

use League\Flysystem\Filesystem;
use League\Flysystem\FilesystemException;
use League\Flysystem\Visibility;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\File\Exception\CannotWriteFileException;
use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\String\Slugger\SluggerInterface;

class UploadHelper
{
    public function __construct(
        #[Autowire(service: 'oneup_flysystem.products_fs_filesystem')]
        private readonly Filesystem $uploadFilesystem,
        private readonly SluggerInterface $slugger,
    ) {
    }

    public function uploadFile(File $file, ?string $directory, ?string $existingFilename = null): string
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
        } catch (FilesystemException) {
            return false;
        }
    }

    public function getPublicFilePath(string $path): string
    {
        return $this->uploadFilesystem->publicUrl($path);
    }

    private function doUpload(File $file, ?string $directory, string $fileName, bool $isPublic = true): void
    {
        $path = $this->createFilePath($directory, $fileName);
        $stream = fopen($file->getPathname(), 'r');

        try {
            $this->uploadFilesystem->writeStream($path, $stream, [
                'visibility' => $isPublic ? Visibility::PUBLIC : Visibility::PRIVATE,
                'mime-type' => $file->getMimeType(),
            ]);
        } catch (FilesystemException $filesystemException) {
            throw new CannotWriteFileException($filesystemException->getMessage());
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

    private function getNewFilename(File $file, string $fileName): string
    {
        $slug = $this->slugger->slug(pathinfo($fileName, PATHINFO_FILENAME))->lower();
        return $slug.'-'.uniqid().'.'.$file->guessExtension();
    }

    private function createFilePath(?string $directory, string $fileName): string
    {
        return null === $directory || '' === $directory || '0' === $directory ? $fileName : $directory.'/'.$fileName;
    }
}
