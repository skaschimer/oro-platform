<?php

namespace Oro\Bundle\ImportExportBundle\Converter;

use Oro\Bundle\EntityBundle\Helper\FieldHelper;
use Oro\Bundle\EntityBundle\Provider\EntityFieldProvider;
use Oro\Bundle\EntityExtendBundle\Entity\EnumOption;
use Oro\Bundle\EntityExtendBundle\EntityConfig\ExtendScope;
use Oro\Bundle\EntityExtendBundle\Tools\ExtendHelper;
use Oro\Bundle\ImportExportBundle\Event\Events;
use Oro\Bundle\ImportExportBundle\Event\LoadEntityRulesAndBackendHeadersEvent;
use Oro\Bundle\ImportExportBundle\Exception\LogicException;
use Oro\Bundle\ImportExportBundle\Processor\EntityNameAwareInterface;
use Oro\Bundle\LocaleBundle\Model\LocaleSettings;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * ConfigurableTableDataConverter is a class that is responsible for the data conversion
 *
 * @SuppressWarnings(PHPMD.NPathComplexity)
 * @SuppressWarnings(PHPMD.CyclomaticComplexity)
 * @SuppressWarnings(PHPMD.ExcessiveClassComplexity)
 */
class ConfigurableTableDataConverter extends AbstractTableDataConverter implements EntityNameAwareInterface
{
    const DEFAULT_SINGLE_RELATION_LEVEL = 5;
    const DEFAULT_MULTIPLE_RELATION_LEVEL = 3;
    const DEFAULT_ORDER = 10000;

    const CONVERSION_TYPE_DATA = 'data';
    const CONVERSION_TYPE_FIXTURES = 'fixtures';

    /** @var string */
    protected $entityName;

    /** @var string */
    protected $relationDelimiter = ' ';

    /** @var string */
    protected $collectionDelimiter = '(\d+)';

    /** @var EventDispatcherInterface */
    protected $dispatcher;
    protected array $availableForExportField = [];

    public function __construct(
        protected FieldHelper $fieldHelper,
        protected RelationCalculatorInterface $relationCalculator,
        protected LocaleSettings $localeSettings,
    ) {
    }

    #[\Override]
    public function setDispatcher(EventDispatcherInterface $dispatcher): void
    {
        $this->dispatcher = $dispatcher;
    }

    /**
     * Get field header
     *
     * @param string $entityClassName
     * @param string $initialFieldName
     * @param bool $isSearchingIdentityField
     * @return null|string
     */
    public function getFieldHeaderWithRelation($entityClassName, $initialFieldName, $isSearchingIdentityField = false)
    {
        $expectedFields = [$initialFieldName];
        $fields = $this->fieldHelper->getEntityFields(
            $entityClassName,
            EntityFieldProvider::OPTION_WITH_RELATIONS | EntityFieldProvider::OPTION_TRANSLATE
        );

        $dotPosition = strpos($initialFieldName, '.');
        if ($dotPosition && $this->attributeConfigHelper) {
            $fieldName = substr($initialFieldName, 0, $dotPosition);

            if ($this->attributeConfigHelper->isFieldAttribute($entityClassName, $fieldName)) {
                $expectedFields[] = $fieldName;
            }
        }

        foreach ($fields as $field) {
            $fieldName = $field['name'];
            $notFoundFieldName = !$isSearchingIdentityField && !in_array($fieldName, $expectedFields, true);
            $foundIdentifyField = $this->fieldHelper->getConfigValue($entityClassName, $fieldName, 'identity');
            $notFoundFieldIdentify = $isSearchingIdentityField && !$foundIdentifyField;

            if ($notFoundFieldName || $notFoundFieldIdentify) {
                continue;
            }

            if ($this->fieldHelper->isRelation($field) &&
                !$this->fieldHelper->processRelationAsScalar($entityClassName, $fieldName)
            ) {
                $relatedClassName = $field['related_entity_name'];
                $relatedFieldName = $dotPosition
                    ? substr($initialFieldName, $dotPosition + 1, strlen($initialFieldName))
                    : '';

                return
                    $this->getFieldHeader($entityClassName, $field) .
                    $this->relationDelimiter .
                    $this->getFieldHeaderWithRelation($relatedClassName, $relatedFieldName, $relatedFieldName === '');
            }

            return $this->getFieldHeader($entityClassName, $field);
        }

        return null;
    }

