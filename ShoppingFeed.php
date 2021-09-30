<?php
/*************************************************************************************/
/*      This file is part of the Thelia package.                                     */
/*                                                                                   */
/*      Copyright (c) OpenStudio                                                     */
/*      email : dev@thelia.net                                                       */
/*      web : http://www.thelia.net                                                  */
/*                                                                                   */
/*      For the full copyright and license information, please view the LICENSE.txt  */
/*      file that was distributed with this source code.                             */
/*************************************************************************************/

namespace ShoppingFeed;

use Thelia\Model\Customer;
use Thelia\Model\CustomerQuery;
use Thelia\Model\CustomerTitleQuery;
use Thelia\Model\LangQuery;
use Thelia\Model\Order;
use Thelia\Module\AbstractPaymentModule;
use Thelia\Module\BaseModule;

class ShoppingFeed extends AbstractPaymentModule
{
    /** @var string */
    const DOMAIN_NAME = 'shoppingfeed';

    /*
     * You may now override BaseModuleInterface methods, such as:
     * install, destroy, preActivation, postActivation, preDeactivation, postDeactivation
     *
     * Have fun !
     */

    public static function getSoppingFeedCustomer()
    {
        $customer = CustomerQuery::create()
            ->filterByRef("SHOPPING_FEED")
            ->findOne();

        if (null !== $customer) {
            return $customer;
        }

        $lang = LangQuery::create()
            ->filterByByDefault(true)
            ->findOne();

        $customerTitle = CustomerTitleQuery::create()
            ->filterByByDefault(true)
            ->findOne();

        $customer = (new Customer())
            ->setLangId($lang->getId())
            ->setTitleId($customerTitle->getId())
            ->setEmail('module-shoppingfeed@thelia.net')
            ->setRef("SHOPPING_FEED");

        $customer->save();

        return $customer;
    }


    public function pay(Order $order)
    {}

    public function isValidPayment()
    {}

    public function manageStockOnCreation()
    {
        return true;
    }
}
