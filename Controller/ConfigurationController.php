<?php

namespace ShoppingFeed\Controller;

use ShoppingFeed\Model\ShoppingfeedFeedQuery;
use ShoppingFeed\Model\ShoppingfeedMappingDeliveryQuery;
use Thelia\Controller\Admin\BaseAdminController;
use Thelia\Model\ModuleQuery;

class ConfigurationController extends BaseAdminController
{
    public function viewAction()
    {
        return $this->render(
            "shoppingfeed/configuration",
            [
                "feeds" => ShoppingfeedFeedQuery::create()->find(),
                "mappings" => ShoppingfeedMappingDeliveryQuery::create()->find(),
                'columnsDefinition' => $this->getContainer()->get('shopping_feed_log_service')->defineColumnsDefinition(),
            ]
        );
    }

    public function getDeliveryModules()
    {
        $deliveryModules = ModuleQuery::create()->filterByType(2)->filterByCategory("delivery")->find();

        $results = [];

        foreach ($deliveryModules as $deliveryModule) {
            $results[$deliveryModule->getId()] = $deliveryModule->getTitle();
        }

        return $results;
    }

}