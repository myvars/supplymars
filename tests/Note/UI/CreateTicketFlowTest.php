<?php

namespace App\Tests\Note\UI;

use App\Tests\Shared\Factory\PoolFactory;
use App\Tests\Shared\Factory\UserFactory;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Zenstruck\Browser\Test\HasBrowser;
use Zenstruck\Foundry\Test\Factories;

final class CreateTicketFlowTest extends WebTestCase
{
    use HasBrowser;
    use Factories;

    public function testSuccessfulCreationViaForm(): void
    {
        $pool = PoolFactory::createOne(['isActive' => true]);
        $customer = UserFactory::createOne();

        $this->browser()
            ->actingAs(UserFactory::new()->asStaff()->create())
            ->get('/note/ticket/new')
            ->fillField('ticket[pool]', (string) $pool->getId())
            ->fillField('ticket[customerId]', (string) $customer->getId())
            ->fillField('ticket[subject]', 'Damaged product received')
            ->fillField('ticket[body]', 'The product arrived with visible damage on the packaging.')
            ->click('Create Ticket')
            ->assertSuccessful()
            ->assertSee('Damaged product received')
            ->assertSee('The product arrived with visible damage on the packaging.');
    }

    public function testValidationErrorsOnEmptySubmission(): void
    {
        $this->browser()
            ->actingAs(UserFactory::new()->asStaff()->create())
            ->get('/note/ticket/new')
            ->click('Create Ticket')
            ->assertOn('/note/ticket/new')
            ->assertSee('Please choose a pool')
            ->assertSee('Please choose a customer')
            ->assertSee('Please enter a subject')
            ->assertSee('Please enter a message');
    }

    public function testTicketShowDisplaysThread(): void
    {
        $pool = PoolFactory::createOne(['isActive' => true]);
        $customer = UserFactory::createOne(['fullName' => 'Jane Mars']);

        $this->browser()
            ->actingAs(UserFactory::new()->asStaff()->create())
            ->get('/note/ticket/new')
            ->fillField('ticket[pool]', (string) $pool->getId())
            ->fillField('ticket[customerId]', (string) $customer->getId())
            ->fillField('ticket[subject]', 'Where is my order?')
            ->fillField('ticket[body]', "I placed an order last week and haven't received it.")
            ->click('Create Ticket')
            ->assertSuccessful()
            ->assertSee('Where is my order?')
            ->assertSee('Jane Mars')
            ->assertSee('I placed an order last week')
            ->assertSee($pool->getName());
    }
}
