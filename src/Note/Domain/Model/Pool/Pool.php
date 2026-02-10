<?php

namespace App\Note\Domain\Model\Pool;

use App\Customer\Domain\Model\User\User;
use App\Note\Infrastructure\Persistence\Doctrine\PoolDoctrineRepository;
use App\Shared\Infrastructure\Persistence\Doctrine\Mapping\HasPublicUlid;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Timestampable\Traits\TimestampableEntity;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: PoolDoctrineRepository::class)]
class Pool
{
    use TimestampableEntity;
    use HasPublicUlid;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank(message: 'Please enter a pool name')]
    private ?string $name = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $description = null;

    #[ORM\Column]
    private bool $isActive = false;

    #[ORM\Column]
    private bool $isCustomerVisible = true;

    /** @var Collection<int, User> */
    #[ORM\ManyToMany(targetEntity: User::class)]
    #[ORM\JoinTable(name: 'pool_subscriber')]
    private Collection $subscribers;

    final public function __construct()
    {
        $this->initializePublicId();
        $this->subscribers = new ArrayCollection();
    }

    public static function create(
        string $name,
        ?string $description,
        bool $isActive,
        bool $isCustomerVisible,
    ): self {
        $self = new self();
        $self->rename($name);
        $self->setDescription($description);
        $self->setActive($isActive);
        $self->setCustomerVisible($isCustomerVisible);

        return $self;
    }

    public function update(
        string $name,
        ?string $description,
        bool $isActive,
        bool $isCustomerVisible,
    ): void {
        $this->rename($name);
        $this->setDescription($description);
        $this->setActive($isActive);
        $this->setCustomerVisible($isCustomerVisible);
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getPublicId(): PoolPublicId
    {
        return PoolPublicId::fromString($this->publicIdString());
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function isActive(): bool
    {
        return $this->isActive;
    }

    public function isCustomerVisible(): bool
    {
        return $this->isCustomerVisible;
    }

    public function subscribe(User $user): void
    {
        if (!$this->subscribers->contains($user)) {
            $this->subscribers->add($user);
        }
    }

    public function unsubscribe(User $user): void
    {
        $this->subscribers->removeElement($user);
    }

    public function isSubscribedBy(User $user): bool
    {
        return $this->subscribers->contains($user);
    }

    /**
     * @return Collection<int, User>
     */
    public function getSubscribers(): Collection
    {
        return $this->subscribers;
    }

    private function rename(string $name): void
    {
        $name = trim($name);
        if ($name === '') {
            throw new \InvalidArgumentException('Pool name cannot be empty');
        }

        $this->name = $name;
    }

    private function setDescription(?string $description): void
    {
        $this->description = $description !== null ? trim($description) : null;
    }

    private function setActive(bool $active): void
    {
        if ($this->isActive === $active) {
            return;
        }

        $this->isActive = $active;
    }

    private function setCustomerVisible(bool $customerVisible): void
    {
        $this->isCustomerVisible = $customerVisible;
    }
}
