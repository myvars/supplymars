<?php

declare(strict_types=1);

namespace App\Customer\UI\Http\Form\Model;

use App\Customer\Domain\Model\User\User;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Validator\Constraints as Assert;

#[UniqueEntity(
    fields: ['email'],
    message: 'There is already an account with this email',
    entityClass: User::class,
    identifierFieldNames: ['userId' => 'id'],
)]
final class CustomerForm
{
    public ?string $id = null;

    /** The entity's own identifier, so the customer's current email is not counted as taken. Not a form field. */
    public ?int $userId = null;

    #[Assert\NotBlank(message: 'Please enter a full name')]
    #[Assert\Length(max: 50, maxMessage: 'Max 50 characters')]
    public ?string $fullName = null;

    #[Assert\NotBlank(message: 'Please enter a valid email')]
    #[Assert\Email]
    #[Assert\Length(max: 180, maxMessage: 'Max 180 characters')]
    public ?string $email = null;

    public bool $isVerified = false;

    public bool $isStaff = false;

    public static function fromEntity(User $customer): self
    {
        $form = new self();

        $form->id = $customer->getPublicId()->value();
        $form->userId = $customer->getId();
        $form->fullName = $customer->getFullName();
        $form->email = $customer->getEmail();
        $form->isVerified = $customer->isVerified();
        $form->isStaff = $customer->isStaff();

        return $form;
    }
}
