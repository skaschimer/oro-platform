<?php

namespace Oro\Bundle\EntityExtendBundle\Grid;

use Doctrine\ORM\Query\Expr\Join;
use Oro\Bundle\DataGridBundle\Datagrid\Common\DatagridConfiguration;
use Oro\Bundle\DataGridBundle\Datagrid\DatagridGuesser;
use Oro\Bundle\DataGridBundle\Extension\AbstractExtension;
use Oro\Bundle\DataGridBundle\Extension\FieldAcl\Configuration as FieldAclConfiguration;
use Oro\Bundle\DataGridBundle\Extension\Formatter\Configuration as FormatterConfiguration;
use Oro\Bundle\DataGridBundle\Extension\Formatter\Property\PropertyInterface as Property;
use Oro\Bundle\DataGridBundle\Extension\Sorter\Configuration as SorterConfiguration;
use Oro\Bundle\EntityBundle\Entity\EntityFieldFallbackValue;
use Oro\Bundle\EntityBundle\EntityConfig\DatagridScope;
use Oro\Bundle\EntityBundle\ORM\EntityClassResolver;
use Oro\Bundle\EntityConfigBundle\Config\ConfigInterface;
use Oro\Bundle\EntityConfigBundle\Config\ConfigManager;
use Oro\Bundle\EntityConfigBundle\Config\Id\FieldConfigId;
use Oro\Bundle\EntityExtendBundle\Entity\EnumOption;
use Oro\Bundle\EntityExtendBundle\Extend\RelationType;
use Oro\Bundle\FilterBundle\Grid\Extension\Configuration as FilterConfiguration;
use Oro\Component\PhpUtils\ArrayUtil;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;

/**
 * Base datagrid extension which adds to datagrids columns, filters and sorters for the extended entities fields.
 */
abstract class AbstractFieldsExtension extends AbstractExtension
{
    public function __construct(
        protected ConfigManager $configManager,
        protected EntityClassResolver $entityClassResolver,
        protected DatagridGuesser $datagridGuesser,
        protected FieldsHelper $fieldsHelper
    ) {
    }

    #[\Override]
    public function isApplicable(DatagridConfiguration $config): bool
    {
        return
            parent::isApplicable($config)
            && $config->isOrmDatasource();
    }

    #[\Override]
    public function processConfigs(DatagridConfiguration $config): void
    {
        $fields = $this->getFields($config);
        if (empty($fields)) {
            return;
        }

        foreach ($fields as $field) {
            $fieldName = $field->getFieldName();
            $columnOptions =
                [
                    DatagridGuesser::FORMATTER => $config->offsetGetByPath(
                        sprintf('[%s][%s]', FormatterConfiguration::COLUMNS_KEY, $fieldName),
                        []
                    ),
                    DatagridGuesser::SORTER => $config->offsetGetByPath(
                        sprintf('%s[%s]', SorterConfiguration::COLUMNS_PATH, $fieldName),
                        []
                    ),
                    DatagridGuesser::FILTER => $config->offsetGetByPath(
                        sprintf('%s[%s]', FilterConfiguration::COLUMNS_PATH, $fieldName),
                        []
                    ),
                ];
            $this->prepareColumnOptions($field, $columnOptions);
            $this->datagridGuesser->setColumnOptions($config, $field->getFieldName(), $columnOptions);
        }

        $entityClassName = $this->entityClassResolver->getEntityClass($this->getEntityName($config));
        $alias = $config->getOrmQuery()->findRootAlias($entityClassName, $this->entityClassResolver);
        if (!$alias) {
            $alias = 'o';
            $config->getOrmQuery()->addFrom($entityClassName, $alias);
        }

        $this->buildExpression($fields, $config, $alias);

        foreach ($fields as $field) {
            $extendFieldConfig = $this->getFieldConfig('extend', $field);
            if (is_a($extendFieldConfig->get('target_entity'), EntityFieldFallbackValue::class, true)) {
                // Render EntityFieldFallbackValue with a html template
                $path = sprintf('[%s][%s]', FormatterConfiguration::COLUMNS_KEY, $field->getFieldName());
                $columnConfig = ArrayUtil::arrayMergeRecursiveDistinct(
                    $config->offsetGetByPath($path, []),
                    [
                        'type' => 'twig',
                        'frontend_type' => Property::TYPE_HTML,
                        'template' => '@OroEntity/Datagrid/Property/entityFallbackValue.html.twig',
                        'context' => [
                            'fieldName' => $field->getFieldName(),
                            'entityClassName' => $entityClassName
                        ]
                    ]
                );
                $config->offsetSetByPath($path, $columnConfig);

                // Disable filter and sorter for EntityFieldFallbackValue
                $config->offsetUnsetByPath(
                    sprintf('%s[%s]', SorterConfiguration::COLUMNS_PATH, $field->getFieldName())
                );
                $config->offsetUnsetByPath(
                    sprintf('%s[%s]', FilterConfiguration::COLUMNS_PATH, $field->getFieldName())
                );
            }
        }
    }

