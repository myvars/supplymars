<?php

namespace App\Note\UI\Http\Controller;

use App\Note\Application\Command\Pool\DeletePool;
use App\Note\Application\Command\Pool\TogglePoolSubscription;
use App\Note\Application\Handler\Pool\CreatePoolHandler;
use App\Note\Application\Handler\Pool\DeletePoolHandler;
use App\Note\Application\Handler\Pool\TogglePoolSubscriptionHandler;
use App\Note\Application\Handler\Pool\UpdatePoolHandler;
use App\Note\Application\Search\PoolSearchCriteria;
use App\Note\Domain\Model\Pool\Pool;
use App\Note\Domain\Repository\PoolRepository;
use App\Note\UI\Http\Form\Mapper\CreatePoolMapper;
use App\Note\UI\Http\Form\Mapper\UpdatePoolMapper;
use App\Note\UI\Http\Form\Model\PoolForm;
use App\Note\UI\Http\Form\Type\PoolType;
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
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_ADMIN')]
class PoolController extends AbstractController
{
    private function model(): FlowModel
    {
        return FlowModel::create('note', 'pool');
    }

    #[Route(path: '/note/pool/', name: 'app_note_pool_index', methods: ['GET'])]
    public function index(
        Request $request,
        SearchFlow $flow,
        PoolRepository $repository,
        #[MapQueryString] PoolSearchCriteria $criteria = new PoolSearchCriteria(),
    ): Response {
        return $flow->search(
            request: $request,
            repository: $repository,
            criteria: $criteria,
            context: FlowContext::forSearch($this->model()),
        );
    }

    #[Route(path: '/note/pool/new', name: 'app_note_pool_new', methods: ['GET', 'POST'])]
    public function new(
        Request $request,
        CreatePoolMapper $mapper,
        CreatePoolHandler $handler,
        FormFlow $flow,
    ): Response {
        return $flow->form(
            request: $request,
            formType: PoolType::class,
            data: new PoolForm(),
            mapper: $mapper,
            handler: $handler,
            context: FlowContext::forCreate($this->model()),
        );
    }

    #[Route(path: '/note/pool/{id}/edit', name: 'app_note_pool_edit', methods: ['GET', 'POST'])]
    public function edit(
        Request $request,
        #[ValueResolver('public_id')] Pool $pool,
        UpdatePoolMapper $mapper,
        UpdatePoolHandler $handler,
        FormFlow $flow,
    ): Response {
        return $flow->form(
            request: $request,
            formType: PoolType::class,
            data: PoolForm::fromEntity($pool),
            mapper: $mapper,
            handler: $handler,
            context: FlowContext::forUpdate($this->model())
                ->allowDelete(true)
                ->successRoute('app_note_pool_show', ['id' => $pool->getPublicId()->value()]),
        );
    }

    #[Route(path: '/note/pool/{id}/delete/confirm', name: 'app_note_pool_delete_confirm', methods: ['GET'])]
    public function deleteConfirm(
        #[ValueResolver('public_id')] Pool $pool,
        DeleteFlow $flow,
    ): Response {
        return $flow->deleteConfirm(
            entity: $pool,
            context: FlowContext::forDelete($this->model()),
        );
    }

    #[Route(path: '/note/pool/{id}/delete', name: 'app_note_pool_delete', methods: ['POST'])]
    public function delete(
        Request $request,
        #[ValueResolver('public_id')] Pool $pool,
        DeletePoolHandler $handler,
        DeleteFlow $flow,
    ): Response {
        return $flow->delete(
            request: $request,
            command: new DeletePool($pool->getPublicId()),
            handler: $handler,
            context: FlowContext::forDelete($this->model()),
        );
    }

    #[Route(path: '/note/pool/{id}', name: 'app_note_pool_show', methods: ['GET'])]
    public function show(
        #[ValueResolver('public_id')] Pool $pool,
        CurrentUserProvider $userProvider,
    ): Response {
        $isSubscribed = $pool->isSubscribedBy($userProvider->get());

        return $this->render('note/pool/show.html.twig', [
            'result' => $pool,
            'is_subscribed' => $isSubscribed,
        ]);
    }

    #[Route(path: '/note/pool/{id}/subscribe', name: 'app_note_pool_toggle_subscription', methods: ['GET'])]
    public function toggleSubscription(
        Request $request,
        #[ValueResolver('public_id')] Pool $pool,
        TogglePoolSubscriptionHandler $handler,
        CommandFlow $flow,
    ): Response {
        return $flow->process(
            request: $request,
            command: new TogglePoolSubscription($pool->getPublicId()),
            handler: $handler,
            context: FlowContext::forSuccess('app_note_pool_show', ['id' => $pool->getPublicId()->value()]),
        );
    }
}
