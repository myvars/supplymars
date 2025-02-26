<?php

namespace App\Controller;

use App\DTO\SearchDto\CustomerSearchDto;
use App\Entity\User;
use App\Form\CustomerType;
use App\Repository\UserRepository;
use App\Service\Crud\CrudDeleter;
use App\Service\Crud\CrudReader;
use App\Service\Crud\CrudSearcher;
use App\Service\Crud\CrudUpdater;
use App\Service\Customer\DeleteCustomer;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapQueryString;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_ADMIN')]
class CustomerController extends AbstractController
{
    public const string SECTION = 'Customer';

    #[Route(path: '/customer/', name: 'app_customer_index', methods: ['GET'])]
    public function index(
        Request $request,
        CrudSearcher $handler,
        UserRepository $repository,
        #[MapQueryString] CustomerSearchDto $dto = new CustomerSearchDto(),
    ): Response {
        return $handler->search(self::SECTION, $dto, $repository, $request->query->all());
    }

    #[Route(path: '/customer/{id}', name: 'app_customer_show', methods: ['GET'])]
    public function show(
        ?User $customer,
        CrudReader $handler,
    ): Response {
        return $handler->read(self::SECTION, $customer);
    }

    #[Route(path: '/customer/{id}/edit', name: 'app_customer_edit', methods: ['GET', 'POST'])]
    public function edit(
        User $customer,
        CrudUpdater $handler,
    ): Response {
        return $handler->update(self::SECTION, $customer, CustomerType::class);
    }

    #[Route(path: '/customer/{id}/delete/confirm', name: 'app_customer_delete_confirm', methods: ['GET'])]
    public function deleteConfirm(
        ?User $customer,
        CrudDeleter $handler,
    ): Response {
        return $handler->deleteConfirm(self::SECTION, $customer);
    }

    #[Route(path: '/customer/{id}/delete', name: 'app_customer_delete', methods: ['POST'])]
    public function delete(
        User $customer,
        CrudDeleter $handler,
        DeleteCustomer $crudAction,
    ): Response {
        return $handler->build(
            $handler->setup(self::SECTION, $customer)
                ->setCrudAction($crudAction)
        );
    }
}
