<?php

namespace App\Controller;

use App\DTO\SearchDto\SubcategorySearchDto;
use App\Entity\Subcategory;
use App\Form\SubcategoryType;
use App\Repository\SubcategoryRepository;
use App\Service\Crud\CrudCreator;
use App\Service\Crud\CrudDeleter;
use App\Service\Crud\CrudReader;
use App\Service\Crud\CrudSearcher;
use App\Service\Crud\CrudUpdater;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapQueryString;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/subcategory')]
#[IsGranted('ROLE_USER')]
class SubcategoryController extends AbstractController
{
    public const SECTION = 'Subcategory';

    #[Route('/', name: 'app_subcategory_index', methods: ['GET'])]
    public function index(
        Request $request,
        CrudSearcher $crudSearcher,
        SubcategoryRepository $repository,
        #[MapQueryString] SubcategorySearchDto $dto = new SubcategorySearchDto()
    ): Response {
        return $crudSearcher->search(self::SECTION, $dto, $repository, $request->query->all());
    }

    #[Route('/new', name: 'app_subcategory_new', methods: ['GET', 'POST'])]
    public function new(CrudCreator $crudCreator): Response
    {
        return $crudCreator->create(self::SECTION, new Subcategory(), SubcategoryType::class);
    }

    #[Route('/{id}', name: 'app_subcategory_show', methods: ['GET'])]
    public function show(?Subcategory $subcategory, CrudReader $crudReader): Response
    {
        return $crudReader->read(self::SECTION, $subcategory);
    }

    #[Route('/{id}/edit', name: 'app_subcategory_edit', methods: ['GET', 'POST'])]
    public function edit(?Subcategory $subcategory, CrudUpdater $crudUpdater): Response
    {
        return $crudUpdater->update(self::SECTION, $subcategory, SubcategoryType::class);
    }

    #[Route('/{id}/delete/confirm', name: 'app_subcategory_delete_confirm', methods: ['GET'])]
    public function deleteConfirm(?Subcategory $subcategory, CrudDeleter $crudDeleter): Response
    {
        return $crudDeleter->deleteConfirm(self::SECTION, $subcategory);
    }

    #[Route('/{id}/delete', name: 'app_subcategory_delete', methods: ['POST'])]
    public function delete(?Subcategory $subcategory, CrudDeleter $crudDeleter): Response
    {
        return $crudDeleter->delete(self::SECTION, $subcategory);
    }
}