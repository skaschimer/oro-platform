<?php

namespace Oro\Bundle\SearchBundle\Query\Factory;

use Oro\Bundle\SearchBundle\Engine\Indexer;
use Oro\Bundle\SearchBundle\Query\IndexerQuery;

class QueryFactory implements QueryFactoryInterface
{
    /** @var Indexer */
    protected $indexer;

    public function __construct(Indexer $indexer)
    {
        $this->indexer = $indexer;
    }

    #[\Override]
    public function create(array $config = [])
    {
        return new IndexerQuery(
            $this->indexer,
            $this->indexer->select()
        );
    }
}
