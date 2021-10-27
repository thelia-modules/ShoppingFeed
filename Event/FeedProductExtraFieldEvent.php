<?php


namespace ShoppingFeed\Event;

use ShoppingFeed\Feed\Product\Product;
use Thelia\Core\Event\ActionEvent;

class FeedProductExtraFieldEvent extends ActionEvent
{
    const SHOPPINGFEED_FEED_PRODUCT_EXTRA_FIELD = 'action.module.shoppingfeed.feed.product.extra.field';

    protected $product;
    protected $productModel;

    public function setProduct(Product $product)
    {
        $this->product = $product;
    }

    public function getProduct()
    {
        return $this->product;
    }

    public function setProductModel(\Thelia\Model\Product $productModel)
    {
        $this->productModel = $productModel;
    }

    public function getProductModel()
    {
        return $this->productModel;
    }

    public function getVariationFromTheliaPseId($pseId)
    {
        /** @var Product $product */
        $product = $this->getProduct();
        foreach ($product->getVariations() as $variation) {
            foreach ($variation->getAttributes() as $attribute) {
                if ($attribute->getName() === "thelia_id" && $attribute->getValue() == $pseId) {
                    return $variation;
                }
            }
        }
        return null;
    }
}