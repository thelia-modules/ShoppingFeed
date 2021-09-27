<?php

namespace ShoppingFeed\Controller;

use Propel\Runtime\Map\TableMap;
use ShoppingFeed\Model\ShoppingfeedFeed;
use ShoppingFeed\Model\ShoppingfeedFeedQuery;
use ShoppingFeed\Service\LogService;
use ShoppingFeed\Service\OrderService;
use ShoppingFeed\ShoppingFeed;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Thelia\Controller\Admin\BaseAdminController;
use Thelia\Core\Security\AccessManager;
use Thelia\Core\Security\Resource\AdminResources;
use Thelia\Core\Translation\Translator;
use Thelia\Tools\URL;


class FeedController extends BaseAdminController
{
    public function testAction()
    {
        /** @var OrderService $orderService */
        $orderService = $this->getContainer()->get("shopping_feed_order_service");

        $configs = ShoppingfeedFeedQuery::create()->find();

        foreach ($configs as $config) {
            $orderService->importOrders($config);
        }
    }

    public function createAction()
    {
        if (null !== $response = $this->checkAuth([AdminResources::MODULE], ShoppingFeed::getModuleCode(), AccessManager::VIEW)) {
            return $response;
        }

        $form = $this->createForm("shoppingfeed_feed_form");

        try {
            $data = $this->validateForm($form)->getData();

            $feed = (new ShoppingfeedFeed())
                ->setCountryId($data['country_id'])
                ->setLangId($data['lang_id'])
                ->setStoreId($data['store_id'])
                ->setApiToken($data['api_token']);

            $feed->save();
        } catch (\Exception $e) {
            $this->setupFormErrorContext(
                Translator::getInstance()->trans(
                    "Error",
                    [],
                    ShoppingFeed::DOMAIN_NAME
                ),
                $e->getMessage(),
                $form
            );
            return $this->viewAction();
        }

        return $this->generateSuccessRedirect($form);
    }

    public function updateAction($feedId)
    {
        if (null !== $response = $this->checkAuth([AdminResources::MODULE], ShoppingFeed::getModuleCode(), AccessManager::VIEW)) {
            return $response;
        }

        $form = $this->createForm("shoppingfeed_feed_form");

        try {
            $data = $this->validateForm($form)->getData();

            $feed = ShoppingfeedFeedQuery::create()
                ->filterById($feedId)
                ->findOne();

            $feed
                ->setCountryId($data['country_id'])
                ->setLangId($data['lang_id'])
                ->setStoreId($data['store_id'])
                ->setApiToken($data['api_token']);

            $feed->save();
        } catch (\Exception $e) {
            $this->setupFormErrorContext(
                Translator::getInstance()->trans(
                    "Error",
                    [],
                    ShoppingFeed::DOMAIN_NAME
                ),
                $e->getMessage(),
                $form
            );
            return $this->viewAction();
        }

        return $this->generateSuccessRedirect($form);
    }

    public function deleteAction($feedId)
    {
        if (null !== $response = $this->checkAuth([AdminResources::MODULE], ShoppingFeed::getModuleCode(), AccessManager::VIEW)) {
            return $response;
        }

        try {
            $feed = ShoppingfeedFeedQuery::create()
                ->filterById($feedId)
                ->findOne();

            $feed->delete();
        } catch (\Exception $e) {
            return $this->viewAction();
        }

        return new RedirectResponse(URL::getInstance()->absoluteUrl("/admin/module/ShoppingFeed"));
    }
}
