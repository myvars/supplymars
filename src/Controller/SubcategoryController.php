<?php

namespace App\Controller;

use App\Entity\Subcategory;
use App\Form\SubcategoryType;
use App\Repository\SubcategoryRepository;
use App\Service\CrudHelper;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/subcategory')]
class SubcategoryController extends AbstractController
{
    public const SECTION = 'Subcategory';

    public function __construct(private readonly CrudHelper $crudHelper)
    {
        $this->crudHelper->setSection(self::SECTION);
    }

    #[Route('/', name: 'app_subcategory_index', methods: ['GET'])]
    public function index(SubcategoryRepository $subcategoryRepository): Response
    {
        $sortOptions = ['id', 'name', 'category.name', 'defaultMarkup', 'isActive'];

        return $this->crudHelper->renderIndex(
            $subcategoryRepository,
            $sortOptions
        );
    }

    #[Route('/new', name: 'app_subcategory_new', methods: ['GET', 'POST'])]
    public function new(): Response
    {
        return $this->crudHelper->renderCreate(
            new Subcategory(),
            SubcategoryType::class
        );
    }

    #[Route('/{id}', name: 'app_subcategory_show', methods: ['GET'])]
    public function show(?Subcategory $subcategory): Response
    {
        return $this->crudHelper->renderShow($subcategory);
    }

    #[Route('/{id}/edit', name: 'app_subcategory_edit', methods: ['GET', 'POST'])]
    public function edit(?Subcategory $subcategory): Response
    {
        return $this->crudHelper->renderUpdate(
            $subcategory,
            SubcategoryType::class
        );
    }

    #[Route('/{id}/delete/confirm', name: 'app_subcategory_delete_confirm', methods: ['GET'])]
    public function deleteConfirm(?Subcategory $subcategory): Response
    {
        return $this->crudHelper->renderDeleteConfirm($subcategory);
    }

    #[Route('/{id}/delete', name: 'app_subcategory_delete', methods: ['POST'])]
    public function delete(?Subcategory $subcategory): Response
    {
        return $this->crudHelper->renderDelete($subcategory);
    }
}