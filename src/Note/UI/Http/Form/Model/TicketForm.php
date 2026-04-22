<?php

declare(strict_types=1);

namespace App\Note\UI\Http\Form\Model;

use Symfony\Component\Validator\Constraints as Assert;

final class TicketForm
{
    #[Assert\NotNull(message: 'Please choose a pool')]
    public ?int $poolId = null;

    #[Assert\NotNull(message: 'Please choose a customer')]
    public ?int $customerId = null;

    #[Assert\NotBlank(message: 'Please enter a subject')]
    public ?string $subject = null;

    #[Assert\NotBlank(message: 'Please enter a message')]
    public ?string $body = null;
}
