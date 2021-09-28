<?php

namespace ShoppingFeed\Service;

use ShoppingFeed\Model\ShoppingfeedMappingDeliveryQuery;
use ShoppingFeed\ShoppingFeed;

class MappingDeliveryService
{
    public function getDeliveryModuleIdFromCode($code)
    {
        if ($code === "") {
            return ShoppingFeed::getModuleId();
        }
        $mappingDeliveries = ShoppingfeedMappingDeliveryQuery::create()->find();

        foreach ($mappingDeliveries as $mappingDelivery) {
            if ($mappingDelivery->getCode() === $code) {
                return $mappingDelivery->getModuleId();
            }
        }
        return 0;
    }
}