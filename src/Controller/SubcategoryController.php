<?php

namespace App\Controller;

use App\DTO\SearchDto\SubcategorySearchDto;
use App\Entity\Subcategory;
use App\Form\SearchForm\SubcategorySearchFilterType;
use App\Form\SubcategoryType;
use App\Repository\SubcategoryRepository;
use App\Service\Crud\CrudCreator;
use App\Service\Crud\CrudDeleter;
use App\Service\Crud\CrudReader;
use App\Service\Crud\CrudSearcher;
use App\Service\Crud\CrudUpdater;
use App\Service\Search\SearchFilter;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapQueryString;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/subcategory')]
#[IsGranted('ROLE_ADMIN')]
class SubcategoryController extends AbstractController
{
    public const SECTION = 'Subcategory';

    #[Route('/', name: 'app_subcategory_index', methods: ['GET'])]
    public function index(
        Request $request,
        CrudSearcher $handler,
        SubcategoryRepository $repository,
        #[MapQueryString] SubcategorySearchDto $dto = new SubcategorySearchDto()
    ): Response {
        return $handler->search(self::SECTION, $dto, $repository, $request->query->all());
    }

    #[Route('/search/filter', name: 'app_subcategory_search_filter', methods: ['GET', 'POST'])]
    public function searchFilter(
        Request $request,
        CrudUpdater $handler,
        SearchFilter $action,
        #[MapQueryString] SubcategorySearchDto $dto = new SubcategorySearchDto()
    ): Response {
        $dto->setQueryString($request->getQueryString());
        $form = $this->createForm(SubcategorySearchFilterType::class, $dto, [
            'action' => $this->generateUrl('app_subcategory_search_filter', $request->query->all()),
        ]);

        return $handler->build(
            $handler->setDefaults()
                ->setTemplate($dto::TEMPLATE)
                ->setForm($form)
                ->setEntity($dto)
                ->setCrudAction($action)
                ->setSuccessLink(
                    $this->generateUrl('app_subcategory_index')
                )
        );
    }

    #[Route('/new', name: 'app_subcategory_new', methods: ['GET', 'POST'])]
    public function new(CrudCreator $handler): Response
    {
        return $handler->create(self::SECTION, new Subcategory(), SubcategoryType::class);
    }

    #[Route('/{id}', name: 'app_subcategory_show', methods: ['GET'])]
    public function show(
        Subcategory $subcategory,
        CrudReader $handler
    ): Response {
        return $handler->read(self::SECTION, $subcategory);
    }

    #[Route('/{id}/edit', name: 'app_subcategory_edit', methods: ['GET', 'POST'])]
    public function edit(
        Subcategory $subcategory,
        CrudUpdater $handler
    ): Response{
        return $handler->update(self::SECTION, $subcategory, SubcategoryType::class);
    }

    #[Route('/{id}/delete/confirm', name: 'app_subcategory_delete_confirm', methods: ['GET'])]
    public function deleteConfirm(
        Subcategory $subcategory,
        CrudDeleter $handler
    ): Response {
        return $handler->deleteConfirm(self::SECTION, $subcategory);
    }

    #[Route('/{id}/delete', name: 'app_subcategory_delete', methods: ['POST'])]
    public function delete(
        Subcategory $subcategory,
        CrudDeleter $handler
    ): Response {
        return $handler->delete(self::SECTION, $subcategory);
    }
}