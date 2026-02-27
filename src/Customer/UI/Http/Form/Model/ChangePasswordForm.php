<?php

namespace App\Customer\UI\Http\Form\Model;

use Symfony\Component\Validator\Constraints as Assert;

final class ChangePasswordForm
{
    #[Assert\NotBlank(message: 'Please enter a password')]
    #[Assert\Length(min: 6, max: 4096, minMessage: 'Your password should be at least {{ limit }} characters')]
    public ?string $plainPassword = null;
}
