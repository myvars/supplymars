<?php

namespace App\Entity;

use App\Enum\PriceModel;
use App\Repository\SubcategoryRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\Common\Collections\Criteria;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Timestampable\Traits\TimestampableEntity;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: SubcategoryRepository::class)]
class Subcategory
{
    use TimestampableEntity;
    public const string DEFAULT_MARKUP = '0.000';

    public const PriceModel DEFAULT_PRICE_MODEL = PriceModel::NONE;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank(message: 'Please enter a Subcategory name')]
    private ?string $name = null;

    #[ORM\Column(type: 'decimal', precision: 9, scale: 3)]
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
        $this->products = new ArrayCollection();
        $this->supplierSubcategories = new ArrayCollection();
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

    public function getDefaultMarkup(): ?string
    {
        return $this->defaultMarkup;
    }

    public function setDefaultMarkup(?string $defaultMarkup): static
    {
        $this->defaultMarkup = $defaultMarkup;

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

    public function getCategory(): ?Category
    {
        return $this->category;
    }

    public function setCategory(?Category $category): static
    {
        $this->category = $category;

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

    public function isActive(): ?bool
    {
        return $this->isActive;
    }

    public function setIsActive(bool $isActive): static
    {
        $this->isActive = $isActive;

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
            $product->setSubcategory($this);
        }

        return $this;
    }

    public function removeProduct(Product $product): static
    {
        // set the owning side to null (unless already changed)
        if ($this->products->removeElement($product) && $product->getSubcategory() === $this) {
            $product->setSubcategory(null);
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
            $supplierSubcategory->setMappedSubcategory($this);
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
            $supplierSubcategory->setMappedSubcategory(null);
        }

        return $this;
    }
}
