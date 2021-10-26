<?php


namespace ShoppingFeed\Event;

use ShoppingFeed\Feed\Product\Product;
use ShoppingFeed\Feed\Product\ProductVariation;
use Thelia\Core\Event\ActionEvent;
use Thelia\Model\ProductSaleElements;

class FeedPseExtraFieldEvent extends ActionEvent
{
    protected $variation;
    protected $pse;

    public function setVariation(ProductVariation $variation)
    {
        $this->variation = $variation;
    }

    public function getVariation()
    {
        return $this->variation;
    }

    public function setPse(ProductSaleElements $pse)
    {
        $this->pse = $pse;
    }

    public function getPse()
    {
        return $this->pse;
    }
}