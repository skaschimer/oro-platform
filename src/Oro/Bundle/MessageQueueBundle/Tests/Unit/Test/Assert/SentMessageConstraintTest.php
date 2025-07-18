<?php

namespace Oro\Bundle\MessageQueueBundle\Tests\Unit\Test\Assert;

use Oro\Bundle\MessageQueueBundle\Test\Assert\SentMessageConstraint;
use PHPUnit\Framework\ExpectationFailedException;
use PHPUnit\Framework\TestCase;

class SentMessageConstraintTest extends TestCase
{
    public function testShouldBeEvaluatedToFalseIfValueIsNotArray(): void
    {
        $expectedMessage = ['topic' => 'test topic', 'message' => 'test message'];
        $allMessages = 'some unexpected value';

        $constraint = new SentMessageConstraint($expectedMessage);
        $this->assertFalse($constraint->evaluate($allMessages, '', true));
    }

    public function testShouldBeEvaluatedToFalseIfValueDoesNotContainExpectedMessage(): void
    {
        $expectedMessage = ['topic' => 'test topic', 'message' => 'test message'];
        $allMessages = [
            ['topic' => 'test topic', 'message' => 'test message 1'],
            ['topic' => 'test topic 1', 'message' => 'test message'],
        ];

        $constraint = new SentMessageConstraint($expectedMessage);
        $this->assertFalse($constraint->evaluate($allMessages, '', true));
    }

    public function testShouldBeEvaluatedToTrueIfValueContainsExpectedMessage(): void
    {
        $expectedMessage = ['topic' => 'test topic', 'message' => 'test message'];
        $allMessages = [
            ['topic' => 'test topic', 'message' => 'test message 1'],
            ['topic' => 'test topic', 'message' => 'test message'],
        ];

        $constraint = new SentMessageConstraint($expectedMessage);
        $this->assertTrue($constraint->evaluate($allMessages, '', true));
    }

    public function testShouldThrowExceptionWithValidMessage(): void
    {
        $expectedMessage = ['topic' => 'test topic', 'message' => 'test message'];
        $allMessages = [
            ['topic' => 'test topic', 'message' => 'test message 1'],
            ['topic' => 'test topic 1', 'message' => 'test message'],
        ];
        $expectedExceptionMessage = <<<TEXT
additional description
Failed asserting that the message Array &0 (
    'topic' => 'test topic'
    'message' => 'test message'
) was sent.
All sent messages: Array &0 (
    0 => Array &1 (
        'topic' => 'test topic'
        'message' => 'test message 1'
    )
    1 => Array &2 (
        'topic' => 'test topic 1'
        'message' => 'test message'
    )
)
TEXT;

        $constraint = new SentMessageConstraint($expectedMessage);
        try {
            $constraint->evaluate($allMessages, 'additional description');
        } catch (ExpectationFailedException $e) {
            self::assertEquals($expectedExceptionMessage, $e->getMessage());
        }
    }
}
