<?php

namespace Oro\Bundle\MessageQueueBundle\Tests\Unit\Test;

use Oro\Bundle\MessageQueueBundle\Test\MessageCollector;
use Oro\Component\MessageQueue\Client\MessageProducerInterface;

class MessageCollectorTest extends \PHPUnit\Framework\TestCase
{
    /** @var \PHPUnit\Framework\MockObject\MockObject */
    private $messageProducer;

    /** @var MessageCollector */
    private $messageCollector;

    #[\Override]
    protected function setUp(): void
    {
        $this->messageProducer = $this->createMock(MessageProducerInterface::class);

        $this->messageCollector = new MessageCollector($this->messageProducer);
    }

    public function testShouldCallInternalMessageProducerSendMethod()
    {
        $topic = 'test topic';
        $message = 'test message';

        $this->messageProducer->expects($this->once())
            ->method('send')
            ->with($topic, $message);

        $this->messageCollector->send($topic, $message);
    }

    public function testShouldCollectMessages()
    {
        $topic = 'test topic';
        $message = 'test message';

        $this->messageCollector->send($topic, $message);

        self::assertEquals(
            [
                ['topic' => $topic, 'message' => $message]
            ],
            $this->messageCollector->getSentMessages()
        );
    }

    public function testShouldAllowClearCollectedMessages()
    {
        $this->messageCollector->send('test topic', 'test message');
        $this->messageCollector->clear();

        self::assertEquals([], $this->messageCollector->getSentMessages());
    }

    public function testShouldAllowClearCollectedTopicMessages()
    {
        $this->messageCollector->send('test topic 1', 'test message 1');
        $this->messageCollector->send('test topic 2', 'test message 2');
        $this->messageCollector->clearTopicMessages('test topic 1');

        self::assertEquals(
            [['topic' => 'test topic 2', 'message' => 'test message 2']],
            $this->messageCollector->getSentMessages()
        );
    }

    public function testShouldNotCatchExceptionFromInternalMessageProducer()
    {
        $exception = new \Exception('some error');

        $this->messageProducer->expects($this->once())
            ->method('send')
            ->willThrowException($exception);

        $this->expectException(get_class($exception));
        $this->expectExceptionMessage($exception->getMessage());
        $this->messageCollector->send('test topic', 'test message');
    }

    public function testShouldNotStoreMessageIfInternalMessageProducerThrowsException()
    {
        $this->messageProducer->expects($this->once())
            ->method('send')
            ->willThrowException(new \Exception());

        try {
            $this->messageCollector->send('test topic', 'test message');
        } catch (\Exception $e) {
            self::assertEquals([], $this->messageCollector->getSentMessages());
        }
    }

    public function testShouldBePossibleToUseWithoutInternalMessageProducer()
    {
        $topic = 'test topic';
        $message = 'test message';

        $messageCollector = new MessageCollector();
        $messageCollector->send($topic, $message);

        self::assertEquals(
            [
                ['topic' => $topic, 'message' => $message]
            ],
            $messageCollector->getSentMessages()
        );
    }
}