    #[\Override]
    protected function getHeaderConversionRules()
    {
        $this->initializeRules();

        return $this->headerConversionRules;
    }

    #[\Override]
    protected function getBackendHeader()
    {
        $this->initialize();

        return $this->backendHeader;
    }

    #[\Override]
    public function setEntityName(string $entityName): void
    {
        $this->entityName = $entityName;
    }

    protected function initializeRules()
    {
        // Do not cache header because it is dependent on the locale
        $this->assertEntityName();
        if ($this->translateUsingLocale) {
            $this->fieldHelper->setLocale($this->localeSettings->getLanguage());
        }

        $headerConversionRules = $this->getEntityRules(
            $this->entityName,
            true,
            self::DEFAULT_SINGLE_RELATION_LEVEL,
            self::DEFAULT_MULTIPLE_RELATION_LEVEL
        );

        $this->headerConversionRules = $this->processCollectionRegexp($headerConversionRules);
    }

    /**
     * Receive backend header and header conversion rules simultaneously
     */
    protected function initialize()
    {
        if ($this->headerConversionRules === null || $this->backendHeader === null) {
            $this->assertEntityName();

            [$headerConversionRules, $backendHeader] = $this->getEntityRulesAndBackendHeaders(
                $this->entityName,
                true,
                self::DEFAULT_SINGLE_RELATION_LEVEL,
                self::DEFAULT_MULTIPLE_RELATION_LEVEL
            );

            [$this->headerConversionRules, $this->backendHeader] = [
                $this->processCollectionRegexp($headerConversionRules),
                $backendHeader
            ];
        }
    }

    /**
     * @throws LogicException
     */
    protected function assertEntityName()
    {
        if (!$this->entityName) {
            throw new LogicException('Entity class for data converter is not specified');
        }
    }

    /**
     * @param string $entityName
     * @param bool $fullData
     * @param int $singleRelationDeepLevel
     * @param int $multipleRelationDeepLevel
     * @return array
     */
    protected function getEntityRules(
        $entityName,
        $fullData = false,
        $singleRelationDeepLevel = 0,
        $multipleRelationDeepLevel = 0
    ) {
        // get fields data
        $fields = $this->fieldHelper->getEntityFields(
            $entityName,
            EntityFieldProvider::OPTION_WITH_RELATIONS | EntityFieldProvider::OPTION_TRANSLATE
        );

        $rules = [];
        $defaultOrder = self::DEFAULT_ORDER;

        // generate conversion rules and backend header
        foreach ($fields as $field) {
            $fieldName = $field['name'];
            if (!$this->isFieldAvailableForExport($entityName, $fieldName)) {
                continue;
            }

            if ($fullData || $this->fieldHelper->getConfigValue($entityName, $fieldName, 'identity')) {
                // get import/export config parameters
                $fieldHeader = $this->getFieldHeader($entityName, $field);

                $fieldOrder = $this->fieldHelper->getConfigValue($entityName, $fieldName, 'order');
                if ($fieldOrder === null || $fieldOrder === '') {
                    $fieldOrder = $defaultOrder;
                    $defaultOrder++;
                }
                $fieldOrder = (int)$fieldOrder;

                // process relations
                if ($this->fieldHelper->isRelation($field)
                    && !$this->fieldHelper->processRelationAsScalar($entityName, $fieldName)
                ) {
                    $relationRules = $this->getRelatedEntityRules(
                        $entityName,
                        $singleRelationDeepLevel,
                        $multipleRelationDeepLevel,
                        $field,
                        $fieldHeader,
                        $fieldOrder
                    );
                    $rules = array_merge($rules, $relationRules);
                } elseif (isset($field['type']) && ExtendHelper::isEnumerableType($field['type'])) {
                    $enumRules = $this->getEnumRules(
                        $entityName,
                        $field,
                        $fieldHeader,
                        $fieldOrder
                    );
                    $rules = array_merge($rules, $enumRules);
                } else {
                    // process scalars
                    $rules[$fieldHeader] = ['value' => $fieldName, 'order' => $fieldOrder];
                }
            }
        }

        $event = $this->dispatchEntityRulesEvent($entityName, [], $rules, $fullData);

        return $this->prepareDataAfterSort($event, true, false);
    }

