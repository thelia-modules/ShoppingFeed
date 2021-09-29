<?php

namespace ShoppingFeed\FormExtend;

use CustomerFamily\Model\ProductPurchasePrice;
use CustomerFamily\Model\ProductPurchasePriceQuery;
use ShoppingFeed\Model\ShoppingfeedPseMarketplaceQuery;
use ShoppingFeed\ShoppingFeed;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Validator\Constraints;
use Thelia\Core\Event\TheliaEvents;
use Thelia\Core\Event\TheliaFormEvent;
use Thelia\Core\Translation\Translator;

/**
 * Class ProductSaleElementsFormExtend
 */
class ProductSaleElementsFormExtend implements EventSubscriberInterface
{
    /**
     * Returns an array of event names this subscriber wants to listen to.
     *
     * @return array The event names to listen to
     */
    public static function getSubscribedEvents()
    {
        return [TheliaEvents::FORM_AFTER_BUILD . '.thelia_product_sale_element_update_form' => ['extendPseForm', 128]];
    }

    /**
     * Add a marketplace input to PSEs
     *
     * @param TheliaFormEvent $event
     */
    public function extendPseForm(TheliaFormEvent $event)
    {
        $event
            ->getForm()
            ->getFormBuilder()
            ->addEventListener(FormEvents::POST_SUBMIT, [$this, 'handleExtendedData'], 0);

        $event->getForm()->getFormBuilder()
            ->add(
                'marketplace',
                TextType::class,
                [
                    "label"=> Translator::getInstance()->trans("MarketPlace", [], ShoppingFeed::DOMAIN_NAME),
                    "required" => false,
                ]
            );
    }

    /**
     * Create or update PSE's marketplace
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

        if (is_array($data['product_sale_element_id'])) {
            foreach (array_keys($data['product_sale_element_id']) as $idx) {
                $pseMarkeplace = ShoppingfeedPseMarketplaceQuery::create()
                    ->filterByPseId($data['product_sale_element_id'][$idx])
                    ->findOneOrCreate();

                $pseMarkeplace->setPseId($data['product_sale_element_id'][$idx]);
                $pseMarkeplace->setMarketplace($data['marketplace'][$idx]);
                $pseMarkeplace->save();
            }
        }
    }
}