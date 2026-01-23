<?php

namespace App\Order\UI\Http\Form\Model;

use App\Order\Domain\Model\Order\CustomerOrderItem;
use App\Order\UI\Http\Validation\MinOrderItemQty;
use Symfony\Component\Validator\Constraints as Assert;

final class UpdateOrderItemForm
{
    #[Assert\NotBlank(message: 'Missing order item id')]
    public ?string $orderItemId = null;

    #[Assert\NotBlank(message: 'Please enter a quantity')]
    #[Assert\Range(notInRangeMessage: 'Please enter a valid quantity', min: 0, max: 10000)]
    #[MinOrderItemQty]
    public ?int $quantity = null;

    /** @var numeric-string|null */
    #[Assert\NotNull(message: 'Please enter a product price inc VAT')]
    #[Assert\Range(notInRangeMessage: 'Please enter a product price inc VAT (0 to 100000)', min: 0, max: 100000)]
    public ?string $priceIncVat = null;

    public ?bool $allowCancel = false;

    public static function fromEntity(CustomerOrderItem $orderItem): self
    {
        $form = new self();

        $form->orderItemId = $orderItem->getPublicId()->value();
        $form->quantity = $orderItem->getQuantity();
        $form->priceIncVat = $orderItem->getPriceIncVat();
        $form->allowCancel = $orderItem->allowCancel();

        return $form;
    }
}
