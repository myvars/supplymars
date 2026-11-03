<?php

declare(strict_types=1);

namespace App\Tests\Shared\UI\Http;

use App\Tests\Shared\Factory\UserFactory;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Zenstruck\Browser\Test\HasBrowser;

/**
 * A redirect Turbo follows inside the body or results frame loads the next page with the Turbo-Frame header, so its
 * flashes must come as a stream inside that frame (base.html.twig, Search.html.twig).
 */
final class FrameFlashFlowTest extends WebTestCase
{
    use HasBrowser;

    private const string FLASH_STREAM = 'turbo-frame#body turbo-stream[action="append"][target="flash-container"]';

    public function testOutOfRangePageShowsItsFlashOnPageOneInTheBodyFrame(): void
    {
        $this->browser()
            ->actingAs(UserFactory::new()->asStaff()->create())
            ->get('/manufacturer/?page=99', ['headers' => ['Turbo-Frame' => 'body']])
            ->assertOn('/manufacturer/?page=1')
            ->assertSuccessful()
            ->assertSeeIn(self::FLASH_STREAM . ' template', 'Page 99 not found.')
            ->assertNotSeeIn('#flash-container', 'Page 99 not found.')
            ->get('/manufacturer/')
            ->assertNotSee('Page 99 not found.');
    }

    public function testOutOfRangePageShowsItsFlashOnPageOneInTheTableFrame(): void
    {
        // The search box and the page and sort links load into the results frame
        $this->browser()
            ->actingAs(UserFactory::new()->asStaff()->create())
            ->get('/manufacturer/?page=99&query=x', ['headers' => ['Turbo-Frame' => 'Manufacturer-table']])
            ->assertOn('/manufacturer/?page=1&query=x')
            ->assertSuccessful()
            ->assertSeeIn('turbo-frame#Manufacturer-table turbo-stream[action="append"][target="flash-container"] template', 'Page 99 not found.')
            ->assertElementCount('turbo-stream[target="flash-container"]', 1)
            ->assertNotSeeIn('#flash-container', 'Page 99 not found.');
    }

    public function testFullLoadShowsTheFlashOnceInTheToaster(): void
    {
        $this->browser()
            ->actingAs(UserFactory::new()->asStaff()->create())
            ->get('/manufacturer/?page=99')
            ->assertOn('/manufacturer/?page=1')
            ->assertSeeIn('#flash-container', 'Page 99 not found.')
            ->assertNotSeeElement(self::FLASH_STREAM);
    }
}
