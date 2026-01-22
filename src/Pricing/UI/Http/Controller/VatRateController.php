<?php

namespace App\Pricing\UI\Http\Controller;

use App\Pricing\Application\Command\VatRate\DeleteVatRate;
use App\Pricing\Application\Handler\VatRate\CreateVatRateHandler;
use App\Pricing\Application\Handler\VatRate\DeleteVatRateHandler;
use App\Pricing\Application\Handler\VatRate\UpdateVatRateHandler;
use App\Pricing\Application\Search\VatRateSearchCriteria;
use App\Pricing\Domain\Model\VatRate\VatRate;
use App\Pricing\Domain\Repository\VatRateRepository;
use App\Pricing\UI\Http\Form\Mapper\CreateVatRateMapper;
use App\Pricing\UI\Http\Form\Mapper\UpdateVatRateMapper;
use App\Pricing\UI\Http\Form\Model\VatRateForm;
use App\Pricing\UI\Http\Form\Type\VatRateType;
use App\Shared\UI\Http\FormFlow\DeleteFlow;
use App\Shared\UI\Http\FormFlow\FormFlow;
use App\Shared\UI\Http\FormFlow\SearchFlow;
use App\Shared\UI\Http\FormFlow\View\FlowContext;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapQueryString;
use Symfony\Component\HttpKernel\Attribute\ValueResolver;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_ADMIN')]
class VatRateController extends AbstractController
{
    public const string MODEL = 'pricing/VAT Rate';

    #[Route(path: '/vat-rate/', name: 'app_pricing_vat_rate_index', methods: ['GET'])]
    public function index(
        Request $request,
        SearchFlow $flow,
        VatRateRepository $repository,
        #[MapQueryString] VatRateSearchCriteria $criteria = new VatRateSearchCriteria(),
    ): Response {
        return $flow->search(
            request: $request,
            repository: $repository,
            criteria: $criteria,
            context: FlowContext::forSearch(self::MODEL),
        );
    }

    #[Route(path: '/vat-rate/new', name: 'app_pricing_vat_rate_new', methods: ['GET', 'POST'])]
    public function new(
        Request $request,
        CreateVatRateMapper $mapper,
        CreateVatRateHandler $handler,
        FormFlow $flow,
    ): Response {
        return $flow->form(
            request: $request,
            formType: VatRateType::class,
            data: new VatRateForm(),
            mapper: $mapper,
            handler: $handler,
            context: FlowContext::forCreate(self::MODEL),
        );
    }

    #[Route(path: '/vat-rate/{id}/edit', name: 'app_pricing_vat_rate_edit', methods: ['GET', 'POST'])]
    public function edit(
        Request $request,
        #[ValueResolver('public_id')] VatRate $vatRate,
        UpdateVatRateMapper $mapper,
        UpdateVatRateHandler $handler,
        FormFlow $flow,
    ): Response {
        return $flow->form(
            request: $request,
            formType: VatRateType::class,
            data: VatRateForm::fromEntity($vatRate),
            mapper: $mapper,
            handler: $handler,
            context: FlowContext::forUpdate(self::MODEL)->allowDelete(true),
        );
    }

    #[Route(path: '/vat-rate/{id}/delete/confirm', name: 'app_pricing_vat_rate_delete_confirm', methods: ['GET'])]
    public function deleteConfirm(
        #[ValueResolver('public_id')] VatRate $vatRate,
        DeleteFlow $flow,
    ): Response {
        return $flow->deleteConfirm(
            entity: $vatRate,
            context: FlowContext::forDelete(self::MODEL),
        );
    }

    #[Route(path: '/vat-rate/{id}/delete', name: 'app_pricing_vat_rate_delete', methods: ['POST'])]
    public function delete(
        Request $request,
        #[ValueResolver('public_id')] VatRate $vatRate,
        DeleteVatRateHandler $handler,
        DeleteFlow $flow,
    ): Response {
        return $flow->delete(
            request: $request,
            command: new DeleteVatRate($vatRate->getPublicId()),
            handler: $handler,
            context: FlowContext::forDelete(self::MODEL),
        );
    }

    #[Route(path: '/vat-rate/{id}', name: 'app_pricing_vat_rate_show', methods: ['GET'])]
    public function show(#[ValueResolver('public_id')] VatRate $vatRate): Response
    {
        return $this->render('/pricing/vat_rate/show.html.twig', ['result' => $vatRate]);
    }
}
