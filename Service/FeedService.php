<?php

namespace ShoppingFeed\Service;

use ShoppingFeed\Feed\Product\Product;
use ShoppingFeed\Feed\ProductFeedResult;
use ShoppingFeed\Feed\ProductGenerator;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Thelia\Core\Event\Image\ImageEvent;
use Thelia\Core\Event\TheliaEvents;
use Thelia\Core\Thelia;
use Thelia\Model\ConfigQuery;
use Thelia\Model\Country;
use Thelia\Model\Lang;
use Thelia\Model\Map\ProductPriceTableMap;
use Thelia\Model\ProductQuery;
use Thelia\Model\ProductSaleElementsQuery;
use Thelia\Tools\URL;

class FeedService
{
    protected $eventDispatcher;

    public function __construct(EventDispatcherInterface $eventDispatcher)
    {
        $this->eventDispatcher = $eventDispatcher;
    }

    /**
     * @return ProductFeedResult
     */
    public function generateFeed($feedFilePrefix, Country $country, Lang $lang)
    {
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

            $taxedPrice = $defaultSaleElements->getTaxedPrice($country);
            $untaxedPrice = $defaultSaleElements->getPrice();

            $vat = ($untaxedPrice / $taxedPrice) * 100;

            // Mandatory fields
            $productOut->setName($productIn->getTitle())
                ->setReference($productIn->getRef())
                ->setPrice($defaultSaleElements->getTaxedPrice($country)) // Todo maybe get promo price
                ->setQuantity($defaultSaleElements->getQuantity());


            // Optional fields
            $productOut->setDescription($productIn->getDescription())
                ->setLink(URL::getInstance()->absoluteUrl($productIn->getRewrittenUrl($locale)));

            $images = $this->getImageData($productIn->getProductImages());
            if (!empty($images)) {
                $productOut->setMainImage($images[0]);
                unset($images[0]);
            }

            $productOut->setAdditionalImages($images);

            foreach ($productSaleElementss as $productSaleElements) {
                $reference = $productSaleElements->getEanCode() !== null ? $productSaleElements->getEanCode() : $productSaleElements->getRef();

                $variation = $productOut->createVariation();
                $variation
                    ->setReference($reference)
                    ->setPrice($productSaleElements->getTaxedPrice($country)) // Todo maybe get promo price
                    ->setQuantity($productSaleElements->getQuantity());

                foreach ($productSaleElements->getAttributeCombinations() as $attributeCombination) {
                    $attribute = $attributeCombination->getAttribute()->setLocale($locale);
                    $attributeAv = $attributeCombination->getAttributeAv()->setLocale($locale);
                    $variation->setAttribute($attribute->getTitle(), $attributeAv->getTitle());
                }
            }
        });

        $products = ProductQuery::create()
            ->find();

        $generator->setUri('file://'.THELIA_WEB_DIR.$feedFilePrefix.'_shopping_feed.xml');
        $generator->setValidationFlags(ProductGenerator::VALIDATE_EXCEPTION);
        return $generator->write($products);
    }

    protected function getImageData($images)
    {
        $data = [];

        foreach ($images as $image) {
            if (null !== $image) {
                try {
                    $imageEvent = self::createImageEvent($image->getFile());
                    $this->eventDispatcher->dispatch(TheliaEvents::IMAGE_PROCESS, $imageEvent);

                    $data[] = $imageEvent->getOriginalFileUrl();
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