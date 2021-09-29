<?php

namespace ShoppingFeed\Controller;

use ShoppingFeed\Model\ShoppingfeedFeedQuery;
use ShoppingFeed\Model\ShoppingfeedLogQuery;
use ShoppingFeed\Model\ShoppingfeedMappingDeliveryQuery;
use ShoppingFeed\Model\ShoppingfeedOrderDataQuery;
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
                "missingMappings" => $this->getMissingMappings(),
                'columnsDefinition' => $this->getContainer()->get('shopping_feed_log_service')->defineColumnsDefinition(),
            ]
        );
    }

    public function getMissingMappings()
    {
        $missingMappings = ShoppingfeedLogQuery::create()
            ->filterByObjectType('Mapping')
            ->groupByObjectRef()
            ->find();

        $results = [];
        foreach ($missingMappings as $missingMapping) {
            $mappingDeliveryService = $this->getContainer()->get('shopping_feed_mapping_delivery_service');
            $deliveryModuleId = $mappingDeliveryService->getDeliveryModuleIdFromCode($missingMapping->getObjectRef());
            if ($deliveryModuleId === 0) {
                $results[] = $missingMapping->getObjectRef();
            }
        }
        return $results;
    }

}