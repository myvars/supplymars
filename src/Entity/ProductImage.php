<?php

namespace App\Entity;

use App\Repository\ProductImageRepository;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Timestampable\Traits\TimestampableEntity;
use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: ProductImageRepository::class)]
class ProductImage
{
    use TimestampableEntity;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'productImages')]
    #[ORM\JoinColumn(nullable: false)]
    #[Assert\NotNull(message: 'Please enter a valid Product')]
    private ?Product $product = null;

    #[ORM\Column(length: 255)]
    private ?string $imageName = null;

    #[ORM\Column]
    private ?int $imageSize = null;

    #[ORM\Column(length: 255)]
    private ?string $imageMimeType = null;

    #[ORM\Column(length: 255)]
    private ?string $imageOriginalName = null;

    #[Assert\NotNull(message: 'Please upload an image')]
    #[Assert\Image(maxSize: '2M', maxSizeMessage: 'The image is too large. Allowed maximum size is 2MB')]
    #[Assert\File(mimeTypes: ['image/*'], mimeTypesMessage: 'Please upload a valid image file')]
    private ?File $imageFile = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getProduct(): ?Product
    {
        return $this->product;
    }

    public function setProduct(?Product $product): static
    {
        $this->product = $product;

        return $this;
    }

    public function setImageName(?string $imageName): void
    {
        $this->imageName = $imageName;
    }

    public function getImageName(): ?string
    {
        return $this->imageName;
    }

    public function getImageSize(): ?int
    {
        return $this->imageSize;
    }

    public function getImageMimeType(): ?string
    {
        return $this->imageMimeType;
    }

    public function getImageOriginalName(): ?string
    {
        return $this->imageOriginalName;
    }

    public function setImageFile(File $imageFile): void
    {
        $this->imageFile = $imageFile;
        $this->imageSize = $imageFile->getSize();
        $this->imageMimeType = $imageFile->getMimeType();
        $this->imageMimeType = $imageFile->getMimeType();
        $this->imageOriginalName = $imageFile->getClientOriginalName();
    }

    public function getImageFile(): ?File
    {
        return $this->imageFile;
    }
}
