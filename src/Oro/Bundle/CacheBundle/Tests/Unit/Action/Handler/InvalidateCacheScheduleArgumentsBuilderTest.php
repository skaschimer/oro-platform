<?php

namespace Oro\Bundle\CacheBundle\Tests\Unit\Action\Handler;

use Oro\Bundle\CacheBundle\Action\DataStorage\InvalidateCacheDataStorage;
use Oro\Bundle\CacheBundle\Action\Handler\InvalidateCacheActionScheduledHandler;
use Oro\Bundle\CacheBundle\Action\Handler\InvalidateCacheScheduleArgumentsBuilder;
use PHPUnit\Framework\TestCase;

class InvalidateCacheScheduleArgumentsBuilderTest extends TestCase
{
    public function testBuild(): void
    {
        $dataStorage = new InvalidateCacheDataStorage();
        $dataStorage->set(InvalidateCacheActionScheduledHandler::PARAM_INVALIDATE_TIME, 'time');
        $dataStorage->set(InvalidateCacheActionScheduledHandler::PARAM_HANDLER_SERVICE_NAME, 'service');
        $dataStorage->set('param1', 'string');
        $dataStorage->set('param2', 64);

        $expectedArgs = [
            'service=service',
            'parameters=' . serialize(['param1' => 'string', 'param2' => 64]),
        ];

        $builder = new InvalidateCacheScheduleArgumentsBuilder();

        self::assertSame($expectedArgs, $builder->build($dataStorage));
    }
}
