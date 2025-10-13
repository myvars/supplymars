<?php

namespace App\Pricing\UI\Http\Form\Model;

use App\Pricing\Domain\Model\VatRate\VatRate;
use Symfony\Component\Validator\Constraints as Assert;

final class VatRateForm
{
    public ?string $id = null;

    #[Assert\NotBlank(message: 'Please enter a VAT rate name')]
    public ?string $name = null;

    #[Assert\NotBlank(message: 'Please enter a VAT rate %')]
    #[Assert\PositiveOrZero(message: 'Please enter a positive or zero VAT rate')]
    public ?string $rate = '0.00';

    public static function fromEntity(VatRate $vatRate): self
    {
        $form = new self();

        $form->id = $vatRate->getPublicId()->value();
        $form->name = $vatRate->getName();
        $form->rate = $vatRate->getRate();

        return $form;
    }
}
