<?php

namespace ShoppingFeed\Command;

use ShoppingFeed\Model\ShoppingfeedFeedQuery;
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

        $feeds = ShoppingfeedFeedQuery::create()->find();

        foreach ($feeds as $feed) {
            $orderService->importOrders($feed);
        }

        return 1;
    }
}