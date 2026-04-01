<?php

namespace App\Shared\UI\Http\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\RouterInterface;
use Twig\Environment;

class HelpController extends AbstractController
{
    private const array ROUTE_MAP = [
        'app_homepage' => 'home/welcome',
        'app_login' => 'account/access',
        'app_register' => 'account/access',
        'app_forgot_password_request' => 'account/access',
        'app_check_email' => 'account/access',
        'app_reset_password' => 'account/access',
        'app_verify_resend_email' => 'account/access',
        'app_catalog_product_index' => 'catalog/products',
        'app_catalog_product_show' => 'catalog/products',
        'app_catalog_product_sales' => 'catalog/products',
        'app_catalog_product_reviews' => 'catalog/products',
        'app_catalog_product_history' => 'catalog/products',
        'app_catalog_product_image_show' => 'catalog/products',
        'app_catalog_category_index' => 'catalog/categories',
        'app_catalog_category_show' => 'catalog/categories',
        'app_catalog_subcategory_index' => 'catalog/categories',
        'app_catalog_subcategory_show' => 'catalog/categories',
        'app_catalog_manufacturer_index' => 'catalog/manufacturers',
        'app_catalog_manufacturer_show' => 'catalog/manufacturers',
        'app_order_index' => 'order/orders',
        'app_order_show' => 'order/orders',
        'app_order_item_show' => 'order/order_items',
        'app_purchasing_purchase_order_index' => 'purchasing/purchase_orders',
        'app_purchasing_purchase_order_show' => 'purchasing/purchase_orders',
        'app_purchasing_supplier_index' => 'purchasing/suppliers',
        'app_purchasing_supplier_show' => 'purchasing/suppliers',
        'app_purchasing_supplier_product_index' => 'purchasing/supplier_products',
        'app_purchasing_supplier_product_show' => 'purchasing/supplier_products',
        'app_customer_index' => 'customer/customers',
        'app_customer_show' => 'customer/customers',
        'app_pricing_vat_rate_index' => 'pricing/vat_rates',
        'app_pricing_vat_rate_show' => 'pricing/vat_rates',
        'app_pricing_stock' => 'stock/stock',
        'app_pricing_cost' => 'pricing/pricing',
        'app_review_index' => 'review/reviews',
        'app_review_show' => 'review/reviews',
        'app_dashboard' => 'reporting/dashboard',
        'app_reports_order_summary' => 'reporting/reports',
        'app_reports_product_sales' => 'reporting/reports',
        'app_reports_customer_insights' => 'reporting/reports',
        'app_reports_overdue_orders' => 'reporting/reports',
        'app_reports_po_performance' => 'reporting/reports',
        'app_note_ticket_index' => 'notes/tickets',
        'app_note_ticket_show' => 'notes/tickets',
        'app_note_pool_index' => 'notes/pools',
        'app_note_pool_show' => 'notes/pools',
    ];

    public function __construct(
        private readonly RouterInterface $router,
    ) {
    }

    #[Route(path: '/help', name: 'app_help')]
    public function __invoke(Request $request): Response
    {
        $page = $request->query->getString('page', '/');
        $from = $request->query->getString('from', '');

        $helpKey = $this->resolveHelpKey($page);
        $template = $helpKey ? 'help/' . $helpKey . '.html.twig' : 'help/_fallback.html.twig';

        return $this->render($template, [
            'from' => $from ?: null,
            'help_key' => $helpKey,
        ]);
    }

    #[Route(path: '/help/{key}', name: 'app_help_direct', requirements: ['key' => '[a-z_/]+'])]
    public function direct(string $key, Request $request): Response
    {
        $from = $request->query->getString('from', '');
        $template = 'help/' . $key . '.html.twig';

        if (!$this->templateExists($template)) {
            $template = 'help/_fallback.html.twig';
        }

        return $this->render($template, [
            'from' => $from ?: null,
            'help_key' => $key,
        ]);
    }

    private function resolveHelpKey(string $page): ?string
    {
        try {
            $match = $this->router->match($page);
            $routeName = $match['_route'] ?? null;

            return $routeName ? (self::ROUTE_MAP[$routeName] ?? null) : null;
        } catch (\Throwable) {
            return null;
        }
    }

    private function templateExists(string $template): bool
    {
        /** @var Environment $twig */
        $twig = $this->container->get('twig');

        return $twig->getLoader()->exists($template);
    }
}
