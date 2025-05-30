<?php

namespace Oro\Bundle\DataGridBundle\Extension\Action;

use Oro\Bundle\DataGridBundle\Datagrid\Common\DatagridConfiguration;
use Oro\Bundle\DataGridBundle\Datagrid\Common\MetadataObject;
use Oro\Bundle\DataGridBundle\Datagrid\Common\ResultsObject;
use Oro\Bundle\DataGridBundle\Datasource\DatasourceInterface;
use Oro\Bundle\DataGridBundle\Datasource\Orm\OrmDatasource;
use Oro\Bundle\DataGridBundle\Datasource\ResultRecord;
use Oro\Bundle\DataGridBundle\Datasource\ResultRecordInterface;
use Oro\Bundle\DataGridBundle\Extension\AbstractExtension;
use Oro\Bundle\DataGridBundle\Extension\Action\Actions\ActionInterface;
use Oro\Bundle\DataGridBundle\Extension\Formatter\Configuration;
use Oro\Bundle\DataGridBundle\Extension\Formatter\Property\CallbackProperty;
use Oro\Bundle\DataGridBundle\Extension\Formatter\Property\PropertyInterface;
use Oro\Bundle\DataGridBundle\Provider\DatagridModeProvider;
use Oro\Bundle\SecurityBundle\Acl\Domain\DomainObjectReference;
use Oro\Bundle\SecurityBundle\Owner\OwnershipQueryHelper;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

/**
 * Provides a way to add actions to datagrid rows.
 */
class ActionExtension extends AbstractExtension
{
    const METADATA_ACTION_KEY               = 'rowActions';
    const METADATA_ACTION_CONFIGURATION_KEY = 'action_configuration';

    const ACTION_KEY               = 'actions';
    const ACTION_CONFIGURATION_KEY = 'action_configuration';

    /** @var ActionFactory */
    protected $actionFactory;

    /** @var ActionMetadataFactory */
    protected $actionMetadataFactory;

    /** @var AuthorizationCheckerInterface */
    protected $authorizationChecker;

    /** @var OwnershipQueryHelper */
    protected $ownershipQueryHelper;

    /** @var bool */
    protected $isMetadataVisited = false;

    protected $excludedModes = [
        DatagridModeProvider::DATAGRID_IMPORTEXPORT_MODE
    ];

    /** @var iterable|DatagridActionProviderInterface[] */
    private $actionProviders;

    /**
     * @var array [entity alias => [
     *                      entity class,
     *                      entity id field alias,
     *                      organization id field alias,
     *                      owner id field alias
     *                  ],
     *                  ...
     *              ]
     */
    private $ownershipFields = [];

    /**
     * @param iterable|DatagridActionProviderInterface[] $actionProviders
     * @param ActionFactory                              $actionFactory
     * @param ActionMetadataFactory                      $actionMetadataFactory
     * @param AuthorizationCheckerInterface              $authorizationChecker
     * @param OwnershipQueryHelper                       $ownershipQueryHelper
     */
    public function __construct(
        iterable $actionProviders,
        ActionFactory $actionFactory,
        ActionMetadataFactory $actionMetadataFactory,
        AuthorizationCheckerInterface $authorizationChecker,
        OwnershipQueryHelper $ownershipQueryHelper
    ) {
        $this->actionProviders = $actionProviders;
        $this->actionFactory = $actionFactory;
        $this->actionMetadataFactory = $actionMetadataFactory;
        $this->authorizationChecker = $authorizationChecker;
        $this->ownershipQueryHelper = $ownershipQueryHelper;
    }

    #[\Override]
    public function processConfigs(DatagridConfiguration $config)
    {
        foreach ($this->actionProviders as $provider) {
            if ($provider->hasActions($config)) {
                $provider->applyActions($config);
            }
        }
    }

    #[\Override]
    public function getPriority()
    {
        // should  be applied before formatter extension
        // this extension add dynamic property and this may cause a bug
        return 200;
    }

