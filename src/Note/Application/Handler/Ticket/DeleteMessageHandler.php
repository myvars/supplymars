<?php

namespace App\Note\Application\Handler\Ticket;

use App\Note\Application\Command\Ticket\DeleteMessage;
use App\Note\Domain\Model\Message\Message;
use App\Note\Domain\Model\Ticket\Ticket;
use App\Note\Domain\Repository\MessageRepository;
use App\Note\Domain\Repository\TicketRepository;
use App\Shared\Application\FlusherInterface;
use App\Shared\Application\Result;

final readonly class DeleteMessageHandler
{
    public function __construct(
        private TicketRepository $tickets,
        private MessageRepository $messages,
        private FlusherInterface $flusher,
    ) {
    }

    public function __invoke(DeleteMessage $command): Result
    {
        $ticket = $this->tickets->getByPublicId($command->ticketId);
        if (!$ticket instanceof Ticket) {
            return Result::fail('Ticket not found.');
        }

        $message = $this->findMessage($ticket, $command);
        if (!$message instanceof Message) {
            return Result::fail('Message not found.');
        }

        if ($message->isSystem()) {
            return Result::fail('System messages cannot be deleted.');
        }

        $firstMessage = $ticket->getMessages()->first();
        if ($firstMessage && $message->getPublicId()->equals($firstMessage->getPublicId())) {
            return Result::fail('The original message cannot be deleted.');
        }

        $ticket->removeMessage($message);
        $this->messages->remove($message);
        $this->flusher->flush();

        return Result::ok('Message deleted');
    }

    private function findMessage(Ticket $ticket, DeleteMessage $command): ?Message
    {
        foreach ($ticket->getMessages() as $message) {
            if ($message->getPublicId()->equals($command->messageId)) {
                return $message;
            }
        }

        return null;
    }
}
