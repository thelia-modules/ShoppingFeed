<?php

namespace ShoppingFeed\Service;

use Propel\Runtime\ActiveQuery\Criteria;
use Propel\Runtime\Propel;
use ShoppingFeed\Event\OrderCreatedEvent;
use ShoppingFeed\Event\ShoppingFeedCustomerEvent;
use ShoppingFeed\Exception\ShoppingfeedException;
use ShoppingFeed\Model\ShoppingfeedCustomerTitleChannelQuery;
use ShoppingFeed\Model\ShoppingfeedFeed;
use ShoppingFeed\Model\ShoppingFeedOrderData;
use ShoppingFeed\Model\ShoppingfeedOrderDataQuery;
use ShoppingFeed\Sdk\Api\Order\OrderOperation;
use ShoppingFeed\ShoppingFeed;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Thelia\Core\Event\Customer\CustomerCreateOrUpdateEvent;
use Thelia\Core\Event\Customer\CustomerEvent;
use Thelia\Core\Event\Order\OrderEvent;
use Thelia\Core\Event\TheliaEvents;
use Thelia\Core\Translation\Translator;
use Thelia\Model\Address;
use Thelia\Model\CountryQuery;
use Thelia\Model\CurrencyQuery;
use Thelia\Model\Customer;
use Thelia\Model\CustomerQuery;
use Thelia\Model\CustomerTitle;
use Thelia\Model\CustomerTitleI18nQuery;
use Thelia\Model\CustomerTitleQuery;
use Thelia\Model\LangQuery;
use Thelia\Model\Map\OrderTableMap;
use Thelia\Model\Order;
use Thelia\Model\OrderAddress;
use Thelia\Model\OrderProduct;
use Thelia\Model\OrderProductAttributeCombination;
use Thelia\Model\OrderProductTax;
use Thelia\Model\OrderStatus;
use Thelia\Model\OrderStatusQuery;
use Thelia\Model\ProductSaleElementsQuery;
use Thelia\TaxEngine\Calculator;
use Thelia\Tools\I18n;

class OrderService
{
    protected $eventDispatcher;
    protected $apiService;
    protected $logger;
    protected $mappingDeliveryService;

    public function __construct(EventDispatcherInterface $eventDispatcher, ApiService $apiService, LogService $logger, MappingDeliveryService $mappingDeliveryService)
    {
        $this->eventDispatcher = $eventDispatcher;
        $this->apiService = $apiService;
        $this->logger = $logger;
        $this->mappingDeliveryService = $mappingDeliveryService;
    }

