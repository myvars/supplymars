<?php

namespace App\Tests\Integration\Service\PurchaseOrder;

use App\DTO\EditOrderItemDto;
use App\Entity\PurchaseOrderItem;
use App\Factory\CustomerOrderFactory;
use App\Factory\CustomerOrderItemFactory;
use App\Factory\ProductFactory;
use App\Factory\PurchaseOrderItemFactory;
use App\Factory\SupplierProductFactory;
use App\Service\Crud\Common\CrudOptions;
use App\Service\Order\EditOrderItem;
use App\Service\Product\MarkupCalculator;
use App\Service\PurchaseOrder\CreatePurchaseOrder;
use App\Service\PurchaseOrder\CreatePurchaseOrderItem;
use App\Story\StaffUserStory;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Zenstruck\Foundry\Test\Factories;

class CreatePurchaseOrderItemIntegrationTest extends KernelTestCase
{
    use Factories;

    private CreatePurchaseOrderItem $createPurchaseOrderItem;

    private EntityManagerInterface $entityManager;

    private EditOrderItem $editOrderItem;


    protected function setUp(): void
    {
        self::bootKernel();
        $entityManager = static::getContainer()->get(EntityManagerInterface::class);
        $validator = static::getContainer()->get(ValidatorInterface::class);
        $createPurchaseOrder = static::getContainer()->get(CreatePurchaseOrder::class);
        $this->createPurchaseOrderItem = new CreatePurchaseOrderItem(
            $entityManager,
            $validator,
            $createPurchaseOrder
        );

        $markupCalculator = static::getContainer()->get(MarkupCalculator::class);
        $this->editOrderItem = new EditOrderItem($entityManager, $markupCalculator, $domainEventDispatcher);
        StaffUserStory::load();
    }

    public function testHandleWithValidCustomerOrderItem(): void
    {
        $customerOrder = CustomerOrderFactory::createOne()->_real();
        $supplierProduct = SupplierProductFactory::createOne()->_real();
        $customerOrderItem = CustomerOrderItemFactory::new()->with([
            'customerOrder' => $customerOrder,
            'product' => $supplierProduct->getProduct()
        ])->create()->_real();

        $crudOptions = new CrudOptions();
        $crudOptions->setEntity($customerOrderItem);
        $crudOptions->setCrudActionContext(['supplierProductId' => $supplierProduct->getId()]);

        $this->createPurchaseOrderItem->handle($crudOptions);

        $purchaseOrderItem = PurchaseOrderItemFactory::repository()->findOneBy([
            'customerOrderItem' => $customerOrderItem,
            'supplierProduct' => $supplierProduct
        ]);

        $this->assertInstanceOf(PurchaseOrderItem::class, $purchaseOrderItem);
    }

    public function testHandleWithEditablePurchaseOrderItem(): void
    {
        $product = ProductFactory::createOne(['sellPriceIncVat' => '50.00'])->_real();
        $purchaseOrderItem = PurchaseOrderItemFactory::createOne(['product' => $product])->_real();
        $customerOrderItem = $purchaseOrderItem->getCustomerOrderItem();
        $supplierProduct = $purchaseOrderItem->getSupplierProduct();

        $dto = new EditOrderItemDto(
            $customerOrderItem->getId(),
            2,
            $customerOrderItem->getPriceIncVat()
        );
        $crudOptions = new CrudOptions();
        $crudOptions->setEntity($dto);

        $this->editOrderItem->handle($crudOptions);

        $crudOptions->setEntity($customerOrderItem);
        $crudOptions->setCrudActionContext(['supplierProductId' => $supplierProduct->getId()]);

        $this->createPurchaseOrderItem->handle($crudOptions);

        $this->assertInstanceOf(PurchaseOrderItem::class, $purchaseOrderItem);
        $this->assertSame(2, $purchaseOrderItem->getQuantity());
    }
}
