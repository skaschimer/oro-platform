<?php

namespace Oro\Bundle\DataGridBundle\Event;

use Doctrine\ORM\AbstractQuery;
use Doctrine\ORM\Query;
use Oro\Bundle\DataGridBundle\Datagrid\DatagridInterface;
use Symfony\Contracts\EventDispatcher\Event;

/**
 * Class ResultBefore
 * @package Oro\Bundle\DataGridBundle\Event
 *
 * This event is dispatched before datagrid builder starts to build result
 */
class OrmResultBefore extends Event implements GridEventInterface
{
    const NAME = 'oro_datagrid.orm_datasource.result.before';

    /**
     * @var DatagridInterface
     */
    protected $datagrid;

    /**
     * @var AbstractQuery
     */
    protected $query;

    public function __construct(DatagridInterface $datagrid, AbstractQuery $query)
    {
        $this->datagrid = $datagrid;
        $this->query    = $query;
    }

    #[\Override]
    public function getDatagrid()
    {
        return $this->datagrid;
    }

    /**
     * @return AbstractQuery|Query
     */
    public function getQuery()
    {
        return $this->query;
    }
}
