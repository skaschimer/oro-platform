<?php

namespace Oro\Bundle\ImapBundle\Tests\Unit\Manager;

use Oro\Bundle\ImapBundle\Connector\ImapMessageIterator;
use Oro\Bundle\ImapBundle\Manager\ImapEmailIterator;
use Oro\Bundle\ImapBundle\Manager\ImapEmailManager;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

/**
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class ImapEmailIteratorTest extends TestCase
{
    /** @var ImapMessageIterator|\PHPUnit\Framework\MockObject\MockObject */
    private $messageIterator;

    /** @var ImapEmailManager|\PHPUnit\Framework\MockObject\MockObject */
    private $manager;

    /** @var ImapEmailIterator */
    private $iterator;

    #[\Override]
    protected function setUp(): void
    {
        $this->messageIterator = $this->createMock(ImapMessageIterator::class);
        $this->manager = $this->createMock(ImapEmailManager::class);

        $this->iterator = new ImapEmailIterator(
            $this->messageIterator,
            $this->manager
        );
    }

    public function testSetIterationOrder()
    {
        $reverse = false;

        $this->messageIterator->expects(self::once())
            ->method('setIterationOrder')
            ->with($reverse);

        $this->iterator->setIterationOrder($reverse);
    }

    public function testSetBatchSize()
    {
        $batchSize = 10;

        $this->messageIterator->expects(self::once())
            ->method('setBatchSize')
            ->with($batchSize);

        $this->iterator->setBatchSize($batchSize);
    }

    public function testSetLogger()
    {
        $logger = $this->createMock(LoggerInterface::class);

        $this->messageIterator->expects(self::once())
            ->method('setLogger')
            ->with($logger);

        $this->iterator->setLogger($logger);
    }

    public function testSetBatchCallbackOnNull()
    {
        $this->messageIterator->expects(self::once())
            ->method('setBatchCallback');

        $this->iterator->setBatchCallback(null);
    }

    public function testSetBatchCallback()
    {
        $this->messageIterator->expects(self::once())
            ->method('setBatchCallback');

        $this->iterator->setBatchCallback(function () {
        });
    }

    public function testSetConvertErrorCallback()
    {
        $this->iterator->setConvertErrorCallback();
    }

    public function testCount()
    {
        $count = 0;

        $this->messageIterator->expects(self::once())
            ->method('count')
            ->willReturn($count);

        self::assertSame($count, $this->iterator->count());
    }

    public function testCurrent()
    {
        self::assertNull($this->iterator->current());
    }

    public function testNext()
    {
        $this->messageIterator->expects(self::once())
            ->method('next');

        $this->iterator->next();
    }

    public function testKey()
    {
        self::assertSame(0, $this->iterator->key());
    }

    public function testValid()
    {
        self::assertFalse($this->iterator->valid());
    }

    public function testRewind()
    {
        $this->messageIterator->expects(self::once())
            ->method('rewind');

        $this->iterator->rewind();
    }
}
