<?php

namespace Oro\Bundle\DigitalAssetBundle\Tests\Unit\Stub;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class EventSubscriberStub implements EventSubscriberInterface
{
    /** @var array */
    private static $events = [];

    public static function setSubscribedEvents(array $events)
    {
        self::$events = $events;
    }

    #[\Override]
    public static function getSubscribedEvents(): array
    {
        return self::$events;
    }
}
