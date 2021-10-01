<?php

namespace ShoppingFeed\EventListener;

use ShoppingFeed\Exception\ShoppingfeedException;
use ShoppingFeed\Model\ShoppingfeedFeedQuery;
use ShoppingFeed\Model\ShoppingfeedMappingDeliveryQuery;
use ShoppingFeed\Model\ShoppingfeedOrderDataQuery;
use ShoppingFeed\Sdk\Api\Catalog\InventoryUpdate;
use ShoppingFeed\Sdk\Api\Catalog\PricingUpdate;
use ShoppingFeed\Sdk\Api\Order\OrderOperation;
use ShoppingFeed\Service\ApiService;
use ShoppingFeed\Service\LogService;
use ShoppingFeed\ShoppingFeed;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Thelia\Core\Event\Order\OrderEvent;
use Thelia\Core\Event\ProductSaleElement\ProductSaleElementUpdateEvent;
use Thelia\Core\Event\TheliaEvents;
use Thelia\Core\Translation\Translator;
use Thelia\Model\OrderStatus;
use Thelia\TaxEngine\Calculator;

class PriceAndStockUpdateListener implements EventSubscriberInterface
{
    /** @var ApiService  */
    protected $apiService;

    /** @var LogService  */
    protected $logger;

    public function __construct(ApiService $apiService, LogService $logService)
    {
        $this->apiService = $apiService;
        $this->logger = $logService;
    }

    public static function getSubscribedEvents()
    {
        return [
            TheliaEvents::PRODUCT_UPDATE_PRODUCT_SALE_ELEMENT => ['onPseUpdate', 64]
        ];
    }

    public function onPseUpdate(ProductSaleElementUpdateEvent $event)
    {
        $feeds = ShoppingfeedFeedQuery::create()->find();
        $product = $event->getProduct();
        $pseRef = $event->getReference();

        try {
            foreach ($feeds as $feed) {
                $taxRule = $product->getTaxRule();
                $taxCalculator = new Calculator();
                $taxCalculator->loadTaxRule($taxRule, $feed->getCountry(), $product);

                $pricingApi = $this->apiService->getFeedStore($feed)->getPricingApi();

                $pricingUpdate = new PricingUpdate();
                $pricingUpdate->add($event->getReference(), $taxCalculator->getTaxedPrice($event->getPrice()));
                $editedItemCollection = $pricingApi->execute($pricingUpdate);

                if ($editedItemCollection->count() === 0) {
                    throw new ShoppingfeedException(
                        $feed,
                "This ref ".$pseRef." wasn't fount in ShoppingFeed catalog.",
                        Translator::getInstance()->trans(
                            "Maybe this pse isn't created in ShoppingFeed, regenerate feed to be sure.",
                            [],
                            ShoppingFeed::DOMAIN_NAME
                        ),
                        LogService::LEVEL_WARNING,
                        $event->getProductSaleElementId(),
                        'PSE',
                        $product->getId()
                    );
                }

                $inventoryApi = $this->apiService->getFeedStore($feed)->getInventoryApi();

                $inventoryUpdate = new InventoryUpdate();
                $inventoryUpdate->add($event->getReference(), $event->getQuantity());

                $editedItemCollection = $inventoryApi->execute($inventoryUpdate);

                if ($editedItemCollection->count() === 0) {
                    throw new ShoppingfeedException(
                        $feed,
                "This ref ".$pseRef." wasn't fount in ShoppingFeed inventory (during stock edition).",
                        Translator::getInstance()->trans(
                            "This ref was found for price edition but not for stock edition. Please contact ShoppingFeed.",
                            [],
                            ShoppingFeed::DOMAIN_NAME
                        ),
                        LogService::LEVEL_WARNING,
                        $event->getProductSaleElementId(),
                        'PSE',
                        $product->getId()
                    );
                }
            }

        } catch (ShoppingfeedException $shoppingfeedException) {
            $this->logger->logShoppingfeedException($shoppingfeedException);
        } catch (\Exception $exception) {
            $this->logger->log(
                $exception->getMessage(),
                LogService::LEVEL_ERROR,
                $feed,
                $event->getProductSaleElementId(),
                "PSE",
                $product->getId(),
                'Error sent when updating quantity or price on pse. Link to PSE in Extra'
            );
        }
    }
}