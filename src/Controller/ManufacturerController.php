<?php

namespace App\Controller;

use App\DTO\SearchDto\ManufacturerSearchDto;
use App\Entity\Manufacturer;
use App\Form\ManufacturerType;
use App\Repository\ManufacturerRepository;
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

#[Route('/manufacturer')]
#[IsGranted('ROLE_USER')]
class ManufacturerController extends AbstractController
{
    public const SECTION = 'Manufacturer';

    #[Route('/', name: 'app_manufacturer_index', methods: ['GET'])]
    public function index(
        Request $request,
        CrudSearcher $crudSearcher,
        ManufacturerRepository $repository,
        #[MapQueryString] ManufacturerSearchDto $dto = new ManufacturerSearchDto()
    ): Response {
        return $crudSearcher->search(self::SECTION, $dto, $repository, $request->query->all());
    }

    #[Route('/new', name: 'app_manufacturer_new', methods: ['GET', 'POST'])]
    public function new(CrudCreator $crudCreator): Response
    {
        return $crudCreator->create(self::SECTION, new Manufacturer(), ManufacturerType::class);
    }

    #[Route('/{id}', name: 'app_manufacturer_show', methods: ['GET'])]
    public function show(?Manufacturer $manufacturer, CrudReader $crudReader): Response
    {
        return $crudReader->read(self::SECTION, $manufacturer);
    }

    #[Route('/{id}/edit', name: 'app_manufacturer_edit', methods: ['GET', 'POST'])]
    public function edit(?Manufacturer $manufacturer, CrudUpdater $crudUpdater): Response
    {
        return $crudUpdater->update(self::SECTION, $manufacturer, ManufacturerType::class);
    }

    #[Route('/{id}/delete/confirm', name: 'app_manufacturer_delete_confirm', methods: ['GET'])]
    public function deleteConfirm(?Manufacturer $manufacturer, CrudDeleter $crudDeleter): Response
    {
        return $crudDeleter->deleteConfirm(self::SECTION, $manufacturer);
    }

    #[Route('/{id}/delete', name: 'app_manufacturer_delete', methods: ['POST'])]
    public function delete(?Manufacturer $manufacturer, CrudDeleter $crudDeleter): Response
    {
        return $crudDeleter->delete(self::SECTION, $manufacturer);
    }
}