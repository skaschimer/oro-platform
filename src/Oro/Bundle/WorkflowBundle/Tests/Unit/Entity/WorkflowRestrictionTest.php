<?php

namespace Oro\Bundle\WorkflowBundle\Tests\Unit\Entity;

use Oro\Bundle\EntityExtendBundle\PropertyAccess;
use Oro\Bundle\WorkflowBundle\Entity\WorkflowDefinition;
use Oro\Bundle\WorkflowBundle\Entity\WorkflowRestriction;
use Oro\Bundle\WorkflowBundle\Entity\WorkflowStep;
use PHPUnit\Framework\TestCase;

class WorkflowRestrictionTest extends TestCase
{
    /**
     * @dataProvider propertiesDataProvider
     *
     * @param string $property
     * @param mixed  $value
     */
    public function testSettersAndGetters($property, $value): void
    {
        $restriction = new WorkflowRestriction();
        $accessor = PropertyAccess::createPropertyAccessor();
        $accessor->setValue($restriction, $property, $value);
        $this->assertEquals($value, $accessor->getValue($restriction, $property));
    }

    public function propertiesDataProvider(): array
    {
        return [
            ['attribute', 'test'],
            ['step', new WorkflowStep()],
            ['definition', new WorkflowDefinition()],
            ['entityClass', 'TestEntity'],
            ['field', 'test'],
            ['mode', 'full'],
            ['values', []],
        ];
    }

    public function testImport(): void
    {
        $restriction = new WorkflowRestriction();
        $step = new WorkflowStep();
        $step->setName('step');
        $definition = new WorkflowDefinition();
        $definition->addStep($step);

        $restriction->setAttribute('attribute');
        $restriction->setStep($step);
        $restriction->setEntityClass('TestEntity');
        $restriction->setField('test');
        $restriction->setMode('allow');
        $restriction->setValues(['1']);
        $newRestriction = new WorkflowRestriction();
        $newRestriction->setDefinition($definition);
        $this->assertEquals($newRestriction, $newRestriction->import($restriction));

        $this->assertEquals('attribute', $newRestriction->getAttribute());
        $this->assertEquals($step, $newRestriction->getStep());
        $this->assertEquals('TestEntity', $newRestriction->getEntityClass());
        $this->assertEquals('test', $newRestriction->getField());
        $this->assertEquals('allow', $newRestriction->getMode());
        $this->assertEquals(['1'], $newRestriction->getValues());

        $this->assertEquals($restriction->getHashKey(), $newRestriction->getHashKey());
    }
}
