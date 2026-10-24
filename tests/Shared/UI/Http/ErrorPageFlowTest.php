<?php

declare(strict_types=1);

namespace App\Tests\Shared\UI\Http;

use App\Tests\Shared\Factory\UserFactory;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\Uid\Ulid;
use Zenstruck\Browser\Test\HasBrowser;
use Zenstruck\Foundry\Test\Factories;

/**
 * Error pages render without debug, as in production; in debug Symfony shows its exception page instead.
 */
final class ErrorPageFlowTest extends WebTestCase
{
    use HasBrowser;
    use Factories;

    public function testNotFoundInTheModalFrameShowsTheErrorInsideTheModal(): void
    {
        // A confirm link for a record someone else has deleted
        $this->browser(['debug' => false])
            ->actingAs(UserFactory::new()->asStaff()->create())
            ->get('/manufacturer/' . new Ulid() . '/delete/confirm', ['headers' => ['Turbo-Frame' => 'modal']])
            ->assertStatus(404)
            ->assertElementCount('turbo-frame#modal', 1)
            ->assertSeeIn('turbo-frame#modal h3#modal-title', 'Lost in the Martian Dust')
            ->assertSeeIn('turbo-frame#modal', "The page you're looking for has drifted away")
            ->assertNotSeeElement('turbo-frame#body');
    }

    public function testForbiddenInTheModalFrameShowsTheErrorInsideTheModal(): void
    {
        $this->browser(['debug' => false])
            ->actingAs(UserFactory::createOne())
            ->get('/note/ticket/', ['headers' => ['Turbo-Frame' => 'modal']])
            ->assertStatus(403)
            ->assertSeeIn('turbo-frame#modal h3#modal-title', 'Unauthorized Access to Mars Colony Systems')
            ->assertSeeIn('turbo-frame#modal', 'Full access may be available once your account has been approved by an admin.');
    }

    public function testNotFoundOutsideTheModalIsAFullPage(): void
    {
        $this->browser(['debug' => false])
            ->get('/no-such-page')
            ->assertStatus(404)
            ->assertSeeIn('turbo-frame#body h2', 'Lost in the Martian Dust')
            ->assertSeeElement('turbo-frame#body a[href="/"][data-turbo-frame="body"]')
            ->assertNotSeeElement('h3#modal-title');
    }
}
