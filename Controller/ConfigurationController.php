<?php

namespace ShoppingFeed\Controller;

use ShoppingFeed\ShoppingFeed;
use Thelia\Controller\Admin\BaseAdminController;
use Thelia\Core\Security\AccessManager;
use Thelia\Core\Security\Resource\AdminResources;
use Thelia\Core\Translation\Translator;


class ConfigurationController extends BaseAdminController
{
    public function viewAction()
    {
        return $this->render(
            "shoppingfeed/configuration",
            [

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
