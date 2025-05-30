<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Filter;

use Doctrine\Common\Collections\Criteria;
use Oro\Bundle\ApiBundle\Exception\InvalidFilterValueException;
use Oro\Bundle\ApiBundle\Filter\FilterValue;
use Oro\Bundle\ApiBundle\Filter\PageSizeFilter;
use Oro\Bundle\ApiBundle\Request\DataType;
use PHPUnit\Framework\TestCase;

class PageSizeFilterTest extends TestCase
{
    public function testApplyWithoutFilter(): void
    {
        $filter = new PageSizeFilter(DataType::INTEGER);
        $criteria = new Criteria();

        $filter->apply($criteria);

        self::assertNull($criteria->getMaxResults());
    }

    public function testApplyWithFilter(): void
    {
        $filter = new PageSizeFilter(DataType::INTEGER);
        $filterValue = new FilterValue('path', 10, null);
        $criteria = new Criteria();

        $filter->apply($criteria, $filterValue);

        self::assertSame(10, $criteria->getMaxResults());
    }

    public function testApplyWithFilterAndNullValue(): void
    {
        $filter = new PageSizeFilter(DataType::INTEGER);
        $filterValue = new FilterValue('path', null, null);
        $criteria = new Criteria();

        $filter->apply($criteria, $filterValue);

        self::assertNull($criteria->getFirstResult());
    }

    public function testApplyWithFilterAndValueEqualsToZero(): void
    {
        $filter = new PageSizeFilter(DataType::INTEGER);
        $filterValue = new FilterValue('path', 0, null);
        $criteria = new Criteria();

        $filter->apply($criteria, $filterValue);

        self::assertSame(0, $criteria->getMaxResults());
    }

    public function testApplyWithFilterAndValueEqualsToMinusOne(): void
    {
        $filter = new PageSizeFilter(DataType::INTEGER);
        $filterValue = new FilterValue('path', -1, null);
        $criteria = new Criteria();

        $filter->apply($criteria, $filterValue);

        self::assertSame(-1, $criteria->getMaxResults());
    }

    public function testApplyWithFilterAndValueLessThanMinusOne(): void
    {
        $this->expectException(InvalidFilterValueException::class);
        $this->expectExceptionMessage('The value should be greater than or equals to -1.');

        $filter = new PageSizeFilter(DataType::INTEGER);
        $filterValue = new FilterValue('path', -2, null);
        $criteria = new Criteria();

        $filter->apply($criteria, $filterValue);
    }
}
