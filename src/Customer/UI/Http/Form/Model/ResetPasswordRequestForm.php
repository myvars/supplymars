<?php

declare(strict_types=1);

namespace App\Customer\UI\Http\Form\Model;

use Symfony\Component\Validator\Constraints as Assert;

final class ResetPasswordRequestForm
{
    #[Assert\NotBlank(message: 'Please enter your email')]
    #[Assert\Email]
    public ?string $email = null;
}
