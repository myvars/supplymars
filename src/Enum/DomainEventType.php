<?php

namespace App\Enum;

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
}
