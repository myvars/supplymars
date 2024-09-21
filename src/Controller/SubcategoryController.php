<?php

namespace App\Controller;

use App\Entity\Subcategory;
use App\Form\SubcategoryType;
use App\Repository\SubcategoryRepository;
use App\Service\Crud\CrudCreator;
use App\Service\Crud\CrudDeleter;
use App\Service\Crud\CrudIndexer;
use App\Service\Crud\CrudUpdater;
use App\Service\Crud\CrudReader;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/subcategory')]
#[IsGranted('ROLE_USER')]
class SubcategoryController extends AbstractController
{
    public const SECTION = 'Subcategory';

    #[Route('/', name: 'app_subcategory_index', methods: ['GET'])]
    public function index(SubcategoryRepository $repository, CrudIndexer $crudIndexer): Response
    {
        $sortOptions = ['id', 'name', 'category.name', 'defaultMarkup', 'isActive'];

        return $crudIndexer->index(self::SECTION, $repository, $sortOptions);
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