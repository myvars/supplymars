<?php

declare(strict_types=1);

namespace App\Tests\Shared\Story;

use App\Shared\Infrastructure\Security\DefaultUserAuthenticator;
use App\Tests\Shared\Factory\UserFactory;
use Zenstruck\Foundry\Story;

final class SuperAdminUserStory extends Story
{
    public function __construct(private readonly DefaultUserAuthenticator $defaultUserAuthenticator)
    {
    }

    public function build(): void
    {
        UserFactory::new(['email' => $this->defaultUserAuthenticator->getDefaultEmail()])->asSuperAdmin()->create();
        $this->defaultUserAuthenticator->ensureAuthenticated();
    }
}
