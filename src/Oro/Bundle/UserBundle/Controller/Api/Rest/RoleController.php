<?php

namespace Oro\Bundle\UserBundle\Controller\Api\Rest;

use FOS\RestBundle\Controller\Annotations\QueryParam;
use Nelmio\ApiDocBundle\Annotation\ApiDoc;
use Oro\Bundle\SecurityBundle\Attribute\Acl;
use Oro\Bundle\SecurityBundle\Attribute\AclAncestor;
use Oro\Bundle\SoapBundle\Controller\Api\Rest\RestController;
use Oro\Bundle\UserBundle\Entity\Role;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * REST API CRUD controller for Role entity.
 */
class RoleController extends RestController
{
    /**
     * Get the list of roles
     *
     * @param Request $request
     * @return Response
     * @ApiDoc(
     *      description="Get the list of roles",
     *      resource=true
     * )
     */
    #[QueryParam(
        name: 'page',
        requirements: '\d+',
        description: 'Page number, starting from 1. Defaults to 1.',
        nullable: true
    )]
    #[QueryParam(
        name: 'limit',
        requirements: '\d+',
        description: 'Number of items per page. defaults to 10.',
        nullable: true
    )]
    #[AclAncestor('oro_user_role_view')]
    public function cgetAction(Request $request)
    {
        $page = (int) $request->get('page', 1);
        $limit = (int) $request->get('limit', self::ITEMS_PER_PAGE);

        return $this->handleGetListRequest($page, $limit);
    }

    /**
     * Get role data
     *
     * @param int $id Role id
     *
     * @ApiDoc(
     *      description="Get role data",
     *      resource=true,
     *      filters={
     *          {"name"="id", "dataType"="integer"},
     *      }
     * )
     *
     * @return Response
     */
    #[AclAncestor('oro_user_role_view')]
    public function getAction(int $id)
    {
        return $this->handleGetRequest($id);
    }

    /**
     * Create new role
     *
     * @return Response
     * @ApiDoc(
     *      description="Create new role",
     *      resource=true
     * )
     */
    #[AclAncestor('oro_user_role_create')]
    public function postAction()
    {
        return $this->handleCreateRequest();
    }

    /**
     * Update existing role
     *
     * @param int $id Role id
     *
     * @ApiDoc(
     *      description="Update existing role",
     *      resource=true,
     *      filters={
     *          {"name"="id", "dataType"="integer"},
     *      }
     * )
     *
     * @return Response
     */
    #[AclAncestor('oro_user_role_update')]
    public function putAction(int $id)
    {
        return $this->handleUpdateRequest($id);
    }

    /**
     * Delete role
     *
     * @param int $id Role id
     *
     * @ApiDoc(
     *      description="Delete role",
     *      resource=true,
     *      filters={
     *          {"name"="id", "dataType"="integer"},
     *      }
     * )
     *
     * @return Response
     */
    #[Acl(id: 'oro_user_role_delete', type: 'entity', class: Role::class, permission: 'DELETE')]
    public function deleteAction(int $id)
    {
        return $this->handleDeleteRequest($id);
    }

    /**
     * Get role by name
     *
     * @param string $name Role name
     *
     * @return Response
     * @ApiDoc(
     *      description="Get role by name",
     *      resource=true,
     *      filters={
     *          {"name"="name", "dataType"="string"},
     *      }
     * )
     */
    #[AclAncestor('oro_user_role_view')]
    public function getBynameAction($name)
    {
        $entity = $this->getManager()->getRepository()->findOneBy(['label' => $name]);

        return $this->handleView(
            $this->view(
                $entity ? $this->getPreparedItem($entity) : null,
                $entity ? Response::HTTP_OK : Response::HTTP_NOT_FOUND
            )
        );
    }

    #[\Override]
    protected function transformEntityField($field, &$value)
    {
        switch ($field) {
            case 'owner':
                if ($value) {
                    $value = [
                        'id' => $value->getId(),
                        'name' => $value->getName()
                    ];
                }
                break;
            default:
                parent::transformEntityField($field, $value);
        }
    }

    #[\Override]
    public function getManager()
    {
        return $this->container->get('oro_user.role_manager.api');
    }

    #[\Override]
    public function getForm()
    {
        return $this->container->get('oro_user.form.role.api');
    }

    #[\Override]
    public function getFormHandler()
    {
        return $this->container->get('oro_user.form.handler.role.api');
    }
}
