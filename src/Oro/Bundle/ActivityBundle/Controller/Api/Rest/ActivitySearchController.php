<?php

namespace Oro\Bundle\ActivityBundle\Controller\Api\Rest;

use FOS\RestBundle\Controller\Annotations\QueryParam;
use Nelmio\ApiDocBundle\Annotation\ApiDoc;
use Oro\Bundle\SoapBundle\Controller\Api\Rest\RestGetController;
use Oro\Bundle\SoapBundle\Request\Parameters\Filter\ChainParameterFilter;
use Oro\Bundle\SoapBundle\Request\Parameters\Filter\EntityClassParameterFilter;
use Oro\Bundle\SoapBundle\Request\Parameters\Filter\StringToArrayParameterFilter;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * REST API controller to find activity associations.
 */
class ActivitySearchController extends RestGetController
{
    /**
     * Searches entities associated with the specified type of activity.
     *
     * @param Request $request
     * @param string $activity The type of the activity entity.
     *
     * @ApiDoc(
     *      description="Searches entities associated with the specified type of activity",
     *      resource=true
     * )
     *
     * @return Response
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
        description: 'Number of items per page. Defaults to 10.',
        nullable: true
    )]
    #[QueryParam(name: 'search', requirements: '.+', description: 'The search string.', nullable: true)]
    #[QueryParam(
        name: 'from',
        requirements: '.+',
        description: 'The entity alias. One or several aliases separated by comma. Defaults to all entities',
        nullable: true
    )]
    public function cgetAction(Request $request, $activity)
    {
        $manager = $this->getManager();
        $manager->setClass($manager->resolveEntityClass($activity, true));

        $page  = (int)$request->get('page', 1);
        $limit = (int)$request->get('limit', self::ITEMS_PER_PAGE);

        $filters = [
            'search' => $request->get('search')
        ];
        $from = $request->get('from', null);
        if ($from) {
            $filter          = new ChainParameterFilter(
                [
                    new StringToArrayParameterFilter(),
                    new EntityClassParameterFilter($this->container->get('oro_entity.entity_class_name_helper'))
                ]
            );
            $filters['from'] = $filter->filter($from, null);
        }

        return $this->handleGetListRequest($page, $limit, $filters);
    }

    #[\Override]
    public function getManager()
    {
        return $this->container->get('oro_activity.manager.activity_search.api');
    }
}
