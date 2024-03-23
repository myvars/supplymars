<?php

namespace App\Controller;

use App\Entity\Manufacturer;
use App\Form\ManufacturerType;
use App\Repository\ManufacturerRepository;
use App\Service\CrudHelper;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapQueryParameter;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/manufacturer')]
class ManufacturerController extends AbstractController
{
    public const string SECTION = 'Manufacturer';
    public const int FORM_COLUMNS = 1;

    public function __construct(private readonly CrudHelper $crudHelper)
    {
        $this->crudHelper->setSection(self::SECTION);
        $this->crudHelper->setFormColumns(self::FORM_COLUMNS);
    }

    #[Route('/', name: 'app_manufacturer_index', methods: ['GET'])]
    public function index(
        ManufacturerRepository $manufacturerRepository,
        #[MapQueryParameter] int $page = 1,
        #[MapQueryParameter] int $limit = 10,
        #[MapQueryParameter] string $sort = 'id',
        #[MapQueryParameter] string $sortDirection = 'ASC',
        #[MapQueryParameter] ?string $query = null,
    ): Response {
        $validSorts = ['id', 'name'];
        $sort = in_array($sort, $validSorts) ? $sort : 'id';

        return $this->crudHelper->renderIndex(
            $manufacturerRepository->findBySearchQueryBuilder($query, $sort, $sortDirection),
            $page,
            $limit,
            $sort,
            $sortDirection,
            $query,
        );
    }

    #[Route('/new', name: 'app_manufacturer_new', methods: ['GET', 'POST'])]
    public function new(Request $request): Response
    {
        return $this->crudHelper->renderCreate(
            $request,
            new Manufacturer(),
            ManufacturerType::class,
        );
    }

    #[Route('/{id}', name: 'app_manufacturer_show', methods: ['GET'])]
    public function show(?Manufacturer $manufacturer): Response
    {
        return $this->crudHelper->renderShow($manufacturer);
    }

    #[Route('/{id}/edit', name: 'app_manufacturer_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, ?Manufacturer $manufacturer): Response
    {
        return $this->crudHelper->renderUpdate(
            $request,
            $manufacturer,
            ManufacturerType::class,
        );
    }

    #[Route('/{id}/delete/confirm', name: 'app_manufacturer_delete_confirm', methods: ['GET'])]
    public function deleteConfirm(?Manufacturer $manufacturer): Response
    {
        return $this->crudHelper->renderDeleteConfirm($manufacturer);
    }

    #[Route('/{id}/delete', name: 'app_manufacturer_delete', methods: ['POST'])]
    public function delete(Request $request, ?Manufacturer $manufacturer): Response
    {
        return $this->crudHelper->renderDelete(
            $request,
            $manufacturer,
        );
    }
}
