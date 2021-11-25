<?php


namespace ShoppingFeed\Event;

use Thelia\Model\Event\OrderEvent;

class OrderCreatedEvent extends OrderEvent
{
    const SHOPPINGFEED_ORDER_CREATED = 'action.module.shoppingfeed.order.created';
}