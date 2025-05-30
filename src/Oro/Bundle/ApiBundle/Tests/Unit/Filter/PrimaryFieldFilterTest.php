<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Filter;

use Doctrine\Common\Collections\Criteria;
use Doctrine\Common\Collections\Expr\Comparison;
use Doctrine\Common\Collections\Expr\CompositeExpression;
use Oro\Bundle\ApiBundle\Filter\FilterOperator;
use Oro\Bundle\ApiBundle\Filter\FilterValue;
use Oro\Bundle\ApiBundle\Filter\PrimaryFieldFilter;
use PHPUnit\Framework\TestCase;

class PrimaryFieldFilterTest extends TestCase
{
    public function testFieldIsNotSpecified(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('The Field must not be empty.');

        $filter = new PrimaryFieldFilter('string');
        $filter->apply(new Criteria(), new FilterValue('path', 'value', FilterOperator::EQ));
    }

    public function testDataFieldIsNotSpecified(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('The DataField must not be empty.');

        $filter = new PrimaryFieldFilter('string');
        $filter->setField('association');
        $filter->apply(new Criteria(), new FilterValue('path', 'value', FilterOperator::EQ));
    }

    public function testOptions(): void
    {
        $filter = new PrimaryFieldFilter('string');
        $filter->setDataField('dataField');
        $filter->setPrimaryFlagField('primaryFlagField');

        self::assertEquals('dataField', $filter->getDataField());
        self::assertEquals('primaryFlagField', $filter->getPrimaryFlagField());
    }

    public function testApplyNullValue(): void
    {
        $filter = new PrimaryFieldFilter('string');
        $filter->setField('association');
        $filter->setDataField('dataField');

        $criteria = new Criteria();
        $filter->apply($criteria, null);

        self::assertNull($criteria->getWhereExpression());
    }

    public function testApplyWithDefaultPrimaryFlagField(): void
    {
        $filter = new PrimaryFieldFilter('string');
        $filter->setField('association');
        $filter->setDataField('dataField');

        $criteria = new Criteria();
        $filter->apply($criteria, new FilterValue('path', 'value', FilterOperator::EQ));

        self::assertEquals(
            new CompositeExpression(
                'AND',
                [
                    new Comparison('association.dataField', Comparison::EQ, 'value'),
                    new Comparison('association.primary', Comparison::EQ, true)
                ]
            ),
            $criteria->getWhereExpression()
        );
    }

    public function testApplyWithCustomPrimaryFlagField(): void
    {
        $filter = new PrimaryFieldFilter('string');
        $filter->setField('association');
        $filter->setDataField('dataField');
        $filter->setPrimaryFlagField('primaryFlagField');

        $criteria = new Criteria();
        $filter->apply($criteria, new FilterValue('path', 'value', FilterOperator::EQ));

        self::assertEquals(
            new CompositeExpression(
                'AND',
                [
                    new Comparison('association.dataField', Comparison::EQ, 'value'),
                    new Comparison('association.primaryFlagField', Comparison::EQ, true)
                ]
            ),
            $criteria->getWhereExpression()
        );
    }
}
