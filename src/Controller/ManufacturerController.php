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

#[IsGranted('ROLE_ADMIN')]
class ManufacturerController extends AbstractController
{
    public const string SECTION = 'Manufacturer';

    #[Route(path: '/manufacturer/', name: 'app_manufacturer_index', methods: ['GET'])]
    public function index(
        Request $request,
        CrudSearcher $handler,
        ManufacturerRepository $repository,
        #[MapQueryString] ManufacturerSearchDto $dto = new ManufacturerSearchDto(),
    ): Response {
        return $handler->search(self::SECTION, $dto, $repository, $request->query->all());
    }

    #[Route(path: '/manufacturer/new', name: 'app_manufacturer_new', methods: ['GET', 'POST'])]
    public function new(CrudCreator $handler): Response
    {
        return $handler->create(self::SECTION, new Manufacturer(), ManufacturerType::class);
    }

    #[Route(path: '/manufacturer/{id}', name: 'app_manufacturer_show', methods: ['GET'])]
    public function show(
        ?Manufacturer $manufacturer,
        CrudReader $handler,
    ): Response {
        return $handler->read(self::SECTION, $manufacturer);
    }

    #[Route(path: '/manufacturer/{id}/edit', name: 'app_manufacturer_edit', methods: ['GET', 'POST'])]
    public function edit(
        Manufacturer $manufacturer,
        CrudUpdater $handler,
    ): Response {
        return $handler->update(self::SECTION, $manufacturer, ManufacturerType::class);
    }

    #[Route(path: '/manufacturer/{id}/delete/confirm', name: 'app_manufacturer_delete_confirm', methods: ['GET'])]
    public function deleteConfirm(
        ?Manufacturer $manufacturer,
        CrudDeleter $handler,
    ): Response {
        return $handler->deleteConfirm(self::SECTION, $manufacturer);
    }

    #[Route(path: '/manufacturer/{id}/delete', name: 'app_manufacturer_delete', methods: ['POST'])]
    public function delete(
        Manufacturer $manufacturer,
        CrudDeleter $handler,
    ): Response {
        return $handler->delete(self::SECTION, $manufacturer);
    }
}
