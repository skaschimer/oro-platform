<?php

namespace Oro\Bundle\SearchBundle\Datagrid\Filter;

use Oro\Bundle\FilterBundle\Datasource\FilterDatasourceAdapterInterface;
use Oro\Bundle\FilterBundle\Filter\AbstractFilter;
use Oro\Bundle\FilterBundle\Filter\FilterUtility;
use Oro\Bundle\FilterBundle\Form\Type\Filter\TextFilterType;
use Oro\Bundle\SearchBundle\Datagrid\Filter\Adapter\SearchFilterDatasourceAdapter;
use Oro\Bundle\SearchBundle\Datagrid\Form\Type\SearchStringFilterType;
use Oro\Bundle\SearchBundle\Query\Criteria\Criteria;
use Oro\Bundle\SearchBundle\Query\Criteria\ExpressionBuilder;
use Oro\Component\Exception\UnexpectedTypeException;

/**
 * The filter by a string value for a datasource based on a search index.
 */
class SearchStringFilter extends AbstractFilter
{
    #[\Override]
    protected function getFormType()
    {
        return SearchStringFilterType::class;
    }

    #[\Override]
    public function apply(FilterDatasourceAdapterInterface $ds, $data)
    {
        if (!$ds instanceof SearchFilterDatasourceAdapter) {
            throw new UnexpectedTypeException($ds, SearchFilterDatasourceAdapter::class);
        }

        $length = strlen($data['value']);
        if ($length < $this->get(FilterUtility::MIN_LENGTH_KEY)
            || $length > $this->get(FilterUtility::MAX_LENGTH_KEY)) {
            return;
        }

        $fieldName = $this->get(FilterUtility::DATA_NAME_KEY);
        $builder = Criteria::expr();

        switch ($data['type']) {
            case TextFilterType::TYPE_EQUAL:
                $ds->addRestriction($builder->eq($fieldName, $data['value']), FilterUtility::CONDITION_AND);
                break;
            case TextFilterType::TYPE_CONTAINS:
                $this->addRestrictionForContains($ds, $builder, $data['value']);
                break;
            case TextFilterType::TYPE_NOT_CONTAINS:
                $this->addRestrictionForNotContains($ds, $builder, $data['value']);
                break;
        }
    }

    #[\Override]
    public function prepareData(array $data): array
    {
        throw new \BadMethodCallException('Not implemented');
    }

    /**
     * @param SearchFilterDatasourceAdapter $ds
     * @param ExpressionBuilder             $builder
     * @param string                        $value
     */
    private function addRestrictionForContains(SearchFilterDatasourceAdapter $ds, ExpressionBuilder $builder, $value)
    {
        $fieldName = $this->get(FilterUtility::DATA_NAME_KEY);
        $forceLikeOption = $this->get(FilterUtility::FORCE_LIKE_KEY);

        if ($forceLikeOption) {
            $ds->addRestriction($builder->like($fieldName, $value), FilterUtility::CONDITION_AND);
        } else {
            $ds->addRestriction($builder->contains($fieldName, $value), FilterUtility::CONDITION_AND);
        }
    }

    /**
     * @param SearchFilterDatasourceAdapter $ds
     * @param ExpressionBuilder             $builder
     * @param string                        $value
     */
    private function addRestrictionForNotContains(SearchFilterDatasourceAdapter $ds, ExpressionBuilder $builder, $value)
    {
        $fieldName = $this->get(FilterUtility::DATA_NAME_KEY);
        $forceLikeOption = $this->get(FilterUtility::FORCE_LIKE_KEY);

        if ($forceLikeOption) {
            $ds->addRestriction($builder->notLike($fieldName, $value), FilterUtility::CONDITION_AND);
        } else {
            $ds->addRestriction($builder->notContains($fieldName, $value), FilterUtility::CONDITION_AND);
        }
    }
}
