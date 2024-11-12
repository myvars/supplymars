<?php

namespace App\Entity;

use App\Enum\PriceModel;
use App\Repository\ProductRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Timestampable\Traits\TimestampableEntity;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: ProductRepository::class)]
class Product
{
    public const DEFAULT_PRICE_MODEL = PriceModel::NONE;

    use TimestampableEntity;

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

    #[ORM\Column(type: 'decimal', precision: 9, scale: 3)]
    #[Assert\NotBlank(message: 'Please enter a product markup %')]
    #[Assert\PositiveOrZero(message: 'Please enter a positive or zero product markup %')]
    private ?string $defaultMarkup = '0';

    #[ORM\Column(type: 'decimal', precision: 9, scale: 3)]
    #[Assert\PositiveOrZero]
    private ?string $markup = '0';

    #[ORM\Column(type: 'decimal', precision: 10, scale: 2)]
    #[Assert\NotBlank(message: 'Please enter a cost')]
    #[Assert\PositiveOrZero]
    private ?string $cost = null;

    #[ORM\Column(type: 'decimal', precision: 10, scale: 2)]
    #[Assert\PositiveOrZero]
    private ?string $sellPrice = '0';

    #[ORM\Column(type: 'decimal', precision: 10, scale: 2)]
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

    #[ORM\OneToMany(targetEntity: SupplierProduct::class, mappedBy: 'product')]
    #[ORM\OrderBy(['cost' => 'ASC'])]
    private Collection $supplierProducts;

    #[ORM\OneToMany(targetEntity: ProductImage::class, mappedBy: 'product')]
    #[ORM\OrderBy(['position' => 'ASC'])]
    private Collection $productImages;

    public function __construct()
    {
        $this->supplierProducts = new ArrayCollection();
        $this->productImages = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(?string $name): static
    {
        $this->name = $name;

        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): static
    {
        $this->description = $description;

        return $this;
    }

    public function getMfrPartNumber(): ?string
    {
        return $this->mfrPartNumber;
    }

    public function setMfrPartNumber(?string $mfrPartNumber): static
    {
        $this->mfrPartNumber = $mfrPartNumber;

        return $this;
    }

    public function getStock(): ?int
    {
        return $this->stock;
    }

    public function setStock(?int $stock): static
    {
        $this->stock = $stock;

        return $this;
    }

    public function getLeadTimeDays(): ?int
    {
        return $this->leadTimeDays;
    }

    public function setLeadTimeDays(?int $leadTimeDays): static
    {
        $this->leadTimeDays = $leadTimeDays;

        return $this;
    }

    public function getWeight(): ?int
    {
        return $this->weight;
    }

    public function setWeight(?int $weight): static
    {
        $this->weight = $weight;

        return $this;
    }

    public function getDefaultMarkup(): ?string
    {
        return $this->defaultMarkup;
    }

    public function setDefaultMarkup(?string $defaultMarkup): static
    {
        $this->defaultMarkup = $defaultMarkup;

        return $this;
    }

    public function getMarkup(): ?string
    {
        return $this->markup;
    }

    public function setMarkup(?string $markup): static
    {
        $this->markup = $markup;

        return $this;
    }

    public function getCost(): ?string
    {
        return $this->cost;
    }

    public function setCost(?string $cost): static
    {
        $this->cost = $cost;

        return $this;
    }

    public function getSellPrice(): ?string
    {
        return $this->sellPrice;
    }

    public function setSellPrice(?string $sellPrice): static
    {
        $this->sellPrice = $sellPrice;

        return $this;
    }

    public function getSellPriceIncVat(): ?string
    {
        return $this->sellPriceIncVat;
    }

    public function setSellPriceIncVat(?string $sellPriceIncVat): static
    {
        $this->sellPriceIncVat = $sellPriceIncVat;

        return $this;
    }

    public function getCategory(): ?Category
    {
        return $this->category;
    }

    public function setCategory(?Category $category): static
    {
        $this->category = $category;

        return $this;
    }

    public function getCategoryVatRate(): ?VatRate
    {
        return $this->getCategory()->getVatRate();
    }

    public function getSubcategory(): ?Subcategory
    {
        return $this->subcategory;
    }

    public function setSubcategory(?Subcategory $subcategory): static
    {
        $this->subcategory = $subcategory;

        return $this;
    }

    public function getManufacturer(): ?Manufacturer
    {
        return $this->manufacturer;
    }

    public function setManufacturer(?Manufacturer $manufacturer): static
    {
        $this->manufacturer = $manufacturer;

        return $this;
    }

    public function getDefaultOwner(): ?User
    {
        if ($this->getOwner() instanceof User) {
            return $this->getOwner();
        }

        if ($this->getSubcategory()->getOwner() instanceof User) {
            return $this->getSubcategory()->getOwner();
        }

        return $this->getCategory()->getOwner();
    }

    public function getOwner(): ?User
    {
        return $this->owner;
    }

    public function setOwner(?User $owner): static
    {
        $this->owner = $owner;

        return $this;
    }

    public function getPriceModel(): ?PriceModel
    {
        return $this->priceModel;
    }

    public function setPriceModel(?PriceModel $priceModel): static
    {
        $this->priceModel = $priceModel;

        return $this;
    }

    public function getActiveProductSource(): ?SupplierProduct
    {
        return $this->activeProductSource;
    }

    public function setActiveProductSource(?SupplierProduct $activeProductSource): static
    {
        $this->activeProductSource = $activeProductSource;

        return $this;
    }

    public function isActive(): bool
    {
        return $this->isActive;
    }

    public function setIsActive(bool $isActive): static
    {
        $this->isActive = $isActive;

        return $this;
    }

    /**
     * @return Collection<int, SupplierProduct>
     */
    public function getSupplierProducts(): Collection
    {
        return $this->supplierProducts;
    }

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

    public function addSupplierProduct(SupplierProduct $supplierProduct): static
    {
        if (!$this->supplierProducts->contains($supplierProduct)) {
            $this->supplierProducts->add($supplierProduct);
            $supplierProduct->setProduct($this);
        }

        return $this;
    }

    public function removeSupplierProduct(SupplierProduct $supplierProduct): static
    {
        // set the owning side to null (unless already changed)
        if ($this->supplierProducts->removeElement($supplierProduct) && $supplierProduct->getProduct() === $this) {
            $supplierProduct->setProduct(null);
        }

        return $this;
    }

    /**
     * @return Collection<int, ProductImage>
     */
    public function getProductImages(): Collection
    {
        return $this->productImages;
    }

    public function addProductImage(ProductImage $productImage): static
    {
        if (!$this->productImages->contains($productImage)) {
            $this->productImages->add($productImage);
            $productImage->setProduct($this);
        }

        return $this;
    }

    public function removeProductImage(ProductImage $productImage): static
    {
        // set the owning side to null (unless already changed)
        if ($this->productImages->removeElement($productImage) && $productImage->getProduct() === $this) {
            $productImage->setProduct(null);
        }

        return $this;
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

    public function getFirstImage(): ?ProductImage
    {
        return $this->productImages->first();
    }

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

    public function isValidProduct(): bool
    {
        return $this->isActive()
            && $this->hasActiveProductSource()
            && $this->getCategory()->isActive()
            && $this->getSubcategory()->isActive();
    }
}
