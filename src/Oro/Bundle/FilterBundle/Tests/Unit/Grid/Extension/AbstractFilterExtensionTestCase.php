<?php

namespace Oro\Bundle\FilterBundle\Tests\Unit\Grid\Extension;

use Oro\Bundle\DataGridBundle\Datagrid\Common\DatagridConfiguration;
use Oro\Bundle\DataGridBundle\Datagrid\Common\MetadataObject;
use Oro\Bundle\DataGridBundle\Datagrid\ParameterBag;
use Oro\Bundle\DataGridBundle\Extension\Formatter\Property\PropertyInterface;
use Oro\Bundle\DataGridBundle\Provider\State\DatagridStateProviderInterface;
use Oro\Bundle\FilterBundle\Filter\FilterInterface;
use Oro\Bundle\FilterBundle\Filter\FilterUtility;
use Oro\Bundle\FilterBundle\Grid\Extension\AbstractFilterExtension;
use Oro\Bundle\FilterBundle\Grid\Extension\Configuration;
use Oro\Bundle\FilterBundle\Provider\DatagridFiltersProviderInterface;
use Oro\Bundle\FilterBundle\Provider\FiltersMetadataProvider;
use Oro\Bundle\FilterBundle\Tests\Unit\Filter\Fixtures\FilterBagStub;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Form\FormInterface;

