<?php

declare(strict_types=1);

namespace App\Tests\Shared\UI\Http;

use App\Tests\Shared\Factory\UserFactory;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Zenstruck\Browser\Test\HasBrowser;

/**
 * A redirect Turbo follows inside the body frame loads the next page with the Turbo-Frame header, so its flashes must
 * come as a stream inside the frame (base.html.twig). The redirect is intercepted and the page requested with the
 * header, as Turbo does.
 */
final class BodyFrameFlashFlowTest extends WebTestCase
{
    use HasBrowser;

    private const string FLASH_STREAM = 'turbo-frame#body turbo-stream[action="append"][target="flash-container"]';

    public function testOutOfRangePageShowsItsFlashOnPageOneInTheBodyFrame(): void
    {
        $this->browser()
            ->actingAs(UserFactory::new()->asStaff()->create())
            ->interceptRedirects()
            ->get('/manufacturer/?page=99')
            ->assertRedirected()
            ->assertHeaderContains('Location', '/manufacturer/?page=1')
            ->get('/manufacturer/?page=1', ['headers' => ['Turbo-Frame' => 'body']])
            ->assertSuccessful()
            ->assertSeeIn(self::FLASH_STREAM . ' template', 'Page 99 not found.')
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
