<?php

namespace App\Customer\UI\Http\Form\Model;

use Symfony\Component\Validator\Constraints as Assert;

final class RegistrationForm
{
    #[Assert\NotBlank(message: 'Please enter a full name')]
    #[Assert\Length(max: 50, maxMessage: 'Max 50 characters')]
    public ?string $fullName = null;

    #[Assert\NotBlank(message: 'Please enter a valid email')]
    #[Assert\Email]
    public ?string $email = null;

    #[Assert\NotBlank(message: 'Please confirm password')]
    #[Assert\Length(min: 6, max: 4096, minMessage: 'Your password should be at least {{ limit }} characters')]
    public ?string $plainPassword = null;

    #[Assert\IsTrue(message: 'Please agree to our terms.')]
    public bool $agreeTerms = false;
}
