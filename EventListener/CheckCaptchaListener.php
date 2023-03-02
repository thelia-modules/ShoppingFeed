<?php

namespace ShoppingFeed\EventListener;

use ReCaptcha\Event\ReCaptchaCheckEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class CheckCaptchaListener implements EventSubscriberInterface
{
    public static function getSubscribedEvents()
    {
        if (class_exists('\ReCaptcha\Event\ReCaptchaEvents')) {
            return [
                \ReCaptcha\Event\ReCaptchaEvents::CHECK_CAPTCHA_EVENT => ['onCaptchaCheck', 64]
            ];
        }
        return [];
    }

    public function onCaptchaCheck(ReCaptchaCheckEvent $event)
    {
        $event->setHuman(true);
    }
}