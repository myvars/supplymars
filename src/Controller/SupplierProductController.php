<?php

namespace App\Controller;

use App\Entity\SupplierProduct;
use App\Form\SupplierProductType;
use App\Repository\SupplierProductRepository;
use App\Service\ActiveSourceCalculator;
use App\Service\CrudHelper;
use App\Service\ProductGenerator;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/supplier-product')]
class SupplierProductController extends AbstractController
{
    public const SECTION = 'Supplier Product';
    public const COLUMNS = 2;

    public function __construct(
        private readonly CrudHelper $crudHelper,
        private readonly ActiveSourceCalculator $activeSourceCalculator
    ) {
        $this->crudHelper->setSection(self::SECTION);
    }

    #[Route('/', name: 'app_supplier_product_index', methods: ['GET'])]
    public function index(SupplierProductRepository $supplierProductRepository): Response
    {
        $sortOptions = ['id', 'supplier.name', 'name', 'cost', 'stock', 'isActive'];

        return $this->crudHelper->renderIndex(
            $supplierProductRepository,
            $sortOptions
        );
    }

    #[Route('/new', name: 'app_supplier_product_new', methods: ['GET', 'POST'])]
    public function new(): Response
    {
        return $this->crudHelper->renderCreate(
            new SupplierProduct(),
            SupplierProductType::class,
            self::COLUMNS
        );
    }

    #[Route('/{id}', name: 'app_supplier_product_show', methods: ['GET'])]
    public function show(?SupplierProduct $supplierProduct): Response
    {
        return $this->crudHelper->renderShow($supplierProduct);
    }

    #[Route('/{id}/edit', name: 'app_supplier_product_edit', methods: ['GET', 'POST'])]
    public function edit(?SupplierProduct $supplierProduct): Response
    {
        return $this->crudHelper->renderUpdate(
            $supplierProduct,
            SupplierProductType::class,
            self::COLUMNS
        );
    }

    #[Route('/{id}/delete/confirm', name: 'app_supplier_product_delete_confirm', methods: ['GET'])]
    public function deleteConfirm(?SupplierProduct $supplierProduct): Response
    {
        return $this->crudHelper->renderDeleteConfirm($supplierProduct);
    }

    #[Route('/{id}/delete', name: 'app_supplier_product_delete', methods: ['POST'])]
    public function delete(?SupplierProduct $supplierProduct): Response
    {
        return $this->crudHelper->renderDelete($supplierProduct);
    }

    #[Route('/{id}/remove', name: 'app_supplier_product_remove_confirm', methods: ['GET'])]
    public function removeConfirm(?SupplierProduct $supplierProduct): Response
    {
        if (!$supplierProduct || !$supplierProduct->getProduct()) {
            return $this->crudHelper->renderShowEmpty(self::SECTION);
        }

        return $this->render('supplier_product/remove.html.twig', [
            'supplierProduct' => $supplierProduct,
        ]);
    }

    #[Route('/{id}/remove', name: 'app_supplier_product_remove', methods: ['POST'])]
    public function remove(Request $request, ?SupplierProduct $supplierProduct): Response
    {
        if (!$supplierProduct || !$supplierProduct->getProduct()) {
            return $this->crudHelper->renderShowEmpty(self::SECTION);
        }

        if ($this->isCsrfTokenValid('remove'.$supplierProduct->getId(), $request->request->get('_token'))) {
            $product = $supplierProduct->getProduct();
            $this->activeSourceCalculator->removeMappedProduct($supplierProduct);
            $this->activeSourceCalculator->recalculateActiveSource($product);

            $this->addFlash(
                'success',
                'Supplier product removed'
            );
        }

        return $this->crudHelper->streamRefresh();
    }

    #[Route('/{id}/status/toggle', name: 'app_supplier_product_toggle_status', methods: ['GET'])]
    public function toggleStatus(?SupplierProduct $supplierProduct): Response
    {
        if (!$supplierProduct) {
            return $this->crudHelper->renderShowEmpty(self::SECTION);
        }

        $this->activeSourceCalculator->toggleStatus($supplierProduct);
        $this->activeSourceCalculator->recalculateActiveSource($supplierProduct->getProduct());

        $this->addFlash(
            'success',
            'Supplier product status updated'
        );

        return $this->crudHelper->streamRefresh();
    }

    #[Route('/{id}/map', name: 'app_supplier_product_map', methods: ['GET'])]
    public function map(?SupplierProduct $supplierProduct, ProductGenerator $productGenerator): Response
    {
        if (!$supplierProduct) {
            return $this->crudHelper->renderShowEmpty(self::SECTION);
        }

        $productGenerator->createFromSupplierProduct($supplierProduct);

        $this->addFlash(
            'success',
            'Supplier product mapped'
        );

        return $this->crudHelper->streamRefresh();
    }
}
