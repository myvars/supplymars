<?php

namespace App\Entity;

use App\Repository\ProductImageRepository;
use App\ValueObject\ProductImagePublicId;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Timestampable\Traits\TimestampableEntity;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: ProductImageRepository::class)]
class ProductImage
{
    use TimestampableEntity;
    use HasPublicUlid;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank(message: 'Please enter an image name')]
    private string $imageName = 'image';

    private function __construct(
        #[ORM\ManyToOne(inversedBy: 'productImages')]
        #[ORM\JoinColumn(nullable: false)]
        private Product $product,

        #[ORM\Column]
        #[Assert\Positive(message: 'Position must be greater than 0')]
        private int $position,

        #[ORM\Column]
        #[Assert\Positive(message: 'Image size must be greater than 0')]
        private readonly int $imageSize,

        #[ORM\Column(length: 255)]
        #[Assert\NotBlank(message: 'Please enter an image mime type')]
        private readonly string $imageMimeType,

        #[ORM\Column(length: 255)]
        #[Assert\NotBlank(message: 'Please enter an image original name')]
        private readonly string $imageOriginalName,

        #[Assert\NotNull(message: 'Please upload an image')]
        #[Assert\Image(maxSize: '2M', maxSizeMessage: 'The image is too large. Allowed maximum size is 2MB')]
        #[Assert\File(mimeTypes: ['image/*'], mimeTypesMessage: 'Please upload a valid file type')]
        private readonly UploadedFile $imageFile,
    ) {
        $this->initializePublicId();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getPublicId(): ProductImagePublicId
    {
        return ProductImagePublicId::fromString($this->publicIdString());
    }

    public function getProduct(): Product
    {
        return $this->product;
    }

    public function setProduct(Product $product): static
    {
        $this->product = $product;

        return $this;
    }

    public function getPosition(): int
    {
        return $this->position;
    }

    private function setPosition(int $position): static
    {
        $this->position = $position;

        return $this;
    }

    private function setImageName(string $imageName): static
    {
        $this->imageName = $imageName;

        return $this;
    }

    public function getImageName(): string
    {
        return $this->imageName;
    }

    public function getImageSize(): int
    {
        return $this->imageSize;
    }

    public function getImageMimeType(): string
    {
        return $this->imageMimeType;
    }

    public function getImageOriginalName(): string
    {
        return $this->imageOriginalName;
    }

    public function getImageFile(): UploadedFile
    {
        return $this->imageFile;
    }

    public static function createFromUploadedFile(
        Product $product,
        UploadedFile $uploadedFile,
        int $position,
    ): self {
        return new self(
            $product,
            $position,
            $uploadedFile->getSize(),
            $uploadedFile->getMimeType(),
            $uploadedFile->getClientOriginalName(),
            $uploadedFile
        );
    }

    public function updateImageName(string $imageName): void
    {
        $this->setImageName($imageName);
    }

    public function updatePosition(int $position): void
    {
        $this->setPosition($position);
    }
}
