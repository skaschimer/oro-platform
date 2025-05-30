<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Processor\Shared;

use Oro\Bundle\ApiBundle\Config\EntityDefinitionConfig;
use Oro\Bundle\ApiBundle\Config\SortersConfig;
use Oro\Bundle\ApiBundle\Filter\FilterNames;
use Oro\Bundle\ApiBundle\Filter\FilterNamesRegistry;
use Oro\Bundle\ApiBundle\Filter\SortFilter;
use Oro\Bundle\ApiBundle\Processor\Shared\SetDefaultSorting;
use Oro\Bundle\ApiBundle\Request\DataType;
use Oro\Bundle\ApiBundle\Tests\Unit\Fixtures\Entity\Category;
use Oro\Bundle\ApiBundle\Tests\Unit\Fixtures\Entity\CompositeKeyEntity;
use Oro\Bundle\ApiBundle\Tests\Unit\Fixtures\Entity\User;
use Oro\Bundle\ApiBundle\Tests\Unit\Processor\GetList\GetListProcessorTestCase;
use Oro\Bundle\ApiBundle\Util\RequestExpressionMatcher;
use Oro\Component\Testing\Unit\TestContainerBuilder;

/**
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class SetDefaultSortingTest extends GetListProcessorTestCase
{
    private SetDefaultSorting $processor;

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();

        $filterNames = $this->createMock(FilterNames::class);
        $filterNames->expects(self::any())
            ->method('getSortFilterName')
            ->willReturn('sort');

        $this->processor = new SetDefaultSorting(
            new FilterNamesRegistry(
                [['filter_names', null]],
                TestContainerBuilder::create()->add('filter_names', $filterNames)->getContainer($this),
                new RequestExpressionMatcher()
            )
        );
    }

    public function testProcessWhenQueryIsAlreadyExist(): void
    {
        $query = new \stdClass();

        $this->context->setQuery($query);
        $this->processor->process($this->context);

        self::assertSame($query, $this->context->getQuery());
    }

    public function testProcessForEntityWithIdentifierNamedId(): void
    {
        $config = new EntityDefinitionConfig();
        $config->setIdentifierFieldNames(['id']);
        $config->addField('id');

        $configOfSorters = new SortersConfig();
        $configOfSorters->addField('id');

        $this->context->setClassName(User::class);
        $this->context->setConfig($config);
        $this->context->setConfigOfSorters($configOfSorters);
        $this->processor->process($this->context);

        $filters = $this->context->getFilters();
        self::assertCount(1, $filters);
        /** @var SortFilter $sortFilter */
        $sortFilter = $filters->get('sort');
        self::assertEquals('orderBy', $sortFilter->getDataType());
        self::assertEquals(['id' => 'ASC'], $sortFilter->getDefaultValue());
        self::assertFalse($filters->isIncludeInDefaultGroup('sort'));
    }

    public function testProcessForEntityWithIdentifierNotNamedId(): void
    {
        $config = new EntityDefinitionConfig();
        $config->setIdentifierFieldNames(['name']);
        $config->addField('name');

        $configOfSorters = new SortersConfig();
        $configOfSorters->addField('name');

        $this->context->setClassName(Category::class);
        $this->context->setConfig($config);
        $this->context->setConfigOfSorters($configOfSorters);
        $this->processor->process($this->context);

        $filters = $this->context->getFilters();
        self::assertCount(1, $filters);
        /** @var SortFilter $sortFilter */
        $sortFilter = $filters->get('sort');
        self::assertEquals('orderBy', $sortFilter->getDataType());
        self::assertEquals(['name' => 'ASC'], $sortFilter->getDefaultValue());
        self::assertFalse($filters->isIncludeInDefaultGroup('sort'));
    }

    public function testProcessForEntityWithSingleIdentifierAndSorterIsDisabled(): void
    {
        $config = new EntityDefinitionConfig();
        $config->setIdentifierFieldNames(['id']);
        $config->addField('id');

        $configOfSorters = new SortersConfig();
        $configOfSorters->addField('id')->setExcluded(true);

        $this->context->setClassName(User::class);
        $this->context->setConfig($config);
        $this->context->setConfigOfSorters($configOfSorters);
        $this->processor->process($this->context);

        $filters = $this->context->getFilters();
        self::assertCount(1, $filters);
        /** @var SortFilter $sortFilter */
        $sortFilter = $filters->get('sort');
        self::assertEquals('orderBy', $sortFilter->getDataType());
        self::assertSame([], $sortFilter->getDefaultValue());
        self::assertFalse($filters->isIncludeInDefaultGroup('sort'));
    }

    public function testProcessForEntityWithCompositeIdentifier(): void
    {
        $config = new EntityDefinitionConfig();
        $config->setIdentifierFieldNames(['id', 'title']);
        $config->addField('id');
        $config->addField('title');

        $configOfSorters = new SortersConfig();
        $configOfSorters->addField('id');
        $configOfSorters->addField('title');

        $this->context->setClassName(CompositeKeyEntity::class);
        $this->context->setConfig($config);
        $this->context->setConfigOfSorters($configOfSorters);
        $this->processor->process($this->context);

        $filters = $this->context->getFilters();
        self::assertCount(1, $filters);
        /** @var SortFilter $sortFilter */
        $sortFilter = $filters->get('sort');
        self::assertEquals('orderBy', $sortFilter->getDataType());
        self::assertEquals(['id' => 'ASC', 'title' => 'ASC'], $sortFilter->getDefaultValue());
        self::assertFalse($filters->isIncludeInDefaultGroup('sort'));
    }

    public function testProcessForEntityWithCompositeIdentifierAndSorterForSomeIdentifierFieldsIsDisabled(): void
    {
        $config = new EntityDefinitionConfig();
        $config->setIdentifierFieldNames(['id', 'title']);
        $config->addField('id');
        $config->addField('title');

        $configOfSorters = new SortersConfig();
        $configOfSorters->addField('id')->setExcluded(true);
        $configOfSorters->addField('title');

        $this->context->setClassName(CompositeKeyEntity::class);
        $this->context->setConfig($config);
        $this->context->setConfigOfSorters($configOfSorters);
        $this->processor->process($this->context);

        $filters = $this->context->getFilters();
        self::assertCount(1, $filters);
        /** @var SortFilter $sortFilter */
        $sortFilter = $filters->get('sort');
        self::assertEquals('orderBy', $sortFilter->getDataType());
        self::assertEquals(['title' => 'ASC'], $sortFilter->getDefaultValue());
        self::assertFalse($filters->isIncludeInDefaultGroup('sort'));
    }

    public function testProcessWhenNoIdentifierFieldInConfig(): void
    {
        $config = new EntityDefinitionConfig();
        $config->setIdentifierFieldNames(['id']);

        $configOfSorters = new SortersConfig();
        $configOfSorters->addField('id');

        $this->context->setClassName(User::class);
        $this->context->setConfig($config);
        $this->context->setConfigOfSorters($configOfSorters);
        $this->processor->process($this->context);

        $filters = $this->context->getFilters();
        self::assertCount(1, $filters);
        /** @var SortFilter $sortFilter */
        $sortFilter = $filters->get('sort');
        self::assertEquals('orderBy', $sortFilter->getDataType());
        self::assertEquals(['id' => 'ASC'], $sortFilter->getDefaultValue());
        self::assertFalse($filters->isIncludeInDefaultGroup('sort'));
    }

    public function testProcessWhenConfigHasOrderByOption(): void
    {
        $config = new EntityDefinitionConfig();
        $config->setIdentifierFieldNames(['id']);
        $config->setOrderBy(['name' => 'DESC']);

        $configOfSorters = new SortersConfig();
        $configOfSorters->addField('id');

        $this->context->setClassName(User::class);
        $this->context->setConfig($config);
        $this->context->setConfigOfSorters($configOfSorters);
        $this->processor->process($this->context);

        $filters = $this->context->getFilters();
        self::assertCount(1, $filters);
        /** @var SortFilter $sortFilter */
        $sortFilter = $filters->get('sort');
        self::assertEquals('orderBy', $sortFilter->getDataType());
        self::assertEquals(['name' => 'DESC'], $sortFilter->getDefaultValue());
        self::assertFalse($filters->isIncludeInDefaultGroup('sort'));
    }

    public function testProcessForEntityWithoutIdentifier(): void
    {
        $config = new EntityDefinitionConfig();
        $configOfSorters = new SortersConfig();

        $this->context->setClassName(User::class);
        $this->context->setConfig($config);
        $this->context->setConfigOfSorters($configOfSorters);
        $this->processor->process($this->context);

        $filters = $this->context->getFilters();
        self::assertCount(1, $filters);
        /** @var SortFilter $sortFilter */
        $sortFilter = $filters->get('sort');
        self::assertEquals('orderBy', $sortFilter->getDataType());
        self::assertEquals([], $sortFilter->getDefaultValue());
        self::assertFalse($filters->isIncludeInDefaultGroup('sort'));
    }

    public function testProcessForEntityWithRenamedIdentifierFieldAndSorterForThisFieldIsDisabled(): void
    {
        $config = new EntityDefinitionConfig();
        $config->setIdentifierFieldNames(['renamedId']);
        $config->addField('renamedId')->setPropertyPath('id');

        $configOfSorters = new SortersConfig();
        $sorterConfig = $configOfSorters->addField('renamedId');
        $sorterConfig->setPropertyPath('id');
        $sorterConfig->setExcluded(true);

        $this->context->setClassName(User::class);
        $this->context->setConfig($config);
        $this->context->setConfigOfSorters($configOfSorters);
        $this->processor->process($this->context);

        $filters = $this->context->getFilters();
        self::assertCount(1, $filters);
        /** @var SortFilter $sortFilter */
        $sortFilter = $filters->get('sort');
        self::assertEquals('orderBy', $sortFilter->getDataType());
        self::assertSame([], $sortFilter->getDefaultValue());
        self::assertFalse($filters->isIncludeInDefaultGroup('sort'));
    }

    public function testProcessWhenSortFilterIsAlreadyAdded(): void
    {
        $sortFilter = new SortFilter(DataType::ORDER_BY);

        $this->context->setClassName(Category::class);
        $this->context->setConfig(new EntityDefinitionConfig());
        $this->context->getFilters()->add('sort', $sortFilter, false);
        $this->processor->process($this->context);

        self::assertSame($sortFilter, $this->context->getFilters()->get('sort'));
        self::assertFalse($this->context->getFilters()->isIncludeInDefaultGroup('sort'));
    }

    public function testProcessWhenSortingIsDisabled(): void
    {
        $config = new EntityDefinitionConfig();
        $config->disableSorting();

        $this->context->setConfig($config);
        $this->processor->process($this->context);

        self::assertCount(0, $this->context->getFilters());
    }
}
