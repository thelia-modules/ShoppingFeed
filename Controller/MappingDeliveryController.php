<?php

namespace ShoppingFeed\Controller;

use Propel\Runtime\Map\TableMap;
use ShoppingFeed\Model\ShoppingfeedFeed;
use ShoppingFeed\Model\ShoppingfeedFeedQuery;
use ShoppingFeed\Model\ShoppingfeedMappingDelivery;
use ShoppingFeed\Model\ShoppingfeedMappingDeliveryQuery;
use ShoppingFeed\Service\LogService;
use ShoppingFeed\Service\OrderService;
use ShoppingFeed\ShoppingFeed;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Thelia\Controller\Admin\BaseAdminController;
use Thelia\Core\Security\AccessManager;
use Thelia\Core\Security\Resource\AdminResources;
use Thelia\Core\Translation\Translator;
use Thelia\Tools\URL;


class MappingDeliveryController extends BaseAdminController
{
    public function createAction()
    {
        if (null !== $response = $this->checkAuth([AdminResources::MODULE], ShoppingFeed::getModuleCode(), AccessManager::VIEW)) {
            return $response;
        }

        $form = $this->createForm("shoppingfeed_mapping_delivery_form");

        try {
            $data = $this->validateForm($form)->getData();

            $mappingDelivery = (new ShoppingfeedMappingDelivery())
                ->setCode($data['code'])
                ->setModuleId($data['module_id']);

            $mappingDelivery->save();
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
            return $this->generateRedirect(URL::getInstance()->absoluteUrl('/admin/module/ShoppingFeed?current_tab=mapping'));
        }

        return $this->generateSuccessRedirect($form);
    }

    public function updateAction($mappingId)
    {
        if (null !== $response = $this->checkAuth([AdminResources::MODULE], ShoppingFeed::getModuleCode(), AccessManager::VIEW)) {
            return $response;
        }

        $form = $this->createForm("shoppingfeed_mapping_delivery_form");

        try {
            $data = $this->validateForm($form)->getData();

            $mappingDelivery = ShoppingfeedMappingDeliveryQuery::create()
                ->filterById($mappingId)
                ->findOne();

            $mappingDelivery
                ->setCode($data['code'])
                ->setModuleId($data['module_id']);

            $mappingDelivery->save();
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
            return $this->generateRedirect(URL::getInstance()->absoluteUrl('/admin/module/ShoppingFeed?current_tab=mapping'));
        }

        return $this->generateSuccessRedirect($form);
    }

    public function deleteAction($mappingId)
    {
        if (null !== $response = $this->checkAuth([AdminResources::MODULE], ShoppingFeed::getModuleCode(), AccessManager::VIEW)) {
            return $response;
        }

        try {
            $mappingDelivery = ShoppingfeedMappingDeliveryQuery::create()
                ->filterById($mappingId)
                ->findOne();

            $mappingDelivery->delete();
        } catch (\Exception $e) {
            return $this->generateRedirect(URL::getInstance()->absoluteUrl('/admin/module/ShoppingFeed?current_tab=mapping'));
        }

        return new RedirectResponse(URL::getInstance()->absoluteUrl("/admin/module/ShoppingFeed?current_tab=mapping"));
    }
}
