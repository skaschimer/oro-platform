<?php

namespace Oro\Bundle\UserBundle\Datagrid;

use Doctrine\Common\Collections\ArrayCollection;
use Oro\Bundle\DataGridBundle\Datagrid\DatagridInterface;
use Oro\Bundle\DataGridBundle\Datasource\DatasourceInterface;
use Oro\Bundle\DataGridBundle\Datasource\ResultRecord;
use Oro\Bundle\EntityConfigBundle\Config\ConfigManager;
use Oro\Bundle\SecurityBundle\Acl\AccessLevel;
use Oro\Bundle\SecurityBundle\Acl\Permission\PermissionManager;
use Oro\Bundle\SecurityBundle\Entity\Permission;
use Oro\Bundle\SecurityBundle\Form\Type\AclAccessLevelSelectorType;
use Oro\Bundle\SecurityBundle\Model\AclPermission;
use Oro\Bundle\SecurityBundle\Model\AclPrivilege;
use Oro\Bundle\UserBundle\Entity\AbstractRole;
use Oro\Bundle\UserBundle\Form\Handler\AclRoleHandler;
use Oro\Bundle\UserBundle\Provider\RolePrivilegeAbstractProvider;
use Oro\Bundle\UserBundle\Provider\RolePrivilegeCategoryProvider;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * The datasource for a datagrid of role permissions.
 */
class RolePermissionDatasource extends RolePrivilegeAbstractProvider implements DatasourceInterface
{
    /** @var PermissionManager */
    protected $permissionManager;

    /** @var ConfigManager */
    protected $configEntityManager;

    /** @var AbstractRole */
    protected $role;

    public function __construct(
        TranslatorInterface $translator,
        PermissionManager $permissionManager,
        AclRoleHandler $aclRoleHandler,
        RolePrivilegeCategoryProvider $categoryProvider,
        ConfigManager $configEntityManager
    ) {
        parent::__construct($translator, $categoryProvider, $aclRoleHandler);
        $this->permissionManager = $permissionManager;
        $this->configEntityManager = $configEntityManager;
    }

    #[\Override]
    public function process(DatagridInterface $grid, array $config)
    {
        $this->role = $grid->getParameters()->get('role');
        $grid->setDatasource(clone $this);
    }

    #[\Override]
    public function getResults()
    {
        $gridData = [];
        $allPrivileges = $this->preparePrivileges($this->role, 'entity');
        $categories = $this->categoryProvider->getCategories();

        foreach ($allPrivileges as $privilege) {
            /** @var AclPrivilege $privilege */
            $item = [
                'identity' => $privilege->getIdentity()->getId(),
                'label' => $privilege->getIdentity()->getName(),
                'group' => $this->getPrivilegeCategory($privilege, $categories),
                'permissions' => []
            ];
            $fields = $this->getFieldPrivileges($privilege->getFields());
            if (count($fields)) {
                $item['fields'] = $fields;
            }
            $item = $this->preparePermissions($privilege, $item);
            $gridData[] = new ResultRecord($item);
        }

        return $gridData;
    }

    /**
     * @param ArrayCollection $fields
     *
     * @return array
     */
    protected function getFieldPrivileges(ArrayCollection $fields)
    {
        $result = [];
        foreach ($fields as $privilege) {
            /** @var AclPrivilege $privilege */
            $item =  [
                'identity' => $privilege->getIdentity()->getId(),
                'label' => $privilege->getIdentity()->getName(),
                'permissions' => []
            ];
            $result[] = $this->preparePermissions($privilege, $item);
        }

        return $result;
    }

    /**
     * @param AclPrivilege $privilege
     * @param array $item
     *
     * @return mixed
     */
    protected function preparePermissions(AclPrivilege $privilege, $item)
    {
        $permissions = [];
        foreach ($privilege->getPermissions() as $permissionName => $permission) {
            /** @var AclPermission $permission */
            $permissionEntity = $this->permissionManager->getPermissionByName($permission->getName());
            if ($permissionEntity && $this->isSupportedPermission($permissionName)) {
                $privilegePermission = $this->getPrivilegePermission(
                    $privilege,
                    $permissionEntity,
                    $permissionName,
                    $permission
                );
                $permissions[$permission->getName()] = $privilegePermission;
            }
        }
        $item['permissions'] = $this->sortPermissions($permissions);

        return $item;
    }

    /**
     * @param string $permissionName
     *
     * @return bool
     */
    protected function isSupportedPermission($permissionName)
    {
        return true;
    }

    /**
     * @param AclPrivilege $privilege
     * @param Permission $permissionEntity
     * @param string $permissionName
     * @param AclPermission $permission
     *
     * @return array
     */
    protected function getPrivilegePermission(
        AclPrivilege $privilege,
        Permission $permissionEntity,
        $permissionName,
        AclPermission $permission
    ) {
        $permissionLabel = $permissionEntity->getLabel() ?: $permissionName;
        $permissionLabel = $this->translator->trans($permissionLabel);

        $permissionDescription = '';
        if ($permissionEntity->getDescription()) {
            $permissionDescription = $this->translator->trans($permissionEntity->getDescription());
        }

        $accessLevel = $permission->getAccessLevel();
        $accessLevelName = AccessLevel::getAccessLevelName($accessLevel);
        $valueText = $this->getRoleTranslationPrefix() . (empty($accessLevelName) ? 'NONE' : $accessLevelName);
        $valueText = $this->translator->trans($valueText);

        return [
            'id'                 => $permissionEntity->getId(),
            'name'               => $permissionEntity->getName(),
            'label'              => $permissionLabel,
            'description'        => $permissionDescription,
            'identity'           => $privilege->getIdentity()->getId(),
            'access_level'       => $accessLevel,
            'access_level_label' => $valueText
        ];
    }

    /**
     * @return string
     */
    protected function getRoleTranslationPrefix()
    {
        return AclAccessLevelSelectorType::TRANSLATE_KEY_ACCESS_LEVEL . '.';
    }

    /**
     * Sort permissions. The CRUD permissions goes first and other ordered by the alphabet.
     *
     * @param array $permissions
     *
     * @return array
     */
    protected function sortPermissions(array $permissions)
    {
        $result = [];
        $permissionsList = ['VIEW', 'CREATE', 'EDIT', 'DELETE'];
        foreach ($permissionsList as $permissionName) {
            if (array_key_exists($permissionName, $permissions)) {
                $result[] = $permissions[$permissionName];
                unset($permissions[$permissionName]);
            }
        }

        if (count($permissions)) {
            ksort($permissions);
            foreach ($permissions as $permission) {
                $result[] = $permission;
            }
        }

        return $result;
    }
}
