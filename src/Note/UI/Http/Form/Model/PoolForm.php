<?php

declare(strict_types=1);

namespace App\Note\UI\Http\Form\Model;

use App\Note\Domain\Model\Pool\Pool;
use Symfony\Component\Validator\Constraints as Assert;

final class PoolForm
{
    public ?string $id = null;

    #[Assert\NotBlank(message: 'Please enter a pool name')]
    #[Assert\Length(max: 255, maxMessage: 'Max 255 characters')]
    public ?string $name = null;

    #[Assert\Length(max: 10000, maxMessage: 'Max 10000 characters')]
    public ?string $description = null;

    public bool $isActive = false;

    public bool $isCustomerVisible = true;

    public static function fromEntity(Pool $pool): self
    {
        $form = new self();

        $form->id = $pool->getPublicId()->value();
        $form->name = $pool->getName();
        $form->description = $pool->getDescription();
        $form->isActive = $pool->isActive();
        $form->isCustomerVisible = $pool->isCustomerVisible();

        return $form;
    }
}