    /**
     * @param string $entityName
     * @param bool $fullData
     * @param int $singleRelationDeepLevel
     * @param int $multipleRelationDeepLevel
     * @return array
     */
    protected function getEntityRulesAndBackendHeaders(
        $entityName,
        $fullData = false,
        $singleRelationDeepLevel = 0,
        $multipleRelationDeepLevel = 0
    ) {
        // get fields data
        $fields = $this->fieldHelper->getEntityFields(
            $entityName,
            EntityFieldProvider::OPTION_WITH_RELATIONS | EntityFieldProvider::OPTION_TRANSLATE
        );

        $rules = [];
        $backendHeaders = [];
        $defaultOrder = self::DEFAULT_ORDER;

        // generate conversion rules and backend header
        foreach ($fields as $field) {
            $fieldName = $field['name'];
            if (!$this->isFieldAvailableForExport($entityName, $fieldName)) {
                continue;
            }

            if ($fullData || $this->fieldHelper->getConfigValue($entityName, $fieldName, 'identity')) {
                // get import/export config parameters
                $fieldHeader = $this->getFieldHeader($entityName, $field);

                $fieldOrder = $this->fieldHelper->getConfigValue($entityName, $fieldName, 'order');
                if ($fieldOrder === null || $fieldOrder === '') {
                    $fieldOrder = $defaultOrder;
                    $defaultOrder++;
                }
                $fieldOrder = (int)$fieldOrder;

                // process relations
                if ($this->fieldHelper->isRelation($field)
                    && !$this->fieldHelper->processRelationAsScalar($entityName, $fieldName)
                ) {
                    [$relationRules, $relationBackendHeaders] = $this->getRelatedEntityRulesAndBackendHeaders(
                        $entityName,
                        $singleRelationDeepLevel,
                        $multipleRelationDeepLevel,
                        $field,
                        $fieldHeader,
                        $fieldOrder
                    );
                    $rules = array_merge($rules, $relationRules);
                    $backendHeaders = array_merge($backendHeaders, $relationBackendHeaders);
                } elseif (isset($field['type']) && ExtendHelper::isEnumerableType($field['type'])) {
                    [$enumRules, $enumBackendHeaders] = $this->getEnumRulesAndBackendHeaders(
                        $entityName,
                        $field,
                        $fieldHeader,
                        $fieldOrder
                    );
                    $rules = array_merge($rules, $enumRules);
                    $backendHeaders = array_merge($backendHeaders, $enumBackendHeaders);
                } else {
                    // process scalars
                    $rules[$fieldHeader] = ['value' => $fieldName, 'order' => $fieldOrder];
                    $backendHeaders[] = $rules[$fieldHeader];
                }
            }
        }

        $event = $this->dispatchEntityRulesEvent($entityName, $backendHeaders, $rules, $fullData);

        return $this->prepareDataAfterSort($event, true, true);
    }

    protected function isFieldAvailableForExport(string $entityName, string $fieldName): bool
    {
        if (!isset($this->availableForExportField[$entityName][$fieldName])) {
            $this->availableForExportField[$entityName][$fieldName] = !$this->fieldHelper->getConfigValue(
                $entityName,
                $fieldName,
                'excluded'
            );
        }

        return $this->availableForExportField[$entityName][$fieldName];
    }

    /**
     * @param LoadEntityRulesAndBackendHeadersEvent $event
     * @param bool                                  $rules
     * @param bool                                  $backendHeaders
     *
     * @return array
     */
    protected function prepareDataAfterSort(
        LoadEntityRulesAndBackendHeadersEvent $event,
        $rules = true,
        $backendHeaders = true
    ) {
        if ($rules && !$backendHeaders) {
            return $this->sortData($event->getRules());
        } elseif (!$rules && $backendHeaders) {
            return $this->sortData($event->getHeaders());
        }

        // by default return both rules and headers
        return [$this->sortData($event->getRules()), $this->sortData($event->getHeaders())];
    }

