<?php

namespace ShoppingFeed\Controller;

use Propel\Runtime\Map\TableMap;
use ShoppingFeed\Model\ShoppingFeedConfig;
use ShoppingFeed\Model\ShoppingFeedConfigQuery;
use ShoppingFeed\Service\OrderService;
use ShoppingFeed\ShoppingFeed;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Thelia\Controller\Admin\BaseAdminController;
use Thelia\Core\Security\AccessManager;
use Thelia\Core\Security\Resource\AdminResources;
use Thelia\Core\Translation\Translator;
use Thelia\Tools\URL;


class FeedConfigurationController extends BaseAdminController
{
    public function testAction()
    {
        /** @var OrderService $orderService */
        $orderService = $this->getContainer()->get("shopping_feed_order_service");

        $configs = ShoppingFeedConfigQuery::create()->find();

        foreach ($configs as $config) {
            $orderService->importOrders($config);
        }
    }

    public function viewAction()
    {
        return $this->render(
            "shoppingfeed/configuration",
            [
                "configs" => ShoppingFeedConfigQuery::create()->find()
            ]
        );
    }

    public function createAction()
    {
        if (null !== $response = $this->checkAuth([AdminResources::MODULE], ShoppingFeed::getModuleCode(), AccessManager::VIEW)) {
            return $response;
        }

        $form = $this->createForm("shoppingfeed_configuration_form");

        try {
            $data = $this->validateForm($form)->getData();

            $shoppinfFeedConfig = (new ShoppingFeedConfig())
                ->setCountryId($data['country_id'])
                ->setLangId($data['lang_id'])
                ->setStoreId($data['store_id'])
                ->setApiToken($data['api_token']);

            $shoppinfFeedConfig->save();
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

    public function updateAction($configId)
    {
        if (null !== $response = $this->checkAuth([AdminResources::MODULE], ShoppingFeed::getModuleCode(), AccessManager::VIEW)) {
            return $response;
        }

        $form = $this->createForm("shoppingfeed_configuration_form");

        try {
            $data = $this->validateForm($form)->getData();

            $shoppinfFeedConfig = ShoppingFeedConfigQuery::create()
                ->filterById($configId)
                ->findOne();

            $shoppinfFeedConfig
                ->setCountryId($data['country_id'])
                ->setLangId($data['lang_id'])
                ->setStoreId($data['store_id'])
                ->setApiToken($data['api_token']);

            $shoppinfFeedConfig->save();
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

    public function deleteAction($configId)
    {
        if (null !== $response = $this->checkAuth([AdminResources::MODULE], ShoppingFeed::getModuleCode(), AccessManager::VIEW)) {
            return $response;
        }

        try {
            $shoppinfFeedConfig = ShoppingFeedConfigQuery::create()
                ->filterById($configId)
                ->findOne();

            $shoppinfFeedConfig->delete();
        } catch (\Exception $e) {
            return $this->viewAction();
        }

        return new RedirectResponse(URL::getInstance()->absoluteUrl("/admin/module/ShoppingFeed"));
    }
}
