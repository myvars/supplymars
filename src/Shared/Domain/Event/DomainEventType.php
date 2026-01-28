<?php

namespace App\Shared\Domain\Event;

enum DomainEventType: string
{
    case ORDER_CREATED = 'order.created';
    case ORDER_STATUS_CHANGED = 'order.status.changed';
    case ORDER_ITEM_CREATED = 'order.item.created';
    case ORDER_ITEM_STATUS_CHANGED = 'order.item.status.changed';
    case PURCHASE_ORDER_CREATED = 'purchase.order.created';
    case PURCHASE_ORDER_STATUS_CHANGED = 'purchase.order.status.changed';
    case PURCHASE_ORDER_ITEM_CREATED = 'purchase.order.item.created';
    case PURCHASE_ORDER_ITEM_STATUS_CHANGED = 'purchase.order.item.status.changed';
    case USER_CREATED = 'user.created';
    case USER_LOGGED_IN = 'user.logged_in';
    case SUPPLIER_PRODUCT_STOCK_CHANGED = 'supplier.product.stock.changed';
    case SUPPLIER_PRODUCT_COST_CHANGED = 'supplier.product.cost.changed';
    case VAT_RATE_WAS_CHANGED = 'vat.rate.was.changed';
    case CATEGORY_PRICING_WAS_CHANGED = 'category.pricing.was.changed';
    case SUBCATEGORY_PRICING_WAS_CHANGED = 'subcategory.pricing.was.changed';
    case SUPPLIER_PRODUCT_PRICING_WAS_CHANGED = 'supplier.product.pricing.was.changed';
    case SUPPLIER_PRODUCT_STATUS_WAS_CHANGED = 'supplier.product.status.was.changed';
    case SUPPLIER_STATUS_WAS_CHANGED = 'supplier.status.was.changed';
    case PRODUCT_IMAGE_WAS_DELETED = 'product.image.was.deleted';
    case REVIEW_CREATED = 'review.created';
    case REVIEW_STATUS_CHANGED = 'review.status.changed';
    case REVIEW_RATING_CHANGED = 'review.rating.changed';
}
