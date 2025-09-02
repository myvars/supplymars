<?php

namespace App\Entity;

use App\Enum\PriceModel;
use App\Repository\CategoryRepository;
use App\ValueObject\CategoryPublicId;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\Common\Collections\Criteria;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Timestampable\Traits\TimestampableEntity;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: CategoryRepository::class)]
class Category
{
    use TimestampableEntity;
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

    #[ORM\Column(type: 'decimal', precision: 9, scale: 3)]
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

    public function setName(?string $name): static
    {
        $this->name = $name;

        return $this;
    }

    public function getDefaultMarkup(): ?string
    {
        return $this->defaultMarkup;
    }

    public function setDefaultMarkup(string $defaultMarkup): static
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

    public function getVatRate(): ?VatRate
    {
        return $this->vatRate;
    }

    public function setVatRate(?VatRate $vatRate): static
    {
        $this->vatRate = $vatRate;

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
            $subcategory->setCategory($this);
        }

        return $this;
    }

    public function removeSubcategory(Subcategory $subcategory): static
    {
        // set the owning side to null (unless already changed)
        if ($this->subcategories->removeElement($subcategory) && $subcategory->getCategory() === $this) {
            $subcategory->setCategory(null);
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
        $criteria = Criteria::create()
            ->andWhere(Criteria::expr()->eq('isActive', true));

        return $this->products->matching($criteria);
    }

    public function addProduct(Product $product): static
    {
        if (!$this->products->contains($product)) {
            $this->products->add($product);
            $product->setCategory($this);
        }

        return $this;
    }

    public function removeProduct(Product $product): static
    {
        // set the owning side to null (unless already changed)
        if ($this->products->removeElement($product) && $product->getCategory() === $this) {
            $product->setCategory(null);
        }

        return $this;
    }
}
