<?php

namespace Oro\Component\Layout\Tests\Unit\Block\Type;

use Oro\Component\Layout\Block\Type\Options;

class OptionsTest extends \PHPUnit\Framework\TestCase
{
    /** @var Options */
    private $options;

    #[\Override]
    protected function setUp(): void
    {
        $this->options = new Options(['value' => 'test']);
    }

    public function testGet()
    {
        $this->assertEquals('test', $this->options->get('value'));

        $this->expectException(\OutOfBoundsException::class);
        $this->assertFalse($this->options->get('nameNotExist'));
    }

    public function testOffsetGet()
    {
        $this->assertEquals('test', $this->options->offsetGet('value'));
    }

    public function testOffsetSet()
    {
        $this->options->offsetSet('attribute', 'bar');
        $this->assertSame(['value' => 'test', 'attribute' => 'bar'], $this->options->toArray());
    }

    public function testOffsetUnset()
    {
        $this->options->offsetUnset('value');
        $this->assertSame([], $this->options->toArray());
    }

    public function testOffsetExists()
    {
        $this->assertTrue($this->options->offsetExists('value'));
        $this->assertFalse($this->options->offsetExists('attr'));
    }

    public function testGetAll()
    {
        $this->assertSame(['value' => 'test'], $this->options->toArray());
    }

    public function testSetMultiple()
    {
        $values = ['value' => 'test1', 'value2' => 'test2', 'value3' => 'test3'];
        $this->options->setMultiple($values);
        $this->assertEquals($values, $this->options->toArray());
    }
}
