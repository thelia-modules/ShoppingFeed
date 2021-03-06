<?php

namespace ShoppingFeed\Service;

use ShoppingFeed\Adapter\Guzzle7Adapter;
use ShoppingFeed\Model\ShoppingfeedFeed;
use ShoppingFeed\Sdk\Client\Client;
use ShoppingFeed\Sdk\Client\ClientOptions;
use ShoppingFeed\Sdk\Credential\Token;
use ShoppingFeed\Sdk\Http\Adapter\Guzzle6Adapter;
use Thelia\Core\Thelia;
use Thelia\Model\Order;

class ApiService
{
    public function getFeedStore(ShoppingfeedFeed $feed)
    {
        $customerAdapter = new Guzzle7Adapter();
        $options = new ClientOptions();
        $options->setHttpAdapter($customerAdapter);
        $options->setHandleRateLimit(false);
        $options->setPlatform('Thelia', Thelia::THELIA_VERSION);

        $credential = new Token($feed->getApiToken());
        $session = Client::createSession($credential, $options);
        return $session->selectStore((int)$feed->getStoreId());
    }
}