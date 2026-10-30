<?php

declare(strict_types=1);

namespace App\Tests\Customer\UI;

use App\Tests\Shared\Factory\UserFactory;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Zenstruck\Browser\Test\HasBrowser;

final class PlaygroundLoginFlowTest extends WebTestCase
{
    use HasBrowser;

    public function testPlaygroundLoginRouteReturns404WhenNotInPlaygroundMode(): void
    {
        $this->browser()
            ->post('/playground/login')
            ->assertStatus(404);
    }

    public function testSignsInAsTheDemoUserInPlaygroundMode(): void
    {
        UserFactory::new(['email' => 'demo@supplymars.com'])->asStaff()->create();

        $this->inPlaygroundMode(function (): void {
            $this->browser()
                ->post('/playground/login', ['body' => ['turnstile_response' => 'token']])
                ->assertOn('/dashboard/')
                ->assertAuthenticated('demo@supplymars.com');
        });
    }

    public function testRefusesWhenTheDemoUserIsMissing(): void
    {
        $this->inPlaygroundMode(function (): void {
            $this->browser()
                ->post('/playground/login', ['body' => ['turnstile_response' => 'token']])
                ->assertOn('/login')
                ->assertSee('Demo account is not available.')
                ->assertNotAuthenticated();
        });
    }

    private function inPlaygroundMode(callable $test): void
    {
        $_SERVER['PLAYGROUND_MODE'] = $_ENV['PLAYGROUND_MODE'] = '1';
        try {
            $test();
        } finally {
            unset($_SERVER['PLAYGROUND_MODE'], $_ENV['PLAYGROUND_MODE']);
        }
    }
}
