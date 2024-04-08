<?php

namespace Oro\Bundle\WorkflowBundle\Tests\Unit\Model;

use Oro\Bundle\WorkflowBundle\Model\WorkflowResult;

class WorkflowResultTest extends \PHPUnit\Framework\TestCase
{
    /** @var WorkflowResult */
    private $result;

    protected function setUp(): void
    {
        $this->result = new WorkflowResult();
    }

    public function testConstructor()
    {
        $this->assertEquals([], $this->result->getValues());
        $values = [
            'foo' => 'bar'
        ];
        $this->result = new WorkflowResult($values);
        $this->assertEquals($values, $this->result->getValues());
    }

    public function testHasGetSetRemove()
    {
        $this->assertFalse($this->result->has('foo'));
        $this->assertNull($this->result->get('foo'));

        $this->result->set('foo', 'bar');
        $this->assertTrue($this->result->has('foo'));
        $this->assertEquals('bar', $this->result->get('foo'));

        $this->result->remove('foo');
        $this->assertFalse($this->result->has('foo'));
        $this->assertNull($this->result->get('foo'));
    }

    public function testIssetGetSetUnset()
    {
        $this->assertTrue(isset($this->result->foo));
        $this->assertNull($this->result->foo);

        $this->result->foo = 'bar';
        $this->assertTrue(isset($this->result->foo));
        $this->assertEquals('bar', $this->result->foo);

        unset($this->result->foo);
        $this->assertTrue(isset($this->result->foo));
        $this->assertNull($this->result->foo);
    }

    public function testArrayAccess()
    {
        $this->assertInstanceOf('ArrayAccess', $this->result);

        $this->assertFalse(isset($this->result['foo']));
        $this->assertNull($this->result['foo']);

        $this->result['foo'] = 'bar';
        $this->assertTrue(isset($this->result['foo']));
        $this->assertEquals('bar', $this->result['foo']);

        unset($this->result['foo']);
        $this->assertFalse(isset($this->result['foo']));
        $this->assertNull($this->result['foo']);
    }

    public function testCount()
    {
        $this->assertCount(0, $this->result);

        $this->result->set('foo', 'bar');
        $this->assertCount(1, $this->result);

        $this->result->set('baz', 'qux');
        $this->assertCount(2, $this->result);

        $this->result->remove('foo');
        $this->assertCount(1, $this->result);

        $this->result->remove('baz');
        $this->assertCount(0, $this->result);
    }

    public function testIsEmpty()
    {
        $this->assertTrue($this->result->isEmpty());

        $this->result->set('foo', 'bar');
        $this->assertFalse($this->result->isEmpty());
    }

    public function testIterable()
    {
        $this->result->set('foo', 'bar');
        $this->result->set('baz', 'qux');

        $data = [];
        foreach ($this->result as $key => $value) {
            $data[$key] = $value;
        }

        $this->assertEquals(['foo' => 'bar', 'baz' => 'qux'], $data);
    }

    public function testGetValuesAll()
    {
        $this->result->set('foo', 'foo_value');
        $this->result->set('bar', 'bar_value');
        $this->result->set('baz', null);
        $this->result->set('quux', 'quux_value');

        $this->assertEquals(
            [
                'foo' => 'foo_value',
                'bar' => 'bar_value',
                'baz' => null,
                'quux' => 'quux_value',
            ],
            $this->result->getValues()
        );
    }

    public function testGetValuesWithNames()
    {
        $this->result->set('foo', 'foo_value');
        $this->result->set('bar', 'bar_value');
        $this->result->set('baz', null);
        $this->result->set('quux', 'quux_value');

        $this->assertEquals(
            [
                'foo' => 'foo_value',
                'baz' => null,
                'qux' => null,
                'quux' => 'quux_value',
            ],
            $this->result->getValues(['foo', 'baz', 'qux', 'quux'])
        );
    }

    public function testAdd()
    {
        $this->result->set('foo', 'foo_value');
        $this->result->set('bar', 'bar_value');
        $this->assertEquals(
            [
                'foo' => 'foo_value',
                'bar' => 'bar_value'
            ],
            $this->result->getValues()
        );

        $this->result->add(
            [
                'bar' => 'new_bar_value',
                'baz' => 'baz_value',
            ]
        );
        $this->assertEquals(
            [
                'foo' => 'foo_value',
                'bar' => 'new_bar_value',
                'baz' => 'baz_value',
            ],
            $this->result->getValues()
        );
    }
}