    /**
     * @param FieldConfigId[]       $fields
     * @param DatagridConfiguration $config
     * @param string                $alias
     *
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */
    public function buildExpression(array $fields, DatagridConfiguration $config, string $alias): void
    {
        $query = $config->getOrmQuery();
        foreach ($fields as $field) {
            $fieldName = $field->getFieldName();
            switch ($field->getFieldType()) {
                case 'enum':
                    $extendFieldConfig = $this->getFieldConfig('extend', $field);
                    $join = sprintf('%s.%s', $alias, $fieldName);
                    $joinAlias = $query->getJoinAlias($join);
                    $query->addLeftJoin(
                        EnumOption::class,
                        $joinAlias,
                        Join::WITH,
                        sprintf("JSON_EXTRACT(order1.serialized_data, '%s') = %s", $fieldName, $joinAlias)
                    );
                    $columnDataName = $fieldName;

                    $targetField = $extendFieldConfig->get('target_field');
                    $sorterDataName = sprintf('%s_%s', $joinAlias, $targetField);
                    $sorterSelectExpr = sprintf('%s.%s as %s', $joinAlias, $targetField, $sorterDataName);

                    $selectExpr = sprintf('IDENTITY(%s.%s) as %s', $alias, $fieldName, $fieldName);
                    $filterDataName = sprintf('%s.%s', $alias, $fieldName);
                    // adding $filterDataName to select list to allow sorting by this column and avoid GROUP BY error
                    $selectExpr = [$selectExpr, $sorterSelectExpr];
                    break;
                case 'multiEnum':
                    $columnDataName = $fieldName;
                    $sorterDataName = sprintf("JSON_EXTRACT(order1.serialized_data, '%s') = %s", $fieldName, $alias);
                    $filterDataName = sprintf("JSON_EXTRACT(order1.serialized_data, '%s') = %s", $fieldName, $alias);
                    $selectExpr = $sorterDataName;
                    break;
                case RelationType::MANY_TO_ONE:
                case RelationType::ONE_TO_ONE:
                    $extendFieldConfig = $this->getFieldConfig('extend', $field);
                    // Skip EntityFieldFallbackValue relations as their values are fetched and rendered by a template.
                    if (is_a($extendFieldConfig->get('target_entity'), EntityFieldFallbackValue::class, true)) {
                        continue 2;
                    }

                    $join = sprintf('%s.%s', $alias, $fieldName);
                    $joinAlias = $query->getJoinAlias($join);
                    $query->addLeftJoin($join, $joinAlias);

                    $dataName = $fieldName . '_target_field';
                    $targetField = $extendFieldConfig->get('target_field', false, 'id');
                    $dataFieldName = sprintf('%s.%s', $joinAlias, $targetField);

                    $groupBy = $query->getGroupBy();
                    if ($groupBy) {
                        $query->addGroupBy($dataFieldName);
                    }

                    $selectExpr = sprintf('%s as %s', $dataFieldName, $dataName);
                    $query->addSelect(sprintf('IDENTITY(%s.%s) as %s_identity', $alias, $fieldName, $fieldName));

                    $columnDataName = $sorterDataName = $dataName;
                    $filterDataName = sprintf('IDENTITY(%s.%s)', $alias, $fieldName);
                    break;
                default:
                    $columnDataName = $fieldName;
                    $selectExpr = $sorterDataName = $filterDataName = sprintf('%s.%s', $alias, $fieldName);
                    break;
            }

            $query->addSelect($selectExpr);

            // set real "data name" for filters and sorters
            $config->offsetSetByPath(
                sprintf('[%s][%s][data_name]', FormatterConfiguration::COLUMNS_KEY, $fieldName),
                $columnDataName
            );

            $path = sprintf('%s[%s][data_name]', SorterConfiguration::COLUMNS_PATH, $fieldName);
            if ($fieldName === $config->offsetGetByPath($path, $fieldName)) {
                $config->offsetSetByPath($path, $sorterDataName);
            }
            $path = sprintf('%s[%s][data_name]', FilterConfiguration::COLUMNS_PATH, $fieldName);
            if ($fieldName === $config->offsetGetByPath($path, $fieldName)) {
                $config->offsetSetByPath($path, $filterDataName);
            }

            // add Field ACL configuration
            $config->offsetSetByPath(
                sprintf('%s[%s][data_name]', FieldAclConfiguration::COLUMNS_PATH, $columnDataName),
                sprintf('%s.%s', $alias, $fieldName)
            );
        }
    }

