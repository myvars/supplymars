<?php

namespace App\Tests\Note\UI;

use App\Tests\Shared\Factory\PoolFactory;
use App\Tests\Shared\Factory\TicketFactory;
use App\Tests\Shared\Factory\UserFactory;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Zenstruck\Browser\Test\HasBrowser;
use Zenstruck\Foundry\Test\Factories;

final class TicketAuthorizationTest extends WebTestCase
{
    use HasBrowser;
    use Factories;

    public function testUnauthenticatedUserIsRedirectedToLogin(): void
    {
        $this->browser()
            ->interceptRedirects()
            ->get('/note/ticket/')
            ->assertRedirectedTo('/login');
    }

    public function testNonStaffUserIsDeniedAccess(): void
    {
        $user = UserFactory::createOne();

        $this->browser()
            ->actingAs($user)
            ->get('/note/ticket/')
            ->assertStatus(403);
    }

    public function testUnauthenticatedUserRedirectedFromTicketShow(): void
    {
        $pool = PoolFactory::createOne();
        $ticket = TicketFactory::createOne(['pool' => $pool]);

        $this->browser()
            ->interceptRedirects()
            ->get('/note/ticket/' . $ticket->getPublicId()->value())
            ->assertRedirectedTo('/login');
    }

    public function testNonStaffUserDeniedFromPoolIndex(): void
    {
        $user = UserFactory::createOne();

        $this->browser()
            ->actingAs($user)
            ->get('/note/pool/')
            ->assertStatus(403);
    }
}
