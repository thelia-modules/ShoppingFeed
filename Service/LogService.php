<?php

namespace ShoppingFeed\Service;

use Propel\Runtime\Propel;
use ShoppingFeed\Exception\ShoppingfeedException;
use ShoppingFeed\Model\Map\ShoppingfeedLogTableMap;
use ShoppingFeed\Model\Map\ShoppingfeedOrderDataTableMap;
use ShoppingFeed\Model\ShoppingfeedFeed;
use ShoppingFeed\Model\ShoppingfeedLog;
use ShoppingFeed\ShoppingFeed;
use Thelia\Core\Translation\Translator;
use Thelia\Model\Map\OrderTableMap;

class LogService
{
    const LEVEL_INFORMATION = 1;
    const LEVEL_SUCCESS = 2;
    const LEVEL_WARNING = 3;
    const LEVEL_ERROR = 4;
    const LEVEL_FATAL = 5;

    public function log($feed, $message, $level = LogService::LEVEL_INFORMATION, $objectId = null, $objectType = null, $objectRef = null, $help = '', $separation = 0)
    {
        $log = (new ShoppingfeedLog())
            ->setLevel($level)
            ->setFeedId($feed->getId())
            ->setObjectId($objectId)
            ->setObjectType($objectType)
            ->setObjectRef($objectRef)
            ->setMessage($message)
            ->setHelp($help)
            ->setSeparation($separation);

        $log->save();
    }

    public function logShoppingfeedException(ShoppingfeedException $exception)
    {
        $this->log(
            $exception->getFeed(),
            $exception->getMessage(),
            $exception->getLevel(),
            $exception->getObjectId(),
            $exception->getObjectType(),
            $exception->getObjectRef(),
            $exception->getHelp());
    }

    /**
     * @return array
     */
    public function defineColumnsDefinition()
    {
        $i = -1;
        return [
            [
                'name' => 'date',
                'targets' => ++$i,
                'orm' => ShoppingfeedLogTableMap::COL_CREATED_AT,
                'title' => Translator::getInstance()->trans('Date', [], ShoppingFeed::DOMAIN_NAME),
            ],
            [
                'name' => 'feed',
                'targets' => ++$i,
                'orderable' => false,
                'title' => Translator::getInstance()->trans('Feed', [], ShoppingFeed::DOMAIN_NAME),
            ],
            [
                'name' => 'channel',
                'targets' => ++$i,
                'orderable' => false,
                'orm' => ShoppingfeedOrderDataTableMap::COL_CHANNEL,
                'title' => Translator::getInstance()->trans('Channel', [], ShoppingFeed::DOMAIN_NAME),
            ],
            [
                'name' => 'level',
                'targets' => ++$i,
                'orm' => ShoppingfeedLogTableMap::COL_LEVEL,
                'title' => Translator::getInstance()->trans('Level', [], ShoppingFeed::DOMAIN_NAME),
            ],
            [
                'name' => 'message',
                'targets' => ++$i,
                'orm' => ShoppingfeedLogTableMap::COL_MESSAGE,
                'title' => Translator::getInstance()->trans('Message', [], ShoppingFeed::DOMAIN_NAME),
            ],
            [
                'name' => 'extra',
                'targets' => ++$i,
                'orderable' => false,
                'title' => Translator::getInstance()->trans('Extra', [], ShoppingFeed::DOMAIN_NAME),
            ],
        ];
    }
}