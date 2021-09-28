<?php

namespace ShoppingFeed\Hook;

use ShoppingFeed\Service\LogService;
use Thelia\Core\Event\Hook\HookRenderEvent;
use Thelia\Core\Hook\BaseHook;
use Thelia\Core\HttpFoundation\Request;

/**
 * Class HookManager
 * @package ShoppingFeed\Hook
 */
class HookManager extends BaseHook
{
    private $logService;

    public function __construct(LogService $logService)
    {
        $this->logService = $logService;
    }

    public function renderLogs(HookRenderEvent $event)
    {
        $event->add($this->render(
            'shoppingfeed/home-bottom.html',
            [
                'columnsDefinition' => $this->logService->defineColumnsDefinition(),
            ]
        ));
    }

    public function renderJs(HookRenderEvent $event)
    {
        $event->add($this->render(
            'shoppingfeed/js/home-js.html',
            [
                'columnsDefinition' => $this->logService->defineColumnsDefinition(),
            ]
        ));
    }

    public function onMainHeadCss(HookRenderEvent $event)
    {
        $content = $this->addCSS('shoppingfeed/css/style.css');
        $event->add($content);
    }
}
