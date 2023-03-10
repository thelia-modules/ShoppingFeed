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

use Exception;
use Propel\Runtime\Connection\ConnectionInterface;
use Propel\Runtime\Exception\PropelException;
use ShoppingFeed\Model\ShoppingfeedFeedQuery;
use SplFileInfo;
use Symfony\Component\DependencyInjection\Loader\Configurator\ServicesConfigurator;
use Symfony\Component\Finder\Finder;
use Thelia\Install\Database;
use Thelia\Model\Order;
use Thelia\Module\AbstractPaymentModule;

class ShoppingFeed extends AbstractPaymentModule
{
    /** @var string */
    public const DOMAIN_NAME = 'shoppingfeed';

    /**
     * @param ConnectionInterface|null $con
     * @throws PropelException
     */
    public function postActivation(ConnectionInterface $con = null): void
    {
        // Once activated, create the module schema in the Thelia database.
        $database = new Database($con);

        try {
            ShoppingfeedFeedQuery::create()->findOne();
        } catch (\Exception $e) {
            $database->insertSql(null, array(
                __DIR__ . DS . 'Config' . DS . 'thelia.sql' // The module schema
            ));
        }
    }

    public function update($currentVersion, $newVersion, ConnectionInterface $con = null): void
    {
        $finder = Finder::create()
            ->name('*.sql')
            ->depth(0)
            ->sortByName()
            ->in(__DIR__ . DS . 'Config' . DS . 'update');

        $database = new Database($con);

        /** @var SplFileInfo $file */
        foreach ($finder as $file) {
            if (version_compare($currentVersion, $file->getBasename('.sql'), '<')) {
                $database->insertSql(null, [$file->getPathname()]);
            }
        }
    }

    public function pay(Order $order)
    {
    }

    public function isValidPayment(): bool
    {
        return false;
    }

    public static function configureServices(ServicesConfigurator $servicesConfigurator): void
    {
        $servicesConfigurator->load(self::getModuleCode() . '\\', __DIR__)
            ->exclude([THELIA_MODULE_DIR . ucfirst(self::getModuleCode()) . "/I18n/*"])
            ->autowire(true)
            ->autoconfigure(true);
    }
}
