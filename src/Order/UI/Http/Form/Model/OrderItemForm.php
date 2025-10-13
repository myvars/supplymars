<?php

namespace App\Order\UI\Http\Form\Model;

use App\Order\Domain\Model\Order\CustomerOrder;
use App\Order\UI\Http\Validation\ValidProductId;
use Symfony\Component\Validator\Constraints as Assert;

final class OrderItemForm
{
    #[Assert\Positive(message: 'Please enter a valid orderId')]
    public ?string $orderId = null;

    #[Assert\NotBlank(message: 'Please enter a product Id')]
    #[Assert\Range(notInRangeMessage: 'Please enter a valid product Id', min: 1, max: 10000000)]
    #[ValidProductId]
    public ?int $productId = null;

    #[Assert\NotBlank(message: 'Please enter a quantity')]
    #[Assert\Range(notInRangeMessage: 'Please enter a valid quantity', min: 1, max: 10000)]
    public ?int $quantity = null;

    public static function fromEntity(CustomerOrder $order): self
    {
        $form = new self();

        $form->orderId = $order->getPublicId()->value();

        return $form;
    }
}
