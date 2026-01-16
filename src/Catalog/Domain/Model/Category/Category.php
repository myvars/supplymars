<?php

namespace App\Catalog\Domain\Model\Category;

use App\Catalog\Domain\Model\Category\Event\CategoryPricingWasChangedEvent;
use App\Catalog\Domain\Model\Product\Product;
use App\Catalog\Domain\Model\Subcategory\Subcategory;
use App\Catalog\Infrastructure\Persistence\Doctrine\CategoryDoctrineRepository;
use App\Customer\Domain\Model\User\User;
use App\Pricing\Domain\Model\VatRate\VatRate;
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

#[ORM\Entity(repositoryClass: CategoryDoctrineRepository::class)]
class Category implements DomainEventProviderInterface
{
    use TimestampableEntity;
    use DomainEventProviderTrait;
    use HasPublicUlid;

    public const string DEFAULT_MARKUP = '5.000';

    public const PriceModel DEFAULT_PRICE_MODEL = PriceModel::DEFAULT;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank(message: 'Please enter a category name')]
    private ?string $name = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 9, scale: 3)]
    #[Assert\NotBlank(message: 'Please enter a category markup %')]
    #[Assert\PositiveOrZero(message: 'Please enter a positive or zero category markup %')]
    private ?string $defaultMarkup = self::DEFAULT_MARKUP;

    #[ORM\ManyToOne(inversedBy: 'categories')]
    #[Assert\NotNull(message: 'Please enter a category owner')]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $owner = null;

    #[ORM\ManyToOne(inversedBy: 'categories')]
    #[ORM\JoinColumn(nullable: false)]
    #[Assert\NotNull(message: 'Please enter a VAT rate')]
    private ?VatRate $vatRate = null;

    #[ORM\Column(length: 255)]
    #[Assert\NotNull(message: 'Please enter a price model')]
    #[Assert\NotEqualTo(value: PriceModel::NONE, message: 'A category must have a price model')]
    private ?PriceModel $priceModel = self::DEFAULT_PRICE_MODEL;

    #[ORM\Column]
    private bool $isActive = false;

    #[ORM\OneToMany(targetEntity: Subcategory::class, mappedBy: 'category')]
    #[Assert\NotNull(message: 'Please enter a subcategory')]
    private Collection $subcategories;

    #[ORM\OneToMany(targetEntity: Product::class, mappedBy: 'category')]
    private Collection $products;

    public function __construct()
    {
        $this->initializePublicId();
        $this->subcategories = new ArrayCollection();
        $this->products = new ArrayCollection();
    }

    public static function create(
        string $name,
        User $owner,
        VatRate $vatRate,
        string $defaultMarkup,
        PriceModel $priceModel,
        bool $isActive,
    ): self {
        $self = new self();
        $self->rename($name);
        $self->assignOwner($owner);
        $self->changePricing($vatRate, $defaultMarkup, $priceModel, $isActive);

        return $self;
    }

    public function update(
        string $name,
        User $owner,
        VatRate $vatRate,
        string $defaultMarkup,
        PriceModel $priceModel,
        bool $isActive,
    ): void {
        $this->rename($name);
        $this->assignOwner($owner);
        $this->changePricing($vatRate, $defaultMarkup, $priceModel, $isActive);
    }

    public function changePricing(
        VatRate $vatRate,
        string $defaultMarkup,
        PriceModel $priceModel,
        bool $isActive,
    ): void {
        $vatRateChanged = $vatRate !== $this->vatRate;
        $markupChanged = $defaultMarkup !== $this->defaultMarkup;
        $priceModelChanged = $priceModel !== $this->priceModel;
        $isActiveChanged = $isActive !== $this->isActive;

        if (!$vatRateChanged && !$markupChanged && !$priceModelChanged && !$isActiveChanged) {
            return;
        }

        $this->vatRate = $vatRate;
        $this->applyDefaultMarkup($defaultMarkup);
        $this->applyPriceModel($priceModel);
        $this->setActive($isActive);

        $this->raiseDomainEvent(
            new CategoryPricingWasChangedEvent(
                $this->getPublicId(),
                $vatRateChanged,
                $markupChanged,
                $priceModelChanged,
            )
        );
    }

    public function assignOwner(?User $owner): void
    {
        $this->owner = $owner;
    }

    public function changeVatRate(?VatRate $vatRate): void
    {
        $this->vatRate = $vatRate;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getPublicId(): CategoryPublicId
    {
        return CategoryPublicId::fromString($this->publicIdString());
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

    public function getVatRate(): ?VatRate
    {
        return $this->vatRate;
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
            throw new \InvalidArgumentException('Category name cannot be empty');
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

    private function applyPriceModel(?PriceModel $priceModel): void
    {
        if ($priceModel === PriceModel::NONE) {
            throw new \InvalidArgumentException('A category must have a price model');
        }

        $this->priceModel = $priceModel;
    }

    private function setActive(bool $active): void
    {
        if ($this->isActive === $active) {
            return;
        }

        $this->isActive = $active;
    }

    /**
     * @return Collection<int, Subcategory>
     */
    public function getSubcategories(): Collection
    {
        return $this->subcategories;
    }

    public function addSubcategory(Subcategory $subcategory): static
    {
        if (!$this->subcategories->contains($subcategory)) {
            $this->subcategories->add($subcategory);
            $subcategory->assignCategory($this);
        }

        return $this;
    }

    public function removeSubcategory(Subcategory $subcategory): static
    {
        // set the owning side to null (unless already changed)
        if ($this->subcategories->removeElement($subcategory) && $subcategory->getCategory() === $this) {
            $subcategory->assignCategory(null);
        }

        return $this;
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
        $criteria = Criteria::create()->andWhere(Criteria::expr()->eq('isActive', true));

        return $this->products->matching($criteria);
    }

    public function addProduct(Product $product): static
    {
        if (!$this->products->contains($product)) {
            $this->products->add($product);
            $product->assignCategory($this);
        }

        return $this;
    }

    public function removeProduct(Product $product): static
    {
        // set the owning side to null (unless already changed)
        if ($this->products->removeElement($product) && $product->getCategory() === $this) {
            $product->assignCategory(null);
        }

        return $this;
    }
}
