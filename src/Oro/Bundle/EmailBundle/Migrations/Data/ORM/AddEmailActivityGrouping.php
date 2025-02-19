<?php

namespace Oro\Bundle\EmailBundle\Migrations\Data\ORM;

use Doctrine\Common\Collections\Criteria;
use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ObjectManager;
use Oro\Bundle\BatchBundle\ORM\Query\BufferedQueryResultIterator;
use Oro\Bundle\EmailBundle\Entity\Email;
use Oro\Bundle\EmailBundle\Entity\EmailThread;

/**
 * Adds grouping for emails in threads.
 */
class AddEmailActivityGrouping extends AbstractFixture implements DependentFixtureInterface
{
    private const BATCH_SIZE = 100;

    #[\Override]
    public function getDependencies(): array
    {
        return [AddEmailActivityLists::class];
    }

    #[\Override]
    public function load(ObjectManager $manager): void
    {
        $criteria = new Criteria();
        $criteria->where($criteria->expr()->neq('xThreadId', null));
        /** @var QueryBuilder $threadQueryBuilder */
        $threadQueryBuilder = $manager->getRepository(Email::class)->createQueryBuilder('entity');
        $threadQueryBuilder->distinct()->select('entity.xThreadId');
        $threadQueryBuilder->addCriteria($criteria);
        $threadQueryBuilder->orderBy('entity.xThreadId'); // critical for paginating on Postgre SQL

        $iterator = new BufferedQueryResultIterator($threadQueryBuilder);
        $iterator->setBufferSize(self::BATCH_SIZE);

        $itemsCount = 0;
        $entities   = [];

        foreach ($iterator as $threadResult) {
            $threadId = $threadResult['xThreadId'];
            /** @var QueryBuilder $queryBuilder */
            $queryBuilder = $manager->getRepository(Email::class)->createQueryBuilder('entity');
            $criteria = new Criteria();
            $criteria->where($criteria->expr()->eq('xThreadId', $threadId));
            $criteria->orderBy(['created' => 'ASC']);
            $queryBuilder->addCriteria($criteria);
            $queryBuilder->setFirstResult(0);
            $emails = $queryBuilder->getQuery()->execute();
            if (count($emails) > 1) {
                $itemsCount++;
                $newThread = new EmailThread();
                $manager->persist($newThread);
                foreach ($emails as $key => $email) {
                    /** @var Email $email */
                    if ($key == 0) {
                        $email->setHead(true);
                    } else {
                        $email->setHead(false);
                    }
                    $email->setThread($newThread);
                    $entities[] = $email;
                }
            } elseif (count($emails) == 1) {
                $email = $emails[0];
                $email->setHead(true);
                $itemsCount++;
                $entities[] = $email;
            }
            if (0 == $itemsCount % self::BATCH_SIZE) {
                $this->saveEntities($manager, $entities);
                $entities = [];
            }
        }

        if ($itemsCount % self::BATCH_SIZE > 0) {
            $this->saveEntities($manager, $entities);
        }
    }

    private function saveEntities(ObjectManager $manager, array $entities)
    {
        foreach ($entities as $email) {
            $manager->persist($email);
        }
        $manager->flush();
        $manager->clear();
    }
}
