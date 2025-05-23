<?php

namespace Oro\Bundle\EntityMergeBundle\Tests\Unit\Model\Accessor;

use Oro\Bundle\EntityExtendBundle\PropertyAccess;
use Oro\Bundle\EntityMergeBundle\Metadata\FieldMetadata;
use Oro\Bundle\EntityMergeBundle\Model\Accessor\DefaultAccessor;
use Oro\Bundle\EntityMergeBundle\Tests\Unit\Stub\EntityStub;
use PHPUnit\Framework\TestCase;

class DefaultAccessorTest extends TestCase
{
    private DefaultAccessor $accessor;

    #[\Override]
    protected function setUp(): void
    {
        $this->accessor = new DefaultAccessor(PropertyAccess::createPropertyAccessor());
    }

    private function getFieldMetadata(?string $fieldName = null, array $options = []): FieldMetadata
    {
        $result = $this->createMock(FieldMetadata::class);
        $result->expects(self::any())
            ->method('getFieldName')
            ->willReturn($fieldName);
        $result->expects(self::any())
            ->method('get')
            ->willReturnCallback(function ($code) use ($options) {
                self::assertArrayHasKey($code, $options);

                return $options[$code];
            });
        $result->expects(self::any())
            ->method('has')
            ->willReturnCallback(function ($code) use ($options) {
                return isset($options[$code]);
            });

        return $result;
    }

    public function testGetName(): void
    {
        self::assertEquals('default', $this->accessor->getName());
    }

    /**
     * @dataProvider getValueDataProvider
     */
    public function testGetValue(object $entity, FieldMetadata $metadata, mixed $expectedValue): void
    {
        self::assertSame($expectedValue, $this->accessor->getValue($entity, $metadata));
    }

    public function getValueDataProvider(): array
    {
        return [
            'default' => [
                'entity' => new EntityStub('foo'),
                'metadata' => $this->getFieldMetadata('id'),
                'expected' => 'foo'
            ],
            'getter' => [
                'entity' => new EntityStub('foo', new EntityStub('bar')),
                'metadata' => $this->getFieldMetadata('id', ['getter' => 'getParentId']),
                'expected' => 'bar'
            ],
            'property_path' => [
                'entity' => new EntityStub('foo', new EntityStub('bar')),
                'metadata' => $this->getFieldMetadata('id', ['property_path' => 'parent.id']),
                'expected' => 'bar'
            ]
        ];
    }

    /**
     * @dataProvider setValueDataProvider
     */
    public function testSetValue(object $entity, FieldMetadata $metadata, mixed $value, object $expectedEntity): void
    {
        $this->accessor->setValue($entity, $metadata, $value);
        self::assertEquals($expectedEntity, $entity);
    }

    public function setValueDataProvider(): array
    {
        return [
            'default' => [
                'entity' => new EntityStub(),
                'metadata' => $this->getFieldMetadata('id'),
                'value' => 'foo',
                'expected' => new EntityStub('foo')
            ],
            'setter' => [
                'entity' => new EntityStub('foo', new EntityStub('bar')),
                'metadata' => $this->getFieldMetadata('id', ['setter' => 'setParentId']),
                'value' => 'baz',
                'expected' => new EntityStub('foo', new EntityStub('baz'))
            ],
            'property_path' => [
                'entity' => new EntityStub('foo', new EntityStub('bar')),
                'metadata' => $this->getFieldMetadata('id', ['property_path' => 'parent.id']),
                'value' => 'baz',
                'expected' => new EntityStub('foo', new EntityStub('baz'))
            ]
        ];
    }
}
