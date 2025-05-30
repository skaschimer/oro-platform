<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\DataTransformer;

use Oro\Bundle\ApiBundle\DataTransformer\EntityClassToEntityTypeTransformer;
use Oro\Bundle\ApiBundle\Processor\Context;
use Oro\Bundle\ApiBundle\Request\DataType;
use Oro\Bundle\ApiBundle\Request\RequestType;
use Oro\Bundle\ApiBundle\Request\ValueNormalizer;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class EntityClassToEntityTypeTransformerTest extends TestCase
{
    private ValueNormalizer&MockObject $valueNormalizer;
    private EntityClassToEntityTypeTransformer $transformer;

    #[\Override]
    protected function setUp(): void
    {
        $this->valueNormalizer = $this->createMock(ValueNormalizer::class);

        $this->transformer = new EntityClassToEntityTypeTransformer($this->valueNormalizer);
    }

    /**
     * @dataProvider emptyValueProvider
     */
    public function testTransformEmptyValue(?string $value): void
    {
        $this->valueNormalizer->expects(self::never())
            ->method('normalizeValue');

        self::assertSame(
            $value,
            $this->transformer->transform($value, [], [])
        );
    }

    public function emptyValueProvider(): array
    {
        return [
            [null],
            ['']
        ];
    }

    public function testTransform(): void
    {
        $value = 'Test\Class1';
        $expectedValue = 'class1';
        $requestType = new RequestType([RequestType::REST]);

        $this->valueNormalizer->expects(self::once())
            ->method('normalizeValue')
            ->with('Test\Class1', DataType::ENTITY_TYPE, $requestType)
            ->willReturn($expectedValue);

        self::assertEquals(
            $expectedValue,
            $this->transformer->transform($value, [], [Context::REQUEST_TYPE => $requestType])
        );
    }
}
