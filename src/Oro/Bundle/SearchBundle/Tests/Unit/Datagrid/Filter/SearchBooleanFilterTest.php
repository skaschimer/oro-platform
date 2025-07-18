<?php

namespace Oro\Bundle\SearchBundle\Tests\Unit\Datagrid\Filter;

use Oro\Bundle\EntityConfigBundle\Attribute\Type\BooleanAttributeType;
use Oro\Bundle\FilterBundle\Datasource\FilterDatasourceAdapterInterface;
use Oro\Bundle\FilterBundle\Filter\FilterUtility;
use Oro\Bundle\FilterBundle\Form\Type\Filter\BooleanFilterType;
use Oro\Bundle\SearchBundle\Datagrid\Filter\Adapter\SearchFilterDatasourceAdapter;
use Oro\Bundle\SearchBundle\Datagrid\Filter\SearchBooleanFilter;
use Oro\Bundle\SearchBundle\Query\Criteria\Criteria;
use Oro\Component\Exception\UnexpectedTypeException;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Contracts\Translation\TranslatorInterface;

class SearchBooleanFilterTest extends TestCase
{
    private const FIELD_NAME = 'testField';

    private FormInterface&MockObject $form;
    private FormFactoryInterface&MockObject $formFactory;
    private TranslatorInterface&MockObject $translator;
    private SearchBooleanFilter $filter;

    #[\Override]
    protected function setUp(): void
    {
        $this->formFactory = $this->createMock(FormFactoryInterface::class);
        $this->translator = $this->createMock(TranslatorInterface::class);

        $this->form = $this->createMock(FormInterface::class);
        $this->formFactory->expects($this->any())
            ->method('create')
            ->willReturn($this->form);

        $this->filter = new SearchBooleanFilter(
            $this->formFactory,
            new FilterUtility(),
            $this->translator
        );
    }

    public function testApplyWhenWrongDatasource(): void
    {
        $this->expectException(UnexpectedTypeException::class);

        $this->filter->apply($this->createMock(FilterDatasourceAdapterInterface::class), []);
    }

    public function testGetMetadata(): void
    {
        $this->filter->init('test', []);

        $formView = new FormView();

        $valueFormView = new FormView($formView);
        $valueFormView->vars['choices'] = [];

        $typeFormView = new FormView($formView);
        $typeFormView->vars['choices'] = [];

        $formView->children = ['value' => $valueFormView, 'type' => $typeFormView];

        $this->form->expects($this->any())
            ->method('createView')
            ->willReturn($formView);

        $this->assertEquals(
            [
                'name' => 'test',
                'label' => 'Test',
                'choices' => [],
                'type' => 'search-boolean',
                'contextSearch' => false,
                'lazy' => false,
            ],
            $this->filter->getMetadata()
        );
    }

    public function testApplyWhenNoValue(): void
    {
        $dataSource = $this->createMock(SearchFilterDatasourceAdapter::class);
        $dataSource->expects($this->never())
            ->method('addRestriction');

        $this->filter->init('test', [FilterUtility::DATA_NAME_KEY => self::FIELD_NAME]);
        $this->filter->apply($dataSource, []);
    }

    public function testApplyWhenYes(): void
    {
        $dataSource = $this->createMock(SearchFilterDatasourceAdapter::class);
        $dataSource->expects($this->once())
            ->method('addRestriction')
            ->with(Criteria::expr()->in(self::FIELD_NAME, [BooleanFilterType::TYPE_YES]));

        $this->filter->init('test', [FilterUtility::DATA_NAME_KEY => self::FIELD_NAME]);
        $this->filter->apply($dataSource, ['value' => [BooleanFilterType::TYPE_YES]]);
    }

    public function testApplyWhenYesAndNo(): void
    {
        $dataSource = $this->createMock(SearchFilterDatasourceAdapter::class);
        $dataSource->expects($this->once())
            ->method('addRestriction')
            ->with(Criteria::expr()->in(
                self::FIELD_NAME,
                [BooleanAttributeType::TRUE_VALUE, BooleanAttributeType::FALSE_VALUE]
            ));

        $this->filter->init('test', [FilterUtility::DATA_NAME_KEY => self::FIELD_NAME]);
        $this->filter->apply($dataSource, ['value' => [BooleanFilterType::TYPE_YES, BooleanFilterType::TYPE_NO]]);
    }

    public function testApplyWhenSomeOther(): void
    {
        $dataSource = $this->createMock(SearchFilterDatasourceAdapter::class);
        $dataSource->expects($this->never())
            ->method('addRestriction');

        $this->filter->init('test', [FilterUtility::DATA_NAME_KEY => self::FIELD_NAME]);
        $this->filter->apply($dataSource, ['value' => 'all']);
    }

    public function testPrepareData(): void
    {
        $this->expectException(\BadMethodCallException::class);
        $this->filter->prepareData([]);
    }
}
