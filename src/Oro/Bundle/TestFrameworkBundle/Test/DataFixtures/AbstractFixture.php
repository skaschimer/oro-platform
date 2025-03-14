<?php

namespace Oro\Bundle\TestFrameworkBundle\Test\DataFixtures;

use Doctrine\Common\DataFixtures\AbstractFixture as BaseAbstractFixture;
use Doctrine\Common\DataFixtures\ReferenceRepository;
use Doctrine\Persistence\ObjectManager;
use Oro\Bundle\EntityExtendBundle\Entity\EnumOption;
use Oro\Bundle\EntityExtendBundle\PropertyAccess;
use Oro\Bundle\EntityExtendBundle\Tools\ExtendHelper;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\PropertyAccess\PropertyAccessor;

/**
 * This base fixture clas can be used in functional tests to make the code of fixture more lightweight.
 * Convenient way to use several reference repositories in fixtures.
 * Entities can have different object managers that's why we need to use several $referenceRepositories
 */
abstract class AbstractFixture extends BaseAbstractFixture implements ContainerAwareInterface
{
    /**
     * @var ContainerInterface
     */
    protected $container;

    /**
     * @var PropertyAccessor
     */
    protected $propertyAccessor = false;

    /** @var ReferenceRepository[] $referenceRepositories */
    private array $referenceRepositories = [];

    /**
     * Sets $entity object properties with values from $data array using property accessor
     * excluding keys in $excludeProperties.
     *
     * Example:
     *
     * <code>
     *      $this->setEntityPropertyValues(
     *          $account,
     *          $data,
     *          ['reference']
     *      );
     * </code>
     *
     * @param object $entity
     * @param array $data
     * @param array $excludeProperties
     */
    public function setEntityPropertyValues($entity, array $data, array $excludeProperties = array())
    {
        foreach ($data as $property => $value) {
            if (in_array($property, $excludeProperties)) {
                continue;
            }
            $this->getPropertyAccessor()->setValue($entity, $property, $value);
        }
    }

    #[\Override]
    public function setReference($name, $object)
    {
        $this->getReferenceRepositoryByClass(get_class($object))->setReference($name, $object);
    }

    #[\Override]
    public function addReference($name, $object)
    {
        $this->getReferenceRepositoryByClass(get_class($object))->addReference($name, $object);
    }

    #[\Override]
    public function getReference($name, ?string $class = null)
    {
        return $this->getReferenceRepositoryByClass($class)->getReference($name, $class);
    }

    #[\Override]
    public function hasReference($name, ?string $class = null)
    {
        return $this->getReferenceRepositoryByClass($class)->hasReference($name, $class);
    }

    protected function getObjectManagerForClass(?string $className): ObjectManager
    {
        return $this->getReferenceRepositoryByClass($className)->getManager();
    }

    /**
     * @return PropertyAccessor
     */
    protected function getPropertyAccessor()
    {
        if (false === $this->propertyAccessor) {
            $this->propertyAccessor = PropertyAccess::createPropertyAccessor();
        }

        return $this->propertyAccessor;
    }

    /**
     * If $data has key $name then replaces $data[$name] with an array value taken from reference repository.
     *
     * Example:
     *
     * <code>
     *      $this->setReference('foo_reference', $fooEntity);
     *      $this->setReference('bar_reference', $barEntity);
     *      $data['entities'] = ['foo_reference', 'bar_reference'];
     *      $this->resolveCollectionOfReferences($data, 'entities');
     *      // $data['entities'] is an array of [$fooEntity, $barEntity]
     * </code>
     *
     * @param array $data
     * @param string $name
     *
     * @throws \Exception
     */
    protected function resolveCollectionOfReferences(array &$data, $name)
    {
        if (!empty($data[$name])) {
            if (!is_array($data[$name]) && !$data[$name] instanceof \Traversable) {
                throw new \Exception(
                    sprintf(
                        'Traversable data[key: "%s"] expected, got: %s.',
                        $name,
                        is_object($data[$name]) ? get_class($data[$name]) : gettype($data[$name])
                    )
                );
            }

            foreach ($data[$name] as &$reference) {
                $reference = $this->getReference($reference);
            }
        }
    }

