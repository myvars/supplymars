<?php

namespace App\Tests\Shared\UI\Http\FormFlow\View;

use App\Shared\UI\Http\FormFlow\View\FlowModel;
use App\Shared\UI\Http\FormFlow\View\FlowRoutes;
use PHPUnit\Framework\TestCase;

final class FlowModelTest extends TestCase
{
    // ── create() factory ─────────────────────────────────────────────

    public function testCreateManufacturer(): void
    {
        $m = FlowModel::create('catalog', 'manufacturer');

        self::assertSame('Manufacturer', $m->displayName);
        self::assertSame('catalog/manufacturer', $m->templateDir);
        self::assertSame('app_catalog_manufacturer_index', $m->defaultSuccessRoute);
        self::assertSame('app_catalog_manufacturer_index', $m->routes->index);
        self::assertSame('app_catalog_manufacturer_new', $m->routes->new);
        self::assertSame('app_catalog_manufacturer_show', $m->routes->show);
        self::assertSame('app_catalog_manufacturer_delete', $m->routes->delete);
        self::assertSame('app_catalog_manufacturer_search_filter', $m->routes->filter);
    }

    public function testCreateCategory(): void
    {
        $m = FlowModel::create('catalog', 'category');

        self::assertSame('Category', $m->displayName);
        self::assertSame('catalog/category', $m->templateDir);
        self::assertSame('app_catalog_category_index', $m->defaultSuccessRoute);
    }

    public function testCreateSubcategory(): void
    {
        $m = FlowModel::create('catalog', 'subcategory');

        self::assertSame('Subcategory', $m->displayName);
        self::assertSame('catalog/subcategory', $m->templateDir);
        self::assertSame('app_catalog_subcategory_index', $m->defaultSuccessRoute);
    }

    public function testCreateProduct(): void
    {
        $m = FlowModel::create('catalog', 'product');

        self::assertSame('Product', $m->displayName);
        self::assertSame('catalog/product', $m->templateDir);
        self::assertSame('app_catalog_product_index', $m->defaultSuccessRoute);
    }

    public function testCreateSupplier(): void
    {
        $m = FlowModel::create('purchasing', 'supplier');

        self::assertSame('Supplier', $m->displayName);
        self::assertSame('purchasing/supplier', $m->templateDir);
        self::assertSame('app_purchasing_supplier_index', $m->defaultSuccessRoute);
    }

    public function testCreateSupplierProduct(): void
    {
        $m = FlowModel::create('purchasing', 'supplier_product');

        self::assertSame('Supplier Product', $m->displayName);
        self::assertSame('purchasing/supplier_product', $m->templateDir);
        self::assertSame('app_purchasing_supplier_product_index', $m->defaultSuccessRoute);
        self::assertSame('app_purchasing_supplier_product_search_filter', $m->routes->filter);
    }

    public function testCreatePurchaseOrder(): void
    {
        $m = FlowModel::create('purchasing', 'purchase_order');

        self::assertSame('Purchase Order', $m->displayName);
        self::assertSame('purchasing/purchase_order', $m->templateDir);
        self::assertSame('app_purchasing_purchase_order_index', $m->defaultSuccessRoute);
    }

    public function testCreatePurchaseOrderItem(): void
    {
        $m = FlowModel::create('purchasing', 'purchase_order_item');

        self::assertSame('Purchase Order Item', $m->displayName);
        self::assertSame('purchasing/purchase_order_item', $m->templateDir);
        self::assertSame('app_purchasing_purchase_order_item_index', $m->defaultSuccessRoute);
    }

    public function testCreateVatRateWithExplicitDisplayName(): void
    {
        $m = FlowModel::create('pricing', 'vat_rate', displayName: 'VAT Rate');

        self::assertSame('VAT Rate', $m->displayName);
        self::assertSame('pricing/vat_rate', $m->templateDir);
        self::assertSame('app_pricing_vat_rate_index', $m->defaultSuccessRoute);
    }

    public function testCreatePool(): void
    {
        $m = FlowModel::create('note', 'pool');

        self::assertSame('Pool', $m->displayName);
        self::assertSame('note/pool', $m->templateDir);
        self::assertSame('app_note_pool_index', $m->defaultSuccessRoute);
    }

