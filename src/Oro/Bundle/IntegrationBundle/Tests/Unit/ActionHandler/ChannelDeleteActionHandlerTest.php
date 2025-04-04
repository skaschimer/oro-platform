<?php

namespace Oro\Bundle\IntegrationBundle\Tests\Unit\ActionHandler;

use Oro\Bundle\IntegrationBundle\ActionHandler\ChannelDeleteActionHandler;
use Oro\Bundle\IntegrationBundle\Entity\Channel;
use Oro\Bundle\IntegrationBundle\Manager\DeleteManager;

class ChannelDeleteActionHandlerTest extends \PHPUnit\Framework\TestCase
{
    /** @var DeleteManager|\PHPUnit\Framework\MockObject\MockObject */
    private $deleteManager;

    /** @var ChannelDeleteActionHandler */
    private $handler;

    #[\Override]
    protected function setUp(): void
    {
        $this->deleteManager = $this->createMock(DeleteManager::class);

        $this->handler = new ChannelDeleteActionHandler($this->deleteManager);
    }

    public function testHandleAction()
    {
        $this->deleteManager->expects(self::once())
            ->method('delete')
            ->willReturn(false);

        self::assertFalse($this->handler->handleAction(new Channel()));
    }
}
