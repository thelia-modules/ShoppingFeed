<?php

namespace ShoppingFeed\Service;

use Propel\Runtime\Propel;
use ShoppingFeed\Model\ShoppingFeedConfig;
use ShoppingFeed\Model\ShoppingFeedOrderData;
use ShoppingFeed\ShoppingFeed;
use Thelia\Model\CurrencyQuery;
use Thelia\Model\Map\OrderTableMap;
use Thelia\Model\Order;
use Thelia\Model\OrderAddress;
use Thelia\Model\OrderProduct;
use Thelia\Model\OrderProductAttributeCombination;
use Thelia\Model\OrderProductTax;
use Thelia\Model\OrderStatus;
use Thelia\Model\OrderStatusQuery;
use Thelia\Model\ProductSaleElementsQuery;
use Thelia\Model\TaxRuleI18n;
use Thelia\Tools\I18n;

class OrderService
{
    protected $apiService;

    public function __construct(ApiService $apiService)
    {
        $this->apiService = $apiService;
    }

    public function importOrders(ShoppingFeedConfig $feedConfig)
    {
        $orderApi = $this->apiService->getFeedStore($feedConfig)->getOrderApi();
        $orders = $orderApi->getAll(['filters' => ['acknowledgment' => 'unacknowledged']]);
        $customer = ShoppingFeed::getSoppingFeedCustomer();

        $paidStatus = OrderStatusQuery::create()->findOneByCode(OrderStatus::CODE_PAID);

        foreach ($orders as $order) {
            $con = Propel::getConnection(
                OrderTableMap::DATABASE_NAME
            );

            $con->beginTransaction();

            $billingAddress = $order->getBillingAddress();
            $theliaInvoiceAddress = $this->createAddressFromData($billingAddress)
                ->setCountryId($feedConfig->getCountryId());
            $theliaInvoiceAddress->save($con);

            $shippingAddress = $order->getShippingAddress();
            $theliaDeliveryAddress = $this->createAddressFromData($shippingAddress)
                ->setCountryId($feedConfig->getCountryId());
            $theliaDeliveryAddress->save($con);

            $currency =  CurrencyQuery::create()
                ->filterByCode($order->getPaymentInformation()['currency'])
                ->findOne();

            $theliaOrder = (new Order())
                ->setCustomer($customer)
                ->setInvoiceOrderAddressId($theliaInvoiceAddress->getId())
                ->setDeliveryOrderAddressId($theliaDeliveryAddress->getId())
                ->setCurrencyId($currency->getId())
                ->setPostage($order->getPaymentInformation()['shippingAmount'])
                ->setPaymentModuleId(ShoppingFeed::getModuleId())
                ->setDeliveryModuleId(ShoppingFeed::getModuleId())
                ->setStatusId($paidStatus->getId())
                ->setLangId($feedConfig->getLangId());

            $theliaOrder->save($con);

            $shoppingFeedOrderData = (new ShoppingFeedOrderData())
                ->setExternalReference($order->getReference())
                ->setId($theliaOrder->getId())
                ->setChannel($order->getChannel()->getName())
                ->setEmail($billingAddress['email']);

            $shoppingFeedOrderData->save($con);

            foreach ($order->getItems() as $item) {
                $productSaleElements = ProductSaleElementsQuery::create()
                    ->filterByRef($item->getReference())
                    ->_or()
                    ->filterByEanCode($item->getReference())
                    ->_or()
                    ->useProductQuery()
                        ->filterByRef($item->getReference())
                    ->endUse()
                    ->findOne();

                $product = $productSaleElements->getProduct();
                $product->setLocale($feedConfig->getLang()->getLocale());

                $orderProduct = (new OrderProduct())
                    ->setOrderId($theliaOrder->getId())
                    // Data from thelia product
                    ->setTitle($product->getTitle())
                    ->setChapo($product->getChapo())
                    ->setDescription($product->getDescription())
                    ->setPostscriptum($product->getPostscriptum())
                    // Data from shopping feed
                    ->setQuantity($item->getQuantity())
                    ->setProductRef($item->getReference())
                    ->setProductSaleElementsRef($item->getReference())
                    ->setEanCode($item->getReference())
                    ->setPrice($item->getUnitPrice());

                $orderProduct->save($con);

                $orderProductTax = (new OrderProductTax())
                    ->setOrderProductId($orderProduct->getId())
                    ->setTitle("")
                    ->setAmount($item->getTaxAmount());

                $orderProductTax->save($con);

                /* fulfill order_attribute_combination and decrease stock */
                foreach ($productSaleElements->getAttributeCombinations() as $attributeCombination) {
                    /** @var \Thelia\Model\Attribute $attribute */
                    $attribute = I18n::forceI18nRetrieving($feedConfig->getLang()->getLocale(), 'Attribute', $attributeCombination->getAttributeId());

                    /** @var \Thelia\Model\AttributeAv $attributeAv */
                    $attributeAv = I18n::forceI18nRetrieving($feedConfig->getLang()->getLocale(), 'AttributeAv', $attributeCombination->getAttributeAvId());

                    $orderAttributeCombination = new OrderProductAttributeCombination();
                    $orderAttributeCombination
                        ->setOrderProductId($orderProduct->getId())
                        ->setAttributeTitle($attribute->getTitle())
                        ->setAttributeChapo($attribute->getChapo())
                        ->setAttributeDescription($attribute->getDescription())
                        ->setAttributePostscriptum($attribute->getPostscriptum())
                        ->setAttributeAvTitle($attributeAv->getTitle())
                        ->setAttributeAvChapo($attributeAv->getChapo())
                        ->setAttributeAvDescription($attributeAv->getDescription())
                        ->setAttributeAvPostscriptum($attributeAv->getPostscriptum())
                        ->save($con);
                }
            }
            $con->commit();
        }
    }

    protected function createAddressFromData($data)
    {
        return (new OrderAddress())
            ->setFirstname($data['firstName'])
            ->setLastname($data['lastName'])
            ->setCompany($data['company'])
            ->setAddress1($data['street'])
            ->setAddress2($data['street2'])
            ->setAddress3($data['other'])
            ->setZipcode($data['postalCode'])
            ->setCity($data['city'])
            ->setPhone($data['phone'])
            ->setCellphone($data['mobilePhone']);
    }

    public function _importOrders(ShoppingFeedConfig $feedConfig, $url = "/v1/store/{storeId}/order?acknowledgement=unacknowledged&limit=2")
    {
        $orderListResponse = $this->apiService->request($feedConfig, $url);
        $shoppingFeedCustomer = ShoppingFeed::getSoppingFeedCustomer();

        foreach ($orderListResponse->_embedded->order as $order) {
            $theliaOrder = (new Order());
        }

        if (property_exists($orderListResponse->_links, 'next')) {
            $this->importOrders($feedConfig, $orderListResponse->_links->next->href);
        }
    }
}