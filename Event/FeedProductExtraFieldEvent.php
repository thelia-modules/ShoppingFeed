<?php


namespace ShoppingFeed\Event;

use ShoppingFeed\Feed\Product\Product;
use Thelia\Core\Event\ActionEvent;

class FeedProductExtraFieldEvent extends ActionEvent
{
    protected $product;

    public function setProduct(Product $product)
    {
        $this->product = $product;
    }

    public function getProduct()
    {
        return $this->product;
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