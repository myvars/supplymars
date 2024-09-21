<?php

namespace App\Controller;

use App\Entity\SupplierProduct;
use App\Form\SupplierProductType;
use App\Repository\SupplierProductRepository;
use App\Service\Crud\CrudCreator;
use App\Service\Crud\CrudDeleter;
use App\Service\Crud\CrudHelper;
use App\Service\Crud\CrudIndexer;
use App\Service\Crud\CrudReader;
use App\Service\Crud\CrudUpdater;
use App\Service\Product\ActiveSourceCalculator;
use App\Service\Product\ProductGenerator;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/supplier-product')]
#[IsGranted('ROLE_USER')]
class SupplierProductController extends AbstractController
{
    public const SECTION = 'Supplier Product';

    public function __construct(private readonly ActiveSourceCalculator $activeSourceCalculator)
    {
    }

    #[Route('/', name: 'app_supplier_product_index', methods: ['GET'])]
    public function index(SupplierProductRepository $repository, CrudIndexer $crudIndexer): Response
    {
        $sortOptions = ['id', 'supplier.name', 'name', 'cost', 'stock', 'isActive'];

        return $crudIndexer->index(self::SECTION, $repository, $sortOptions);
    }

    #[Route('/new', name: 'app_supplier_product_new', methods: ['GET', 'POST'])]
    public function new(CrudCreator $crudCreator): Response
    {
        return $crudCreator->create(self::SECTION, new SupplierProduct(), SupplierProductType::class);
    }

    #[Route('/{id}', name: 'app_supplier_product_show', methods: ['GET'])]
    public function show(?SupplierProduct $supplierProduct, CrudReader $crudReader): Response
    {
        return $crudReader->read(self::SECTION, $supplierProduct);
    }

    #[Route('/{id}/edit', name: 'app_supplier_product_edit', methods: ['GET', 'POST'])]
    public function edit(?SupplierProduct $supplierProduct, CrudUpdater $crudUpdater): Response
    {
        return $crudUpdater->update(self::SECTION, $supplierProduct, SupplierProductType::class);
    }

    #[Route('/{id}/delete/confirm', name: 'app_supplier_product_delete_confirm', methods: ['GET'])]
    public function deleteConfirm(?SupplierProduct $supplierProduct, CrudDeleter $crudDeleter): Response
    {
        return $crudDeleter->deleteConfirm(self::SECTION, $supplierProduct);
    }

    #[Route('/{id}/delete', name: 'app_supplier_product_delete', methods: ['POST'])]
    public function delete(?SupplierProduct $supplierProduct, CrudDeleter $crudDeleter): Response
    {
        return $crudDeleter->delete(self::SECTION, $supplierProduct);
    }

    #[Route('/{id}/remove', name: 'app_supplier_product_remove_confirm', methods: ['GET'])]
    public function removeConfirm(?SupplierProduct $supplierProduct, CrudHelper $crudHelper): Response
    {
        if (!$supplierProduct || !$supplierProduct->getProduct()) {
            return $crudHelper->showEmpty(self::SECTION);
        }

        return $this->render('supplier_product/remove.html.twig', ['supplierProduct' => $supplierProduct]);
    }

    #[Route('/{id}/remove', name: 'app_supplier_product_remove', methods: ['POST'])]
    public function remove(Request $request, ?SupplierProduct $supplierProduct, CrudHelper $crudHelper): Response
    {
        if (!$supplierProduct || !$supplierProduct->getProduct()) {
            return $crudHelper->showEmpty(self::SECTION);
        }

        if ($this->isCsrfTokenValid(
            'remove'.$supplierProduct->getId(),
            $request->request->get('_token')
        )) {
            $product = $supplierProduct->getProduct();
            $this->activeSourceCalculator->removeMappedProduct($supplierProduct);
            $this->activeSourceCalculator->recalculateActiveSource($product);

            $this->addFlash('success', 'Supplier product removed');
        }

        return $crudHelper->redirectToLink(
            $this->generateUrl('app_product_stock', ['id' => $supplierProduct->getProduct()->getId()])
        );
    }

    #[Route('/{id}/status/toggle', name: 'app_supplier_product_toggle_status', methods: ['GET'])]
    public function toggleStatus(?SupplierProduct $supplierProduct, CrudHelper $crudHelper): Response
    {
        if (!$supplierProduct instanceof SupplierProduct) {
            return $crudHelper->showEmpty(self::SECTION);
        }

        $this->activeSourceCalculator->toggleStatus($supplierProduct);
        $this->activeSourceCalculator->recalculateActiveSource($supplierProduct->getProduct());

        $this->addFlash('success', 'Supplier product status updated');

        return $crudHelper->redirectToLink(
            $this->generateUrl('app_product_stock', ['id' => $supplierProduct->getProduct()->getId()])
        );
    }

    #[Route('/{id}/map', name: 'app_supplier_product_map', methods: ['GET'])]
    public function map(
        ?SupplierProduct $supplierProduct,
        ProductGenerator $productGenerator,
        CrudHelper $crudHelper
    ): Response
    {
        if (!$supplierProduct instanceof SupplierProduct) {
            return $crudHelper->showEmpty(self::SECTION);
        }

        $productGenerator->createFromSupplierProduct($supplierProduct);

        $this->addFlash('success', 'Supplier product mapped');

        return $crudHelper->redirectToLink(
            $this->generateUrl('app_supplier_product_show', ['id' => $supplierProduct->getId()])
        );
    }
}
