<?php

namespace App\Catalog\Domain\Model\Product;

use App\Catalog\Domain\Model\Category\Category;
use App\Catalog\Domain\Model\Manufacturer\Manufacturer;
use App\Catalog\Domain\Model\ProductImage\ProductImage;
use App\Catalog\Domain\Model\Subcategory\Subcategory;
use App\Catalog\Infrastructure\Persistence\Doctrine\ProductDoctrineRepository;
use App\Customer\Domain\Model\User\User;
use App\Pricing\Domain\Model\VatRate\VatRate;
use App\Purchasing\Domain\Model\SupplierProduct\SupplierProduct;
use App\Shared\Domain\Service\Pricing\MarkupCalculator;
use App\Shared\Domain\ValueObject\PriceModel;
use App\Shared\Infrastructure\Persistence\Doctrine\Mapping\HasPublicUlid;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Timestampable\Traits\TimestampableEntity;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: ProductDoctrineRepository::class)]
class Product
{
    use TimestampableEntity;
    use HasPublicUlid;

    public const string DEFAULT_MARKUP = '0.000';

    public const PriceModel DEFAULT_PRICE_MODEL = PriceModel::NONE;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank(message: 'Please enter a product name')]
    private ?string $name = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $description = null;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank(message: 'Please enter a manufacturer part number')]
    private ?string $mfrPartNumber = null;

    #[ORM\Column]
    #[Assert\Range(notInRangeMessage: 'Please enter a stock level', min: 0, max: 10000)]
    private ?int $stock = 0;

    #[ORM\Column]
    #[Assert\NotNull(message: 'Please enter a lead time(days)')]
    #[Assert\Range(notInRangeMessage: 'Please enter a lead time (0 to 1000)', min: 0, max: 1000)]
    private ?int $leadTimeDays = 7;

    #[ORM\Column]
    #[Assert\NotNull(message: 'Please enter a product weight(grams)')]
    #[Assert\Range(notInRangeMessage: 'Please enter a product weight (0 to 100000)', min: 0, max: 100000)]
    private ?int $weight = 0;

    /** @var numeric-string|null */
    #[ORM\Column(type: Types::DECIMAL, precision: 9, scale: 3)]
    #[Assert\NotBlank(message: 'Please enter a product markup %')]
    #[Assert\PositiveOrZero(message: 'Please enter a positive or zero product markup %')]
    private ?string $defaultMarkup = self::DEFAULT_MARKUP;

    /** @var numeric-string|null */
    #[ORM\Column(type: Types::DECIMAL, precision: 9, scale: 3)]
    #[Assert\PositiveOrZero]
    private ?string $markup = '0';

    /** @var numeric-string|null */
    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2)]
    #[Assert\NotBlank(message: 'Please enter a cost')]
    #[Assert\PositiveOrZero(message: 'Please enter a positive or zero cost')]
    private ?string $cost = '0.00';

    /** @var numeric-string|null */
    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2)]
    #[Assert\PositiveOrZero]
    private ?string $sellPrice = '0';

    /** @var numeric-string|null */
    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2)]
    #[Assert\PositiveOrZero]
    private ?string $sellPriceIncVat = '0';

    #[ORM\ManyToOne(inversedBy: 'products')]
    #[ORM\JoinColumn(nullable: false)]
    #[Assert\NotNull(message: 'Please enter a category')]
    private ?Category $category = null;

    #[ORM\ManyToOne(inversedBy: 'products')]
    #[ORM\JoinColumn(nullable: false)]
    #[Assert\NotNull(message: 'Please enter a subcategory')]
    private ?Subcategory $subcategory = null;

    #[ORM\ManyToOne(inversedBy: 'products')]
    #[ORM\JoinColumn(nullable: false)]
    #[Assert\NotNull(message: 'Please enter a manufacturer')]
    private ?Manufacturer $manufacturer = null;

    #[ORM\ManyToOne]
    private ?User $owner = null;

    #[ORM\Column(length: 255)]
    #[Assert\NotNull(message: 'Please enter a price model')]
    private ?PriceModel $priceModel = self::DEFAULT_PRICE_MODEL;

    #[ORM\OneToOne(cascade: ['persist', 'remove'])]
    private ?SupplierProduct $activeProductSource = null;

    #[ORM\Column]
    private bool $isActive = false;

    /** @var Collection<int, SupplierProduct> */
    #[ORM\OneToMany(targetEntity: SupplierProduct::class, mappedBy: 'product')]
    #[ORM\OrderBy(['cost' => 'ASC'])]
    private Collection $supplierProducts;

    /** @var Collection<int, ProductImage> */
    #[ORM\OneToMany(targetEntity: ProductImage::class, mappedBy: 'product')]
    #[ORM\OrderBy(['position' => 'ASC'])]
    private Collection $productImages;

    final public function __construct()
    {
        $this->initializePublicId();
        $this->supplierProducts = new ArrayCollection();
        $this->productImages = new ArrayCollection();
    }

    public static function create(
        string $name,
        ?string $description,
        Category $category,
        Subcategory $subcategory,
        Manufacturer $manufacturer,
        string $mfrPartNumber,
        ?User $owner,
        bool $isActive,
    ): self {
        $self = new self();
        $self->rename($name);
        $self->description = $description;
        $self->assignCategory($category);
        $self->assignSubcategory($subcategory);
        $self->assignManufacturer($manufacturer);
        $self->mfrPartNumber = $mfrPartNumber;
        $self->assignOwner($owner);
        $self->setActive($isActive);

        return $self;
    }

    public function update(
        MarkupCalculator $markupCalculator,
        string $name,
        ?string $description,
        Category $category,
        Subcategory $subcategory,
        Manufacturer $manufacturer,
        string $mfrPartNumber,
        ?User $owner,
        bool $isActive,
    ): void {
        $this->rename($name);
        $this->description = $description;
        $this->assignCategory($category);
        $this->assignSubcategory($subcategory);
        $this->assignManufacturer($manufacturer);
        $this->mfrPartNumber = $mfrPartNumber;
        $this->assignOwner($owner);
        $this->setActive($isActive);

        $this->recalculatePrice($markupCalculator);
    }

    /**
     * @param numeric-string $defaultMarkup
     */
    public function changePricing(
        MarkupCalculator $markupCalculator,
        string $defaultMarkup,
        PriceModel $priceModel,
        bool $isActive,
    ): void {
        $this->applyDefaultMarkup($defaultMarkup);
        $this->priceModel = $priceModel;
        $this->setActive($isActive);

        $this->recalculatePrice($markupCalculator);
    }

    public function assignCategory(?Category $category): void
    {
        $this->category = $category;
    }

    public function assignManufacturer(?Manufacturer $manufacturer): void
    {
        $this->manufacturer = $manufacturer;
    }

    public function assignSubcategory(?Subcategory $subcategory): void
    {
        $this->subcategory = $subcategory;
    }

    public function assignOwner(?User $owner): void
    {
        $this->owner = $owner;
    }

    public function recalculateActiveSource(MarkupCalculator $markupCalculator): void
    {
        $activeSource = $this->calculateBestActiveSource();
        if (!$activeSource instanceof SupplierProduct) {
            $this->removeActiveSource();

            return;
        }

        $this->applyActiveSource($activeSource);
        $this->recalculatePrice($markupCalculator);
    }

    public function recalculatePrice(MarkupCalculator $calculator): void
    {
        $prettyPriceIncVat = $calculator->calculatePrettyPrice(
            $this->getCost(),
            $this->getActiveMarkup(),
            $this->getCategoryVatRate()->getRate(),
            $this->getActivePriceModel()
        );

        $customMarkup = $calculator->calculateCustomMarkup(
            $this->getCost(),
            $prettyPriceIncVat,
            $this->getCategoryVatRate()->getRate()
        );

        $newSellPrice = $calculator->calculateSellPrice($this->getCost(), $customMarkup);

        $this->applyMarkup($customMarkup);
        $this->changeSellPrice($newSellPrice);
        $this->changeSellPriceIncVat($prettyPriceIncVat);
    }

    /**
     * @param array<int, int> $newImageOrder
     */
    public function reorderProductImagesBy(array $newImageOrder): void
    {
        foreach ($this->getProductImages() as $productImage) {
            if (!isset($newImageOrder[$productImage->getId()])) {
                continue;
            }

            $newPosition = $newImageOrder[$productImage->getId()] + 1;
            $productImage->changePosition($newPosition);
        }
    }

    public function getBestSourceWithMinQuantity(int $minQuantity): ?SupplierProduct
    {
        return $this->calculateBestActiveSource($minQuantity);
    }

    /**
     * @return numeric-string|null
     */
    public function getActiveMarkup(): ?string
    {
        if ($this->hasDefaultMarkup()) {
            return $this->getDefaultMarkup();
        }

        if ($this->getSubcategory()->hasDefaultMarkup()) {
            return $this->getSubcategory()->getDefaultMarkup();
        }

        return $this->getCategory()->getDefaultMarkup();
    }

    public function getActiveMarkupTarget(): string
    {
        if ($this->hasDefaultMarkup()) {
            return 'PRODUCT';
        }

        if ($this->getSubcategory()->hasDefaultMarkup()) {
            return 'SUBCATEGORY';
        }

        return 'CATEGORY';
    }

    public function getActivePriceModel(): ?PriceModel
    {
        if ($this->hasPriceModel()) {
            return $this->getPriceModel();
        }

        if ($this->getSubcategory()->hasPriceModel()) {
            return $this->getSubcategory()->getPriceModel();
        }

        return $this->getCategory()->getPriceModel();
    }

    public function getActivePriceModelTarget(): string
    {
        if ($this->hasPriceModel()) {
            return 'PRODUCT';
        }

        if ($this->getSubcategory()->hasPriceModel()) {
            return 'SUBCATEGORY';
        }

        return 'CATEGORY';
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

    public function hasActiveProductSource(): bool
    {
        return $this->activeProductSource instanceof SupplierProduct;
    }

    public function hasProductImage(): bool
    {
        return $this->productImages->count() > 0;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getPublicId(): ProductPublicId
    {
        return ProductPublicId::fromString($this->publicIdString());
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function getMfrPartNumber(): ?string
    {
        return $this->mfrPartNumber;
    }

    public function getStock(): ?int
    {
        return $this->stock;
    }

    public function getLeadTimeDays(): ?int
    {
        return $this->leadTimeDays;
    }

    public function getWeight(): ?int
    {
        return $this->weight;
    }

    /**
     * @return numeric-string|null
     */
    public function getDefaultMarkup(): ?string
    {
        return $this->defaultMarkup;
    }

    /**
     * @return numeric-string|null
     */
    public function getMarkup(): ?string
    {
        return $this->markup;
    }

    /**
     * @return numeric-string|null
     */
    public function getCost(): ?string
    {
        return $this->cost;
    }

    /**
     * @return numeric-string|null
     */
    public function getSellPrice(): ?string
    {
        return $this->sellPrice;
    }

    /**
     * @return numeric-string|null
     */
    public function getSellPriceIncVat(): ?string
    {
        return $this->sellPriceIncVat;
    }

    public function getCategory(): Category
    {
        return $this->category ?? throw new \LogicException('Category must be set');
    }

    public function getCategoryVatRate(): VatRate
    {
        return $this->getCategory()->getVatRate();
    }

    public function getSubcategory(): Subcategory
    {
        return $this->subcategory ?? throw new \LogicException('Subcategory must be set');
    }

    public function getManufacturer(): Manufacturer
    {
        return $this->manufacturer ?? throw new \LogicException('Manufacturer must be set');
    }

    public function getOwner(): ?User
    {
        return $this->owner;
    }

    public function getPriceModel(): ?PriceModel
    {
        return $this->priceModel;
    }

    public function getActiveProductSource(): ?SupplierProduct
    {
        return $this->activeProductSource;
    }

    public function getFirstImage(): ?ProductImage
    {
        $first = $this->productImages->first();

        return $first ?: null;
    }

    public function isActive(): bool
    {
        return $this->isActive;
    }

    public function isValidProduct(): bool
    {
        return $this->isActive()
            && $this->hasActiveProductSource()
            && $this->getCategory()->isActive()
            && $this->getSubcategory()->isActive();
    }

    public function rename(string $name): void
    {
        $name = trim($name);
        if ($name === '') {
            throw new \InvalidArgumentException('Product name cannot be empty');
        }

        $this->name = $name;
    }

    private function changeStock(int $stock): void
    {
        if ($stock < 0) {
            throw new \InvalidArgumentException('Stock level cannot be negative');
        }

        $this->stock = $stock;
    }

    private function changeLeadTimeDays(int $leadTimeDays): void
    {
        if ($leadTimeDays < 0) {
            throw new \InvalidArgumentException('Lead time(days) cannot be negative');
        }

        $this->leadTimeDays = $leadTimeDays;
    }

    /**
     * @param numeric-string $markup
     */
    private function applyMarkup(string $markup): void
    {
        if ((float) $markup < 0) {
            throw new \InvalidArgumentException('Markup cannot be negative');
        }

        $this->markup = $markup;
    }

    /**
     * @param numeric-string $cost
     */
    private function changeCost(string $cost): void
    {
        if ((float) $cost < 0) {
            throw new \InvalidArgumentException('Cost cannot be negative');
        }

        $this->cost = $cost;
    }

    /**
     * @param numeric-string $defaultMarkup
     */
    private function applyDefaultMarkup(string $defaultMarkup): void
    {
        if ((float) $defaultMarkup < 0) {
            throw new \InvalidArgumentException('Markup cannot be negative');
        }

        $this->defaultMarkup = $defaultMarkup;
    }

    /**
     * @param numeric-string $sellPrice
     */
    private function changeSellPrice(string $sellPrice): void
    {
        if ((float) $sellPrice < 0) {
            throw new \InvalidArgumentException('Sell price cannot be negative');
        }

        $this->sellPrice = $sellPrice;
    }

    /**
     * @param numeric-string $sellPriceIncVat
     */
    private function changeSellPriceIncVat(string $sellPriceIncVat): void
    {
        if ((float) $sellPriceIncVat < 0) {
            throw new \InvalidArgumentException('Sell price (inc VAT) cannot be negative');
        }

        $this->sellPriceIncVat = $sellPriceIncVat;
    }

    private function setActive(bool $active): void
    {
        if ($this->isActive === $active) {
            return;
        }

        $this->isActive = $active;
    }

    private function calculateBestActiveSource(int $minQuantity = 1): ?SupplierProduct
    {
        if ($minQuantity < 1) {
            return null;
        }

        $activeSource = null;

        foreach ($this->getActiveSupplierProducts() as $supplierProduct) {
            if (!$supplierProduct->hasStock()) {
                continue;
            }

            if ($supplierProduct->getStock() < $minQuantity) {
                continue;
            }

            if (!$supplierProduct->hasPositiveCost()) {
                continue;
            }

            if ($activeSource === null) {
                $activeSource = $supplierProduct;
                continue;
            }

            if ($supplierProduct->getCost() < $activeSource->getCost()) {
                $activeSource = $supplierProduct;
                continue;
            }

            if ($supplierProduct->getCost() === $activeSource->getCost()
                && $supplierProduct->getStock() > $activeSource->getStock()
            ) {
                $activeSource = $supplierProduct;
            }
        }

        return $activeSource;
    }

    private function applyActiveSource(SupplierProduct $activeSource): void
    {
        $this->changeCost($activeSource->getCost());
        $this->changeLeadTimeDays($activeSource->getLeadTimeDays());
        $this->changeStock($activeSource->getStock());
        $this->activeProductSource = $activeSource;
    }

    private function removeActiveSource(): void
    {
        $this->activeProductSource = null;
        $this->changeStock(0);
    }

    private function reorderProductImages(): void
    {
        $position = 1;
        foreach ($this->getProductImages() as $productImage) {
            $productImage->changePosition($position++);
        }
    }

    /**
     * @return Collection<int, SupplierProduct>
     */
    public function getSupplierProducts(): Collection
    {
        return $this->supplierProducts;
    }

    /**
     * @return Collection<int, SupplierProduct>
     */
    public function getActiveSupplierProducts(): Collection
    {
        $activeSupplierProducts = new ArrayCollection();
        foreach ($this->supplierProducts as $supplierProduct) {
            if ($supplierProduct->isActive() && $supplierProduct->getSupplier()->isActive()) {
                $activeSupplierProducts->add($supplierProduct);
            }
        }

        return $activeSupplierProducts;
    }

    public function addSupplierProduct(
        MarkupCalculator $markupCalculator,
        SupplierProduct $supplierProduct,
    ): void {
        if (!$this->supplierProducts->contains($supplierProduct)) {
            $this->supplierProducts->add($supplierProduct);
            $supplierProduct->assignProduct($this);
        }

        $this->recalculateActiveSource($markupCalculator);
    }

    public function removeSupplierProduct(
        MarkupCalculator $markupCalculator,
        SupplierProduct $supplierProduct,
    ): void {
        // set the owning side to null (unless already changed)
        if ($this->supplierProducts->removeElement($supplierProduct) && $supplierProduct->getProduct() === $this) {
            $supplierProduct->assignProduct(null);
        }

        $this->recalculateActiveSource($markupCalculator);
    }

    /**
     * @return Collection<int, ProductImage>
     */
    public function getProductImages(): Collection
    {
        $images = $this->productImages->toArray();

        \usort($images, fn (ProductImage $a, ProductImage $b): int => $a->getPosition() <=> $b->getPosition());

        return new ArrayCollection($images);
    }

    public function addProductImage(ProductImage $productImage): void
    {
        if (!$this->productImages->contains($productImage)) {
            $this->productImages->add($productImage);
            $productImage->assignProduct($this);
        }
    }

    public function removeProductImage(ProductImage $productImage): void
    {
        // set the owning side to null (unless already changed)
        if ($this->productImages->removeElement($productImage) && $productImage->getProduct() === $this) {
            $this->reorderProductImages();
        }
    }
}
