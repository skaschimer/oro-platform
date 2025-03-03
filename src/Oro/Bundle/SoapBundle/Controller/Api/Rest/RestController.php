<?php

namespace Oro\Bundle\SoapBundle\Controller\Api\Rest;

use Doctrine\ORM\EntityNotFoundException;
use Oro\Bundle\SoapBundle\Controller\Api\FormAwareInterface;
use Oro\Bundle\SoapBundle\Controller\Api\FormHandlerAwareInterface;
use Oro\Bundle\SoapBundle\Handler\DeleteHandler;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Acl\Util\ClassUtils;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

/**
 * The base class for REST API CRUD controllers.
 */
abstract class RestController extends RestGetController implements
    FormAwareInterface,
    FormHandlerAwareInterface,
    RestApiCrudInterface
{
    #[\Override]
    public function handleUpdateRequest($id)
    {
        $entity = $this->getManager()->find($id);

        if ($entity) {
            $entity = $this->processForm($entity);
            if ($entity) {
                $view = $this->view(null, Response::HTTP_NO_CONTENT);
            } else {
                $view = $this->view($this->getForm(), Response::HTTP_BAD_REQUEST);
            }
        } else {
            $view = $this->view(null, Response::HTTP_NOT_FOUND);
        }

        return $this->buildResponse($view, self::ACTION_UPDATE, ['id' => $id, 'entity' => $entity]);
    }

    /**
     * Create new
     *
     * @param mixed $_ [optional] Arguments will be passed to createEntity method
     *
     * @return Response
     */
    #[\Override]
    public function handleCreateRequest($_ = null)
    {
        $isProcessed = false;

        $entity = call_user_func_array(array($this, 'createEntity'), func_get_args());
        $entity = $this->processForm($entity);

        if ($entity) {
            $view = $this->view($this->createResponseData($entity), Response::HTTP_CREATED);
            $isProcessed = true;
        } else {
            $view = $this->view($this->getForm(), Response::HTTP_BAD_REQUEST);
        }

        return $this->buildResponse($view, self::ACTION_CREATE, ['success' => $isProcessed, 'entity' => $entity]);
    }

    #[\Override]
    public function getForm()
    {
        $handler = $this->getFormHandler();
        if (!$handler instanceof FormAwareInterface) {
            throw new \RuntimeException(
                'The form cannot be retrieved. You have two choices to solve this: '
                . 'implement "Oro\Bundle\SoapBundle\Controller\Api\FormAwareInterface" in your form handler '
                . ' or implement "getForm" method in your controller.'
            );
        }

        return $handler->getForm();
    }

    /**
     * Create new entity
     *
     * @param mixed $_ [optional] Arguments will be passed to createEntity method of manager (result of getManager)
     *
     * @return mixed
     */
    protected function createEntity()
    {
        return call_user_func_array(array($this->getManager(), 'createEntity'), func_get_args());
    }

    #[\Override]
    public function handleDeleteRequest($id, array $options = [])
    {
        $isProcessed = false;

        try {
            $this->getDeleteHandler()->handleDelete($id, $this->getManager(), $options);

            $isProcessed = true;
            $view        = $this->view(null, Response::HTTP_NO_CONTENT);
        } catch (EntityNotFoundException $e) {
            $view = $this->view(null, Response::HTTP_NOT_FOUND);
        } catch (AccessDeniedException $e) {
            $view = $this->view(['reason' => $e->getMessage()], Response::HTTP_FORBIDDEN);
        }

        return $this->buildResponse($view, self::ACTION_DELETE, ['id' => $id, 'success' => $isProcessed]);
    }

    /**
     * Process form.
     *
     * @param mixed $entity
     *
     * @return mixed|null The instance of saved entity on successful processing; otherwise, null
     */
    protected function processForm($entity)
    {
        $this->fixRequestAttributes($entity);

        $result = $this->getFormHandler()->process($entity);
        if (is_object($result) || null === $result) {
            return $result;
        }

        // some form handlers may return true/false rather than saved entity
        return $result ? $entity : null;
    }

    /**
     * Convert REST request to format applicable for form.
     *
     * @param object $entity
     */
    protected function fixRequestAttributes($entity)
    {
        $request  = $this->container->get('request_stack')->getCurrentRequest();
        $formName = $this->getForm()->getName();
        $data     = empty($formName)
            ? $request->request->all()
            : $request->get($formName);

        if (is_array($data) && $this->fixFormData($data, $entity)) {
            if (empty($formName)) {
                // save fixed values for unnamed form
                foreach ($request->request->keys() as $key) {
                    if (array_key_exists($key, $data)) {
                        $request->request->set($key, $data[$key]);
                    } else {
                        $request->request->remove($key);
                    }
                }
                foreach ($data as $key => $val) {
                    if (!$request->request->has($key)) {
                        $request->request->set($key, $data[$key]);
                    }
                }
            } else {
                // save fixed values for named form
                $request->request->set($this->getForm()->getName(), $data);
            }
        }
    }

    /**
     * Creates data returned if an entity has been successfully created
     *
     * @param mixed $entity
     *
     * @return array
     */
    protected function createResponseData($entity)
    {
        $entityClass   = ClassUtils::getRealClass($entity);
        $classMetadata = $this->getManager()->getObjectManager()->getClassMetadata($entityClass);

        return $classMetadata->getIdentifierValues($entity);
    }

    /**
     * Fixes form data
     *
     * @param array $data
     * @param mixed $entity
     *
     * @return bool true if any changes in $data array was made; otherwise, false.
     */
    protected function fixFormData(array &$data, $entity)
    {
        return false;
    }

    /**
     * Gets an object responsible to delete an entity.
     *
     * @return DeleteHandler
     */
    protected function getDeleteHandler()
    {
        return $this->container->get('oro_soap.handler.delete');
    }
}
