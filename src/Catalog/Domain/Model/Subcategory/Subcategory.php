<?php

namespace App\Catalog\Domain\Model\Subcategory;

use App\Catalog\Domain\Model\Category\Category;
use App\Catalog\Domain\Model\Product\Product;
use App\Catalog\Domain\Model\Subcategory\Event\SubcategoryPricingWasChangedEvent;
use App\Catalog\Infrastructure\Persistence\Doctrine\SubcategoryDoctrineRepository;
use App\Customer\Domain\Model\User\User;
use App\Purchasing\Domain\Model\SupplierProduct\SupplierSubcategory;
use App\Shared\Domain\Event\DomainEventProviderInterface;
use App\Shared\Domain\Event\DomainEventProviderTrait;
use App\Shared\Domain\ValueObject\PriceModel;
use App\Shared\Infrastructure\Persistence\Doctrine\Mapping\HasPublicUlid;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\Common\Collections\Criteria;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Timestampable\Traits\TimestampableEntity;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: SubcategoryDoctrineRepository::class)]
class Subcategory implements DomainEventProviderInterface
{
    use TimestampableEntity;
    use DomainEventProviderTrait;
    use HasPublicUlid;

    public const string DEFAULT_MARKUP = '0.000';

    public const PriceModel DEFAULT_PRICE_MODEL = PriceModel::NONE;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank(message: 'Please enter a Subcategory name')]
    private ?string $name = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 9, scale: 3)]
    #[Assert\NotBlank(message: 'Please enter a subcategory markup %')]
    #[Assert\PositiveOrZero(message: 'Please enter a positive or zero subcategory markup %')]
    private ?string $defaultMarkup = self::DEFAULT_MARKUP;

    #[ORM\ManyToOne(inversedBy: 'subcategories')]
    private ?User $owner = null;

    #[ORM\ManyToOne(inversedBy: 'subcategories')]
    #[Assert\NotNull(message: 'Please enter a category')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Category $category = null;

    #[ORM\Column(length: 255)]
    #[Assert\NotNull(message: 'Please enter a price model')]
    private ?PriceModel $priceModel = self::DEFAULT_PRICE_MODEL;

    #[ORM\Column]
    private bool $isActive = false;

    #[ORM\OneToMany(targetEntity: Product::class, mappedBy: 'subcategory')]
    private Collection $products;

    #[ORM\OneToMany(targetEntity: SupplierSubcategory::class, mappedBy: 'mappedSubcategory')]
    private Collection $supplierSubcategories;

    public function __construct()
    {
        $this->initializePublicId();
        $this->products = new ArrayCollection();
        $this->supplierSubcategories = new ArrayCollection();
    }

    public static function create(
        string $name,
        Category $category,
        ?User $owner,
        string $defaultMarkup,
        PriceModel $priceModel,
        bool $isActive,
    ): self {
        $self = new self();
        $self->rename($name);
        $self->assignCategory($category);
        $self->assignOwner($owner);
        $self->changePricing($defaultMarkup, $priceModel, $isActive);

        return $self;
    }

    public function update(
        string $name,
        Category $category,
        ?User $owner,
        string $defaultMarkup,
        PriceModel $priceModel,
        bool $isActive,
    ): void {
        $this->rename($name);
        $this->assignCategory($category);
        $this->assignOwner($owner);
        $this->changePricing($defaultMarkup, $priceModel, $isActive);
    }

    public function changePricing(
        string $defaultMarkup,
        PriceModel $priceModel,
        bool $isActive,
    ): void {
        $markupChanged = $defaultMarkup !== $this->defaultMarkup;
        $priceModelChanged = $priceModel !== $this->priceModel;
        $isActiveChanged = $isActive !== $this->isActive;

        if (!$markupChanged && !$priceModelChanged && !$isActiveChanged) {
            return;
        }

        $this->applyDefaultMarkup($defaultMarkup);
        $this->priceModel = $priceModel;
        $this->setActive($isActive);

        $this->raiseDomainEvent(
            new SubcategoryPricingWasChangedEvent($this->getPublicId(), $markupChanged, $priceModelChanged)
        );
    }

    public function assignOwner(?User $owner): void
    {
        $this->owner = $owner;
    }

    public function assignCategory(?Category $category): void
    {
        $this->category = $category;
    }

    public function hasDefaultMarkup(): bool
    {
        return $this->defaultMarkup > 0;
    }

    public function hasOwner(): bool
    {
        return $this->owner instanceof User;
    }

    public function hasPriceModel(): bool
    {
        return $this->priceModel instanceof PriceModel && PriceModel::NONE !== $this->priceModel;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getPublicId(): SubcategoryPublicId
    {
        return SubcategoryPublicId::fromString($this->publicIdString());
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function getDefaultMarkup(): ?string
    {
        return $this->defaultMarkup;
    }

    public function getOwner(): ?User
    {
        return $this->owner;
    }

    public function getCategory(): ?Category
    {
        return $this->category;
    }

    public function getPriceModel(): ?PriceModel
    {
        return $this->priceModel;
    }

    public function isActive(): bool
    {
        return $this->isActive;
    }

    private function rename(string $name): void
    {
        $name = trim($name);
        if ($name === '') {
            throw new \InvalidArgumentException('Subcategory name cannot be empty');
        }

        $this->name = $name;
    }

    private function applyDefaultMarkup(string $defaultMarkup): void
    {
        if ((float) $defaultMarkup < 0) {
            throw new \InvalidArgumentException('Markup cannot be negative');
        }

        $this->defaultMarkup = $defaultMarkup;
    }

    private function setActive(bool $active): void
    {
        if ($this->isActive === $active) {
            return;
        }

        $this->isActive = $active;
    }

    /**
     * @return Collection<int, Product>
     */
    public function getProducts(): Collection
    {
        return $this->products;
    }

    /**
     * @return Collection<int, Product>
     */
    public function getActiveProducts(): Collection
    {
        $criteria = Criteria::create()
            ->andWhere(Criteria::expr()->eq('isActive', true));

        return $this->products->matching($criteria);
    }

    public function addProduct(Product $product): static
    {
        if (!$this->products->contains($product)) {
            $this->products->add($product);
            $product->assignSubcategory($this);
        }

        return $this;
    }

    public function removeProduct(Product $product): static
    {
        // set the owning side to null (unless already changed)
        if ($this->products->removeElement($product) && $product->getSubcategory() === $this) {
            $product->assignSubcategory(null);
        }

        return $this;
    }

    /**
     * @return Collection<int, SupplierSubcategory>
     */
    public function getSupplierSubcategories(): Collection
    {
        return $this->supplierSubcategories;
    }

    public function addSupplierSubcategory(SupplierSubcategory $supplierSubcategory): static
    {
        if (!$this->supplierSubcategories->contains($supplierSubcategory)) {
            $this->supplierSubcategories->add($supplierSubcategory);
            $supplierSubcategory->assignMappedSubcategory($this);
        }

        return $this;
    }

    public function removeSupplierSubcategory(SupplierSubcategory $supplierSubcategory): static
    {
        // set the owning side to null (unless already changed)
        if (
            $this->supplierSubcategories->removeElement($supplierSubcategory)
            && $supplierSubcategory->getMappedSubcategory() === $this
        ) {
            $supplierSubcategory->assignMappedSubcategory(null);
        }

        return $this;
    }
}
