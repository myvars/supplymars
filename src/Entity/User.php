<?php

namespace App\Entity;

use App\Repository\UserRepository;
use App\ValueObject\UserPublicId;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Timestampable\Traits\TimestampableEntity;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: UserRepository::class)]
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

    #[ORM\Column(type: 'boolean')]
    private bool $isVerified = false;

    #[ORM\Column(type: 'boolean')]
    private bool $isStaff = false;

    #[ORM\OneToMany(targetEntity: Category::class, mappedBy: 'owner')]
    private Collection $categories;

    #[ORM\OneToMany(targetEntity: Subcategory::class, mappedBy: 'owner')]
    private Collection $subcategories;

    /**
     * @var Collection<int, Address>
     */
    #[ORM\OneToMany(targetEntity: Address::class, mappedBy: 'customer')]
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

    public function setEmail(?string $email): static
    {
        $this->email = $email;

        return $this;
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

    public function setRoles(array $roles): static
    {
        $this->roles = $roles;

        return $this;
    }

    /**
     * @see PasswordAuthenticatedUserInterface
     */
    public function getPassword(): string
    {
        return $this->password;
    }

    public function setPassword(string $password): static
    {
        $this->password = $password;

        return $this;
    }

    /**
     * @see UserInterface
     */
    public function eraseCredentials(): void
    {
        // If you store any temporary, sensitive data on the user, clear it here
        // $this->plainPassword = null;
    }

    public function getFullName(): ?string
    {
        return $this->fullName;
    }

    public function setFullName(?string $fullName): static
    {
        $this->fullName = $fullName;

        return $this;
    }

    public function __toString(): string
    {
        return $this->getUserIdentifier();
    }

    public function isVerified(): bool
    {
        return $this->isVerified;
    }

    public function setIsVerified(bool $isVerified): static
    {
        $this->isVerified = $isVerified;

        return $this;
    }

    public function isStaff(): bool
    {
        return $this->isStaff;
    }

    public function setIsStaff(bool $isStaff): User
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
            $category->setOwner($this);
        }

        return $this;
    }

    public function removeCategory(Category $category): static
    {
        // set the owning side to null (unless already changed)
        if ($this->categories->removeElement($category) && $category->getOwner() === $this) {
            $category->setOwner(null);
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
            $subcategory->setOwner($this);
        }

        return $this;
    }

    public function removeSubcategory(Subcategory $subcategory): static
    {
        // set the owning side to null (unless already changed)
        if ($this->subcategories->removeElement($subcategory) && $subcategory->getOwner() === $this) {
            $subcategory->setOwner(null);
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
            $address->setCustomer($this);
        }

        return $this;
    }

    public function removeAddress(Address $address): static
    {
        // set the owning side to null (unless already changed)
        if ($this->addresses->removeElement($address) && $address->getCustomer() === $this) {
            $address->setCustomer(null);
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
            $customerOrder->setCustomer($this);
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
            $statusChangeLog->setUser($this);
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
}
