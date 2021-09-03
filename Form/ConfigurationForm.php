<?php

namespace ShoppingFeed\Form;

use ShoppingFeed\ShoppingFeed;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Thelia\Core\Translation\Translator;
use Thelia\Form\BaseForm;


class ConfigurationForm extends BaseForm
{
    protected function buildForm()
    {
        $this->formBuilder
            ->add(
                "auth_token",
                TextType::class,
                [
                    "data" => ShoppingFeed::getConfigValue("auth_token"),
                    "label"=> Translator::getInstance()->trans("Authentication token", [], ShoppingFeed::DOMAIN_NAME),
                    "label_attr" => ["for" => "auth_token"],
                    "required" => true,
                ]
            )
        ;
    }

    public function getName()
    {
        return "shoppingfeed_configuration_form";
    }
}
