<?php

declare(strict_types=1);

namespace App\Purchasing\Application\Handler\SupplierProduct;

use App\Catalog\Domain\Model\Product\Product;
use App\Purchasing\Application\Command\SupplierProduct\DeleteSupplierProduct;
use App\Purchasing\Domain\Model\SupplierProduct\SupplierProduct;
use App\Purchasing\Domain\Repository\PurchaseOrderItemRepository;
use App\Purchasing\Domain\Repository\SupplierProductRepository;
use App\Shared\Application\FlusherInterface;
use App\Shared\Application\Result;
use App\Shared\Domain\Service\Pricing\MarkupCalculator;
use Symfony\Bundle\SecurityBundle\Security;

final readonly class DeleteSupplierProductHandler
{
    public function __construct(
        private SupplierProductRepository $supplierProducts,
        private PurchaseOrderItemRepository $purchaseOrderItems,
        private MarkupCalculator $markupCalculator,
        private FlusherInterface $flusher,
        private Security $security,
    ) {
    }

    public function __invoke(DeleteSupplierProduct $command): Result
    {
        if (!$this->security->isGranted('ROLE_SUPER_ADMIN')) {
            return Result::fail('Deleting is disabled for this user.');
        }

        $supplierProduct = $this->supplierProducts->getByPublicId($command->id);
        if (!$supplierProduct instanceof SupplierProduct) {
            return Result::fail('Supplier product not found.');
        }

        if ($this->purchaseOrderItems->existsForSupplierProduct($supplierProduct)) {
            return Result::fail('Used on purchase orders — deactivate it instead.');
        }

        // Choose a new active source first, or the product would still point at this one
        $product = $supplierProduct->getProduct();
        if ($product instanceof Product) {
            $product->removeSupplierProduct($this->markupCalculator, $supplierProduct);
        }

        $this->supplierProducts->remove($supplierProduct);
        $this->flusher->flush();

        return Result::ok(message: 'Supplier product deleted');
    }
}
