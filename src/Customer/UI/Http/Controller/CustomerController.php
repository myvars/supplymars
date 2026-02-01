<?php

namespace App\Customer\UI\Http\Controller;

use App\Customer\Application\Command\DeleteCustomer;
use App\Customer\Application\Handler\DeleteCustomerHandler;
use App\Customer\Application\Handler\UpdateCustomerHandler;
use App\Customer\Application\Search\CustomerSearchCriteria;
use App\Customer\Domain\Model\User\User;
use App\Customer\Domain\Repository\UserRepository;
use App\Customer\UI\Http\Form\Mapper\UpdateCustomerMapper;
use App\Customer\UI\Http\Form\Model\CustomerForm;
use App\Customer\UI\Http\Form\Type\CustomerType;
use App\Reporting\Application\Handler\Report\CustomerProfileInsightsHandler;
use App\Shared\UI\Http\FormFlow\DeleteFlow;
use App\Shared\UI\Http\FormFlow\FormFlow;
use App\Shared\UI\Http\FormFlow\SearchFlow;
use App\Shared\UI\Http\FormFlow\View\FlowContext;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapQueryString;
use Symfony\Component\HttpKernel\Attribute\ValueResolver;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_ADMIN')]
class CustomerController extends AbstractController
{
    public const string MODEL = 'customer';

    #[Route(path: '/customer/', name: 'app_customer_index', methods: ['GET'])]
    public function index(
        Request $request,
        SearchFlow $flow,
        UserRepository $repository,
        #[MapQueryString] CustomerSearchCriteria $criteria = new CustomerSearchCriteria(),
    ): Response {
        return $flow->search(
            request: $request,
            repository: $repository,
            criteria: $criteria,
            context: FlowContext::forSearch(self::MODEL),
        );
    }

    #[Route(path: '/customer/{id}/edit', name: 'app_customer_edit', methods: ['GET', 'POST'])]
    public function edit(
        Request $request,
        #[ValueResolver('public_id')] User $customer,
        UpdateCustomerMapper $mapper,
        UpdateCustomerHandler $handler,
        FormFlow $flow,
    ): Response {
        return $flow->form(
            request: $request,
            formType: CustomerType::class,
            data: CustomerForm::fromEntity($customer),
            mapper: $mapper,
            handler: $handler,
            context: FlowContext::forUpdate(self::MODEL)->allowDelete(true),
        );
    }

    #[Route(path: '/customer/{id}/delete/confirm', name: 'app_customer_delete_confirm', methods: ['GET'])]
    public function deleteConfirm(
        #[ValueResolver('public_id')] User $customer,
        DeleteFlow $flow,
    ): Response {
        return $flow->deleteConfirm(
            entity: $customer,
            context: FlowContext::forDelete(self::MODEL),
        );
    }

    #[Route(path: '/customer/{id}/delete', name: 'app_customer_delete', methods: ['POST'])]
    public function delete(
        Request $request,
        #[ValueResolver('public_id')] User $customer,
        DeleteCustomerHandler $handler,
        DeleteFlow $flow,
    ): Response {
        return $flow->delete(
            request: $request,
            command: new DeleteCustomer($customer->getPublicId()),
            handler: $handler,
            context: FlowContext::forDelete(self::MODEL),
        );
    }

    #[Route(path: '/customer/{id}', name: 'app_customer_show', methods: ['GET'])]
    public function show(
        #[ValueResolver('public_id')] User $customer,
        CustomerProfileInsightsHandler $insightsHandler,
    ): Response {
        $insights = $insightsHandler($customer);

        return $this->render('/customer/show.html.twig', [
            'result' => $customer,
            'insights' => $insights->payload,
        ]);
    }
}
