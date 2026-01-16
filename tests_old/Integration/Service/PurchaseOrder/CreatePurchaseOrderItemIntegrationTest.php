<?php

namespace App\Tests\Integration\Service\PurchaseOrder;

use App\Order\Application\DTO\EditOrderItemDto;
use App\Purchasing\Domain\Model\PurchaseOrder\PurchaseOrderItem;
use App\Service\Crud\Common\CrudContext;
use App\Service\Order\EditOrderItem;
use App\Service\PurchaseOrder\CreatePurchaseOrder;
use App\Service\PurchaseOrder\CreatePurchaseOrderItem;
use App\Shared\Domain\Service\Pricing\MarkupCalculator;
use Doctrine\ORM\EntityManagerInterface;
use Story\StaffUserStory;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use tests\Shared\Factory\CustomerOrderFactory;
use tests\Shared\Factory\CustomerOrderItemFactory;
use tests\Shared\Factory\ProductFactory;
use tests\Shared\Factory\PurchaseOrderItemFactory;
use tests\Shared\Factory\SupplierProductFactory;
use Zenstruck\Foundry\Test\Factories;

class CreatePurchaseOrderItemIntegrationTest extends KernelTestCase
{
    use Factories;

    private CreatePurchaseOrderItem $createPurchaseOrderItem;

    private EntityManagerInterface $em;

    private EditOrderItem $editOrderItem;

    protected function setUp(): void
    {
        self::bootKernel();
        $em = static::getContainer()->get(EntityManagerInterface::class);
        $validator = static::getContainer()->get(ValidatorInterface::class);
        $createPurchaseOrder = static::getContainer()->get(CreatePurchaseOrder::class);
        $this->createPurchaseOrderItem = new CreatePurchaseOrderItem(
            $em,
            $validator,
            $createPurchaseOrder
        );

        $markupCalculator = static::getContainer()->get(MarkupCalculator::class);
        $this->editOrderItem = new EditOrderItem($em, $markupCalculator, $domainEventDispatcher);
        StaffUserStory::load();
    }

    public function testHandleWithValidCustomerOrderItem(): void
    {
        $customerOrder = CustomerOrderFactory::createOne();
        $supplierProduct = SupplierProductFactory::createOne();
        $customerOrderItem = CustomerOrderItemFactory::new()->with([
            'customerOrder' => $customerOrder,
            'product' => $supplierProduct->getProduct(),
        ])->create();

        $context = new CrudContext();
        $context->setEntity($customerOrderItem);
        $context->setCrudHandlerContext(['supplierProductId' => $supplierProduct->getId()]);

        ($this->createPurchaseOrderItem)($context);

        $purchaseOrderItem = PurchaseOrderItemFactory::repository()->findOneBy([
            'customerOrderItem' => $customerOrderItem,
            'supplierProduct' => $supplierProduct,
        ]);

        $this->assertInstanceOf(PurchaseOrderItem::class, $purchaseOrderItem);
    }

    public function testHandleWithEditablePurchaseOrderItem(): void
    {
        $product = ProductFactory::createOne(['sellPriceIncVat' => '50.00']);
        $purchaseOrderItem = PurchaseOrderItemFactory::createOne(['product' => $product]);
        $customerOrderItem = $purchaseOrderItem->getCustomerOrderItem();
        $supplierProduct = $purchaseOrderItem->getSupplierProduct();

        $dto = new EditOrderItemDto(
            $customerOrderItem->getId(),
            2,
            $customerOrderItem->getPriceIncVat()
        );
        $context = new CrudContext();
        $context->setEntity($dto);

        ($this->editOrderItem)($context);

        $context->setEntity($customerOrderItem);
        $context->setCrudHandlerContext(['supplierProductId' => $supplierProduct->getId()]);

        ($this->createPurchaseOrderItem)($context);

        $this->assertInstanceOf(PurchaseOrderItem::class, $purchaseOrderItem);
        $this->assertSame(2, $purchaseOrderItem->getQuantity());
    }
}