    /**
     * @param array $relationRules
     * @param bool $isSingleRelation
     * @param bool $isMultipleRelation
     * @param string $fieldName
     * @param string $fieldHeader
     * @param int $fieldOrder
     * @return array
     */
    protected function buildRelationRules(
        array $relationRules,
        $isSingleRelation,
        $isMultipleRelation,
        $fieldName,
        $fieldHeader,
        $fieldOrder
    ) {
        $subOrder = 0;
        $delimiter = $this->convertDelimiter;
        $rules = [];

        foreach ($relationRules as $header => $name) {
            // single relation
            if ($isSingleRelation) {
                $relationHeader = $fieldHeader . $this->relationDelimiter . $header;
                $relationName = $fieldName . $delimiter . $name;
                $rules[$relationHeader] = [
                    'value' => $relationName,
                    'order' => $fieldOrder,
                    'subOrder' => $subOrder++,
                ];
            } elseif ($isMultipleRelation) {
                // multiple relation
                $frontendCollectionDelimiter = $this->relationDelimiter
                    . $this->collectionDelimiter
                    . $this->relationDelimiter;
                $frontendHeader = $fieldHeader . $frontendCollectionDelimiter . $header;
                $backendHeader
                    = $fieldName . $delimiter . $this->collectionDelimiter . $delimiter . $name;
                $rules[$frontendHeader] = [
                    'value' => $backendHeader,
                    'order' => $fieldOrder,
                    'subOrder' => $subOrder++,
                ];
            }
        }

        return $rules;
    }

    /**
     * @param array $relationBackendHeaders
     * @param bool $isSingleRelation
     * @param bool $isMultipleRelation
     * @param string $entityName
     * @param string $fieldName
     * @param int $fieldOrder
     * @return array
     */
    protected function buildBackendHeaders(
        array $relationBackendHeaders,
        $isSingleRelation,
        $isMultipleRelation,
        $entityName,
        $fieldName,
        $fieldOrder
    ) {
        $subOrder = 0;
        $delimiter = $this->convertDelimiter;
        $backendHeaders = [];

        // single relation
        if ($isSingleRelation) {
            foreach ($relationBackendHeaders as $header) {
                $backendHeaders[] = [
                    'value' => $fieldName . $delimiter . $header,
                    'order' => $fieldOrder,
                    'subOrder' => $subOrder++,
                ];
            }
        } elseif ($isMultipleRelation) {
            // multiple relation
            $maxEntities = $this->relationCalculator->getMaxRelatedEntities($entityName, $fieldName);
            for ($i = 0; $i < $maxEntities; $i++) {
                foreach ($relationBackendHeaders as $header) {
                    $backendHeaders[] = [
                        'value' => $fieldName . $delimiter . $i . $delimiter . $header,
                        'order' => $fieldOrder,
                        'subOrder' => $subOrder++,
                    ];
                }
            }
        }

        return $backendHeaders;
    }

    /**
     * @param array $rules
     * @return array
     */
    protected function processCollectionRegexp(array $rules)
    {
        foreach ($rules as $frontendHeader => $backendHeader) {
            if (str_contains($frontendHeader, $this->collectionDelimiter)) {
                $rules[$frontendHeader] = [
                    self::FRONTEND_TO_BACKEND => [
                        $frontendHeader,
                        $this->getReplaceCallback($backendHeader, -1),
                    ],
                    self::BACKEND_TO_FRONTEND => [
                        $backendHeader,
                        $this->getReplaceCallback($frontendHeader, +1),
                    ],
                ];
            }
        }

        return $rules;
    }

    /**
     * @param string $string
     * @param int $shift
     * @return callable
     */
    protected function getReplaceCallback($string, $shift)
    {
        return function (array $matches) use ($string, $shift) {
            $result = '';
            $parts = explode($this->collectionDelimiter, $string);

            foreach ($parts as $index => $value) {
                $result .= $value;
                if ($index + 1 < count($parts)) {
                    $result .= ((int)$matches[$index + 1] + $shift);
                }
            }

            return $result;
        };
    }

    /**
     * @param array $a
     * @param array $b
     * @return int
     */
    protected function sortDataCallback($a, $b)
    {
        if ($a['order'] > $b['order']) {
            return 1;
        } elseif ($a['order'] < $b['order']) {
            return -1;
        } else {
            $aSub = isset($a['subOrder']) ? $a['subOrder'] : 0;
            $bSub = isset($b['subOrder']) ? $b['subOrder'] : 0;

            return $aSub > $bSub ? 1 : -1;
        }
    }

    /**
     * Uses key "order" to sort rules
     *
     * @param array $rules
     * @return array
     */
    protected function sortData(array $rules)
    {
        // sort fields by order
        uasort($rules, [$this, 'sortDataCallback']);

        // clear unused data
        foreach ($rules as $label => $data) {
            $rules[$label] = $data['value'];
        }

        return $rules;
    }

    /**
     * @param string $entityName
     * @param array $field
     * @return string
     */
    protected function getFieldHeader($entityName, $field)
    {
        $fieldHeader = $this->fieldHelper->getConfigValue($entityName, $field['name'], 'header', $field['label']);

        return $fieldHeader ?: $field['label'];
    }

