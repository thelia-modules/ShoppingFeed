<?php

namespace ShoppingFeed\Model;

use ShoppingFeed\Model\Base\ShoppingFeedConfig as BaseShoppingFeedConfig;

/**
 * Skeleton subclass for representing a row from the 'shopping_feed_config' table.
 *
 *
 *
 * You should add additional methods to this class to meet the
 * application requirements.  This class will only be generated as
 * long as it does not already exist in the output directory.
 *
 */
class ShoppingFeedConfig extends BaseShoppingFeedConfig
{
    public function getFeedFilePrefix()
    {
        return $this->getCountryId()."_".$this->getLang()->getLocale();
    }
}
