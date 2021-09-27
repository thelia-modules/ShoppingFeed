<?php

namespace ShoppingFeed\Model;

use ShoppingFeed\Model\Base\ShoppingfeedLog as BaseShoppingfeedLog;
use ShoppingFeed\Service\LogService;
use ShoppingFeed\ShoppingFeed;
use Thelia\Core\Translation\Translator;

/**
 * Skeleton subclass for representing a row from the 'shoppingfeed_log' table.
 *
 *
 *
 * You should add additional methods to this class to meet the
 * application requirements.  This class will only be generated as
 * long as it does not already exist in the output directory.
 *
 */
class ShoppingfeedLog extends BaseShoppingfeedLog
{
    /**
     * Get the [level] column value.
     *
     * @return int
     */
    public function getLevelText()
    {
        if ($this->level === LogService::LEVEL_INFORMATION) {
            return 'Info';
        }
        if ($this->level === LogService::LEVEL_SUCCESS) {
            return 'Success';
        }
        if ($this->level === LogService::LEVEL_WARNING) {
            return 'Warning';
        }
        if ($this->level === LogService::LEVEL_ERROR) {
            return 'Error';
        }
        if ($this->level === LogService::LEVEL_FATAL) {
            return 'Fatal';
        }
        return $this->level;
    }
}
