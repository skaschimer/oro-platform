<?php

namespace Oro\Bundle\OrganizationBundle\Autocomplete;

use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\EntityExtendBundle\PropertyAccess;
use Oro\Bundle\FormBundle\Autocomplete\SearchHandlerInterface;
use Oro\Bundle\OrganizationBundle\Entity\Repository\OrganizationRepository;
use Oro\Bundle\SecurityBundle\Authentication\TokenAccessorInterface;
use Symfony\Component\PropertyAccess\PropertyAccessor;

/**
 * Search organization handler.
 */
class OrganizationSearchHandler implements SearchHandlerInterface
{
    /** @var string */
    protected $className;

    /** @var array */
    protected $fields;

    /** @var array */
    protected $displayFields;

    /** @var ManagerRegistry */
    protected $managerRegistry;

    /** @var TokenAccessorInterface */
    protected $tokenAccessor;

    /** @var PropertyAccessor */
    protected $accessor;

    /**
     * @param string                 $className
     * @param array                  $fields
     * @param array                  $displayFields
     * @param ManagerRegistry        $managerRegistry
     * @param TokenAccessorInterface $tokenAccessor
     */
    public function __construct(
        $className,
        $fields,
        $displayFields,
        ManagerRegistry $managerRegistry,
        TokenAccessorInterface $tokenAccessor
    ) {
        $this->className = $className;
        $this->fields = $fields;
        $this->displayFields = $displayFields;
        $this->managerRegistry = $managerRegistry;
        $this->tokenAccessor = $tokenAccessor;
        $this->accessor = PropertyAccess::createPropertyAccessor();
    }

    #[\Override]
    public function search($query, $page, $perPage, $searchById = false)
    {
        $user = $this->tokenAccessor->getUser();
        /** @var OrganizationRepository $repository */
        $repository = $this->managerRegistry->getRepository($this->className);
        if (!$searchById) {
            $items = $repository->getEnabledByUserAndName($user, $query);
        } else {
            $items = $repository->getEnabledUserOrganizationById($user, $query);
        }

        $resultsData = [];
        foreach ($items as $organization) {
            $resultsData[] = $this->convertItem($organization);
        }

        return [
            'results' => $resultsData,
            'more' => false
        ];
    }

    #[\Override]
    public function getProperties()
    {
        return $this->displayFields;
    }

    #[\Override]
    public function getEntityName()
    {
        return $this->className;
    }

    #[\Override]
    public function convertItem($item)
    {
        $result = [];
        foreach ($this->fields as $field) {
            $result[$field] = $this->accessor->getValue($item, $field);
        }

        return $result;
    }
}
