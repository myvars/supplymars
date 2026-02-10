<?php

namespace App\Catalog\UI\Http\Controller;

use App\Catalog\Application\Command\Manufacturer\DeleteManufacturer;
use App\Catalog\Application\Handler\Manufacturer\CreateManufacturerHandler;
use App\Catalog\Application\Handler\Manufacturer\DeleteManufacturerHandler;
use App\Catalog\Application\Handler\Manufacturer\UpdateManufacturerHandler;
use App\Catalog\Application\Search\ManufacturerSearchCriteria;
use App\Catalog\Domain\Model\Manufacturer\Manufacturer;
use App\Catalog\Domain\Repository\ManufacturerRepository;
use App\Catalog\UI\Http\Form\Mapper\CreateManufacturerMapper;
use App\Catalog\UI\Http\Form\Mapper\UpdateManufacturerMapper;
use App\Catalog\UI\Http\Form\Model\ManufacturerForm;
use App\Catalog\UI\Http\Form\Type\ManufacturerType;
use App\Shared\UI\Http\FormFlow\DeleteFlow;
use App\Shared\UI\Http\FormFlow\FormFlow;
use App\Shared\UI\Http\FormFlow\InlineEdit\InlineEditContext;
use App\Shared\UI\Http\FormFlow\InlineEdit\InlineEditFlow;
use App\Shared\UI\Http\FormFlow\SearchFlow;
use App\Shared\UI\Http\FormFlow\View\FlowContext;
use App\Shared\UI\Http\FormFlow\View\FlowModel;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapQueryString;
use Symfony\Component\HttpKernel\Attribute\ValueResolver;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_ADMIN')]
class ManufacturerController extends AbstractController
{
    private function model(): FlowModel
    {
        return FlowModel::create('catalog', 'manufacturer');
    }

    #[Route(path: '/manufacturer/', name: 'app_catalog_manufacturer_index', methods: ['GET'])]
    public function index(
        Request $request,
        SearchFlow $flow,
        ManufacturerRepository $repository,
        #[MapQueryString] ManufacturerSearchCriteria $criteria = new ManufacturerSearchCriteria(),
    ): Response {
        return $flow->search(
            request: $request,
            repository: $repository,
            criteria: $criteria,
            context: FlowContext::forSearch($this->model()),
        );
    }

    #[Route(path: '/manufacturer/new', name: 'app_catalog_manufacturer_new', methods: ['GET', 'POST'])]
    public function new(
        Request $request,
        CreateManufacturerMapper $mapper,
        CreateManufacturerHandler $handler,
        FormFlow $flow,
    ): Response {
        return $flow->form(
            request: $request,
            formType: ManufacturerType::class,
            data: new ManufacturerForm(),
            mapper: $mapper,
            handler: $handler,
            context: FlowContext::forCreate($this->model()),
        );
    }

    #[Route(path: '/manufacturer/{id}/edit', name: 'app_catalog_manufacturer_edit', methods: ['GET', 'POST'])]
    public function edit(
        Request $request,
        #[ValueResolver('public_id')] Manufacturer $manufacturer,
        UpdateManufacturerMapper $mapper,
        UpdateManufacturerHandler $handler,
        FormFlow $flow,
    ): Response {
        return $flow->form(
            request: $request,
            formType: ManufacturerType::class,
            data: ManufacturerForm::fromEntity($manufacturer),
            mapper: $mapper,
            handler: $handler,
            context: FlowContext::forUpdate($this->model())
                ->allowDelete(true)
                ->successRoute('app_catalog_manufacturer_show', ['id' => $manufacturer->getPublicId()->value()]),
        );
    }

    #[Route(path: '/manufacturer/{id}/delete/confirm', name: 'app_catalog_manufacturer_delete_confirm', methods: ['GET'])]
    public function deleteConfirm(
        #[ValueResolver('public_id')] Manufacturer $manufacturer,
        DeleteFlow $flow,
    ): Response {
        return $flow->deleteConfirm(
            entity: $manufacturer,
            context: FlowContext::forDelete($this->model()),
        );
    }

    #[Route(path: '/manufacturer/{id}/delete', name: 'app_catalog_manufacturer_delete', methods: ['POST'])]
    public function delete(
        Request $request,
        #[ValueResolver('public_id')] Manufacturer $manufacturer,
        DeleteManufacturerHandler $handler,
        DeleteFlow $flow,
    ): Response {
        return $flow->delete(
            request: $request,
            command: new DeleteManufacturer($manufacturer->getPublicId()),
            handler: $handler,
            context: FlowContext::forDelete($this->model()),
        );
    }

    #[Route(path: '/manufacturer/{id}', name: 'app_catalog_manufacturer_show', methods: ['GET'])]
    public function show(#[ValueResolver('public_id')] Manufacturer $manufacturer): Response
    {
        return $this->render('/catalog/manufacturer/show.html.twig', ['result' => $manufacturer]);
    }

    #[Route(path: '/manufacturer/{id}/inline/name', name: 'app_catalog_manufacturer_inline_name', methods: ['GET', 'POST'])]
    public function inlineName(
        Request $request,
        #[ValueResolver('public_id')] Manufacturer $manufacturer,
        InlineEditFlow $flow,
    ): Response {
        return $flow->handleField(
            request: $request,
            value: $manufacturer->getName(),
            onSave: fn ($value) => $manufacturer->update((string) $value, $manufacturer->isActive()),
            context: InlineEditContext::create(
                frameId: 'inline-edit-manufacturer-' . $manufacturer->getPublicId() . '-name',
                displayTemplate: 'catalog/manufacturer/_inline_name.html.twig',
                entity: $manufacturer,
            ),
        );
    }
}
