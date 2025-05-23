<?php

namespace Oro\Bundle\EntityExtendBundle\Tests\Unit\EntityExtend;

use Oro\Bundle\EntityExtendBundle\EntityExtend\PropertyAccessorWithDotArraySyntax;
use Oro\Bundle\EntityExtendBundle\PropertyAccess;
use Symfony\Component\PropertyAccess\Exception\NoSuchPropertyException;

abstract class PropertyAccessorArrayAccessTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var PropertyAccessorWithDotArraySyntax
     */
    protected $propertyAccessor;

    #[\Override]
    protected function setUp(): void
    {
        $this->propertyAccessor = PropertyAccess::createPropertyAccessorWithDotSyntax(
            throw: PropertyAccessorWithDotArraySyntax::THROW_ON_INVALID_INDEX
        );
    }

    abstract protected function getContainer(array $array);

    public function getValidPropertyPaths()
    {
        return array(
            array($this->getContainer(array('firstName' => 'John')), 'firstName', 'John'),
            array($this->getContainer(array('firstName' => 'John')), '[firstName]', 'John'),
            array(
                $this->getContainer(array('person' => $this->getContainer(array('firstName' => 'John')))),
                'person.firstName',
                'John'
            ),
            array(
                $this->getContainer(array('person' => $this->getContainer(array('firstName' => 'John')))),
                'person[firstName]',
                'John'
            ),
            array(
                $this->getContainer(array('person' => $this->getContainer(array('firstName' => 'John')))),
                '[person][firstName]',
                'John'
            ),
            array(
                $this->getContainer(array('person' => $this->getContainer(array('firstName' => 'John')))),
                '[person].firstName',
                'John'
            ),
        );
    }

    public function getPathsWithMissingIndex()
    {
        return array(
            array($this->getContainer(array('firstName' => 'John')), 'lastName'),
            array($this->getContainer(array('firstName' => 'John')), '[lastName]'),
            array($this->getContainer(array()), 'index.lastName'),
            array($this->getContainer(array()), 'index[lastName]'),
            array($this->getContainer(array()), '[index][lastName]'),
            array($this->getContainer(array()), '[index].lastName'),
            array($this->getContainer(array('index' => array())), 'index.lastName'),
            array($this->getContainer(array('index' => array())), '[index][lastName]'),
            array($this->getContainer(array('index' => array('firstName' => 'John'))), 'index.lastName'),
            array($this->getContainer(array('index' => array('firstName' => 'John'))), '[index][lastName]'),
        );
    }

    /**
     * @dataProvider getValidPropertyPaths
     */
    public function testGetValue($collection, $path, $value)
    {
        $this->assertSame($value, $this->propertyAccessor->getValue($collection, $path));
    }

    /**
     * @dataProvider getPathsWithMissingIndex
     */
    public function testGetValueThrowsExceptionIfIndexNotFound($collection, $path)
    {
        $this->expectException(NoSuchPropertyException::class);
        $this->propertyAccessor->getValue($collection, $path);
    }

    /**
     * @dataProvider getPathsWithMissingIndex
     */
    public function testGetValueThrowsNoExceptionIfIndexNotFoundAndIndexExceptionsDisabled($collection, $path)
    {
        $this->propertyAccessor = PropertyAccess::createPropertyAccessorWithDotSyntax(
            throw: PropertyAccessorWithDotArraySyntax::DO_NOT_THROW
        );
        $this->assertNull($this->propertyAccessor->getValue($collection, $path));
    }

    /**
     * @dataProvider getValidPropertyPaths
     */
    public function testSetValue($collection, $path)
    {
        $this->propertyAccessor->setValue($collection, $path, 'Updated');

        $this->assertSame('Updated', $this->propertyAccessor->getValue($collection, $path));
    }

    /**
     * @dataProvider getPathsWithMissingIndex
     */
    public function testSetValueThrowsNoExceptionIfIndexNotFound($collection, $path)
    {
        $this->propertyAccessor->setValue($collection, $path, 'Updated');

        $this->assertSame('Updated', $this->propertyAccessor->getValue($collection, $path));
    }

    /**
     * @dataProvider getValidPropertyPaths
     */
    public function testRemove($collection, $path)
    {
        $this->propertyAccessor->remove($collection, $path);

        try {
            $this->propertyAccessor->getValue($collection, $path);
            $this->fail(sprintf('It is expected that "%s" is removed.', $path));
        } catch (NoSuchPropertyException $ex) {
        }
    }

    /**
     * @dataProvider getPathsWithMissingIndex
     */
    public function testRemoveThrowsNoExceptionIfIndexNotFound($collection, $path)
    {
        $clone = unserialize(serialize($collection));

        $this->propertyAccessor->remove($collection, $path);

        $this->assertEquals($clone, $collection);
    }

    /**
     * @dataProvider getValidPropertyPaths
     */
    public function testIsReadable($collection, $path)
    {
        $this->assertTrue($this->propertyAccessor->isReadable($collection, $path));
    }

    /**
     * @dataProvider getValidPropertyPaths
     */
    public function testIsWritable($collection, $path)
    {
        $this->assertTrue($this->propertyAccessor->isWritable($collection, $path, 'Updated'));
    }
}
