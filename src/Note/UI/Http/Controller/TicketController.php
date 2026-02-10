<?php

namespace App\Note\UI\Http\Controller;

use App\Note\Application\Command\Ticket\CloseTicket;
use App\Note\Application\Command\Ticket\DeleteMessage;
use App\Note\Application\Command\Ticket\ReopenTicket;
use App\Note\Application\Command\Ticket\ToggleSnoozeTicket;
use App\Note\Application\Handler\Ticket\CloseTicketHandler;
use App\Note\Application\Handler\Ticket\CreateTicketHandler;
use App\Note\Application\Handler\Ticket\DeleteMessageHandler;
use App\Note\Application\Handler\Ticket\ReassignTicketHandler;
use App\Note\Application\Handler\Ticket\ReopenTicketHandler;
use App\Note\Application\Handler\Ticket\ReplyToTicketHandler;
use App\Note\Application\Handler\Ticket\TicketFilterHandler;
use App\Note\Application\Handler\Ticket\ToggleSnoozeTicketHandler;
use App\Note\Application\Search\TicketSearchCriteria;
use App\Note\Domain\Model\Message\Message;
use App\Note\Domain\Model\Message\MessagePublicId;
use App\Note\Domain\Model\Ticket\Ticket;
use App\Note\Domain\Model\Ticket\TicketPublicId;
use App\Note\Domain\Repository\TicketRepository;
use App\Note\UI\Http\Form\Mapper\CreateTicketMapper;
use App\Note\UI\Http\Form\Mapper\ReassignTicketMapper;
use App\Note\UI\Http\Form\Mapper\ReplyToTicketMapper;
use App\Note\UI\Http\Form\Mapper\TicketFilterMapper;
use App\Note\UI\Http\Form\Model\ReassignForm;
use App\Note\UI\Http\Form\Model\ReplyForm;
use App\Note\UI\Http\Form\Model\TicketForm;
use App\Note\UI\Http\Form\Type\ReassignType;
use App\Note\UI\Http\Form\Type\ReplyType;
use App\Note\UI\Http\Form\Type\TicketFilterType;
use App\Note\UI\Http\Form\Type\TicketType;
use App\Shared\Infrastructure\Security\CurrentUserProvider;
use App\Shared\UI\Http\FormFlow\CommandFlow;
use App\Shared\UI\Http\FormFlow\DeleteFlow;
use App\Shared\UI\Http\FormFlow\FormFlow;
use App\Shared\UI\Http\FormFlow\SearchFlow;
use App\Shared\UI\Http\FormFlow\View\FlowContext;
use App\Shared\UI\Http\FormFlow\View\FlowModel;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapQueryString;
use Symfony\Component\HttpKernel\Attribute\ValueResolver;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_ADMIN')]
class TicketController extends AbstractController
{
    private function model(): FlowModel
    {
        return FlowModel::create('note', 'ticket');
    }

    #[Route(path: '/note/ticket/', name: 'app_note_ticket_index', methods: ['GET'])]
    public function index(
        Request $request,
        SearchFlow $flow,
        TicketRepository $repository,
        #[MapQueryString] TicketSearchCriteria $criteria = new TicketSearchCriteria(),
    ): Response {
        return $flow->search(
            request: $request,
            repository: $repository,
            criteria: $criteria,
            context: FlowContext::forSearch($this->model()),
        );
    }

    #[Route(path: '/note/ticket/search/filter', name: 'app_note_ticket_search_filter', methods: ['GET', 'POST'])]
    public function searchFilter(
        Request $request,
        TicketFilterMapper $mapper,
        TicketFilterHandler $handler,
        FormFlow $flow,
        #[MapQueryString] TicketSearchCriteria $criteria = new TicketSearchCriteria(),
    ): Response {
        return $flow->form(
            request: $request,
            formType: TicketFilterType::class,
            data: $criteria,
            mapper: $mapper,
            handler: $handler,
            context: FlowContext::forFilter($this->model()),
        );
    }

    #[Route(path: '/note/ticket/new', name: 'app_note_ticket_new', methods: ['GET', 'POST'])]
    public function new(
        Request $request,
        CreateTicketMapper $mapper,
        CreateTicketHandler $handler,
        FormFlow $flow,
    ): Response {
        return $flow->form(
            request: $request,
            formType: TicketType::class,
            data: new TicketForm(),
            mapper: $mapper,
            handler: $handler,
            context: FlowContext::forCreate($this->model()),
        );
    }

