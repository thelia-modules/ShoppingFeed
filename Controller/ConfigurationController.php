<?php

namespace ShoppingFeed\Controller;

use ShoppingFeed\Model\ShoppingfeedFeedQuery;
use ShoppingFeed\Model\ShoppingfeedLogQuery;
use ShoppingFeed\Model\ShoppingfeedMappingDeliveryQuery;
use ShoppingFeed\Model\ShoppingfeedOrderDataQuery;
use ShoppingFeed\Service\LogService;
use ShoppingFeed\Service\MappingDeliveryService;
use Thelia\Controller\Admin\BaseAdminController;
use Thelia\Model\ModuleQuery;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("/admin/module/ShoppingFeed", name="config_module")
 */
class ConfigurationController extends BaseAdminController
{
    /**
     * @Route("", name="view")
     */
    public function viewAction(LogService $logService, MappingDeliveryService $deliveryService)
    {
        return $this->render(
            "shoppingfeed/configuration",
            [
                "feeds" => ShoppingfeedFeedQuery::create()->find(),
                "mappings" => ShoppingfeedMappingDeliveryQuery::create()->find(),
                "missingMappings" => $this->getMissingMappings($deliveryService),
                'columnsDefinition' => $logService->defineColumnsDefinition(),
            ]
        );
    }

    private function getMissingMappings(MappingDeliveryService $deliveryService)
    {
        $missingMappings = ShoppingfeedLogQuery::create()
            ->filterByObjectType('Mapping')
            ->groupByObjectRef()
            ->find();

        $results = [];
        foreach ($missingMappings as $missingMapping) {

            $deliveryModuleId = $deliveryService->getDeliveryModuleIdFromCode($missingMapping->getObjectRef());
            if ($deliveryModuleId === 0) {
                $results[] = $missingMapping->getObjectRef();
            }
        }
        return $results;
    }

}