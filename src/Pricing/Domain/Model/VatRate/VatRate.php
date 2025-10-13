<?php

namespace App\Pricing\Domain\Model\VatRate;

use App\Catalog\Domain\Model\Category\Category;
use App\Pricing\Domain\Model\VatRate\Event\VatRateWasChangedEvent;
use App\Pricing\Infrastructure\Persistence\Doctrine\VatRateDoctrineRepository;
use App\Shared\Domain\Event\DomainEventProviderInterface;
use App\Shared\Domain\Event\DomainEventProviderTrait;
use App\Shared\Infrastructure\Persistence\Doctrine\Mapping\HasPublicUlid;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Timestampable\Traits\TimestampableEntity;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: VatRateDoctrineRepository::class)]
class VatRate implements DomainEventProviderInterface
{
    public const string STANDARD_VAT_NAME = 'Standard Rate';
    public const string STANDARD_VAT_RATE = '20.00';

    use TimestampableEntity;
    use DomainEventProviderTrait;
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
    private ?string $rate = '0.00';

    #[ORM\Column]
    private bool $isDefaultVatRate = false;

    #[ORM\OneToMany(targetEntity: Category::class, mappedBy: 'vatRate')]
    private Collection $categories;

    public function __construct()
    {
        $this->initializePublicId();
        $this->categories = new ArrayCollection();
    }

    public static function create(string $name, string $rate): self
    {
        $self = new self();
        $self->rename($name);
        $self->changeRate($rate);

        return $self;
    }

    public function update(string $name, string $rate): void
    {
        $this->rename($name);
        $this->changeRate($rate);
    }

    public function setAsDefaultRate(bool $isDefault): void
    {
        $this->isDefaultVatRate = $isDefault;
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

    public function getRate(): ?string
    {
        return $this->rate;
    }

    public function isDefaultVatRate(): bool
    {
        return $this->isDefaultVatRate;
    }

    private function rename(string $name): void
    {
        $name = trim($name);
        if ($name === '') {
            throw new \InvalidArgumentException('Rate name cannot be empty');
        }
        $this->name = $name;
    }

    private function changeRate(?string $rate): void
    {
        if ((float) $rate < 0) {
            throw new \InvalidArgumentException('Rate cannot be negative');
        }

        if ($rate === $this->rate) {
            return;
        }

        $this->rate = $rate;

        $this->raiseDomainEvent(new VatRateWasChangedEvent($this->getPublicId()));
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
            $category->changeVatRate($this);
        }

        return $this;
    }

    public function removeCategory(Category $category): static
    {
        // set the owning side to null (unless already changed)
        if ($this->categories->removeElement($category) && $category->getVatRate() === $this) {
            $category->changeVatRate(null);
        }

        return $this;
    }
}
