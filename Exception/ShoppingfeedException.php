<?php

namespace ShoppingFeed\Exception;

use ShoppingFeed\Model\ShoppingfeedFeed;
use ShoppingFeed\Service\LogService;
use Throwable;


class ShoppingfeedException extends \Exception
{
    /** @var ShoppingfeedFeed $feed */
    protected $feed;

    /** @var string $help */
    protected $help;

    /** @var int $level */
    protected $level;

    /** @var int $objectId */
    protected $objectId;

    /** @var string $objectType */
    protected $objectType;

    /** @var string $objectRef */
    protected $objectRef;

    public function __construct(ShoppingfeedFeed $feed, $message = "", $help = "", $level = LogService::LEVEL_INFORMATION, $objectId = null, $objectType = null, $objectRef = null, $code = 0, Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
        $this->setFeed($feed);
        $this->setHelp($help);
        $this->setLevel($level);
        $this->setObjectId($objectId);
        $this->setObjectType($objectType);
        $this->setObjectRef($objectRef);
    }

    /**
     * @return int
     */
    public function getObjectId(): int
    {
        return $this->objectId;
    }

    /**
     * @param int $objectId
     * @return ShoppingfeedException
     */
    public function setObjectId(int $objectId): ShoppingfeedException
    {
        $this->objectId = $objectId;
        return $this;
    }

    /**
     * @return string
     */
    public function getObjectType(): string
    {
        return $this->objectType;
    }

    /**
     * @param string $objectType
     * @return ShoppingfeedException
     */
    public function setObjectType(string $objectType): ShoppingfeedException
    {
        $this->objectType = $objectType;
        return $this;
    }

    /**
     * @return string
     */
    public function getObjectRef(): string
    {
        return $this->objectRef;
    }

    /**
     * @param string $objectRef
     * @return ShoppingfeedException
     */
    public function setObjectRef(string $objectRef): ShoppingfeedException
    {
        $this->objectRef = $objectRef;
        return $this;
    }

    /**
     * @return ShoppingfeedFeed
     */
    public function getFeed(): ShoppingfeedFeed
    {
        return $this->feed;
    }

    /**
     * @param ShoppingfeedFeed $feed
     * @return ShoppingfeedException
     */
    public function setFeed(ShoppingfeedFeed $feed): ShoppingfeedException
    {
        $this->feed = $feed;
        return $this;
    }

    /**
     * @return string
     */
    public function getHelp(): string
    {
        return $this->help;
    }

    /**
     * @param string $help
     * @return ShoppingfeedException
     */
    public function setHelp(string $help): ShoppingfeedException
    {
        $this->help = $help;
        return $this;
    }

    /**
     * @return int
     */
    public function getLevel(): int
    {
        return $this->level;
    }

    /**
     * @param int $level
     * @return ShoppingfeedException
     */
    public function setLevel(int $level): ShoppingfeedException
    {
        $this->level = $level;
        return $this;
    }
}