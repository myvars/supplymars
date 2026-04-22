<?php

declare(strict_types=1);

namespace App\Tests\Customer\UI;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Zenstruck\Browser\Test\HasBrowser;
use Zenstruck\Foundry\Test\Factories;

final class PlaygroundLoginFlowTest extends WebTestCase
{
    use HasBrowser;
    use Factories;

    public function testPlaygroundLoginRouteReturns404WhenNotInPlaygroundMode(): void
    {
        $this->browser()
            ->post('/playground/login')
            ->assertStatus(404);
    }
}
