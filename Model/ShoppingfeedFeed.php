<?php

namespace ShoppingFeed\Model;

use ShoppingFeed\Model\Base\ShoppingfeedFeed as BaseShoppingfeedFeed;

/**
 * Skeleton subclass for representing a row from the 'shoppingfeed_feed' table.
 *
 *
 *
 * You should add additional methods to this class to meet the
 * application requirements.  This class will only be generated as
 * long as it does not already exist in the output directory.
 *
 */
class ShoppingfeedFeed extends BaseShoppingfeedFeed
{
    public function getFeedFilePrefix()
    {
        return $this->getCountryId()."_".$this->getLang()->getLocale();
    }

    public function isDeletable()
    {
        $orders = ShoppingfeedOrderDataQuery::create()->filterByFeedId($this->getId())->find();
        return ($orders->count() === 0);
    }
}