    /**
     * If $data has key in $names then replaces $data keys with a respective value taken from reference repository.
     *
     * Example:
     *
     * <code>
     *      $this->setReference('some_reference', $entity);
     *      $data['channel'] = 'some_reference';
     *      $this->resolveReferences($data, ['channel']);
     *      // $data['status'] refers to $entity
     * </code>
     *
     * @param array $data
     * @param string[] $names
     */
    protected function resolveReferences(array &$data, array $names = [])
    {
        foreach ($names as $name) {
            if (!empty($data[$name])) {
                $data[$name] = $this->getReference($data[$name]);
            }
        }
    }

    /**
     * If $data has key $name then replaces $data[$name] with an entity of respective value of enum with code $enumCode.
     *
     * Example:
     *
     * <code>
     *      $data['status'] = 'accepted';
     *      $this->resolveEnum($data, 'status', 'ce_attendee_status');
     *      // $data['status'] refers to entity of enum "ce_attendee_status" with id="accepted"
     * </code>
     *
     * @param array $data
     * @param string $name
     * @param string $enumCode
     *
     * @see \Oro\Bundle\EntityExtendBundle\Entity\EnumOptionInterface
     */
    protected function resolveEnum(array &$data, $name, $enumCode)
    {
        if (!empty($data[$name])) {
            $enumValueRepository = $this->container->get('doctrine')->getManager()->getRepository(EnumOption::class);
            $data[$name] = $enumValueRepository->find(ExtendHelper::buildEnumOptionId($enumCode, $data[$name]));
        }
    }

    /**
     * If $data has key $name then replaces $data[$name] with an entity $entityName found by $criteria.
     *
     * Example:
     *
     * <code>
     *      $data['channel'] = 'some_value';
     *      $this->resolveEntity($data, 'channel', Channel::class, ['name' => '%value%'])
     *      // $data['channel'] refers to entity of Channel with name="some_value"
     * </code>
     *
     * @param array $data
     * @param string $name
     * @param string $entityName
     * @param array $criteria
     */
    protected function resolveEntity(array &$data, $name, $entityName, $criteria)
    {
        if (!empty($data[$name])) {
            $entityRepository = $this->container->get('doctrine')->getManager()->getRepository($entityName);

            foreach ($criteria as $key => $value) {
                if ($value === '%value%') {
                    $criteria[$key] = $data[$name];
                }
            }

            $data[$name] = $entityRepository->findOneBy($criteria);
        }
    }

    /**
     * @throws \Exception
     */
    private function getReferenceRepositoryByClass(?string $className): ReferenceRepository
    {
        if (is_null($className)) {
            return $this->referenceRepository;
        }

        foreach ($this->referenceRepositories as $referenceRepository) {
            $manager = $referenceRepository->getManager();

            if ($manager->getMetadataFactory()->hasMetadataFor($className)) {
                $className = $manager->getClassMetadata($className)->getName();
            }

            if (!$manager->getMetadataFactory()->isTransient($className)) {
                return $referenceRepository;
            }
        }

        throw new \Exception(
            sprintf(
                "There is no object manager for the %s class or ReferenceRepository is not initialized",
                $className
            )
        );
    }

    #[\Override]
    public function setContainer(?ContainerInterface $container = null)
    {
        $this->container = $container;
    }

    /**
     * Fills fixture with all initialized ReferenceRepositories for correct
     * object managers and reference repositories usage
     *
     * @param ReferenceRepository[] $referenceRepositories
     */
    public function setReferenceRepositories(array $referenceRepositories): void
    {
        $this->referenceRepositories = $referenceRepositories;
    }
}