    /**
     * @param string $entityName
     * @param int $singleRelationDeepLevel
     * @param int $multipleRelationDeepLevel
     * @param array $field
     * @param string $fieldHeader
     * @param int $fieldOrder
     *
     * @return array
     */
    protected function getRelatedEntityRules(
        $entityName,
        $singleRelationDeepLevel,
        $multipleRelationDeepLevel,
        $field,
        $fieldHeader,
        $fieldOrder
    ) {
        $relationRules = [];

        $isSingleRelation = $this->fieldHelper->isSingleRelation($field) && $singleRelationDeepLevel > 0;
        $isMultipleRelation = $this->fieldHelper->isMultipleRelation($field) && $multipleRelationDeepLevel > 0;

        // if relation must be included
        if ($isSingleRelation || $isMultipleRelation) {
            $relatedEntityName = $field['related_entity_name'];
            $fieldName = $field['name'];
            $fieldFullData = $this->fieldHelper->getConfigValue($entityName, $fieldName, 'full', false);

            // process and merge relation rules
            $relationRules = $this->getEntityRules(
                $relatedEntityName,
                $fieldFullData,
                $singleRelationDeepLevel - 1,
                $multipleRelationDeepLevel - 1
            );

            $relationRules = $this->buildRelationRules(
                $relationRules,
                $isSingleRelation,
                $isMultipleRelation,
                $fieldName,
                $fieldHeader,
                $fieldOrder
            );
        }

        return $relationRules;
    }

    /**
     * @param string $entityName
     * @param int $singleRelationDeepLevel
     * @param int $multipleRelationDeepLevel
     * @param array $field
     * @param string $fieldHeader
     * @param int $fieldOrder
     *
     * @return array
     */
    protected function getRelatedEntityRulesAndBackendHeaders(
        $entityName,
        $singleRelationDeepLevel,
        $multipleRelationDeepLevel,
        $field,
        $fieldHeader,
        $fieldOrder
    ) {
        $relationRules = [];
        $relationBackendHeaders = [];

        $isSingleRelation = $this->fieldHelper->isSingleRelation($field) && $singleRelationDeepLevel > 0;
        $isMultipleRelation = $this->fieldHelper->isMultipleRelation($field) && $multipleRelationDeepLevel > 0;

        // if relation must be included
        if ($isSingleRelation || $isMultipleRelation) {
            $relatedEntityName = $field['related_entity_name'];
            $fieldName = $field['name'];
            $fieldFullData = $this->fieldHelper->getConfigValue($entityName, $fieldName, 'full', false);

            // process and merge relation rules and backend header for relation
            [$relationRules, $relationBackendHeaders] = $this->getEntityRulesAndBackendHeaders(
                $relatedEntityName,
                $fieldFullData,
                $singleRelationDeepLevel - 1,
                $multipleRelationDeepLevel - 1
            );

            $relationRules = $this->buildRelationRules(
                $relationRules,
                $isSingleRelation,
                $isMultipleRelation,
                $fieldName,
                $fieldHeader,
                $fieldOrder
            );

            $relationBackendHeaders = $this->buildBackendHeaders(
                $relationBackendHeaders,
                $isSingleRelation,
                $isMultipleRelation,
                $entityName,
                $fieldName,
                $fieldOrder
            );
        }

        return [$relationRules, $relationBackendHeaders];
    }

    /**
     * @return string
     */
    protected function getConversionType()
    {
        return $this->relationCalculator instanceof RelationCalculator
            ? static::CONVERSION_TYPE_DATA
            : static::CONVERSION_TYPE_FIXTURES;
    }

    /**
     * @param string $entityName
     * @param array $backendHeaders
     * @param array $rules
     * @param bool $fullData
     *
     * @return LoadEntityRulesAndBackendHeadersEvent
     */
    protected function dispatchEntityRulesEvent($entityName, $backendHeaders, array $rules, $fullData)
    {
        $event = new LoadEntityRulesAndBackendHeadersEvent(
            $entityName,
            $backendHeaders,
            $rules,
            $this->convertDelimiter,
            $this->getConversionType(),
            $fullData
        );
        if ($this->dispatcher && $this->dispatcher->hasListeners(Events::AFTER_LOAD_ENTITY_RULES_AND_BACKEND_HEADERS)) {
            $this->dispatcher->dispatch($event, Events::AFTER_LOAD_ENTITY_RULES_AND_BACKEND_HEADERS);
        }

        return $event;
    }

