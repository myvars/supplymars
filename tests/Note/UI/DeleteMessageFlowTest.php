<?php

namespace App\Tests\Note\UI;

use App\Note\Domain\Model\Message\AuthorType;
use App\Tests\Shared\Factory\MessageFactory;
use App\Tests\Shared\Factory\PoolFactory;
use App\Tests\Shared\Factory\TicketFactory;
use App\Tests\Shared\Factory\UserFactory;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Zenstruck\Browser\Test\HasBrowser;
use Zenstruck\Foundry\Test\Factories;

final class DeleteMessageFlowTest extends WebTestCase
{
    use HasBrowser;
    use Factories;

    public function testStaffCanDeleteReply(): void
    {
        $pool = PoolFactory::createOne(['isActive' => true]);
        $ticket = TicketFactory::createOne(['pool' => $pool]);
        $reply = MessageFactory::createOne([
            'ticket' => $ticket,
            'authorType' => AuthorType::STAFF,
            'body' => 'This reply should be deleted',
        ]);

        $ticketId = $ticket->getPublicId()->value();
        $messageId = $reply->getPublicId()->value();

        $this->browser()
            ->actingAs(UserFactory::new()->asStaff()->create())
            ->get('/note/ticket/' . $ticketId . '/message/' . $messageId . '/delete')
            ->assertSuccessful()
            ->assertSee('Delete Message?')
            ->assertSee('This reply should be deleted')
            ->click('Delete')
            ->assertOn('/note/ticket/' . $ticketId)
            ->assertSee('Message deleted')
            ->assertNotSee('This reply should be deleted');
    }

    public function testOriginalMessageCannotBeDeleted(): void
    {
        $pool = PoolFactory::createOne(['isActive' => true]);
        $ticket = TicketFactory::createOne(['pool' => $pool]);

        $ticketId = $ticket->getPublicId()->value();
        $originalMessage = $ticket->getMessages()->first();
        self::assertNotFalse($originalMessage);
        $messageId = $originalMessage->getPublicId()->value();

        $this->browser()
            ->actingAs(UserFactory::new()->asStaff()->create())
            ->get('/note/ticket/' . $ticketId . '/message/' . $messageId . '/delete')
            ->assertSuccessful()
            ->click('Delete')
            ->assertSee('The original message cannot be deleted');
    }

    public function testSystemMessageCannotBeDeleted(): void
    {
        $pool = PoolFactory::createOne(['isActive' => true]);
        $ticket = TicketFactory::createOne(['pool' => $pool]);
        $systemMessage = MessageFactory::createOne([
            'ticket' => $ticket,
            'authorType' => AuthorType::SYSTEM,
            'author' => null,
            'body' => 'Ticket closed by Admin',
        ]);

        $ticketId = $ticket->getPublicId()->value();
        $messageId = $systemMessage->getPublicId()->value();

        $this->browser()
            ->actingAs(UserFactory::new()->asStaff()->create())
            ->get('/note/ticket/' . $ticketId . '/message/' . $messageId . '/delete')
            ->assertSuccessful()
            ->click('Delete')
            ->assertSee('System messages cannot be deleted');
    }

    public function testDeleteUpdatesMessageCountAndLastMessageAt(): void
    {
        $pool = PoolFactory::createOne(['isActive' => true]);
        $ticket = TicketFactory::createOne(['pool' => $pool]);
        $reply = MessageFactory::createOne([
            'ticket' => $ticket,
            'authorType' => AuthorType::STAFF,
            'body' => 'Reply to be deleted',
        ]);

        $before = TicketFactory::repository()->find($ticket->getId());
        $countBefore = $before->getMessageCount();

        $ticketId = $ticket->getPublicId()->value();
        $messageId = $reply->getPublicId()->value();

        $this->browser()
            ->actingAs(UserFactory::new()->asStaff()->create())
            ->get('/note/ticket/' . $ticketId . '/message/' . $messageId . '/delete')
            ->click('Delete')
            ->assertSee('Message deleted');

        $after = TicketFactory::repository()->find($ticket->getId());
        self::assertSame($countBefore - 1, $after->getMessageCount());
        self::assertNotNull($after->getLastMessageAt());
    }
}
