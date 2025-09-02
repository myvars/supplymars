<?php

namespace App\Entity;

use App\Repository\VatRateRepository;
use App\ValueObject\VatRatePublicId;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Timestampable\Traits\TimestampableEntity;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: VatRateRepository::class)]
class VatRate
{
    use TimestampableEntity;
    use HasPublicUlid;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank(message: 'Please enter a VAT rate name')]
    private ?string $name = null;

    #[ORM\Column(type: 'decimal', precision: 10, scale: 2)]
    #[Assert\NotBlank(message: 'Please enter a VAT rate %')]
    #[Assert\PositiveOrZero(message: 'Please enter a positive or zero VAT rate')]
    private ?string $rate = '0.000';

    #[ORM\Column]
    private bool $isDefaultVatRate = false;

    #[ORM\OneToMany(targetEntity: Category::class, mappedBy: 'vatRate')]
    private Collection $categories;

    public function __construct()
    {
        $this->initializePublicId();
        $this->categories = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getPublicId(): VatRatePublicId
    {
        return VatRatePublicId::fromString($this->publicIdString());
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

    public function getRate(): ?string
    {
        return $this->rate;
    }

    public function setRate(?string $rate): static
    {
        $this->rate = $rate;

        return $this;
    }

    public function isDefaultVatRate(): bool
    {
        return $this->isDefaultVatRate;
    }

    public function setIsDefaultVatRate(bool $isDefaultVatRate): static
    {
        $this->isDefaultVatRate = $isDefaultVatRate;

        return $this;
    }

    /**
     * @return Collection<int, Category>
     */
    public function getCategories(): Collection
    {
        return $this->categories;
    }

    public function addCategory(Category $category): static
    {
        if (!$this->categories->contains($category)) {
            $this->categories->add($category);
            $category->setVatRate($this);
        }

        return $this;
    }

    public function removeCategory(Category $category): static
    {
        // set the owning side to null (unless already changed)
        if ($this->categories->removeElement($category) && $category->getVatRate() === $this) {
            $category->setVatRate(null);
        }

        return $this;
    }
}
