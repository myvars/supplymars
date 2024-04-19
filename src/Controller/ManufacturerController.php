<?php

namespace App\Controller;

use App\Entity\Manufacturer;
use App\Form\ManufacturerType;
use App\Repository\ManufacturerRepository;
use App\Service\CrudHelper;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/manufacturer')]
class ManufacturerController extends AbstractController
{
    public const SECTION = 'Manufacturer';

    public function __construct(private readonly CrudHelper $crudHelper)
    {
        $this->crudHelper->setSection(self::SECTION);
    }

    #[Route('/', name: 'app_manufacturer_index', methods: ['GET'])]
    public function index(ManufacturerRepository $manufacturerRepository): Response
    {
        $sortOptions = ['id', 'name', 'isActive'];

        return $this->crudHelper->renderIndex(
            $manufacturerRepository,
            $sortOptions
        );
    }

    #[Route('/new', name: 'app_manufacturer_new', methods: ['GET', 'POST'])]
    public function new(): Response
    {
        return $this->crudHelper->renderCreate(
            new Manufacturer(),
            ManufacturerType::class
        );
    }

    #[Route('/{id}', name: 'app_manufacturer_show', methods: ['GET'])]
    public function show(?Manufacturer $manufacturer): Response
    {
        return $this->crudHelper->renderShow($manufacturer);
    }

    #[Route('/{id}/edit', name: 'app_manufacturer_edit', methods: ['GET', 'POST'])]
    public function edit(?Manufacturer $manufacturer): Response
    {
        return $this->crudHelper->renderUpdate(
            $manufacturer,
            ManufacturerType::class
        );
    }

    #[Route('/{id}/delete/confirm', name: 'app_manufacturer_delete_confirm', methods: ['GET'])]
    public function deleteConfirm(?Manufacturer $manufacturer): Response
    {
        return $this->crudHelper->renderDeleteConfirm($manufacturer);
    }

    #[Route('/{id}/delete', name: 'app_manufacturer_delete', methods: ['POST'])]
    public function delete(?Manufacturer $manufacturer): Response
    {
        return $this->crudHelper->renderDelete($manufacturer);
    }
}
