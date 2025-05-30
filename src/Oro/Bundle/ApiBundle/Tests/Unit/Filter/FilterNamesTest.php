<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Filter;

use Oro\Bundle\ApiBundle\Filter\FilterNames;
use PHPUnit\Framework\TestCase;

class FilterNamesTest extends TestCase
{
    public function testAllNames(): void
    {
        $filterNames = new FilterNames(
            'sort',
            'page[number]',
            'page[size]',
            'meta',
            'filter',
            'fields',
            'include'
        );
        self::assertEquals('sort', $filterNames->getSortFilterName());
        self::assertEquals('page[number]', $filterNames->getPageNumberFilterName());
        self::assertEquals('page[size]', $filterNames->getPageSizeFilterName());
        self::assertEquals('meta', $filterNames->getMetaPropertyFilterName());
        self::assertEquals('filter', $filterNames->getDataFilterGroupName());
        self::assertEquals('fields', $filterNames->getFieldsFilterGroupName());
        self::assertEquals('fields[%s]', $filterNames->getFieldsFilterTemplate());
        self::assertEquals('include', $filterNames->getIncludeFilterName());
    }

    public function testOptionalNames(): void
    {
        $filterNames = new FilterNames(
            'sort',
            'page[number]',
            'page[size]',
            'meta'
        );
        self::assertEquals('sort', $filterNames->getSortFilterName());
        self::assertEquals('page[number]', $filterNames->getPageNumberFilterName());
        self::assertEquals('page[size]', $filterNames->getPageSizeFilterName());
        self::assertEquals('meta', $filterNames->getMetaPropertyFilterName());
        self::assertNull($filterNames->getDataFilterGroupName());
        self::assertNull($filterNames->getFieldsFilterGroupName());
        self::assertNull($filterNames->getFieldsFilterTemplate());
        self::assertNull($filterNames->getIncludeFilterName());
    }
}
