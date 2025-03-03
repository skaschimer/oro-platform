<?php

namespace Oro\Bundle\BatchBundle\Tests\Functional\ORM;

use Doctrine\DBAL\Platforms\MySQL80Platform;
use Doctrine\DBAL\Platforms\PostgreSqlPlatform;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\QueryBuilder;
use Oro\Bundle\BatchBundle\ORM\Query\AbstractBufferedQueryResultIterator;
use Oro\Bundle\TestFrameworkBundle\Entity\Item;
use Oro\Bundle\TestFrameworkBundle\Entity\ItemValue;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Bundle\TestFrameworkBundle\Tests\Functional\DataFixtures\LoadOrganization;
use Oro\Component\Testing\Assert\ArrayContainsConstraint;

/**
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
abstract class AbstractBufferedIdentityQueryResultIteratorTest extends WebTestCase
{
    #[\Override]
    public function setUp(): void
    {
        $this->initClient();
        $this->loadFixtures([
            LoadOrganization::class,
            '@OroBatchBundle/Tests/Functional/Fixture/data/buffered_iterator.yml',
        ]);
    }

    public function testSimpleQuery()
    {
        $queryBuilder = $this->getEntityManager()->getRepository(Item::class)->createQueryBuilder('item');

        $this->assertSameResult($queryBuilder);
    }

    public function testJoinAndGroup()
    {
        $queryBuilder = $this->getEntityManager()->getRepository(Item::class)->createQueryBuilder('item');
        $queryBuilder
            ->select('item.id, item.stringValue, SUM(value.id)')
            ->leftJoin('item.values', 'value')
            ->groupBy('item.id');

        $this->assertSameResult($queryBuilder);
    }

    public function testInconsistentKey()
    {
        $this->expectException(\LogicException::class);

        $queryBuilder = $this->getEntityManager()->getRepository(Item::class)->createQueryBuilder('item');
        $queryBuilder
            ->select('item.id, item.stringValue, value.id')
            ->leftJoin('item.values', 'value')
            ->groupBy('value.id');

        $this->assertSameResult($queryBuilder);
    }

    /**
     * When selecting certain fields default hydration will be array
     * In this case result rows may appear in different order after iteration by Iterator
     */
    public function testLeftJoinScalar()
    {
        $queryBuilder = $this->getEntityManager()->getRepository(Item::class)->createQueryBuilder('item');
        $queryBuilder
            ->select('item.id, item.stringValue, value.id as vid')
            ->leftJoin('item.values', 'value');

        $this->assertSameResult($queryBuilder);
    }

    public function testLeftJoinObject()
    {
        $queryBuilder = $this->getEntityManager()->getRepository(Item::class)->createQueryBuilder('item');
        $queryBuilder
            ->select('item, value')
            ->leftJoin('item.values', 'value');

        $this->assertSameResult($queryBuilder);
    }

    public function testWhereScalar()
    {
        $queryBuilder = $this->getEntityManager()->getRepository(Item::class)->createQueryBuilder('item');
        $queryBuilder
            ->select('item.id, item.stringValue, value.id as vid')
            ->leftJoin('item.values', 'value')
            ->where('value.id > 15 and item.stringValue != :stringValue')
            ->setParameter('stringValue', 'String Value 3');

        $this->assertSameResult($queryBuilder);
    }

    public function testWhereObject()
    {
        $queryBuilder = $this->getEntityManager()->getRepository(Item::class)->createQueryBuilder('item');
        $queryBuilder
            ->select('item, value')
            ->leftJoin('item.values', 'value')
            ->where('value.id > 15 and item.stringValue != :stringValue')
            ->setParameter('stringValue', 'String Value 3');

        $this->assertSameResult($queryBuilder);
    }

    /**
     * @dataProvider limitOffsetProvider
     */
    public function testLimitOffset(int $offset, int $limit)
    {
        $queryBuilder = $this->getEntityManager()->getRepository(Item::class)->createQueryBuilder('item');
        $queryBuilder->setFirstResult($offset);
        $queryBuilder->setMaxResults($limit);

        $this->assertSameResult($queryBuilder);
    }

    public function limitOffsetProvider(): array
    {
        $data = [];
        foreach (range(0, 10) as $i) {
            $data[] = [
                'offset' => $i % 5,
                'limit'  => $i * 2,
            ];
        }

        return $data;
    }

    public function testChangingDataset()
    {
        $em = $this->getEntityManager();

        $queryBuilder = $em->getRepository(Item::class)->createQueryBuilder('item');
        $queryBuilder->where('item.stringValue != :v');
        $queryBuilder->setParameter('v', 'processed');

        $result = $queryBuilder->getQuery()->execute();

        $iterator = $this->getIterator($queryBuilder);
        $iterator->setBufferSize(3);

        $iteratorResult = [];
        foreach ($iterator as $i => $item) {
            // every few records set one as processed to change initial dataset (ruins default pagination)
            if ($i % 3 == 0) {
                $id = $item->getId();
                $em->getConnection()
                    ->executeStatement("update test_search_item set stringValue = 'processed' where id = {$id}");
            }
            $iteratorResult[] = $item;
        }

        if ($this->isPostgreSql()) {
            // Iterator adds sorting automatically, on PostgreSQL results order may be different without sorting
            $queryBuilder->orderBy('item.id');
        }

        self::assertEquals($result, $iteratorResult);
    }

    public function testDelete()
    {
        $em = $this->getEntityManager();

        $queryBuilder = $em->getRepository(ItemValue::class)->createQueryBuilder('value');
        $all = count($queryBuilder->getQuery()->execute());

        //every 3rd row
        $queryBuilder->where('Mod(value.id, 3) = 0');
        $toDelete = count($queryBuilder->getQuery()->execute());

        $iterator = $this->getIterator($queryBuilder);
        $iterator->setBufferSize(4);

        foreach ($iterator as $item) {
            $id = $item->getId();
            $em->getConnection()
                ->executeStatement("delete from test_search_item_value where id = {$id}");
        }

        $queryBuilder = $em->getRepository(ItemValue::class)->createQueryBuilder('value');
        $afterDelete = count($queryBuilder->getQuery()->execute());

        if ($this->isPostgreSql()) {
            // Iterator adds sorting automatically, on PostgreSQL results order may be different without sorting
            $queryBuilder->orderBy('item.id');
        }

        self::assertEquals($all - $toDelete, $afterDelete);
    }

    /**
     * When selecting certain fields default hydration will be array
     * In this case result rows may appear in different order after iteration by Iterator
     */
    public function testOrderByJoinedFieldScalar()
    {
        $queryBuilder = $this->getEntityManager()->getRepository(Item::class)->createQueryBuilder('item');
        $queryBuilder
            ->select('item.id, item.stringValue, item.integerValue')
            ->leftJoin('item.values', 'value')
            ->orderBy('item.stringValue')
            ->where('MOD(value.id, 2) = 0')
            ->orderBy('value.id');

        if ($this->isPostgreSql() || $this->isMySql8()) {
            $this->expectException(\LogicException::class);
        }

        $this->assertSameByIdWithoutOrder($queryBuilder);
    }

    /**
     * With Object Hydration there will be no previous problem
     */
    public function testOrderByJoinedFieldObjectHydration()
    {
        $queryBuilder = $this->getEntityManager()->getRepository(Item::class)->createQueryBuilder('item');
        $queryBuilder
            ->select('item, value')
            ->leftJoin('item.values', 'value')
            ->orderBy('item.stringValue')
            ->where('MOD(value.id, 2) = 0')
            ->orderBy('value.id');

        if ($this->isPostgreSql() || $this->isMySql8()) {
            $this->expectException(\LogicException::class);
        }

        $this->assertSameResult($queryBuilder);
    }

    private function getResultsWithForeachLoop(QueryBuilder $queryBuilder): array
    {
        $iterator = $this->getIterator($queryBuilder);
        $iterator->setBufferSize(3);

        $iteratorResult = [];
        foreach ($iterator as $entity) {
            $iteratorResult[] = $entity;
        }

        $query = $queryBuilder->getQuery();
        $result = $query->execute();

        return [$result, $iteratorResult];
    }

    private function getResultsWithWhileLoopRewindFirst(QueryBuilder $queryBuilder): array
    {
        $iteratorResult = [];

        $iterator = $this->getIterator($queryBuilder);
        $iterator->setBufferSize(3);

        $iterator->rewind();
        while ($iterator->valid()) {
            $data = $iterator->current();
            $iteratorResult[] = $data;

            $iterator->next();
        }

        $query = $queryBuilder->getQuery();
        $result = $query->execute();

        return [$result, $iteratorResult];
    }

    private function getResultsWithWhileLoopNextFirst(QueryBuilder $queryBuilder): array
    {
        $iteratorResult = [];

        $iterator = $this->getIterator($queryBuilder);
        $iterator->setBufferSize(3);

        /**
         * typically $iterator->rewind() should be called before loop
         * but in case $iterator->next() called first all should be fine too
         */
        $iterator->next();
        while ($iterator->valid()) {
            $data = $iterator->current();
            $iteratorResult[] = $data;

            $iterator->next();
        }

        $query = $queryBuilder->getQuery();
        $result = $query->execute();

        return [$result, $iteratorResult];
    }

    /**
     * Asserts 2 datasets are equal
     */
    private function assertSameResult(QueryBuilder $queryBuilder): void
    {
        [$expected, $actual] = $this->getResultsWithForeachLoop($queryBuilder);
        self::assertCount(count($expected), $actual);
        self::assertThat($expected, new ArrayContainsConstraint($actual, false));

        [$expected, $actual] = $this->getResultsWithWhileLoopRewindFirst($queryBuilder);
        self::assertCount(count($expected), $actual);
        self::assertThat($expected, new ArrayContainsConstraint($actual, false));

        [$expected, $actual] = $this->getResultsWithWhileLoopNextFirst($queryBuilder);
        self::assertCount(count($expected), $actual);
        self::assertThat($expected, new ArrayContainsConstraint($actual, false));
    }

    /**
     * Asserts 2 datasets are equal by comparing only result IDs without taking into account results order.
     */
    private function assertSameByIdWithoutOrder(QueryBuilder $queryBuilder): void
    {
        [$expected, $actual] = $this->getResultsWithForeachLoop($queryBuilder);
        $this->compareQueryResultWithIteratorResult($expected, $actual);

        [$expected, $actual] = $this->getResultsWithWhileLoopRewindFirst($queryBuilder);
        $this->compareQueryResultWithIteratorResult($expected, $actual);

        [$expected, $actual] = $this->getResultsWithWhileLoopNextFirst($queryBuilder);
        $this->compareQueryResultWithIteratorResult($expected, $actual);
    }

    private function compareQueryResultWithIteratorResult(array $queryResult, array $iteratorResult): void
    {
        // Compares datasets expecting each item will contain 'id' field
        $queryResultIds = array_column($queryResult, 'id');
        $iteratorResultIds = array_column($iteratorResult, 'id');
        self::assertCount(count($queryResultIds), $iteratorResultIds);

        // Sorting results due to result rows may appear in different order after iteration by Iterator
        asort($queryResultIds, SORT_NUMERIC);
        asort($iteratorResultIds, SORT_NUMERIC);

        self::assertSame(
            array_values($queryResultIds),
            array_values($iteratorResultIds)
        );
    }

    /**
     * Checks if current DB adapter is PostgreSQL
     */
    private function isPostgreSql(): bool
    {
        return $this->getEntityManager()->getConnection()->getDatabasePlatform() instanceof PostgreSqlPlatform;
    }

    /**
     * Checks if current DB adapter is MySQL 8
     */
    private function isMySql8(): bool
    {
        return $this->getEntityManager()->getConnection()->getDatabasePlatform() instanceof MySQL80Platform;
    }

    private function getEntityManager(): EntityManagerInterface
    {
        return self::getContainer()->get('doctrine')->getManager();
    }

    abstract public function getIterator(QueryBuilder $queryBuilder): AbstractBufferedQueryResultIterator;
}
