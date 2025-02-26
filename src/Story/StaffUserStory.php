<?php

namespace App\Story;

use App\Factory\UserFactory;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use Zenstruck\Foundry\Story;

final class StaffUserStory extends Story
{
    public function __construct(private readonly TokenStorageInterface $tokenStorage)
    {
    }

    public function build(): void
    {
        $user = UserFactory::new()->staff()->create()->_real();
        $token = new UsernamePasswordToken($user, 'main', $user->getRoles());
        $this->tokenStorage->setToken($token);
    }
}
