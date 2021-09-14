<?php

namespace ShoppingFeed\Command;

use ShoppingFeed\Model\ShoppingFeedConfigQuery;
use ShoppingFeed\Service\FeedService;
use ShoppingFeed\Service\OrderService;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Thelia\Command\ContainerAwareCommand;

class SynchronizeOrderCommand extends ContainerAwareCommand
{
    protected function configure()
    {
        $this
            ->setName("shopping_feed:sync:order")
            ->setDescription("Synchronize order from Shopping Feed");
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        /** @var OrderService $orderService */
        $orderService = $this->getContainer()->get("shopping_feed_order_service");

        $configs = ShoppingFeedConfigQuery::create()->find();

        foreach ($configs as $config) {
            $orderService->importOrders($config);
        }

        return 1;
    }
}