    #[\Override]
    public function visitMetadata(DatagridConfiguration $config, MetadataObject $data)
    {
        $this->isMetadataVisited = true;
        $data->offsetAddToArray(self::METADATA_ACTION_KEY, $this->getActionsMetadata($config));
    }

    #[\Override]
    public function visitDatasource(DatagridConfiguration $config, DatasourceInterface $datasource)
    {
        if ($datasource instanceof OrmDatasource && $this->hasAclProtectedActions($config)) {
            $this->ownershipFields = $this->ownershipQueryHelper->addOwnershipFields(
                $datasource->getQueryBuilder()
            );
        }
    }

    #[\Override]
    public function visitResult(DatagridConfiguration $config, ResultsObject $result)
    {
        if (!$this->isMetadataVisited) {
            $result->offsetAddToArray(
                'metadata',
                [self::METADATA_ACTION_KEY => $this->getActionsMetadata($config)]
            );
        }

        if (!empty($this->ownershipFields)) {
            $aclResources = $this->getUniqueAclResources($config);
            if (!empty($aclResources)) {
                $aliases = array_keys($this->ownershipFields);
                $entityAlias = reset($aliases);
                [
                    $entityClass,
                    $entityIdFieldAlias,
                    $organizationIdFieldAlias,
                    $ownerIdFieldAlias
                ] = $this->ownershipFields[$entityAlias];

                /** @var ResultRecord[] $records */
                $records = $result->getData();
                $disabledActions = $this->getDisabledActions(
                    $records,
                    $entityClass,
                    $entityIdFieldAlias,
                    $organizationIdFieldAlias,
                    $ownerIdFieldAlias,
                    $aclResources
                );

                // set action_configuration callback only if there are some actions to disable.
                if (!empty($disabledActions)) {
                    $this->setActionsCallback($config, $disabledActions, $entityIdFieldAlias);
                }
            }
        }
    }

    /**
     * @param DatagridConfiguration $config
     *
     * @return array
     */
    protected function getActionsMetadata(DatagridConfiguration $config)
    {
        $actionsMetadata = [];
        /** @var array $actions */
        $actions = $config->offsetGetOr(self::ACTION_KEY, []);
        foreach ($actions as $actionName => $actionConfig) {
            $action = $this->createAction($actionName, $actionConfig);
            if (null !== $action) {
                $actionsMetadata[$action->getName()] = $this->createActionMetadata($action);
            }
        }

        return $actionsMetadata;
    }

    /**
     * @param DatagridConfiguration $config
     *
     * @return bool
     */
    protected function hasAclProtectedActions(DatagridConfiguration $config)
    {
        $hasAclActions = false;
        $actions = $config->offsetGetOr(self::ACTION_KEY, []);
        foreach ($actions as $actionName => $actionConfig) {
            if (!empty($actionConfig[ActionInterface::ACL_KEY])) {
                $hasAclActions = true;
                break;
            }
        }

        return $hasAclActions;
    }

    /**
     * @param string $actionName
     * @param array  $actionConfig
     *
     * @return ActionInterface|null
     */
    protected function createAction($actionName, array $actionConfig)
    {
        if ($actionConfig[PropertyInterface::DISABLED_KEY] ?? false) {
            return null;
        }

        $action = $this->actionFactory->createAction($actionName, $actionConfig);

        $aclResource = $action->getAclResource();
        if ($aclResource && !$this->authorizationChecker->isGranted($aclResource)) {
            $action = null;
        }

        return $action;
    }

    /**
     * @param ActionInterface $action
     *
     * @return array
     */
    protected function createActionMetadata(ActionInterface $action)
    {
        return $this->actionMetadataFactory->createActionMetadata($action);
    }

    /**
     * @param DatagridConfiguration $config
     *
     * @return array [action name => acl resource, ...]
     */
    protected function getActionsAclResources(DatagridConfiguration $config)
    {
        $aclResources = [];
        $actions = $config->offsetGetOr(self::ACTION_KEY, []);
        foreach ($actions as $actionName => $actionConfig) {
            if (!empty($actionConfig[ActionInterface::ACL_KEY])) {
                $aclResources[$actionName] = $actionConfig[ActionInterface::ACL_KEY];
            }
        }

        return $aclResources;
    }

