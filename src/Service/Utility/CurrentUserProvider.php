<?php

namespace App\Service\Utility;

use App\Entity\User;
use Symfony\Bundle\SecurityBundle\Security;

final readonly class CurrentUserProvider
{
    public function __construct(private Security $security)
    {
    }

    public function get(): User
    {
        $user = $this->security->getUser();
        if (!$user instanceof User) {
            throw new \RuntimeException('No authenticated user found.');
        }

        return $user;
    }
}