    #[Route(path: '/note/ticket/{id}', name: 'app_note_ticket_show', methods: ['GET'])]
    public function show(#[ValueResolver('public_id')] Ticket $ticket): Response
    {
        $replyForm = null;
        if (!$ticket->getStatus()->isClosed()) {
            $replyForm = $this->createForm(ReplyType::class, new ReplyForm(), [
                'action' => $this->generateUrl('app_note_ticket_reply', ['id' => $ticket->getPublicId()->value()]),
            ])->createView();
        }

        return $this->render('note/ticket/show.html.twig', [
            'result' => $ticket,
            'replyForm' => $replyForm,
        ]);
    }

    #[Route(path: '/note/ticket/{id}/reply', name: 'app_note_ticket_reply', methods: ['GET', 'POST'])]
    public function reply(
        Request $request,
        #[ValueResolver('public_id')] Ticket $ticket,
        ReplyToTicketHandler $handler,
        CurrentUserProvider $userProvider,
        FormFlow $flow,
    ): Response {
        return $flow->form(
            request: $request,
            formType: ReplyType::class,
            data: new ReplyForm(),
            mapper: new ReplyToTicketMapper($ticket, $userProvider->get()->getId()),
            handler: $handler,
            context: FlowContext::forCreate($this->model())
                ->template('note/ticket/reply_embedded.html.twig')
                ->successRoute('app_note_ticket_show', ['id' => $ticket->getPublicId()->value()]),
        );
    }

    #[Route(path: '/note/ticket/{id}/close', name: 'app_note_ticket_close', methods: ['GET'])]
    public function close(
        Request $request,
        #[ValueResolver('public_id')] Ticket $ticket,
        CloseTicketHandler $handler,
        CommandFlow $flow,
    ): Response {
        return $flow->process(
            request: $request,
            command: new CloseTicket($ticket->getPublicId()),
            handler: $handler,
            context: FlowContext::forSuccess('app_note_ticket_show', ['id' => $ticket->getPublicId()->value()]),
        );
    }

    #[Route(path: '/note/ticket/{id}/reopen', name: 'app_note_ticket_reopen', methods: ['GET'])]
    public function reopen(
        Request $request,
        #[ValueResolver('public_id')] Ticket $ticket,
        ReopenTicketHandler $handler,
        CommandFlow $flow,
    ): Response {
        return $flow->process(
            request: $request,
            command: new ReopenTicket($ticket->getPublicId()),
            handler: $handler,
            context: FlowContext::forSuccess('app_note_ticket_show', ['id' => $ticket->getPublicId()->value()]),
        );
    }

    #[Route(path: '/note/ticket/{id}/reassign', name: 'app_note_ticket_reassign', methods: ['GET', 'POST'])]
    public function reassign(
        Request $request,
        #[ValueResolver('public_id')] Ticket $ticket,
        ReassignTicketHandler $handler,
        FormFlow $flow,
    ): Response {
        return $flow->form(
            request: $request,
            formType: ReassignType::class,
            data: new ReassignForm(),
            mapper: new ReassignTicketMapper($ticket),
            handler: $handler,
            context: FlowContext::forCreate($this->model())
                ->template('note/ticket/reassign.html.twig')
                ->successRoute('app_note_ticket_show', ['id' => $ticket->getPublicId()->value()]),
        );
    }

    #[Route(path: '/note/ticket/{id}/snooze', name: 'app_note_ticket_toggle_snooze', methods: ['GET'])]
    public function toggleSnooze(
        Request $request,
        #[ValueResolver('public_id')] Ticket $ticket,
        ToggleSnoozeTicketHandler $handler,
        CommandFlow $flow,
    ): Response {
        return $flow->process(
            request: $request,
            command: new ToggleSnoozeTicket($ticket->getPublicId()),
            handler: $handler,
            context: FlowContext::forSuccess('app_note_ticket_show', ['id' => $ticket->getPublicId()->value()]),
        );
    }

    #[Route(path: '/note/ticket/{ticketId}/message/{messageId}/delete', name: 'app_note_ticket_message_delete_confirm', methods: ['GET'])]
    public function deleteMessageConfirm(
        string $ticketId,
        string $messageId,
        TicketRepository $tickets,
    ): Response {
        $ticket = $tickets->getByPublicId(TicketPublicId::fromString($ticketId));
        if (!$ticket instanceof Ticket) {
            throw new NotFoundHttpException('Ticket not found.');
        }

        $message = $this->findMessageInTicket($ticket, $messageId);

        return $this->render('note/ticket/delete_message.html.twig', [
            'ticket' => $ticket,
            'message' => $message,
        ]);
    }

    #[Route(path: '/note/ticket/{ticketId}/message/{messageId}/delete', name: 'app_note_ticket_message_delete', methods: ['POST'])]
    public function deleteMessage(
        Request $request,
        string $ticketId,
        string $messageId,
        DeleteMessageHandler $handler,
        DeleteFlow $flow,
    ): Response {
        $ticketPublicId = TicketPublicId::fromString($ticketId);
        $messagePublicId = MessagePublicId::fromString($messageId);

        return $flow->delete(
            request: $request,
            command: new DeleteMessage($ticketPublicId, $messagePublicId),
            handler: $handler,
            context: FlowContext::forDelete($this->model())
                ->successRoute('app_note_ticket_show', ['id' => $ticketId]),
        );
    }

    private function findMessageInTicket(Ticket $ticket, string $messageId): Message
    {
        $publicId = MessagePublicId::fromString($messageId);

        foreach ($ticket->getMessages() as $message) {
            if ($message->getPublicId()->equals($publicId)) {
                return $message;
            }
        }

        throw new NotFoundHttpException('Message not found.');
    }
}
