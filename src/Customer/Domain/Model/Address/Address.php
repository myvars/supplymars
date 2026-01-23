<?php

namespace App\Customer\Domain\Model\Address;

use App\Customer\Domain\Model\User\User;
use App\Customer\Infrastructure\Persistence\Doctrine\AddressDoctrineRepository;
use App\Order\Domain\Model\Order\CustomerOrder;
use App\Purchasing\Domain\Model\PurchaseOrder\PurchaseOrder;
use App\Shared\Infrastructure\Persistence\Doctrine\Mapping\HasPublicUlid;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: AddressDoctrineRepository::class)]
class Address
{
    use HasPublicUlid;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $fullName = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $companyName = null;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank(message: 'Please enter a street')]
    private ?string $street = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $street2 = null;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank(message: 'Please enter a city')]
    private ?string $city = null;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank(message: 'Please enter a county')]
    private ?string $county = null;

    #[ORM\Column(length: 10)]
    #[Assert\NotBlank(message: 'Please enter a post code')]
    private ?string $postCode = null;

    #[ORM\Column(length: 100)]
    #[Assert\NotBlank(message: 'Please enter a country')]
    private ?string $country = null;

    #[ORM\Column(length: 20, nullable: true)]
    private ?string $phoneNumber = null;

    #[ORM\Column(length: 255, nullable: true)]
    #[Assert\Email(message: 'Please enter a valid email address')]
    private ?string $email = null;

    #[ORM\ManyToOne(inversedBy: 'addresses')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?User $customer = null;

    #[ORM\Column]
    private bool $isDefaultShippingAddress = false;

    #[ORM\Column]
    private bool $isDefaultBillingAddress = false;

    /**
     * @var Collection<int, CustomerOrder>
     */
    #[ORM\OneToMany(targetEntity: CustomerOrder::class, mappedBy: 'shippingAddress')]
    private Collection $customerOrders;

    /**
     * @var Collection<int, PurchaseOrder>
     */
    #[ORM\OneToMany(targetEntity: PurchaseOrder::class, mappedBy: 'shippingAddress')]
    private Collection $purchaseOrders;

    final public function __construct()
    {
        $this->initializePublicId();
        $this->customerOrders = new ArrayCollection();
        $this->purchaseOrders = new ArrayCollection();
    }

    public static function create(
        ?string $fullName,
        ?string $companyName,
        string $street,
        ?string $street2,
        string $city,
        string $county,
        string $postCode,
        string $country,
        ?string $phoneNumber,
        ?string $email,
        ?User $customer,
        bool $isDefaultShippingAddress,
        bool $isDefaultBillingAddress,
    ): self {
        $self = new self();
        $self->fullName = $fullName;
        $self->companyName = $companyName;
        $self->street = $street;
        $self->street2 = $street2;
        $self->city = $city;
        $self->county = $county;
        $self->postCode = $postCode;
        $self->country = $country;
        $self->phoneNumber = $phoneNumber;
        $self->email = $email;
        $self->assignCustomer($customer);
        $self->isDefaultShippingAddress = $isDefaultShippingAddress;
        $self->isDefaultBillingAddress = $isDefaultBillingAddress;

        return $self;
    }

    public function assignCustomer(?User $customer): void
    {
        $this->customer = $customer;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getPublicId(): AddressPublicId
    {
        return AddressPublicId::fromString($this->publicIdString());
    }

    public function getFullName(): ?string
    {
        return $this->fullName;
    }

    public function getCompanyName(): ?string
    {
        return $this->companyName;
    }

    public function getStreet(): ?string
    {
        return $this->street;
    }

    public function getStreet2(): ?string
    {
        return $this->street2;
    }

    public function getCity(): ?string
    {
        return $this->city;
    }

    public function getCounty(): ?string
    {
        return $this->county;
    }

    public function getPostCode(): ?string
    {
        return $this->postCode;
    }

    public function getCountry(): ?string
    {
        return $this->country;
    }

    public function getPhoneNumber(): ?string
    {
        return $this->phoneNumber;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function getCustomer(): User
    {
        return $this->customer ?? throw new \LogicException('Customer must be set');
    }

    public function isDefaultShippingAddress(): bool
    {
        return $this->isDefaultShippingAddress;
    }

    public function isDefaultBillingAddress(): bool
    {
        return $this->isDefaultBillingAddress;
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
            $customerOrder->assignShippingAddress($this);
        }

        return $this;
    }

    public function removeCustomerOrder(CustomerOrder $customerOrder): static
    {
        // set the owning side to null (unless already changed)
        if ($this->customerOrders->removeElement($customerOrder) && $customerOrder->getShippingAddress() === $this) {
        }

        return $this;
    }

    /**
     * @return Collection<int, PurchaseOrder>
     */
    public function getPurchaseOrders(): Collection
    {
        return $this->purchaseOrders;
    }

    public function addPurchaseOrder(PurchaseOrder $purchaseOrder): static
    {
        if (!$this->purchaseOrders->contains($purchaseOrder)) {
            $this->purchaseOrders->add($purchaseOrder);
            $purchaseOrder->assignShippingAddress($this);
        }

        return $this;
    }

    public function removePurchaseOrder(PurchaseOrder $purchaseOrder): static
    {
        // set the owning side to null (unless already changed)
        if ($this->purchaseOrders->removeElement($purchaseOrder) && $purchaseOrder->getShippingAddress() === $this) {
        }

        return $this;
    }
}
