<?php

namespace App\Tests\Shared\UI\Http\Api;

use App\Tests\Shared\Factory\UserFactory;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Zenstruck\Browser\HttpOptions;
use Zenstruck\Browser\Test\HasBrowser;
use Zenstruck\Foundry\Test\Factories;

final class ApiAuthenticationTest extends WebTestCase
{
    use HasBrowser;
    use Factories;

    public function testUnauthenticatedRequestReturns401WithRfc7807(): void
    {
        $this->browser()
            ->get('/api/v1/orders')
            ->assertStatus(401)
            ->assertJson()
            ->assertJsonMatches('type', 'about:blank')
            ->assertJsonMatches('status', 401)
            ->assertJsonMatches('detail', 'Authentication required.');
    }

    public function testInvalidBearerTokenReturns401WithRfc7807(): void
    {
        $this->browser()
            ->get('/api/v1/orders', [
                'headers' => ['Authorization' => 'Bearer invalid-token-value'],
            ])
            ->assertStatus(401)
            ->assertJson()
            ->assertJsonMatches('type', 'about:blank')
            ->assertJsonMatches('status', 401)
            ->assertJsonMatches('detail', 'Invalid API token.');
    }

    public function testValidBearerTokenAllowsAccess(): void
    {
        $token = bin2hex(random_bytes(32));
        UserFactory::new()->asStaff()->afterInstantiate(function ($user) use ($token): void {
            $user->setApiToken($token);
        })->create();

        $this->browser()
            ->get('/api/v1/orders', [
                'headers' => ['Authorization' => 'Bearer ' . $token],
            ])
            ->assertSuccessful()
            ->assertJson()
            ->assertJsonMatches('length(data)', 0);
    }

    public function testNotFoundReturnsRfc7807(): void
    {
        $this->browser()
            ->get('/api/v1/catalog/products/01ZZZZZZZZZZZZZZZZZZZZZZZZ')
            ->assertStatus(404)
            ->assertJsonMatches('type', 'about:blank')
            ->assertJsonMatches('status', 404);
    }

    public function testValidationErrorReturnsViolations(): void
    {
        $user = UserFactory::new()->asStaff()->create();

        $this->browser()
            ->actingAs($user, 'api')
            ->post('/api/v1/orders', HttpOptions::json([
                'customer' => '',
                'shippingMethod' => 'INVALID',
            ]))
            ->assertStatus(422)
            ->assertJsonMatches('type', 'about:blank')
            ->assertJsonMatches('title', 'Validation Failed')
            ->assertJsonMatches('status', 422)
            ->assertJsonMatches('length(violations) > `0`', true);
    }
}
