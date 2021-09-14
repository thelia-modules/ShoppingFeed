<?php

namespace ShoppingFeed\Form;

use ShoppingFeed\ShoppingFeed;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Thelia\Core\Translation\Translator;
use Thelia\Form\BaseForm;
use Thelia\Model\CountryQuery;


class ConfigurationForm extends BaseForm
{
    protected function buildForm()
    {

    }

    public function getName()
    {
        return "shoppingfeed_configuration_form";
    }
}