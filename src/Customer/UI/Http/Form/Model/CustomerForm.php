<?php

namespace App\Customer\UI\Http\Form\Model;

use App\Customer\Domain\Model\User\User;
use Symfony\Component\Validator\Constraints as Assert;

final class CustomerForm
{
    public ?string $id = null;

    #[Assert\NotBlank(message: 'Please enter a full name')]
    #[Assert\Length(max: 50, maxMessage: 'Max 50 characters')]
    public ?string $fullName = null;

    #[Assert\NotBlank(message: 'Please enter a valid email')]
    #[Assert\Email]
    public ?string $email = null;

    public bool $isVerified = false;

    public bool $isStaff = false;

    public static function fromEntity(User $customer): self
    {
        $form = new self();

        $form->id = $customer->getPublicId()->value();
        $form->fullName = $customer->getFullName();
        $form->email = $customer->getEmail();
        $form->isVerified = $customer->isVerified();
        $form->isStaff = $customer->isStaff();

        return $form;
    }
}
