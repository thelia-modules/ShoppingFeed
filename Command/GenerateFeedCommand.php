<?php

namespace ShoppingFeed\Command;

use ShoppingFeed\Model\ShoppingfeedFeedQuery;
use ShoppingFeed\Service\FeedService;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Thelia\Command\ContainerAwareCommand;

class GenerateFeedCommand extends ContainerAwareCommand
{
    protected function configure()
    {
        $this
            ->setName("shopping_feed:generate:feed")
            ->setDescription("Generate feed for Shopping Feed");
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->initRequest();
        /** @var FeedService $feedService */
        $feedService = $this->getContainer()->get("shopping_feed_feed_service");

        $feeds = ShoppingfeedFeedQuery::create()->find();

        foreach ($feeds as $feed) {
            $feedService->generateFeed($feed);
        }

        return 1;
    }
}