<?php

namespace ShoppingFeed\Service;

use ShoppingFeed\Event\FeedProductExtraFieldEvent;
use ShoppingFeed\Event\FeedPseExtraFieldEvent;
use ShoppingFeed\Feed\Product\Product;
use ShoppingFeed\Feed\ProductFeedResult;
use ShoppingFeed\Feed\ProductGenerator;
use ShoppingFeed\Model\ShoppingfeedFeed;
use ShoppingFeed\Model\ShoppingfeedPseMarketplaceQuery;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Thelia\Core\Event\Image\ImageEvent;
use Thelia\Core\Event\TheliaEvents;
use Thelia\Core\Thelia;
use Thelia\Model\Base\CategoryQuery;
use Thelia\Model\ConfigQuery;
use Thelia\Model\Map\ProductPriceTableMap;
use Thelia\Model\ProductImage;
use Thelia\Model\ProductQuery;
use Thelia\Model\ProductSaleElementsQuery;
use Thelia\Tools\URL;

class FeedService
{
    protected $eventDispatcher;
    protected $logger;

    public function __construct(EventDispatcherInterface $eventDispatcher, LogService $logger)
    {
        $this->eventDispatcher = $eventDispatcher;
        $this->logger = $logger;
    }

    /**
     * @return ProductFeedResult
     */
    public function generateFeed(ShoppingfeedFeed $feed)
    {
        $productFeedResult = null;
        try {
            $feedFilePrefix = $feed->getFeedFilePrefix();
            $country = $feed->getCountry();
            $lang = $feed->getLang();

            $generator = (new ProductGenerator())
                ->setPlatform("Thelia", Thelia::THELIA_VERSION);

            $generator->addMapper(function (\Thelia\Model\Product $productIn, Product $productOut) use ($country, $lang) {

                $locale = $lang->getLocale();
                $productSaleElementss = ProductSaleElementsQuery::create()
                    ->useProductPriceQuery()
                    ->endUse()
                    ->withColumn(ProductPriceTableMap::COL_PRICE, "price_PRICE")
                    ->filterByProductId($productIn->getId())
                    ->find();

                $defaultSaleElements = null;
                foreach ($productSaleElementss as $productSaleElements) {
                    if ($productSaleElements->isDefault()) {
                        $defaultSaleElements = $productSaleElements;
                    }
                }

                if (null === $defaultSaleElements) {
                    $defaultSaleElements = $productSaleElementss->getFirst();
                }

                $productIn->setLocale($locale);

                // Mandatory fields
                $productOut->setName($productIn->getTitle())
                    ->setReference($productIn->getRef())
                    ->setPrice($defaultSaleElements->getTaxedPrice($country))
                    ->setQuantity($defaultSaleElements->getQuantity());


                // Optional fields
                $productOut->setDescription($productIn->getDescription())
                    ->setLink(URL::getInstance()->absoluteUrl($productIn->getRewrittenUrl($locale)));

                $images = $this->getImageData($productIn->getProductImages());
                ksort($images);
                if (!empty($images)) {
                    $productOut->setMainImage(array_shift($images));
                }

                $productOut->setAdditionalImages($images);

                $marketplaceCategory = CategoryQuery::create()
                    ->useShoppingfeedProductMarketplaceCategoryQuery()
                    ->filterByProductId($productIn->getId())
                    ->endUse()
                    ->findOne();
                if (null === $marketplaceCategory) {
                    $marketplaceCategory = CategoryQuery::create()->filterById($productIn->getDefaultCategoryId())->findOne();
                }
                $productOut->setCategory($marketplaceCategory->setLocale($locale)->getTitle());

                $brand = $productIn->getBrand();
                if ($brand) {
                    $productOut->setBrand($brand->setLocale($locale)->getTitle());
                }

                foreach ($productIn->getFeatureProducts() as $featureProduct) {
                    $feature = $featureProduct->getFeature()->setLocale($locale);
                    $featureAv = $featureProduct->getFeatureAv()->setLocale($locale);
                    $productOut->setAttribute($feature->getTitle(), $featureAv->getTitle());
                }

                $productOut->setAttribute("thelia_id", $productIn->getId());

                foreach ($productSaleElementss as $productSaleElements) {
                    $pseMarketplace = ShoppingfeedPseMarketplaceQuery::create()->filterByPseId($productSaleElements->getId())->findOne();
                    $reference = $productSaleElements->getEanCode() !== null ? $productSaleElements->getEanCode() : $productSaleElements->getRef();

                    $variation = $productOut->createVariation();
                    $variation
                        ->setReference($reference)
                        ->setPrice($productSaleElements->getTaxedPrice($country)) // Todo maybe get promo price
                        ->setQuantity($productSaleElements->getQuantity());

                    $variation->setAttribute('weight', $productSaleElements->getWeight());
                    if ($productSaleElements->getEanCode()) {
                        $variation->setGtin($productSaleElements->getEanCode());
                    }

                    if ($pseMarketplace) {
                        $variation->setAttribute("marketplace", $pseMarketplace->getMarketplace());
                    }

                    foreach ($productSaleElements->getAttributeCombinations() as $attributeCombination) {
                        $attribute = $attributeCombination->getAttribute()->setLocale($locale);
                        $attributeAv = $attributeCombination->getAttributeAv()->setLocale($locale);
                        $variation->setAttribute($attribute->getTitle(), $attributeAv->getTitle());
                    }

                    $variation->setAttribute("thelia_id", $productSaleElements->getId());

                    $pseExtraFieldEvent = new FeedPseExtraFieldEvent();
                    $pseExtraFieldEvent->setPse($productSaleElements);
                    $pseExtraFieldEvent->setVariation($variation);
                    $this->eventDispatcher->dispatch(FeedPseExtraFieldEvent::SHOPPINGFEED_FEED_PSE_EXTRA_FIELD, $pseExtraFieldEvent);
                }

                $productExtraFieldEvent = new FeedProductExtraFieldEvent();
                $productExtraFieldEvent->setProduct($productOut);
                $productExtraFieldEvent->setProductModel($productIn);
                $this->eventDispatcher->dispatch(FeedProductExtraFieldEvent::SHOPPINGFEED_FEED_PRODUCT_EXTRA_FIELD, $productExtraFieldEvent);
            });

            $products = ProductQuery::create()
                ->find();

            $generator->setUri('file://' . THELIA_WEB_DIR . $feedFilePrefix . '_shopping_feed.xml');

            $generator->setValidationFlags(ProductGenerator::VALIDATE_EXCEPTION);

            $productFeedResult = $generator->write($products);

        }  catch (\Exception $exception) {
            $this->logger->log(
                'Error during xml generation : '.$exception->getMessage(),
                LogService::LEVEL_ERROR,
                $feed
            );
            return null;
        }

        $this->logger->log(
            'Catalog ' . $country->getIsoalpha2() . ' xml generated with success.',
            LogService::LEVEL_SUCCESS,
            $feed
        );

        return $productFeedResult;
    }

    protected function getImageData($images)
    {
        $data = [];

        /** @var ProductImage $image */
        foreach ($images as $image) {
            if (null !== $image) {
                try {
                    $imageEvent = self::createImageEvent($image->getFile());
                    $this->eventDispatcher->dispatch(TheliaEvents::IMAGE_PROCESS, $imageEvent);

                    $data[$image->getPosition()] = $imageEvent->getOriginalFileUrl();
                } catch (\Exception $e) {
                    $error = $e->getMessage();
                }
            }
        }

        return $data;
    }

    protected function createImageEvent($imageFile)
    {
        $imageEvent = new ImageEvent();
        $baseSourceFilePath = ConfigQuery::read('images_library_path');
        if ($baseSourceFilePath === null) {
            $baseSourceFilePath = THELIA_LOCAL_DIR . 'media' . DS . 'images';
        } else {
            $baseSourceFilePath = THELIA_ROOT . $baseSourceFilePath;
        }
        // Put source image file path
        $sourceFilePath = sprintf(
            '%s/%s/%s',
            $baseSourceFilePath,
            'product',
            $imageFile
        );
        $imageEvent->setSourceFilepath($sourceFilePath);
        $imageEvent->setCacheSubdirectory('product');
        return $imageEvent;
    }
}