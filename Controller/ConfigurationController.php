<?php

namespace ShoppingFeed\Controller;

use ShoppingFeed\Model\ShoppingfeedFeedQuery;
use Thelia\Controller\Admin\BaseAdminController;

class ConfigurationController extends BaseAdminController
{
    public function viewAction()
    {
        return $this->render(
            "shoppingfeed/configuration",
            [
                "feeds" => ShoppingfeedFeedQuery::create()->find(),
                'columnsDefinition' => $this->getContainer()->get('shopping_feed_log_service')->defineColumnsDefinition(),
            ]
        );
    }
}