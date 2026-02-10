<?php

namespace App\Note\UI\Http\Form\Model;

use Symfony\Component\Validator\Constraints as Assert;

final class ReassignForm
{
    #[Assert\NotNull(message: 'Please choose a pool')]
    public ?int $poolId = null;
}
