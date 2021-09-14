<?php

namespace ShoppingFeed\Service;

use ShoppingFeed\Adapter\Guzzle7Adapter;
use ShoppingFeed\Model\ShoppingFeedConfig;
use ShoppingFeed\Sdk\Client\Client;
use ShoppingFeed\Sdk\Client\ClientOptions;
use ShoppingFeed\Sdk\Credential\Token;
use ShoppingFeed\Sdk\Http\Adapter\Guzzle6Adapter;
use Thelia\Core\Thelia;

class ApiService
{
    public function getFeedStore(ShoppingFeedConfig $feedConfig)
    {
        $customerAdapter = new Guzzle7Adapter();
        $options = new ClientOptions();
        $options->setHttpAdapter($customerAdapter);
        $options->setHandleRateLimit(false);
        $options->setPlatform('Thelia', Thelia::THELIA_VERSION);

        $credential = new Token($feedConfig->getApiToken());
        $session = Client::createSession($credential, $options);
        return $session->selectStore((int)$feedConfig->getStoreId());
    }

    public function request(ShoppingFeedConfig $feedConfig, $route, $body = [], $method = "GET", $options = [])
    {
        $client = new Client([
            'base_uri' => 'https://api.shopping-feed.com',
            'headers' => [
                'Authorization' => 'Bearer '.$feedConfig->getApiToken(),
                'Accept' => 'application/json',
                'Content-Type' => 'application/json'
            ]
        ]);

        if ($method !== "GET" && !empty($body)) {
            $options['body'] = $body;
        }

        $route = str_replace("{storeId}", $feedConfig->getStoreId(), $route);

        return json_decode($client->request($method, $route, $options)->getBody());
    }
}