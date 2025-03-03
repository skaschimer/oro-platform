<?php

namespace Oro\Bundle\SearchBundle\Query;

use Oro\Bundle\SearchBundle\Engine\Indexer;

class IndexerQuery extends AbstractSearchQuery
{
    /**
     * @var Indexer
     */
    protected $indexer;

    public function __construct(Indexer $indexer, Query $query)
    {
        $this->indexer = $indexer;
        $this->query   = $query;
    }

    public function __call($name, $args)
    {
        return call_user_func_array(array($this->query, $name), $args);
    }

    #[\Override]
    protected function query()
    {
        return $this->indexer->query($this->query);
    }
}
