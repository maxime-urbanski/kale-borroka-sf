<?php

declare(strict_types=1);

namespace App\Entity;

use App\Enum\OrderStatus;
use App\Enum\PaymentStatus;
use App\Repository\OrderRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

/**
 * A customer order. `status` is driven by the `order` workflow (config/packages/workflow.yaml)
 * through getMarking()/setMarking(): apply transitions, never set it by hand.
 */
#[ORM\Entity(repositoryClass: OrderRepository::class)]
#[ORM\Table(name: '`order`')]
class Order
{
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'SEQUENCE')]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 100, unique: true)]
    private ?string $reference = null;

    #[ORM\ManyToOne(inversedBy: 'orders')]
    private ?User $buyer = null;

    #[ORM\Column]
    private ?\DateTimeImmutable $created_at = null;

    /** The address book entry picked at checkout; null once the customer deletes it. */
    #[ORM\ManyToOne]
    #[ORM\JoinColumn(onDelete: 'SET NULL')]
    private ?Address $address = null;

    /**
     * Copy of the delivery address taken at checkout (Address::__toString(), plain text):
     * editing or deleting the address book entry later must not change a placed order.
     */
    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $shippingAddress = null;

    #[ORM\Column(length: 20, enumType: OrderStatus::class, options: ['default' => 'pending'])]
    private OrderStatus $status = OrderStatus::PENDING;

    #[ORM\Column(length: 20, enumType: PaymentStatus::class, options: ['default' => 'awaiting'])]
    private PaymentStatus $paymentStatus = PaymentStatus::AWAITING;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $paidAt = null;

    #[ORM\Column]
    private ?int $totalPrice = null;

    /** @var Collection<int, OrderDetails> */
    #[ORM\OneToMany(mappedBy: 'orders', targetEntity: OrderDetails::class, cascade: ['persist'], orphanRemoval: true)]
    private Collection $orderDetails;

    #[ORM\ManyToOne]
    private ?Transporter $delivery = null;

    #[ORM\ManyToOne(inversedBy: 'orders')]
    private ?Payment $payment = null;

    public function __construct()
    {
        $this->orderDetails = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getReference(): ?string
    {
        return $this->reference;
    }

    public function setReference(string $reference): static
    {
        $this->reference = $reference;

        return $this;
    }

    public function getBuyer(): ?User
    {
        return $this->buyer;
    }

    public function setBuyer(?User $buyer): static
    {
        $this->buyer = $buyer;

        return $this;
    }

    public function getCreatedAt(): ?\DateTimeImmutable
    {
        return $this->created_at;
    }

    public function setCreatedAt(\DateTimeImmutable $created_at): static
    {
        $this->created_at = $created_at;

        return $this;
    }

    public function getAddress(): ?Address
    {
        return $this->address;
    }

    public function setAddress(?Address $address): static
    {
        $this->address = $address;

        return $this;
    }

    public function getShippingAddress(): ?string
    {
        return $this->shippingAddress;
    }

    public function setShippingAddress(?string $shippingAddress): static
    {
        $this->shippingAddress = $shippingAddress;

        return $this;
    }

    public function getStatus(): OrderStatus
    {
        return $this->status;
    }

    /**
     * Workflow marking store accessor (the workflow deals in strings).
     */
    public function getMarking(): string
    {
        return $this->status->value;
    }

    /**
     * Workflow marking store accessor. Only the workflow calls this.
     *
     * @param array<string, mixed> $context
     */
    public function setMarking(string $marking, array $context = []): void
    {
        $this->status = OrderStatus::from($marking);
    }

    public function getPaymentStatus(): PaymentStatus
    {
        return $this->paymentStatus;
    }

    public function setPaymentStatus(PaymentStatus $paymentStatus): static
    {
        $this->paymentStatus = $paymentStatus;

        return $this;
    }

    public function getPaidAt(): ?\DateTimeImmutable
    {
        return $this->paidAt;
    }

    public function setPaidAt(?\DateTimeImmutable $paidAt): static
    {
        $this->paidAt = $paidAt;

        return $this;
    }

    public function getTotalPrice(): ?int
    {
        return $this->totalPrice;
    }

    public function setTotalPrice(int $totalPrice): static
    {
        $this->totalPrice = $totalPrice;

        return $this;
    }

    /**
     * @return Collection<int, OrderDetails>
     */
    public function getOrderDetails(): Collection
    {
        return $this->orderDetails;
    }

    public function addOrderDetail(OrderDetails $orderDetail): static
    {
        if (!$this->orderDetails->contains($orderDetail)) {
            $this->orderDetails->add($orderDetail);
            $orderDetail->setOrders($this);
        }

        return $this;
    }

    public function removeOrderDetail(OrderDetails $orderDetail): static
    {
        if ($this->orderDetails->removeElement($orderDetail)) {
            // set the owning side to null (unless already changed)
            if ($orderDetail->getOrders() === $this) {
                $orderDetail->setOrders(null);
            }
        }

        return $this;
    }

    public function getDelivery(): ?Transporter
    {
        return $this->delivery;
    }

    public function setDelivery(?Transporter $delivery): static
    {
        $this->delivery = $delivery;

        return $this;
    }

    public function getPayment(): ?Payment
    {
        return $this->payment;
    }

    public function setPayment(?Payment $payment): static
    {
        $this->payment = $payment;

        return $this;
    }
}
