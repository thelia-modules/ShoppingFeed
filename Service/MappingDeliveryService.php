<?php

namespace ShoppingFeed\Service;

use ShoppingFeed\Model\ShoppingfeedMappingDeliveryQuery;
use ShoppingFeed\ShoppingFeed;

class MappingDeliveryService
{
    public function getDeliveryModuleIdFromCode($code)
    {

        if ($code === "") {
            $code = null;
        }
        $mappingDelivery = ShoppingfeedMappingDeliveryQuery::create()->findOneByCode($code);

        if ($mappingDelivery === null) {
            return 0;
        }

        return $mappingDelivery->getModuleId();
    }
}