    private function getEnumRulesAndBackendHeaders($entityName, $field, $fieldHeader, $fieldOrder)
    {
        $enumRules = [];
        $enumBackendHeaders = [];

        $headerPostfix = $this->getEnumHeaderPostfix($entityName, $field['name']);

        if (ExtendHelper::isMultiEnumType($field['type'])) {
            $frontendCollectionDelimiter = $this->relationDelimiter
                . $this->collectionDelimiter
                . $this->relationDelimiter;
            $frontendHeader = $fieldHeader . $frontendCollectionDelimiter . $headerPostfix['key'];
            $backendHeader = $field['name']
                . $this->convertDelimiter
                . $this->collectionDelimiter
                . $this->convertDelimiter
                . $headerPostfix['key'];

            $enumRules[$frontendHeader] = [
                'value' => $backendHeader,
                'order' => $fieldOrder,
            ];

            $enumOptions = $this->fieldHelper->getEnumOptionKeys($entityName, $field['name']);
            foreach ($enumOptions as $key => $enumOption) {
                $enumBackendHeaders[] = $this->createEnumHeader(
                    $field,
                    $headerPostfix,
                    $this->convertDelimiter,
                    $fieldOrder,
                    $key
                );
            }
        } else {
            $enumHeader = $fieldHeader . $this->relationDelimiter . $headerPostfix['key'];
            $enumRules[$enumHeader] = $this->createEnumHeader(
                $field,
                $headerPostfix,
                $this->convertDelimiter,
                $fieldOrder
            );
            $enumBackendHeaders[] = $enumRules[$enumHeader];
        }

        return [$enumRules, $enumBackendHeaders];
    }

    protected function getEnumRules($entityName, $field, $fieldHeader, $fieldOrder): array
    {
        $enumRules = [];
        $headerPostfix = $this->getEnumHeaderPostfix($entityName, $field['name']);

        $frontendHeaderBase = $fieldHeader . $this->relationDelimiter;
        $backendHeaderBase = $field['name'] . $this->convertDelimiter;

        if (ExtendHelper::isMultiEnumType($field['type'])) {
            $frontendHeader = $frontendHeaderBase
                . $this->collectionDelimiter
                . $this->relationDelimiter
                . $headerPostfix['key'];
            $backendHeader = $backendHeaderBase
                . $this->collectionDelimiter
                . $this->convertDelimiter
                . $headerPostfix['value'];

            $enumRules[$frontendHeader] = [
                'value' => $backendHeader,
                'order' => $fieldOrder,
            ];
        } else {
            $frontendHeader = $frontendHeaderBase . $headerPostfix['key'];
            $backendHeader = $backendHeaderBase . $headerPostfix['value'];

            $enumRules[$frontendHeader] = [
                'value' => $backendHeader,
                'order' => $fieldOrder,
            ];
        }

        return $enumRules;
    }

    private function getEnumHeaderPostfix($entityName, $fieldName): array
    {
        $fields = $this->fieldHelper->getEntityFields(
            EnumOption::class,
            EntityFieldProvider::OPTION_WITH_RELATIONS | EntityFieldProvider::OPTION_TRANSLATE
        );
        $isOwnerSystem =
            $this->fieldHelper->getExtendConfigOwner($entityName, $fieldName) === ExtendScope::OWNER_SYSTEM;
        foreach ($fields as $field) {
            $fieldName = $field['name'];
            if (!$this->isFieldAvailableForExport(EnumOption::class, $fieldName)) {
                continue;
            }
            if ($fieldName === 'id' && !$isOwnerSystem || !in_array($fieldName, ['id', 'name'])) {
                continue;
            }

            return [
                'key' => $this->getFieldHeader($entityName, $field),
                'value' => $fieldName
            ];
        }

        return [
            'key' => $isOwnerSystem ? 'Id' : 'Name',
            'value' => $isOwnerSystem ? 'id' : 'name'
        ];
    }

    private function createEnumHeader($field, $headerPostfix, $delimiter, $fieldOrder, $key = null): array
    {
        $backendHeaderValue = $field['name'] . $delimiter . $key . $delimiter . $headerPostfix['value'];

        return [
            'value' => $key !== null ? $backendHeaderValue : $field['name'] . $delimiter . $headerPostfix['value'],
            'order' => $fieldOrder,
            'subOrder' => $key,
        ];
    }
}
