<?php

namespace App\Catalog\Domain\Model\ProductImage;

use App\Catalog\Domain\Model\Product\Product;
use App\Catalog\Domain\Model\ProductImage\Event\ProductImageWasDeletedEvent;
use App\Catalog\Infrastructure\Persistence\Doctrine\ProductImageDoctrineRepository;
use App\Shared\Domain\Event\DomainEventProviderInterface;
use App\Shared\Domain\Event\DomainEventProviderTrait;
use App\Shared\Infrastructure\Persistence\Doctrine\Mapping\HasPublicUlid;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Timestampable\Traits\TimestampableEntity;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: ProductImageDoctrineRepository::class)]
class ProductImage implements DomainEventProviderInterface
{
    use TimestampableEntity;
    use HasPublicUlid;
    use DomainEventProviderTrait;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank(message: 'Please enter an image name')]
    private string $imageName = 'image';

    final private function __construct(
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

    public function assignProduct(Product $product): static
    {
        $this->product = $product;

        return $this;
    }

    /**
     * Call before removing it from the repository. The event is dispatched once the removal is
     * flushed, and its listener deletes the stored file.
     */
    public function delete(): void
    {
        $this->raiseDomainEvent(new ProductImageWasDeletedEvent($this->product->getPublicId(), $this->imageName));
    }

    public function changeImageName(string $imageName): void
    {
        $this->imageName = $imageName;
    }

    public function changePosition(int $position): void
    {
        if ($position <= 0) {
            throw new \InvalidArgumentException('Position must be greater than 0');
        }

        $this->position = $position;
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

    public function getPosition(): int
    {
        return $this->position;
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
}