    public function importOrders(ShoppingfeedFeed $feed)
    {
        $orderApi = $this->apiService->getFeedStore($feed)->getOrderApi();
        $orders = $orderApi->getAll([
            'filters' => [
                'acknowledgment' => 'unacknowledged',
                'status' => ['waiting_shipment']
            ]]);

        $orderOperation = new OrderOperation();

        $paidStatus = OrderStatusQuery::create()->findOneByCode(OrderStatus::CODE_PAID);
        $nbImportedOrder = 0;

        foreach ($orders as $order) {
            try {
                $con = Propel::getConnection(
                    OrderTableMap::DATABASE_NAME
                );

                $con->beginTransaction();

                $shoppingFeedOrderData = ShoppingfeedOrderDataQuery::create()->filterByExternalReference($order->getReference())->findOne();
                if (null !== $shoppingFeedOrderData && !$shoppingFeedOrderData->getOrder()->getOrderStatus()->isCancelled()) {
                    throw new ShoppingfeedException(
                        $feed,
                        Translator::getInstance()->trans(
                            "This order has already been imported.",
                            [],
                            ShoppingFeed::DOMAIN_NAME
                        ),
                        Translator::getInstance()->trans(
                            "To import this command, cancel previous order (see link in extra)",
                            [],
                            ShoppingFeed::DOMAIN_NAME
                        ),
                        LogService::LEVEL_WARNING,
                        $shoppingFeedOrderData->getId(),
                        'Order',
                        $order->getReference()
                    );
                }

                $billingAddress = $order->getBillingAddress();
                $theliaInvoiceAddress = $this->createAddressFromData($billingAddress);
                $theliaInvoiceAddress->save($con);

                $shippingAddress = $order->getShippingAddress();
                $theliaDeliveryAddress = $this->createAddressFromData($shippingAddress);
                $theliaDeliveryAddress->save($con);

                $currency =  CurrencyQuery::create()
                    ->filterByCode($order->getPaymentInformation()['currency'])
                    ->findOne();

                $deliveryModuleId = $this->mappingDeliveryService->getDeliveryModuleIdFromCode($order->getShipment()["carrier"]);
                if ($deliveryModuleId === 0) {
                    throw new ShoppingfeedException(
                        $feed,
                        Translator::getInstance()->trans(
                            "This delivery code mapping does not exists.",
                            [],
                            ShoppingFeed::DOMAIN_NAME
                        ),
                        Translator::getInstance()->trans(
                            "To create this mapping, go to Mapping Delivery tab (see link in extra)",
                            [],
                            ShoppingFeed::DOMAIN_NAME
                        ),
                        LogService::LEVEL_ERROR,
                        null,
                        'Mapping',
                        $order->getShipment()["carrier"]
                    );
                }

                $customer = $this->createCustomerFromDeliveryAddress($theliaDeliveryAddress, $order->getChannel()->getName(), $feed, $order->getReference());

                $theliaOrder = (new Order())
                    ->setCustomer($customer)
                    ->setInvoiceOrderAddressId($theliaInvoiceAddress->getId())
                    ->setDeliveryOrderAddressId($theliaDeliveryAddress->getId())
                    ->setCurrencyId($currency->getId())
                    ->setPostage($order->getPaymentInformation()['shippingAmount'])
                    ->setPaymentModuleId(ShoppingFeed::getModuleId())
                    ->setDeliveryModuleId($deliveryModuleId)
                    ->setStatusId($paidStatus->getId())
                    ->setLangId($feed->getLangId());

                $theliaOrder->save($con);

                $shoppingFeedOrderData = (new ShoppingfeedOrderData())
                    ->setExternalReference($order->getReference())
                    ->setFeedId($feed->getId())
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
                    $product->setLocale($feed->getLang()->getLocale());

                    $taxRule = $product->getTaxRule();
                    $taxCalculator = new Calculator();
                    $taxCalculator->loadTaxRule($taxRule, $feed->getCountry(), $product);
                    if ($item->getTaxAmount() === 0) {
                        $untaxedPrice = $taxCalculator->getUntaxedPrice($item->getUnitPrice());
                        $taxAmount = $taxCalculator->getTaxAmountFromTaxedPrice($item->getUnitPrice());
                    } else {
                        $untaxedPrice = $item->getUnitPrice() - $item->getTaxAmount();
                        $taxAmount = $item->getTaxAmount();
                    }

                    $orderProduct = (new OrderProduct())
                        ->setOrderId($theliaOrder->getId())
                        // Data from thelia product
                        ->setTitle($product->getTitle())
                        ->setChapo($product->getChapo())
                        ->setDescription($product->getDescription())
                        ->setPostscriptum($product->getPostscriptum())
                        // Data from shopping feed
                        ->setQuantity($item->getQuantity())
                        ->setProductRef($product->getRef())
                        ->setProductSaleElementsRef($productSaleElements->getRef())
                        ->setEanCode($item->getReference())
                        ->setPrice($untaxedPrice);

                    $orderProduct->save($con);

                    $orderProductTax = (new OrderProductTax())
                        ->setOrderProductId($orderProduct->getId())
                        ->setTitle("")
                        ->setAmount($taxAmount);

                    $orderProductTax->save($con);

                    /* fulfill order_attribute_combination and decrease stock */
                    foreach ($productSaleElements->getAttributeCombinations() as $attributeCombination) {
                        /** @var \Thelia\Model\Attribute $attribute */
                        $attribute = I18n::forceI18nRetrieving($feed->getLang()->getLocale(), 'Attribute', $attributeCombination->getAttributeId());

                        /** @var \Thelia\Model\AttributeAv $attributeAv */
                        $attributeAv = I18n::forceI18nRetrieving($feed->getLang()->getLocale(), 'AttributeAv', $attributeCombination->getAttributeAvId());

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
                $orderUpdateEvent = new OrderEvent($theliaOrder);
                $orderUpdateEvent->setStatus($theliaOrder->getStatusId());
                $this->eventDispatcher->dispatch($orderUpdateEvent, TheliaEvents::ORDER_PRODUCT_AFTER_CREATE);
                $this->eventDispatcher->dispatch($orderUpdateEvent, OrderCreatedEvent::SHOPPINGFEED_ORDER_CREATED);
                $orderOperation->acknowledge($order->getReference(), $order->getChannel()->getName(), $theliaOrder->getRef());
                $nbImportedOrder++;
            } catch (ShoppingfeedException $shoppingfeedException) {
                $con->rollBack();
                $this->logger->logShoppingfeedException($shoppingfeedException);
            } catch (\Exception $exception) {
                $con->rollBack();
                $this->logger->log(
                    $exception->getMessage(),
                    LogService::LEVEL_ERROR,
                    $feed,
                    null,
                    'Order',
                    $order->getReference()
                );
            }
        }
        $orderApi->execute($orderOperation);
        if ($nbImportedOrder > 0) {
            $this->logger->log(
                $nbImportedOrder.' order(s) have been imported successfully.',
                LogService::LEVEL_SUCCESS,
                $feed
            );
        }
    }

    protected function createAddressFromData($data)
    {
        return (new OrderAddress())
            ->setCustomerTitle(CustomerTitleQuery::create()->findOne())
            ->setFirstname($data['firstName'])
            ->setLastname($data['lastName'])
            ->setCompany($data['company'])
            ->setAddress1($data['street'])
            ->setAddress2($data['street2'])
            ->setAddress3($data['other'])
            ->setZipcode($data['postalCode'])
            ->setCountry($this->getCountryByIsoAlpha2($data['country']))
            ->setCity($data['city'])
            ->setPhone($data['phone'])
            ->setCellphone($data['mobilePhone']);
    }

    public function getCountryByIsoAlpha2($isoAlpha2)
    {
        $country = CountryQuery::create()->filterByIsoalpha2($isoAlpha2)->findOne();
        if (null === $country) {
            $country =  CountryQuery::create()->filterByIsoalpha2('FR')->findOne();
        }
        return $country;
    }

    protected function createCustomerFromDeliveryAddress(OrderAddress $deliveryAddress, $channel, $feed, $orderRef)
    {
        if ($deliveryAddress->getFirstname() == "") {
            throw new ShoppingfeedException(
                $feed,
                Translator::getInstance()->trans("Customer do not have a firstname.", [], ShoppingFeed::DOMAIN_NAME),
                Translator::getInstance()->trans("You can check if this data is empty on shopping feed back-office", [], ShoppingFeed::DOMAIN_NAME),
                LogService::LEVEL_ERROR,
                null,
                'Order',
                $orderRef
            );
        }
        if ($deliveryAddress->getLastname() == "") {
            throw new ShoppingfeedException(
                $feed,
                Translator::getInstance()->trans("Customer do not have a lastname.", [], ShoppingFeed::DOMAIN_NAME),
                Translator::getInstance()->trans("You can check if this data is empty on shopping feed back-office", [], ShoppingFeed::DOMAIN_NAME),
                LogService::LEVEL_ERROR,
                null,
                'Order',
                $orderRef
            );
        }
        $email = $deliveryAddress->getFirstname().'.'.$deliveryAddress->getLastname().'-SF'.$deliveryAddress->getCellphone().$deliveryAddress->getPhone().'@'.$channel.'.net';
        $customer = CustomerQuery::create()
            ->filterByEmail($email)
            ->findOne();

        if (null !== $customer) {
            return $customer;
        }

        $lang = LangQuery::create()
            ->filterByByDefault(true)
            ->findOne();

        $customerTitle = CustomerTitleQuery::create()
            ->filterByByDefault(true)
            ->findOne();

        $customerEvent = new CustomerCreateOrUpdateEvent(
            $customerTitle->getId(),
            $deliveryAddress->getFirstname(),
            $deliveryAddress->getLastname(),
            $deliveryAddress->getAddress1(),
            $deliveryAddress->getAddress2(),
            $deliveryAddress->getAddress3(),
            $deliveryAddress->getPhone(),
            $deliveryAddress->getCellphone(),
            $deliveryAddress->getZipcode(),
            $deliveryAddress->getCity(),
            $deliveryAddress->getCountry()->getId(),
            $email,
            md5($email),
            $lang->getId()
        );

        $this->saveCustomer($customerEvent);

        $customer = $customerEvent->getCustomer();

        $customer->getDefaultAddress()
            ->setCompany($deliveryAddress->getCompany())
            ->setLabel($channel)
            ->save();

        return $customer;
    }

    /**
     * @param CustomerCreateOrUpdateEvent $customerEvent
     * @return CustomerCreateOrUpdateEvent
     * @throws \Propel\Runtime\Exception\PropelException
     */
    protected function saveCustomer(CustomerCreateOrUpdateEvent $customerEvent)
    {
        $customer = new Customer();

        //need to copy this to create customer from command and shunt Recaptcha check event
        $customer->createOrUpdate(
            $customerEvent->getTitle(),
            $customerEvent->getFirstname(),
            $customerEvent->getLastname(),
            $customerEvent->getAddress1(),
            $customerEvent->getAddress2(),
            $customerEvent->getAddress3(),
            $customerEvent->getPhone(),
            $customerEvent->getCellphone(),
            $customerEvent->getZipcode(),
            $customerEvent->getCity(),
            $customerEvent->getCountry(),
            $customerEvent->getEmail(),
            $customerEvent->getPassword(),
            $customerEvent->getLangId(),
            $customerEvent->getReseller(),
            $customerEvent->getSponsor(),
            $customerEvent->getDiscount(),
            $customerEvent->getCompany(),
            $customerEvent->getRef(),
            $customerEvent->getEmailUpdateAllowed(),
            $customerEvent->getState()
        );

        $customerEvent->setCustomer($customer);

        return $customerEvent;
    }
}