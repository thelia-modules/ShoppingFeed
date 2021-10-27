<?php

namespace ShoppingFeed\EventListener\FormExtend;

use CustomerFamily\Model\ProductPurchasePrice;
use CustomerFamily\Model\ProductPurchasePriceQuery;
use ShoppingFeed\Model\ShoppingfeedProductMarketplaceCategoryQuery;
use ShoppingFeed\Model\ShoppingfeedPseMarketplaceQuery;
use ShoppingFeed\ShoppingFeed;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Validator\Constraints;
use Thelia\Core\Event\TheliaEvents;
use Thelia\Core\Event\TheliaFormEvent;
use Thelia\Core\Translation\Translator;
use Thelia\Model\CategoryQuery;

/**
 * Class ProductFormExtend
 */
class ProductFormExtend implements EventSubscriberInterface
{
    /**
     * Returns an array of event names this subscriber wants to listen to.
     *
     * @return array The event names to listen to
     */
    public static function getSubscribedEvents()
    {
        return [TheliaEvents::FORM_AFTER_BUILD . '.thelia_product_modification' => ['extendProductForm', 128]];
    }

    /**
     * Add a marketplace category input to product
     *
     * @param TheliaFormEvent $event
     */
    public function extendProductForm(TheliaFormEvent $event)
    {
        $event
            ->getForm()
            ->getFormBuilder()
            ->addEventListener(FormEvents::POST_SUBMIT, [$this, 'handleExtendedData'], 0);

        $event->getForm()->getFormBuilder()
            ->add(
                'marketplace_category',
                TextType::class,
                [
                    "label"=> Translator::getInstance()->trans("MarketPlace Category", [], ShoppingFeed::DOMAIN_NAME),
                    "required" => false,
                ]
            );
    }

    /**
     * Create or update product marketplace category
     *
     * @param FormEvent $formEvent
     * @throws \Exception
     * @throws \Propel\Runtime\Exception\PropelException
     */
    public function handleExtendedData(FormEvent $formEvent)
    {
        if (!$formEvent->getForm()->isValid()) {
            return;
        }

        $data = $formEvent->getData();

        $marketplaceCategory = ShoppingfeedProductMarketplaceCategoryQuery::create()->filterByProductId($data["id"])->findOneOrCreate();
        $marketplaceCategory->setProductId($data["id"]);
        $marketplaceCategory->setCategoryId($data["marketplace_category"]);
        $marketplaceCategory->save();
    }
}