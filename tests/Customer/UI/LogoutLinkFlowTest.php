<?php

declare(strict_types=1);

namespace App\Tests\Customer\UI;

use App\Tests\Shared\Factory\UserFactory;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Zenstruck\Browser\Test\HasBrowser;

final class LogoutLinkFlowTest extends WebTestCase
{
    use HasBrowser;

    /** Logout is a GET with no CSRF, so a hover prefetch of the link would sign the user out */
    public function testLogoutLinksAreNotPrefetched(): void
    {
        $this->browser()
            ->actingAs(UserFactory::new()->asStaff()->create())
            ->get('/login')
            ->assertSuccessful()
            ->assertSee('You are logged in as')
            ->assertElementCount('a[href="/logout"]', 2)
            ->assertElementCount('a[href="/logout"][data-turbo-prefetch="false"]', 2);
    }
}