    /**
     * Set actions callback to disable actions where not allowed.
     *
     * @param DatagridConfiguration $config
     * @param array                 $disabledActions
     * @param string                $entityIdFieldAlias
     */
    protected function setActionsCallback(DatagridConfiguration $config, $disabledActions, $entityIdFieldAlias)
    {
        $actionConfigurationPropertyPath = sprintf(
            '[%s][%s]',
            Configuration::PROPERTIES_KEY,
            self::METADATA_ACTION_CONFIGURATION_KEY
        );

        $existingCallback = null;
        $actionConfiguration = $config->offsetGetByPath($actionConfigurationPropertyPath);
        if ($actionConfiguration
            && 'callback' === $actionConfiguration[CallbackProperty::TYPE_KEY]
            && !empty($actionConfiguration[CallbackProperty::CALLABLE_KEY])
        ) {
            $existingCallback = $actionConfiguration[CallbackProperty::CALLABLE_KEY];
        }

        $config->offsetAddToArrayByPath(
            $actionConfigurationPropertyPath,
            [
                CallbackProperty::TYPE_KEY          => 'callback',
                CallbackProperty::CALLABLE_KEY      => $this->getActionsCallback(
                    $disabledActions,
                    $entityIdFieldAlias,
                    $existingCallback
                ),
                CallbackProperty::FRONTEND_TYPE_KEY => CallbackProperty::TYPE_ROW_ARRAY
            ]
        );
    }

    /**
     * @param array         $disabledActions
     * @param string        $entityIdFieldAlias
     * @param callable|null $existingCallback
     *
     * @return callable
     */
    protected function getActionsCallback(array $disabledActions, $entityIdFieldAlias, $existingCallback)
    {
        return function (
            ResultRecordInterface $record,
            array $actions = []
        ) use (
            $disabledActions,
            $entityIdFieldAlias,
            $existingCallback
        ) {
            $result = [];

            $entityId = $record->getValue($entityIdFieldAlias);
            if (!empty($disabledActions[$entityId])) {
                $result = $disabledActions[$entityId];
            }

            if (null !== $existingCallback) {
                $result = array_merge($existingCallback($record, $actions), $result);
            }

            return $result;
        };
    }

    private function getUniqueAclResources(DatagridConfiguration $config): array
    {
        $aclResources = $this->getActionsAclResources($config);

        $uniqueAclResources = [];
        foreach ($aclResources as $actionName => $aclResource) {
            $uniqueAclResources[$aclResource][] = $actionName;
        }

        return $uniqueAclResources;
    }

    /**
     * @param ResultRecord[] $records
     * @param string $entityClass
     * @param string $entityIdFieldAlias
     * @param string $organizationIdFieldAlias
     * @param string $ownerIdFieldAlias
     * @param array $aclResources
     *
     * @return array
     */
    private function getDisabledActions(
        array $records,
        string $entityClass,
        string $entityIdFieldAlias,
        string $organizationIdFieldAlias,
        string $ownerIdFieldAlias,
        array $aclResources
    ): array {
        $disabledActions = [];

        foreach ($records as $record) {
            $entityId = $record->getValue($entityIdFieldAlias);
            $entityReference = null;
            $ownerId = $record->getValue($ownerIdFieldAlias);
            if (null !== $ownerId) {
                $entityReference = new DomainObjectReference(
                    $entityClass,
                    $record->getValue($entityIdFieldAlias),
                    $ownerId,
                    $record->getValue($organizationIdFieldAlias)
                );
            }

            foreach ($aclResources as $aclResource => $actionNames) {
                if (!$this->authorizationChecker->isGranted($aclResource, $entityReference)) {
                    foreach ($actionNames as $actionName) {
                        $disabledActions[$entityId][$actionName] = false;
                    }
                }
            }
        }

        return $disabledActions;
    }
}