/**
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
abstract class AbstractFilterExtensionTestCase extends TestCase
{
    protected const DATAGRID_NAME = 'sampleDatagridName';
    protected const FILTER_NAME = 'sampleFilter1';
    protected const FILTER_TYPE = 'sampleFilterType1';
    protected const FILTER_LABEL = 'SampleFilterLabel1';
    protected const TRANSLATED_FILTER_LABEL = 'TranslatedFilterLabel1';

    protected FilterBagStub $filterBag;
    protected DatagridFiltersProviderInterface&MockObject $filtersProvider;
    protected FiltersMetadataProvider&MockObject $filtersMetadataProvider;
    protected DatagridStateProviderInterface&MockObject $filtersStateProvider;
    protected ParameterBag&MockObject $datagridParameters;
    protected AbstractFilterExtension $extension;

    #[\Override]
    protected function setUp(): void
    {
        $this->filterBag = new FilterBagStub();
        $this->filtersProvider = $this->createMock(DatagridFiltersProviderInterface::class);
        $this->filtersMetadataProvider = $this->createMock(FiltersMetadataProvider::class);
        $this->filtersStateProvider = $this->createMock(DatagridStateProviderInterface::class);
        $this->datagridParameters = $this->createMock(ParameterBag::class);
    }

    protected function createCommonDatagridConfig(): DatagridConfiguration
    {
        return $this->createDatagridConfig([
            'name' => self::DATAGRID_NAME,
            'filters' => [
                'columns' => [
                    self::FILTER_NAME => [
                        'label' => self::FILTER_LABEL,
                        FilterUtility::TYPE_KEY => self::FILTER_TYPE,
                    ],
                ],
            ],
        ]);
    }

    protected function createDatagridConfig(array $datagridConfigArray): DatagridConfiguration
    {
        return DatagridConfiguration::create($datagridConfigArray);
    }

    protected function createMetadataObject(array $metadataArray): MetadataObject
    {
        return MetadataObject::create($metadataArray);
    }

    protected function createFilter(): FilterInterface&MockObject
    {
        $filter = $this->createMock(FilterInterface::class);
        $filter->expects(self::any())
            ->method('getName')
            ->willReturn(self::FILTER_NAME);

        return $filter;
    }

    protected function createFilterForm(FilterInterface&MockObject $filter): FormInterface&MockObject
    {
        $filter->expects(self::atLeastOnce())
            ->method('getForm')
            ->willReturn($filterForm = $this->createMock(FormInterface::class));

        $filterForm->expects(self::atLeastOnce())
            ->method('submit');

        return $filterForm;
    }

    protected function mockStateProviders(array $filtersState, array $defaultFiltersState): void
    {
        $this->filtersStateProvider->expects(self::once())
            ->method('getStateFromParameters')
            ->with(self::isInstanceOf(DatagridConfiguration::class), $this->datagridParameters)
            ->willReturn($filtersState);
        $this->filtersStateProvider->expects(self::once())
            ->method('getDefaultState')
            ->with(self::isInstanceOf(DatagridConfiguration::class))
            ->willReturn($defaultFiltersState);
    }

    public function testProcessConfigs(): void
    {
        $datagridConfiguration = $this->createDatagridConfig([
            'filters' => [
                'columns' => [
                    self::FILTER_NAME => [
                        FilterUtility::TYPE_KEY => 'sampleFilterType1',
                        FilterUtility::DATA_NAME_KEY => 'sampleDataName',
                    ],
                ],
            ],
        ]);

        $this->filterBag->addFilter('sampleFilterType1', $this->createMock(FilterInterface::class));
        $this->extension->processConfigs($datagridConfiguration);

        $filtersNormalized = [
            'columns' => [
                self::FILTER_NAME => [
                    FilterUtility::TYPE_KEY             => 'sampleFilterType1',
                    FilterUtility::DATA_NAME_KEY        => 'sampleDataName',
                    FilterUtility::RENDERABLE_KEY       => true,
                    FilterUtility::VISIBLE_KEY          => true,
                    FilterUtility::DISABLED_KEY         => false,
                    FilterUtility::TRANSLATABLE_KEY     => true,
                    FilterUtility::FORCE_LIKE_KEY       => false,
                    FilterUtility::CASE_INSENSITIVE_KEY => true,
                    FilterUtility::MIN_LENGTH_KEY       => 0,
                    FilterUtility::MAX_LENGTH_KEY       => PHP_INT_MAX,
                ],
            ],
            'default' => [],
        ];

        self::assertEquals(
            $filtersNormalized,
            $datagridConfiguration->offsetGetByPath(Configuration::FILTERS_PATH)
        );
    }

    public function testVisitMetadataRequiredModuleIsAdded(): void
    {
        $datagridConfig = $this->createDatagridConfig(['name' => self::DATAGRID_NAME]);
        $metadata = $this->createMetadataObject([]);

        $this->extension->setParameters($this->datagridParameters);
        $this->extension->visitMetadata($datagridConfig, $metadata);

        $this->assertSame(
            ['orofilter/js/datafilter-builder'],
            $metadata->toArray()[MetadataObject::REQUIRED_MODULES_KEY]
        );
    }

    public function testVisitMetadataWhenFilterIsDisabled(): void
    {
        $datagridConfig = $this->createDatagridConfig([
            'name' => self::DATAGRID_NAME,
            'filters' => [
                'columns' => [
                    self::FILTER_NAME => [
                        FilterUtility::TYPE_KEY => self::FILTER_TYPE,
                        PropertyInterface::DISABLED_KEY => 1,
                    ]
                ]
            ],
        ]);

        $filter = $this->createMock(FilterInterface::class);
        $filter->expects(self::never())
            ->method('init');

        $this->filterBag->addFilter(self::FILTER_TYPE, $filter);
        $this->extension->setParameters($this->datagridParameters);
        $this->extension->visitMetadata($datagridConfig, $this->createMetadataObject([]));
    }

    public function testVisitMetadataStateIsSetWhenNoFilters(): void
    {
        $datagridConfig = $this->createDatagridConfig(['name' => self::DATAGRID_NAME]);
        $metadata = $this->createMetadataObject([]);

        $this->mockStateProviders(
            $filtersState = [self::FILTER_NAME => ['value' => 'sampleValue1']],
            $defaultFiltersState = [self::FILTER_NAME => ['value' => 'sampleValue0']]
        );

        $this->extension->setParameters($this->datagridParameters);
        $this->extension->visitMetadata($datagridConfig, $metadata);

        self::assertEquals($filtersState, $metadata->offsetGetByPath('[state][filters]'));
        self::assertEquals($defaultFiltersState, $metadata->offsetGetByPath('[initialState][filters]'));
    }

    public function testVisitMetadataFilterOptionsAreResolved(): void
    {
        $datagridConfig = $this->createCommonDatagridConfig();
        $metadata = $this->createMetadataObject([MetadataObject::LAZY_KEY => false]);

        $filter = $this->createFilter();
        $filter->expects(self::once())
            ->method('resolveOptions');

        $filterForm = $this->createFilterForm($filter);
        $filterForm->expects(self::exactly(2))
            ->method('isValid')
            ->willReturn(true);

        $this->mockStateProviders(
            $filtersState = [self::FILTER_NAME => ['value' => 'sampleValue1']],
            $defaultFiltersState = [self::FILTER_NAME => ['value' => 'sampleValue0']]
        );

        $this->filtersProvider->expects(self::once())
            ->method('getDatagridFilters')
            ->with($datagridConfig)
            ->willReturn([self::FILTER_NAME => $filter]);

        $filtersMetadata = ['sample_key' => 'sample_value'];
        $this->filtersMetadataProvider->expects(self::once())
            ->method('getMetadataForFilters')
            ->with([self::FILTER_NAME => $filter], $datagridConfig)
            ->willReturn($filtersMetadata);

        $this->extension->setParameters($this->datagridParameters);
        $this->extension->visitMetadata($datagridConfig, $metadata);

        self::assertEquals(['filters' => $filtersMetadata], $metadata->toArray(['filters']));
        self::assertEquals($filtersState, $metadata->offsetGetByPath('[state][filters]'));
        self::assertEquals($defaultFiltersState, $metadata->offsetGetByPath('[initialState][filters]'));
    }

    public function testVisitMetadataNoStates(): void
    {
        $datagridConfig = $this->createCommonDatagridConfig();
        $metadata = $this->createMetadataObject([]);

        $filter = $this->createFilter();
        $filter->expects(self::never())
            ->method('resolveOptions');
        $filter->expects(self::never())
            ->method('setFilterState');

        $this->mockStateProviders($filtersState = [], $defaultFiltersState = []);

        $this->filtersProvider->expects(self::once())
            ->method('getDatagridFilters')
            ->with($datagridConfig)
            ->willReturn([self::FILTER_NAME => $filter]);

        $filtersMetadata = ['sample_key' => 'sample_value'];
        $this->filtersMetadataProvider->expects(self::once())
            ->method('getMetadataForFilters')
            ->with([self::FILTER_NAME => $filter], $datagridConfig)
            ->willReturn($filtersMetadata);

        $this->extension->setParameters($this->datagridParameters);
        $this->extension->visitMetadata($datagridConfig, $metadata);

        self::assertEquals(['filters' => $filtersMetadata], $metadata->toArray(['filters']));
        self::assertEquals($filtersState, $metadata->offsetGetByPath('[state][filters]'));
        self::assertEquals($defaultFiltersState, $metadata->offsetGetByPath('[initialState][filters]'));
    }

    /**
     * @dataProvider visitMetadataNoStateDataProvider
     */
    public function testVisitMetadataStatesAreValidated(
        array $filtersState,
        array $defaultFiltersState,
        array $isValid,
        array $expectedMetadata
    ): void {
        $datagridConfig = $this->createCommonDatagridConfig();
        $metadata = $this->createMetadataObject([]);

        $filter = $this->createFilter();
        $filter->expects(self::never())
            ->method('resolveOptions');
        $filter->expects(self::once())
            ->method('setFilterState')
            ->with($filtersState[self::FILTER_NAME] ?? null);

        $filterForm = $this->createFilterForm($filter);
        $filterForm->expects(self::exactly(count($isValid)))
            ->method('isValid')
            ->willReturnOnConsecutiveCalls(...$isValid);

        $this->mockStateProviders($filtersState, $defaultFiltersState);

        $this->filtersProvider->expects(self::once())
            ->method('getDatagridFilters')
            ->with($datagridConfig)
            ->willReturn([self::FILTER_NAME => $filter]);

        $filtersMetadata = ['sample_key' => 'sample_value'];
        $this->filtersMetadataProvider->expects(self::once())
            ->method('getMetadataForFilters')
            ->with([self::FILTER_NAME => $filter], $datagridConfig)
            ->willReturn($filtersMetadata);

        $this->extension->setParameters($this->datagridParameters);
        $this->extension->visitMetadata($datagridConfig, $metadata);

        $metadataAsArray = $metadata->toArray();
        foreach ($expectedMetadata as $key => $expectedValue) {
            $this->assertSame($expectedValue, $metadataAsArray[$key]);
        }
    }

    public function visitMetadataNoStateDataProvider(): array
    {
        return [
            'default filter state is valid, filter state is not valid' => [
                'filtersState' => [self::FILTER_NAME => ['value' => 'sampleValue1']],
                'defaultFiltersState' => [self::FILTER_NAME => ['value' => 'sampleValue0']],
                'isValid' => [true, false],
                'expectedMetadata' => [
                    'initialState' => ['filters' => [self::FILTER_NAME => ['value' => 'sampleValue0']]],
                    'state' => ['filters' => []],
                ],
            ],
            'default filter state is not valid, filter state is valid' => [
                'filtersState' => [self::FILTER_NAME => ['value' => 'sampleValue1']],
                'defaultFiltersState' => [self::FILTER_NAME => ['value' => 'sampleValue0']],
                'isValid' => [false, true],
                'expectedMetadata' => [
                    'initialState' => ['filters' => []],
                    'state' => ['filters' => [self::FILTER_NAME => ['value' => 'sampleValue1']]],
                ],
            ],
            'default filter state and filter state are not valid' => [
                'filtersState' => [self::FILTER_NAME => ['value' => 'sampleValue1']],
                'defaultFiltersState' => [self::FILTER_NAME => ['value' => 'sampleValue0']],
                'isValid' => [false, false],
                'expectedMetadata' => [
                    'initialState' => ['filters' => []],
                    'state' => ['filters' => []],
                ],
            ],
            'default filter state and filter state are valid' => [
                'filtersState' => [self::FILTER_NAME => ['value' => 'sampleValue1']],
                'defaultFiltersState' => [self::FILTER_NAME => ['value' => 'sampleValue0']],
                'isValid' => [true, true],
                'expectedMetadata' => [
                    'initialState' => ['filters' => [self::FILTER_NAME => ['value' => 'sampleValue0']]],
                    'state' => ['filters' => [self::FILTER_NAME => ['value' => 'sampleValue1']]],
                ],
            ],
        ];
    }
}
