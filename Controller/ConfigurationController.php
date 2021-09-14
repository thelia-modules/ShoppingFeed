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


class ConfigurationController extends BaseAdminController
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

    public function saveAction()
    {
        if (null !== $response = $this->checkAuth([AdminResources::MODULE], 'CreditAccount', AccessManager::VIEW)) {
            return $response;
        }

        $form = $this->createForm("shoppingfeed_configuration_form");

        try {
            $data = $this->validateForm($form)->getData();

            $excludeData = [
                'success_url',
                'error_url',
                'error_message',
            ];

            foreach ($data as $key => $value) {
                if (!in_array($key, $excludeData)) {
                    ShoppingFeed::setConfigValue($key, $value);
                }
            }
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
}