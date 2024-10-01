<?php

namespace App\Controller;

use App\Entity\User;
use App\Form\CustomerType;
use App\Repository\UserRepository;
use App\Service\Crud\CrudDeleter;
use App\Service\Crud\CrudIndexer;
use App\Service\Crud\CrudUpdater;
use App\Service\Crud\CrudReader;
use App\Service\Customer\DeleteCustomer;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/customer')]
#[IsGranted('ROLE_USER')]
class CustomerController extends AbstractController
{
    public const SECTION = 'Customer';

    public function __construct(private readonly CrudDeleter $crudDeleter)
    {
    }

    #[Route('/', name: 'app_customer_index', methods: ['GET'])]
    public function index(UserRepository $repository, CrudIndexer $crudIndexer): Response
    {
        $sortOptions = ['id', 'fullName', 'email', 'isVerified'];

        return $crudIndexer->index(self::SECTION, $repository, $sortOptions);
    }

    #[Route('/{id}', name: 'app_customer_show', methods: ['GET'])]
    public function show(?User $customer, CrudReader $crudReader): Response
    {
        return $crudReader->read(self::SECTION, $customer);
    }

    #[Route('/{id}/edit', name: 'app_customer_edit', methods: ['GET', 'POST'])]
    public function edit(?User $customer, CrudUpdater $crudUpdater): Response
    {
        return $crudUpdater->update(self::SECTION, $customer, CustomerType::class);
    }

    #[Route('/{id}/delete/confirm', name: 'app_customer_delete_confirm', methods: ['GET'])]
    public function deleteConfirm(?User $customer, CrudDeleter $crudDeleter): Response
    {
        return $crudDeleter->deleteConfirm(self::SECTION, $customer);
    }

    #[Route('/{id}/delete', name: 'app_customer_delete', methods: ['POST'])]
    public function delete(
        ?User $customer,
        CrudDeleter $crudDeleter,
        DeleteCustomer $crudAction
    ): Response {
        return $this->crudDeleter->build(
            $this->crudDeleter->createOptions(self::SECTION, $customer)->setCrudAction($crudAction)
        );
    }
}