    #[\Override]
    public function getPriority(): int
    {
        return 250;
    }

    /**
     * Gets a root entity name for which additional fields to be shown
     */
    abstract protected function getEntityName(DatagridConfiguration $config): string;

    /**
     * Gets a list of fields to show
     *
     * @param DatagridConfiguration $config
     *
     * @return FieldConfigId[]
     */
    protected function getFields(DatagridConfiguration $config): array
    {
        return $this->fieldsHelper->getFields($this->getEntityName($config));
    }

    protected function prepareColumnOptions(FieldConfigId $field, array &$columnOptions): void
    {
        $fieldName = $field->getFieldName();

        // if field is "visible as mandatory" it is required in grid settings and rendered
        // if field is just "visible" it's rendered by default and manageable in grid settings
        // otherwise - not required and hidden by default.
        $gridVisibilityValue = (int)$this->getFieldConfig('datagrid', $field)->get('is_visible');

        $isRequired = $gridVisibilityValue === DatagridScope::IS_VISIBLE_MANDATORY;
        $isRenderable = $isRequired ?: $gridVisibilityValue === DatagridScope::IS_VISIBLE_TRUE;

        $defaultColumnOptions = [
            DatagridGuesser::FORMATTER => [
                'label' => $this->getFieldConfig('entity', $field)->get('label', false, $fieldName),
                'renderable' => $isRenderable,
                'required' => $isRequired,
            ],
            DatagridGuesser::SORTER => [
                'data_name' => $fieldName,
            ],
            DatagridGuesser::FILTER => [
                'data_name' => $fieldName,
                'renderable' => false,
            ],
        ];

        $columnOrder = $this->getFieldConfig('datagrid', $field)->get('order');
        if ($columnOrder !== null) {
            $defaultColumnOptions[DatagridGuesser::FORMATTER]['order'] = $columnOrder;
        }

        $columnOptions = ArrayUtil::arrayMergeRecursiveDistinct($defaultColumnOptions, $columnOptions);

        switch ($field->getFieldType()) {
            case RelationType::MANY_TO_ONE:
            case RelationType::ONE_TO_ONE:
            case RelationType::TO_ONE:
                $extendFieldConfig = $this->getFieldConfig('extend', $field);
                $columnOptions = ArrayUtil::arrayMergeRecursiveDistinct(
                    [
                        DatagridGuesser::FILTER => [
                            'type' => 'entity',
                            'translatable' => true,
                            'options' => [
                                'field_type' => EntityType::class,
                                'field_options' => [
                                    'class' => $extendFieldConfig->get('target_entity'),
                                    'choice_label' => $extendFieldConfig->get('target_field'),
                                    'multiple' => true,
                                ]
                            ]
                        ]
                    ],
                    $columnOptions
                );
                break;
            default:
                break;
        }

        $this->datagridGuesser->applyColumnGuesses(
            $field->getClassName(),
            $fieldName,
            $field->getFieldType(),
            $columnOptions
        );
    }

    protected function getFieldConfig(string $scope, FieldConfigId $fieldId): ConfigInterface
    {
        return $this->configManager->getProvider($scope)
            ->getConfig($fieldId->getClassName(), $fieldId->getFieldName());
    }
}
