<?php

namespace Oro\Bundle\SearchBundle\Tests\Unit\Datagrid\Filter;

use Doctrine\Common\Collections\Expr\Comparison as DoctrineComparison;
use Oro\Bundle\FilterBundle\Datasource\FilterDatasourceAdapterInterface;
use Oro\Bundle\FilterBundle\Filter\FilterUtility;
use Oro\Bundle\FilterBundle\Form\Type\Filter\TextFilterType;
use Oro\Bundle\SearchBundle\Datagrid\Filter\Adapter\SearchFilterDatasourceAdapter;
use Oro\Bundle\SearchBundle\Datagrid\Filter\SearchStringFilter;
use Oro\Bundle\SearchBundle\Query\Criteria\Comparison;
use Oro\Component\Exception\UnexpectedTypeException;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Form\FormFactoryInterface;

class SearchStringFilterTest extends TestCase
{
    private SearchStringFilter $filter;

    #[\Override]
    protected function setUp(): void
    {
        $formFactory = $this->createMock(FormFactoryInterface::class);

        $this->filter = new SearchStringFilter($formFactory, new FilterUtility());
    }

    public function testThrowsExceptionForWrongFilterDatasourceAdapter(): void
    {
        $this->expectException(UnexpectedTypeException::class);

        $this->filter->apply(
            $this->createMock(FilterDatasourceAdapterInterface::class),
            ['type' => TextFilterType::TYPE_EQUAL, 'value' => 'bar']
        );
    }

    /**
     * @dataProvider applyDataProvider
     */
    public function testApply(int $filterType, string $comparisonOperator, array $filterParams = []): void
    {
        $fieldName = 'field';
        $fieldValue = 'value';

        $ds = $this->createMock(SearchFilterDatasourceAdapter::class);
        $ds->expects($this->once())
            ->method('addRestriction')
            ->with($this->isInstanceOf(DoctrineComparison::class), FilterUtility::CONDITION_AND)
            ->willReturnCallback(
                function (DoctrineComparison $comparison) use ($fieldName, $comparisonOperator, $fieldValue) {
                    $this->assertEquals($fieldName, $comparison->getField());
                    $this->assertEquals($comparisonOperator, $comparison->getOperator());
                    $this->assertEquals($fieldValue, $comparison->getValue()->getValue());
                }
            );

        $this->filter->init('test', array_merge([
            FilterUtility::FORCE_LIKE_KEY => false,
            FilterUtility::MIN_LENGTH_KEY => 0,
            FilterUtility::MAX_LENGTH_KEY => 100,
            FilterUtility::DATA_NAME_KEY => $fieldName
        ], $filterParams));
        $this->filter->apply($ds, ['type' => $filterType, 'value' => $fieldValue]);
    }

    /**
     * @dataProvider applyWithMinAndMaxLengthViolatedDataProvider
     */
    public function testApplyWithMinAndMaxLengthViolated(string $fieldValue, array $filterParams = []): void
    {
        $filterType = 'anyCustomFilterType';
        $fieldName = 'field';

        $ds = $this->createMock(SearchFilterDatasourceAdapter::class);
        $ds->expects($this->never())
            ->method('addRestriction');

        $this->filter->init('test', array_merge([
            FilterUtility::FORCE_LIKE_KEY => false,
            FilterUtility::MIN_LENGTH_KEY => 0,
            FilterUtility::MAX_LENGTH_KEY => 100,
            FilterUtility::DATA_NAME_KEY => $fieldName
        ], $filterParams));
        $this->filter->apply($ds, ['type' => $filterType, 'value' => $fieldValue]);
    }

    public function applyDataProvider(): array
    {
        return [
            'contains' => [
                'filterType' => TextFilterType::TYPE_CONTAINS,
                'comparisonOperator' => Comparison::CONTAINS,
                [
                    FilterUtility::FORCE_LIKE_KEY => false,
                ]
            ],
            'contains force like' => [
                'filterType' => TextFilterType::TYPE_CONTAINS,
                'comparisonOperator' => Comparison::LIKE,
                [
                    FilterUtility::FORCE_LIKE_KEY => true,
                ]
            ],
            'contains like min_length' => [
                'filterType' => TextFilterType::TYPE_CONTAINS,
                'comparisonOperator' => Comparison::LIKE,
                [
                    FilterUtility::FORCE_LIKE_KEY => true,
                    FilterUtility::MIN_LENGTH_KEY => 5,
                ]
            ],
            'contains like max_length' => [
                'filterType' => TextFilterType::TYPE_CONTAINS,
                'comparisonOperator' => Comparison::LIKE,
                [
                    FilterUtility::FORCE_LIKE_KEY => true,
                    FilterUtility::MAX_LENGTH_KEY => 20,
                ]
            ],
            'not contains' => [
                'filterType' => TextFilterType::TYPE_NOT_CONTAINS,
                'comparisonOperator' => Comparison::NOT_CONTAINS,
                [
                    FilterUtility::FORCE_LIKE_KEY => false,
                ]
            ],
            'not contains force like' => [
                'filterType' => TextFilterType::TYPE_NOT_CONTAINS,
                'comparisonOperator' => Comparison::NOT_LIKE,
                [
                    FilterUtility::FORCE_LIKE_KEY => true,
                ]
            ],
            'equal' => [
                'filterType' => TextFilterType::TYPE_EQUAL,
                'comparisonOperator' => Comparison::EQ,
            ],
        ];
    }

    public function applyWithMinAndMaxLengthViolatedDataProvider(): array
    {
        return [
            ['abc', [FilterUtility::MIN_LENGTH_KEY => 4]],
            ['abcabcabc', [FilterUtility::MAX_LENGTH_KEY => 6]],
        ];
    }

    public function testPrepareData(): void
    {
        $this->expectException(\BadMethodCallException::class);
        $this->filter->prepareData([]);
    }
}
