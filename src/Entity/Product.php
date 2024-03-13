<?php

namespace App\Entity;

use App\Repository\ProductRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Timestampable\Traits\TimestampableEntity;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: ProductRepository::class)]
class Product
{
    use TimestampableEntity;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    #[Assert\NotNull(message: 'Please enter a product name')]
    private ?string $name = null;

    #[ORM\Column(length: 255)]
    #[Assert\NotNull(message: 'Please enter a manufacturer part number')]
    private ?string $MfrPartNumber = null;

    #[ORM\Column]
    #[Assert\NotNull(message: 'Please enter a stock level')]
    #[Assert\Range(notInRangeMessage: 'Please enter a stock level', min: 0, max: 10000)]
    private ?int $stock = null;

    #[ORM\Column]
    #[Assert\NotNull(message: 'Please enter a lead time')]
    #[Assert\Range(notInRangeMessage: 'Please enter a lead time(days)', min: 0, max: 1000)]
    private ?int $leadTimeDays = null;

    #[ORM\Column]
    #[Assert\NotNull(message: 'Please enter a weight')]
    #[Assert\Range(notInRangeMessage: 'Please enter a product weight(grams)', min: 0, max: 100000)]
    private ?int $weight = null;


    #[ORM\Column(type: 'decimal', precision: 9, scale: 3)]
    #[Assert\NotBlank(message: 'Please enter a product markup %')]
    #[Assert\PositiveOrZero]
    private ?string $defaultMarkup = '0';

    #[ORM\Column(type: 'decimal', precision: 9, scale: 3)]
    #[Assert\NotBlank(message: 'Please enter a markup %')]
    #[Assert\PositiveOrZero]
    private ?string $markup = null;

    #[ORM\Column(type: 'decimal', precision: 10, scale: 2)]
    #[Assert\NotBlank(message: 'Please enter a cost')]
    #[Assert\PositiveOrZero]
    private ?string $cost = null;

    #[ORM\Column(type: 'decimal', precision: 10, scale: 2)]
    #[Assert\NotBlank(message: 'Please enter a sell price')]
    #[Assert\PositiveOrZero]
    private ?string $sellPrice = null;

    #[ORM\Column(type: 'decimal', precision: 10, scale: 2)]
    #[Assert\NotBlank(message: 'Please enter a sell price inc VAT')]
    #[Assert\PositiveOrZero]
    private ?string $sellPriceIncVat = null;

    #[ORM\ManyToOne(inversedBy: 'products')]
    #[ORM\JoinColumn(nullable: false)]
    #[Assert\NotNull(message: 'Please enter a Category')]
    private ?Category $category = null;

    #[ORM\ManyToOne(inversedBy: 'products')]
    #[ORM\JoinColumn(nullable: false)]
    #[Assert\NotNull(message: 'Please enter a Subcategory')]
    private ?Subcategory $subcategory = null;

    #[ORM\ManyToOne(inversedBy: 'products')]
    #[ORM\JoinColumn(nullable: false)]
    #[Assert\NotNull(message: 'Please enter a Manufacturer')]
    private ?Manufacturer $manufacturer = null;

    #[ORM\ManyToOne]
    #[Assert\NotNull(message: 'Please enter a valid product manager')]
    private ?User $owner = null;

    #[ORM\Column(length: 255)]
    #[Assert\NotNull(message: 'Please enter a price model')]
    private ?PriceModel $priceModel = null;

    #[ORM\OneToOne(cascade: ['persist', 'remove'])]
    private ?SupplierProduct $activeProductSource = null;

    #[ORM\Column]
    private bool $isActive = false;

    #[ORM\OneToMany(mappedBy: 'product', targetEntity: SupplierProduct::class)]
    private Collection $supplierProducts;

    #[ORM\OneToMany(mappedBy: 'product', targetEntity: ProductImage::class)]
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

    public function getMfrPartNumber(): ?string
    {
        return $this->MfrPartNumber;
    }

    public function setMfrPartNumber(?string $MfrPartNumber): static
    {
        $this->MfrPartNumber = $MfrPartNumber;

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

    public function isIsActive(): bool
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
        if ($this->supplierProducts->removeElement($supplierProduct)) {
            // set the owning side to null (unless already changed)
            if ($supplierProduct->getProduct() === $this) {
                $supplierProduct->setProduct(null);
            }
        }

        return $this;
    }

    public function getActiveMarkup(): ?string
    {
        if ($this->getDefaultMarkup() > 0) {
            return $this->getDefaultMarkup();
        }
        if ($this->getSubcategory()->getDefaultMarkup() > 0) {
            return $this->getSubcategory()->getDefaultMarkup();
        }
        return $this->getCategory()->getDefaultMarkup();
    }

    public function getActiveMarkupTarget(): string
    {
        if ($this->getDefaultMarkup() > 0) {
            return 'product';
        }
        if ($this->getSubcategory()->getDefaultMarkup() > 0) {
            return 'subcategory';
        }
        return 'category';
    }

    public function getActivePriceModel(): ?PriceModel
    {
        if ($this->getPriceModel()->value !== 'NONE') {
            return $this->getPriceModel();
        }
        if ($this->getSubcategory()->getPriceModel()->value !== 'NONE') {
            return $this->getSubcategory()->getPriceModel();
        }
        return $this->getCategory()->getPriceModel();
    }

    public function getActivePriceModelTarget(): string
    {
        if ($this->getPriceModel()->value !== 'NONE') {
            return 'product';
        }
        if ($this->getSubcategory()->getPriceModel()->value !== 'NONE') {
            return 'subcategory';
        }
        return 'category';
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
        if ($this->productImages->removeElement($productImage)) {
            // set the owning side to null (unless already changed)
            if ($productImage->getProduct() === $this) {
                $productImage->setProduct(null);
            }
        }

        return $this;
    }
}
