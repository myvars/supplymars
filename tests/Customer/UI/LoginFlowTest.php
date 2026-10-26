<?php

declare(strict_types=1);

namespace App\Tests\Customer\UI;

use App\Tests\Shared\Factory\UserFactory;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Zenstruck\Browser\Test\HasBrowser;
use Zenstruck\Foundry\Test\Factories;

final class LoginFlowTest extends WebTestCase
{
    use HasBrowser;
    use Factories;

    public function testEverySignInIsRememberedWithoutABox(): void
    {
        UserFactory::new()->asStaff()->create(['email' => 'pilot@example.com', 'password' => 'Secret-123']);

        $browser = $this->browser()
            ->visit('/login')
            ->assertNotSee('Remember me')
            ->assertElementCount('input[type="checkbox"]', 0)
            ->fillField('email', 'pilot@example.com')
            ->fillField('password', 'Secret-123')
            ->click('Sign in')
            ->assertNotOn('/login');

        self::assertNotNull($browser->client()->getCookieJar()->get('REMEMBERME'));
    }
}
