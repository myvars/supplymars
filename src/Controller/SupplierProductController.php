<?php

namespace App\Controller;

use App\DTO\SearchDto\SupplierProductSearchDto;
use App\Entity\Product;
use App\Entity\SupplierProduct;
use App\Form\SearchForm\SupplierProductSearchFilterType;
use App\Form\SupplierProductType;
use App\Repository\SupplierProductRepository;
use App\Service\Crud\CrudCreator;
use App\Service\Crud\CrudDeleter;
use App\Service\Crud\CrudHandler;
use App\Service\Crud\CrudReader;
use App\Service\Crud\CrudSearcher;
use App\Service\Crud\CrudUpdater;
use App\Service\Product\ProductGenerator;
use App\Service\Search\SearchFilter;
use App\Service\SupplierProduct\ChangeMappedProductStatus;
use App\Service\SupplierProduct\RemoveMappedProduct;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapQueryString;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/supplier-product')]
#[IsGranted('ROLE_ADMIN')]
class SupplierProductController extends AbstractController
{
    public const SECTION = 'Supplier Product';

    #[Route('/', name: 'app_supplier_product_index', methods: ['GET'])]
    public function index(
        Request $request,
        CrudSearcher $handler,
        SupplierProductRepository $repository,
        #[MapQueryString] SupplierProductSearchDto $dto = new SupplierProductSearchDto()
    ): Response {
        return $handler->search(self::SECTION, $dto, $repository, $request->query->all());
    }

    #[Route('/search/filter', name: 'app_supplier_product_search_filter', methods: ['GET', 'POST'])]
    public function searchFilter(
        Request $request,
        CrudUpdater $handler,
        SearchFilter $action,
        #[MapQueryString] SupplierProductSearchDto $dto = new SupplierProductSearchDto()
    ): Response {
        $dto->setQueryString($request->getQueryString());
        $form = $this->createForm(SupplierProductSearchFilterType::class, $dto, [
            'action' => $this->generateUrl('app_supplier_product_search_filter', $request->query->all()),
        ]);

        return $handler->build(
            $handler->setDefaults()
                ->setTemplate($dto::TEMPLATE)
                ->setForm($form)
                ->setEntity($dto)
                ->setCrudAction($action)
                ->setSuccessLink(
                    $this->generateUrl('app_supplier_product_index')
                )
        );
    }

    #[Route('/new', name: 'app_supplier_product_new', methods: ['GET', 'POST'])]
    public function new(CrudCreator $handler): Response
    {
        return $handler->create(self::SECTION, new SupplierProduct(), SupplierProductType::class);
    }

    #[Route('/{id}', name: 'app_supplier_product_show', methods: ['GET'])]
    public function show(
        SupplierProduct $supplierProduct,
        CrudReader $handler
    ): Response {
        return $handler->read(self::SECTION, $supplierProduct);
    }

    #[Route('/{id}/edit', name: 'app_supplier_product_edit', methods: ['GET', 'POST'])]
    public function edit(
        SupplierProduct $supplierProduct,
        CrudUpdater $handler
    ): Response {
        return $handler->update(self::SECTION, $supplierProduct, SupplierProductType::class);
    }

    #[Route('/{id}/delete/confirm', name: 'app_supplier_product_delete_confirm', methods: ['GET'])]
    public function deleteConfirm(
        SupplierProduct $supplierProduct,
        CrudDeleter $handler
    ): Response {
        return $handler->deleteConfirm(self::SECTION, $supplierProduct);
    }

    #[Route('/{id}/delete', name: 'app_supplier_product_delete', methods: ['POST'])]
    public function delete(
        SupplierProduct $supplierProduct,
        CrudDeleter $handler
    ): Response {
        return $handler->delete(self::SECTION, $supplierProduct);
    }

    #[Route('/{id}/remove', name: 'app_supplier_product_remove_confirm', methods: ['GET'])]
    public function removeConfirm(
        SupplierProduct $supplierProduct,
        CrudReader $handler
    ): Response {
        return $handler->build(
            $handler->setDefaults()
                ->setEntity($supplierProduct)
                ->setTemplate('supplier_product/remove.html.twig')
        );
    }

    #[Route('/{id}/remove', name: 'app_supplier_product_remove', methods: ['POST'])]
    public function remove(
        SupplierProduct $supplierProduct,
        CrudDeleter $handler,
        RemoveMappedProduct $action
    ): Response {
        if (!$supplierProduct->getProduct() instanceof Product) {
            throw new \InvalidArgumentException('Supplier product must be mapped to a product');
        }

        return $handler->build(
            $handler->setup(self::SECTION, $supplierProduct)
                ->setCrudAction($action)
                ->setSuccessFlash('Supplier product removed')
                ->setErrorFlash('Supplier product cannot be removed')
                ->setSuccessLink(
                    $this->generateUrl('app_product_stock', ['id' => $supplierProduct->getProduct()->getId()])
                )
        );
    }

    #[Route('/{id}/status/toggle', name: 'app_supplier_product_toggle_status', methods: ['GET'])]
    public function toggleStatus(
        SupplierProduct $supplierProduct,
        CrudHandler $handler,
        ChangeMappedProductStatus $action,
    ): Response {
        if (!$supplierProduct->getProduct() instanceof Product) {
            throw new \InvalidArgumentException('Supplier product must be mapped to a product');
        }

        return $handler->build(
            $handler->setDefaults()
                ->setEntity($supplierProduct)
                ->setCrudAction($action)
                ->setSuccessFlash('Supplier product status updated')
                ->setErrorFlash('Supplier product status cannot be updated')
                ->setSuccessLink(
                    $this->generateUrl('app_product_stock', ['id' => $supplierProduct->getProduct()->getId()])
                )
        );
    }

    #[Route('/{id}/map', name: 'app_supplier_product_map', methods: ['GET'])]
    public function map(
        SupplierProduct $supplierProduct,
        CrudHandler $handler,
        ProductGenerator $action,
    ): Response
    {
        return $handler->build(
            $handler->setDefaults()
                ->setEntity($supplierProduct)
                ->setCrudAction($action)
                ->setSuccessFlash('Supplier product mapped')
                ->setErrorFlash('Supplier product cannot be mapped')
                ->setSuccessLink(
                    $this->generateUrl('app_supplier_product_show', ['id' => $supplierProduct->getId()])
                )
        );
    }
}
