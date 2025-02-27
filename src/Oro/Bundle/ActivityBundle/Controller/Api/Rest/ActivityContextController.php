<?php

namespace Oro\Bundle\ActivityBundle\Controller\Api\Rest;

use Nelmio\ApiDocBundle\Annotation\ApiDoc;
use Oro\Bundle\SoapBundle\Controller\Api\Rest\RestGetController;
use Symfony\Component\HttpFoundation\Response;

/**
 * REST API controller to get activity context data.
 */
class ActivityContextController extends RestGetController
{
    /**
     * Get activity context data.
     *
     * @param string $activity The type of the activity entity.
     * @param int    $id       The id of the activity entity.
     *
     * @ApiDoc(
     *      description="Get activity context data",
     *      resource=true
     * )
     *
     * @return Response
     */
    public function getAction($activity, $id)
    {
        $className = $this->container->get('oro_entity.routing_helper')->resolveEntityClass($activity);

        $result = $this->getManager()->getActivityContext($className, $id);

        return $this->buildResponse($result, self::ACTION_LIST, ['result' => $result]);
    }

    #[\Override]
    public function getManager()
    {
        return $this->container->get('oro_activity.manager.activity_context.api');
    }
}
