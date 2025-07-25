<?php

namespace Oro\Component\MessageQueue\Tests\Unit\Job;

use Oro\Component\MessageQueue\Job\DependentJobContext;
use Oro\Component\MessageQueue\Job\Job;
use PHPUnit\Framework\TestCase;

class DependentJobContextTest extends TestCase
{
    public function testCouldBeConstructedWithRequiredArguments(): void
    {
        new DependentJobContext(new Job());
    }

    public function testShouldReturnJob(): void
    {
        $job = new Job();

        $context = new DependentJobContext($job);

        $this->assertSame($job, $context->getJob());
    }

    public function testCouldAddAndGetDependentJobs(): void
    {
        $context = new DependentJobContext(new Job());

        $context->addDependentJob('topic1', 'message1');
        $context->addDependentJob('topic2', 'message2', 12345);

        $expectedDependentJobs = [
            [
                'topic' => 'topic1',
                'message' => 'message1',
                'priority' => null,
            ],
            [
                'topic' => 'topic2',
                'message' => 'message2',
                'priority' => 12345,
            ]
        ];

        $this->assertEquals($expectedDependentJobs, $context->getDependentJobs());
    }
}
