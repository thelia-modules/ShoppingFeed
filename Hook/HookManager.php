<?php

namespace ShoppingFeed\Hook;

use Propel\Runtime\Exception\PropelException;
use ShoppingFeed\Model\ShoppingfeedOrderDataQuery;
use ShoppingFeed\Model\ShoppingfeedProductMarketplaceCategoryQuery;
use ShoppingFeed\Model\ShoppingfeedPseMarketplaceQuery;
use ShoppingFeed\Service\LogService;
use ShoppingFeed\ShoppingFeed;
use Thelia\Core\Event\Hook\HookRenderEvent;
use Thelia\Core\Hook\BaseHook;
use Thelia\Model\OrderQuery;

/**
 * Class HookManager
 * @package ShoppingFeed\Hook
 */
class HookManager extends BaseHook
{
    private LogService $logService;

    public function __construct(LogService $logService)
    {
        $this->logService = $logService;
    }

    public function onModulesListConfiguration(HookRenderEvent $event): void{
        $event->add($this->render(
            'shoppingfeed/js/modules-list-js.html',
            [
                'moduleId' => ShoppingFeed::getModuleId()
            ]
        ));
    }

    public function renderLogs(HookRenderEvent $event): void
    {
        $event->add($this->render(
            'shoppingfeed/hook/home-bottom.html',
            [
                'columnsDefinition' => $this->logService->defineColumnsDefinition(),
            ]
        ));
    }

    public function renderJs(HookRenderEvent $event): void
    {
        $event->add($this->render(
            'shoppingfeed/js/home-js.html',
            [
                'columnsDefinition' => $this->logService->defineColumnsDefinition(),
            ]
        ));
    }

    public function onMainHeadCss(HookRenderEvent $event): void
    {
        $content = $this->addCSS('shoppingfeed/css/style.css');
        $event->add($content);
    }

    public function onPseMarketPlaceEdit(HookRenderEvent $event): void
    {
        $marketplace = ShoppingfeedPseMarketplaceQuery::create()->filterByPseId($event->getArgument('pse'))->findOne();
        $event->add($this->render(
            'shoppingfeed/hook/pse-edit-marketplace.html',
            [
                'pseId' => $event->getArgument('pse'),
                'marketplace' => ($marketplace) ? $marketplace->getMarketplace() : ''
            ]
        ));
    }

    public function onProductEdit(HookRenderEvent $event): void
    {
        $marketplaceCategory = ShoppingfeedProductMarketplaceCategoryQuery::create()->filterByProductId($event->getArgument('product_id'))->findOne();
        $event->add($this->render(
            'shoppingfeed/hook/product-edit-marketplace-category.html',
            [
                'marketplaceCategoryId' => ($marketplaceCategory) ? $marketplaceCategory->getCategoryId() : 0
            ]
        ));
    }

    /**
     * @throws PropelException
     */
    public function onOrderEdit(HookRenderEvent $event): void
    {
        $orderId = $event->getArgument("order_id");
        $order = OrderQuery::create()->filterById($orderId)->findOne();
        $orderData = ShoppingfeedOrderDataQuery::create()->filterById($orderId)->findOne();

        $event->add($this->render(
            'shoppingfeed/hook/order-edit.html',
            [
                'is_ShoppingFeed_order' => ($orderData),
                'channel' => ($orderData) ? $orderData->getChannel() : '',
                'external_ref' => ($orderData) ? $orderData->getExternalReference() : '',
                'created_at' => ($orderData) ? $order->getCreatedAt()->format("d/m/Y à H:i:s") : ''
            ]
        ));
    }
}
