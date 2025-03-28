<?php

namespace Oro\Component\ConfigExpression\Tests\Unit\Condition;

use Oro\Component\ConfigExpression\Condition;
use Oro\Component\ConfigExpression\Exception\InvalidArgumentException;

class TrueTest extends \PHPUnit\Framework\TestCase
{
    /** @var Condition\TrueCondition */
    protected $condition;

    #[\Override]
    protected function setUp(): void
    {
        $this->condition = new Condition\TrueCondition();
    }

    public function testEvaluate()
    {
        $this->assertTrue($this->condition->evaluate('anything'));
    }

    public function testInitializeSuccess()
    {
        $this->assertSame($this->condition, $this->condition->initialize([]));
    }

    public function testInitializeFails()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Options are prohibited');

        $this->condition->initialize(['anything']);
    }

    /**
     * @dataProvider toArrayDataProvider
     */
    public function testToArray(?string $message, array $expected)
    {
        if ($message !== null) {
            $this->condition->setMessage($message);
        }
        $actual = $this->condition->toArray();
        $this->assertEquals($expected, $actual);
    }

    public function toArrayDataProvider(): array
    {
        return [
            [
                'message'  => null,
                'expected' => [
                    '@true' => null
                ]
            ],
            [
                'message'  => 'Test',
                'expected' => [
                    '@true' => [
                        'message' => 'Test'
                    ]
                ]
            ]
        ];
    }

    /**
     * @dataProvider compileDataProvider
     */
    public function testCompile(?string $message, string $expected)
    {
        if ($message !== null) {
            $this->condition->setMessage($message);
        }
        $actual = $this->condition->compile('$factory');
        $this->assertEquals($expected, $actual);
    }

    public function compileDataProvider(): array
    {
        return [
            [
                'message'  => null,
                'expected' => '$factory->create(\'true\', [])'
            ],
            [
                'message'  => 'Test',
                'expected' => '$factory->create(\'true\', [])->setMessage(\'Test\')'
            ]
        ];
    }
}
