<?php

namespace Oro\Bundle\FeatureToggleBundle\Tests\Unit\Async;

use Oro\Bundle\FeatureToggleBundle\Async\MessageFilter;
use Oro\Bundle\FeatureToggleBundle\Checker\FeatureChecker;
use Oro\Bundle\MessageQueueBundle\Client\MessageBuffer;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class MessageFilterTest extends TestCase
{
    private FeatureChecker&MockObject $featureChecker;
    private MessageFilter $filter;

    #[\Override]
    protected function setUp(): void
    {
        $this->featureChecker = $this->createMock(FeatureChecker::class);

        $this->filter = new MessageFilter($this->featureChecker);
    }

    public function testApply(): void
    {
        $buffer = new MessageBuffer();
        $buffer->addMessage('topic1', 'topic1.message1');
        $buffer->addMessage('topic1', 'topic1.message2');
        $buffer->addMessage('topic2', 'topic2.message1');

        $this->featureChecker->expects($this->exactly(2))
            ->method('isResourceEnabled')
            ->withConsecutive(
                ['topic1', 'mq_topics'],
                ['topic2', 'mq_topics']
            )
            ->willReturnOnConsecutiveCalls(
                false,
                true
            );

        $this->filter->apply($buffer);

        $this->assertFalse($buffer->hasMessagesForTopic('topic1'));
        $this->assertTrue($buffer->hasMessagesForTopic('topic2'));
    }
}
