<?php

namespace Oro\Bundle\EntityBundle\DataCollector;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\DataCollector\DataCollector;

/**
 * Collects hydration statistic - number of hydrations, time and entities
 */
class OrmDataCollector extends DataCollector
{
    /** @var OrmLogger */
    protected $logger;

    public function __construct(OrmLogger $logger)
    {
        $this->logger = $logger;

        $this->reset();
    }

    #[\Override]
    public function collect(Request $request, Response $response, ?\Throwable $exception = null)
    {
        $this->data['hydrations'] = $this->logger->getHydrations();
        $this->data['stats']      = $this->logger->getStats();
        $this->data['statsTime']  = $this->logger->getStatsTime();
    }

    /**
     * Gets executed hydrations.
     *
     * @return array
     */
    public function getHydrations()
    {
        return $this->data['hydrations'];
    }

    /**
     * Gets a number of executed hydrations.
     *
     * @return int
     */
    public function getHydrationCount()
    {
        return count($this->data['hydrations']);
    }

    /**
     * Gets a total time of all executed hydrations.
     *
     * @return float
     */
    public function getHydrationTime()
    {
        $time = 0;
        foreach ($this->data['hydrations'] as $hydration) {
            if (isset($hydration['time'])) {
                $time += $hydration['time'];
            }
        }

        return $time;
    }

    /**
     * Gets a number of hydrated entities.
     *
     * @return int
     */
    public function getHydratedEntities()
    {
        $result = 0;
        foreach ($this->data['hydrations'] as $hydration) {
            if (isset($hydration['resultCount'])) {
                $result += $hydration['resultCount'];
            }
        }

        return $result;
    }

    /**
     * Gets statistic of executed ORM operations.
     *
     * @return array
     */
    public function getStats()
    {
        return $this->data['stats'];
    }

    /**
     * Gets a total time of all executed hydrations and executed ORM operations.
     *
     * @return float
     */
    public function getTotalTime()
    {
        return $this->getHydrationTime() + $this->data['statsTime'];
    }

    #[\Override]
    public function getName(): string
    {
        return 'orm';
    }

    #[\Override]
    public function reset()
    {
        $this->data = [
            'hydrations' => [],
            'stats' => [],
            'statsTime' => 0.0,
        ];
    }
}
