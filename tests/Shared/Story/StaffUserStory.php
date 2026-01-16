<?php

namespace App\Tests\Shared\Story;

use App\Shared\Infrastructure\Security\DefaultUserAuthenticator;
use App\Tests\Shared\Factory\UserFactory;
use Zenstruck\Foundry\Story;

final class StaffUserStory extends Story
{
    public function __construct(private readonly DefaultUserAuthenticator $defaultUserAuthenticator)
    {
    }

    public function build(): void
    {
        UserFactory::new(['email' => 'adam@admin.com'])->asStaff()->create();
        $this->defaultUserAuthenticator->ensureAuthenticated();
    }
}
