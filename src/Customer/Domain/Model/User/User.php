<?php

namespace App\Customer\Domain\Model\User;

use App\Audit\Domain\Model\StatusChange\StatusChangeLog;
use App\Catalog\Domain\Model\Category\Category;
use App\Catalog\Domain\Model\Subcategory\Subcategory;
use App\Customer\Domain\Model\Address\Address;
use App\Customer\Infrastructure\Persistence\Doctrine\UserDoctrineRepository;
use App\Order\Domain\Model\Order\CustomerOrder;
use App\Shared\Infrastructure\Persistence\Doctrine\Mapping\HasPublicUlid;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Timestampable\Traits\TimestampableEntity;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: UserDoctrineRepository::class)]
#[UniqueEntity(fields: ['email'], message: 'There is already an account with this email')]
class User implements UserInterface, PasswordAuthenticatedUserInterface, \Stringable
{
    use TimestampableEntity;
    use HasPublicUlid;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 180, unique: true)]
    #[Assert\NotBlank(message: 'Please enter a valid email')]
    #[Assert\Email]
    private ?string $email = null;

    #[ORM\Column]
    private array $roles = [];

    /**
     * @var string The hashed password
     */
    #[ORM\Column]
    private ?string $password = null;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank(message: 'Please enter a full name')]
    #[Assert\Length(max: 50, maxMessage: 'Max 50 characters')]
    private ?string $fullName = null;

    #[ORM\Column(type: Types::BOOLEAN)]
    private bool $isVerified = false;

    #[ORM\Column(type: Types::BOOLEAN)]
    private bool $isStaff = false;

    #[ORM\OneToMany(targetEntity: Category::class, mappedBy: 'owner')]
    private Collection $categories;

    #[ORM\OneToMany(targetEntity: Subcategory::class, mappedBy: 'owner')]
    private Collection $subcategories;

    /**
     * @var Collection<int, Address>
     */
    #[ORM\OneToMany(targetEntity: Address::class, mappedBy: 'customer', orphanRemoval: true)]
    private Collection $addresses;

    /**
     * @var Collection<int, CustomerOrder>
     */
    #[ORM\OneToMany(targetEntity: CustomerOrder::class, mappedBy: 'customer')]
    private Collection $customerOrders;

    /**
     * @var Collection<int, StatusChangeLog>
     */
    #[ORM\OneToMany(targetEntity: StatusChangeLog::class, mappedBy: 'user')]
    private Collection $statusChangeLogs;

    public function __construct()
    {
        $this->initializePublicId();
        $this->categories = new ArrayCollection();
        $this->subcategories = new ArrayCollection();
        $this->addresses = new ArrayCollection();
        $this->customerOrders = new ArrayCollection();
        $this->statusChangeLogs = new ArrayCollection();
    }

    public static function create(
        string $fullName,
        string $email,
        bool $isStaff,
        bool $isVerified,
    ): self {
        $self = new self();
        $self->fullName = $fullName;
        $self->email = $email;
        $self->setStaff($isStaff);
        $self->setVerified($isVerified);

        return $self;
    }

    public function update(
        string $fullName,
        string $email,
        bool $isStaff,
        bool $isVerified,
    ): void {
        $this->fullName = $fullName;
        $this->email = $email;
        $this->setStaff($isStaff);
        $this->setVerified($isVerified);
    }

    public function setPassword(string $password): void
    {
        $this->password = $password;
    }

    /**
     * @see UserInterface
     */
    public function eraseCredentials(): void
    {
        // If you store any temporary, sensitive data on the user, clear it here
        // $this->plainPassword = null;
    }

    public function setVerified(bool $isVerified): static
    {
        $this->isVerified = $isVerified;

        return $this;
    }

    public function setStaff(bool $isStaff): User
    {
        $this->isStaff = $isStaff;

        if ($isStaff) {
            $this->roles[] = 'ROLE_ADMIN';
        } else {
            $key = array_search('ROLE_ADMIN', $this->roles, true);
            if (false !== $key) {
                unset($this->roles[$key]);
            }
        }

        return $this;
    }

    public function setRoles(array $roles): static
    {
        $this->roles = $roles;

        return $this;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getPublicId(): UserPublicId
    {
        return UserPublicId::fromString($this->publicIdString());
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    /**
     * A visual identifier that represents this user.
     *
     * @see UserInterface
     */
    public function getUserIdentifier(): string
    {
        return (string) $this->email;
    }

    /**
     * @see UserInterface
     */
    public function getRoles(): array
    {
        $roles = $this->roles;
        // guarantee every user at least has ROLE_USER
        $roles[] = 'ROLE_USER';

        return array_unique($roles);
    }

    public function __toString(): string
    {
        return $this->getUserIdentifier();
    }

    /**
     * @see PasswordAuthenticatedUserInterface
     */
    public function getPassword(): string
    {
        return $this->password;
    }

    public function getFullName(): ?string
    {
        return $this->fullName;
    }

    public function getBillingAddress(): ?Address
    {
        foreach ($this->addresses as $address) {
            if ($address->isDefaultBillingAddress()) {
                return $address;
            }
        }

        return null;
    }

    public function getShippingAddress(): ?Address
    {
        foreach ($this->addresses as $address) {
            if ($address->isDefaultShippingAddress()) {
                return $address;
            }
        }

        return null;
    }

    public function isVerified(): bool
    {
        return $this->isVerified;
    }

    public function isStaff(): bool
    {
        return $this->isStaff;
    }

    public function isDeletable(): bool
    {
        return !$this->isAdmin() && $this->getCustomerOrders()->isEmpty();
    }

    public function isAdmin(): bool
    {
        return in_array('ROLE_ADMIN', $this->getRoles(), true);
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
            $category->assignOwner($this);
        }

        return $this;
    }

    public function removeCategory(Category $category): static
    {
        // set the owning side to null (unless already changed)
        if ($this->categories->removeElement($category) && $category->getOwner() === $this) {
            $category->assignOwner(null);
        }

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
            $subcategory->assignOwner($this);
        }

        return $this;
    }

    public function removeSubcategory(Subcategory $subcategory): static
    {
        // set the owning side to null (unless already changed)
        if ($this->subcategories->removeElement($subcategory) && $subcategory->getOwner() === $this) {
            $subcategory->assignOwner(null);
        }

        return $this;
    }

    /**
     * @return Collection<int, Address>
     */
    public function getAddresses(): Collection
    {
        return $this->addresses;
    }

    public function addAddress(Address $address): static
    {
        if (!$this->addresses->contains($address)) {
            $this->addresses->add($address);
            $address->assignCustomer($this);
        }

        return $this;
    }

    public function removeAddress(Address $address): static
    {
        // set the owning side to null (unless already changed)
        if ($this->addresses->removeElement($address) && $address->getCustomer() === $this) {
            $address->assignCustomer(null);
        }

        return $this;
    }

    /**
     * @return Collection<int, CustomerOrder>
     */
    public function getCustomerOrders(): Collection
    {
        return $this->customerOrders;
    }

    public function addCustomerOrder(CustomerOrder $customerOrder): static
    {
        if (!$this->customerOrders->contains($customerOrder)) {
            $this->customerOrders->add($customerOrder);
            $customerOrder->assignCustomer($this);
        }

        return $this;
    }

    public function removeCustomerOrder(CustomerOrder $customerOrder): static
    {
        // set the owning side to null (unless already changed)
        if ($this->customerOrders->removeElement($customerOrder) && $customerOrder->getCustomer() === $this) {
        }

        return $this;
    }

    /**
     * @return Collection<int, StatusChangeLog>
     */
    public function getStatusChangeLogs(): Collection
    {
        return $this->statusChangeLogs;
    }

    public function addStatusChangeLog(StatusChangeLog $statusChangeLog): static
    {
        if (!$this->statusChangeLogs->contains($statusChangeLog)) {
            $this->statusChangeLogs->add($statusChangeLog);
            $statusChangeLog->assignUser($this);
        }

        return $this;
    }

    public function removeStatusChangeLog(StatusChangeLog $statusChangeLog): static
    {
        // set the owning side to null (unless already changed)
        if ($this->statusChangeLogs->removeElement($statusChangeLog) && $statusChangeLog->getUser() === $this) {
        }

        return $this;
    }
}
