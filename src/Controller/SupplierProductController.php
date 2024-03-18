<?php

namespace App\Controller;

use App\Entity\SupplierProduct;
use App\Form\SupplierProductType;
use App\Repository\SupplierProductRepository;
use App\Service\ActiveSourceCalculator;
use App\Service\CrudHelper;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapQueryParameter;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/supplier-product')]
class SupplierProductController extends AbstractController
{
    public const string SECTION = 'Supplier Product';
    public const int FORM_COLUMNS = 2;

    public function __construct(
        private readonly CrudHelper $crudHelper,
        private readonly ActiveSourceCalculator $activeSourceCalculator
    ) {
        $this->crudHelper->setSection(self::SECTION);
        $this->crudHelper->setFormColumns(self::FORM_COLUMNS);
    }

    #[Route('/', name: 'app_supplier_product_index', methods: ['GET'])]
    public function index(
        SupplierProductRepository $supplierProductRepository,
        #[MapQueryParameter] int $page = 1,
        #[MapQueryParameter] int $limit = 10,
        #[MapQueryParameter] string $sort = 'id',
        #[MapQueryParameter] string $sortDirection = 'ASC',
        #[MapQueryParameter] ?string $query = null,
    ): Response {
        $validSorts = ['id', 'supplier.name', 'name', 'productCode', 'cost', 'stock', 'isActive'];
        $sort = in_array($sort, $validSorts) ? $sort : 'id';

        return $this->crudHelper->renderIndex(
            $supplierProductRepository->findBySearchQueryBuilder($query, $sort, $sortDirection),
            $page,
            $limit,
            $sort,
            $sortDirection,
            $query,
        );
    }

    #[Route('/new', name: 'app_supplier_product_new', methods: ['GET', 'POST'])]
    public function new(Request $request): Response
    {
        return $this->crudHelper->renderCreate(
            $request,
            new SupplierProduct(),
            SupplierProductType::class,
        );
    }

    #[Route('/{id}', name: 'app_supplier_product_show', methods: ['GET'])]
    public function show(?SupplierProduct $supplierProduct): Response
    {
        return $this->crudHelper->renderShow($supplierProduct);
    }

    #[Route('/{id}/edit', name: 'app_supplier_product_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, ?SupplierProduct $supplierProduct): Response
    {
        return $this->crudHelper->renderUpdate(
            $request,
            $supplierProduct,
            SupplierProductType::class,
        );
    }

    #[Route('/{id}/delete', name: 'app_supplier_product_delete_confirm', methods: ['GET'])]
    public function deleteConfirm(?SupplierProduct $supplierProduct): Response
    {
        return $this->crudHelper->renderDeleteConfirm($supplierProduct);
    }

    #[Route('/{id}', name: 'app_supplier_product_delete', methods: ['POST'])]
    public function delete(Request $request, ?SupplierProduct $supplierProduct): Response
    {
        return $this->crudHelper->renderDelete(
            $request,
            $supplierProduct,
        );
    }

    #[Route('/{id}/remove', name: 'app_supplier_product_remove_confirm', methods: ['GET'])]
    public function removeConfirm(?SupplierProduct $supplierProduct): Response
    {
        return $this->render('supplier_product/remove.html.twig', [
            'supplierProduct' => $supplierProduct,
        ]);
    }

    #[Route('/{id}/remove', name: 'app_supplier_product_remove', methods: ['POST'])]
    public function remove(Request $request, ?SupplierProduct $supplierProduct): Response
    {
        if ($this->isCsrfTokenValid('remove'.$supplierProduct->getId(), $request->request->get('_token'))) {
            $product = $supplierProduct->getProduct();
            $this->activeSourceCalculator->removeMappedProduct($supplierProduct);
            $this->activeSourceCalculator->recalculateActiveSource($product);

            $this->addFlash(
                'success',
                'Supplier product removed'
            );
        }

        return $this->crudHelper->streamRefresh($request);
    }

    #[Route('/{id}/toggle/status', name: 'app_supplier_product_toggle_status', methods: ['GET'])]
    public function toggleStatus(Request $request, ?SupplierProduct $supplierProduct): Response
    {
        $this->activeSourceCalculator->toggleStatus($supplierProduct);
        $this->activeSourceCalculator->recalculateActiveSource($supplierProduct->getProduct());

        $this->addFlash(
            'success',
            'Supplier product status updated'
        );

        return $this->crudHelper->streamRefresh($request);
    }
}
