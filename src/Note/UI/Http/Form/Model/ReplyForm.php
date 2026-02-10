<?php

namespace App\Note\UI\Http\Form\Model;

use Symfony\Component\Validator\Constraints as Assert;

final class ReplyForm
{
    #[Assert\NotBlank(message: 'Please enter a message')]
    public ?string $body = null;

    public string $visibility = 'PUBLIC';
}