    public function testCreateTicket(): void
    {
        $m = FlowModel::create('note', 'ticket');

        self::assertSame('Ticket', $m->displayName);
        self::assertSame('note/ticket', $m->templateDir);
        self::assertSame('app_note_ticket_index', $m->defaultSuccessRoute);
    }

    // ── simple() factory ─────────────────────────────────────────────

    public function testSimplePricing(): void
    {
        $m = FlowModel::simple('pricing');

        self::assertSame('Pricing', $m->displayName);
        self::assertSame('pricing', $m->templateDir);
        self::assertSame('app_pricing_index', $m->defaultSuccessRoute);
        self::assertSame('app_pricing_index', $m->routes->index);
    }

    public function testSimpleCustomer(): void
    {
        $m = FlowModel::simple('customer');

        self::assertSame('Customer', $m->displayName);
        self::assertSame('customer', $m->templateDir);
        self::assertSame('app_customer_index', $m->defaultSuccessRoute);
    }

    public function testSimpleOrder(): void
    {
        $m = FlowModel::simple('order');

        self::assertSame('Order', $m->displayName);
        self::assertSame('order', $m->templateDir);
        self::assertSame('app_order_index', $m->defaultSuccessRoute);
    }

    public function testSimpleOrderItem(): void
    {
        $m = FlowModel::simple('order_item');

        self::assertSame('Order Item', $m->displayName);
        self::assertSame('order_item', $m->templateDir);
        self::assertSame('app_order_item_index', $m->defaultSuccessRoute);
    }

    public function testSimpleReview(): void
    {
        $m = FlowModel::simple('review');

        self::assertSame('Review', $m->displayName);
        self::assertSame('review', $m->templateDir);
        self::assertSame('app_review_index', $m->defaultSuccessRoute);
    }

    // ── template() method ────────────────────────────────────────────

    public function testTemplateDerivesBoundedContextPath(): void
    {
        $m = FlowModel::create('catalog', 'manufacturer');
        self::assertSame('catalog/manufacturer/create.html.twig', $m->template('create'));
        self::assertSame('catalog/manufacturer/update.html.twig', $m->template('update'));
        self::assertSame('catalog/manufacturer/index.html.twig', $m->template('index'));
        self::assertSame('catalog/manufacturer/delete.html.twig', $m->template('delete'));
    }

    public function testTemplateDerivesSimplePath(): void
    {
        $m = FlowModel::simple('customer');
        self::assertSame('customer/create.html.twig', $m->template('create'));
        self::assertSame('customer/update.html.twig', $m->template('update'));
    }

    public function testTemplateDerivesMultiWordPath(): void
    {
        $m = FlowModel::create('purchasing', 'supplier_product');
        self::assertSame('purchasing/supplier_product/create.html.twig', $m->template('create'));
    }

    // ── withDisplayName() method ─────────────────────────────────────

    public function testWithDisplayNameReturnsNewInstance(): void
    {
        $original = FlowModel::simple('pricing');
        $override = $original->withDisplayName('Product Cost');

        self::assertSame('Product Cost', $override->displayName);
        self::assertSame('Pricing', $original->displayName);

        // Other properties unchanged
        self::assertSame($original->templateDir, $override->templateDir);
        self::assertSame($original->defaultSuccessRoute, $override->defaultSuccessRoute);
        self::assertSame($original->routes->index, $override->routes->index);
    }

    public function testWithDisplayNamePricingCostVariants(): void
    {
        $base = FlowModel::simple('pricing');

        self::assertSame('Product Cost', $base->withDisplayName('Product Cost')->displayName);
        self::assertSame('Category Cost', $base->withDisplayName('Category Cost')->displayName);
        self::assertSame('Subcategory Cost', $base->withDisplayName('Subcategory Cost')->displayName);
    }

    // ── BASE_TEMPLATE constant ───────────────────────────────────────

    public function testBaseTemplateMatchesExpectedPath(): void
    {
        self::assertSame('shared/form_flow/base.html.twig', FlowModel::BASE_TEMPLATE);
    }

    // ── routes are FlowRoutes instances ──────────────────────────────

    public function testRoutesAreFlowRoutesInstances(): void
    {
        $m = FlowModel::create('catalog', 'product');
        self::assertInstanceOf(FlowRoutes::class, $m->routes);
    }
}
