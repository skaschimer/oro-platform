<?php

namespace Oro\Bundle\AddressBundle\Validator\Constraints;

use Oro\Bundle\AddressBundle\Entity\AbstractAddress;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;

/**
 * The validator for NameOrOrganization constraint.
 */
class NameOrOrganizationValidator extends ConstraintValidator
{
    /**
     * @param AbstractAddress    $entity
     * @param NameOrOrganization $constraint
     */
    #[\Override]
    public function validate($entity, Constraint $constraint)
    {
        if (null === $entity) {
            return;
        }
        if (!$entity instanceof AbstractAddress) {
            throw new UnexpectedTypeException($entity, AbstractAddress::class);
        }

        if ($this->isNameOrOrganizationNotValid($entity)) {
            // organization or (first name and last name) should be filled
            $this->context->buildViolation($constraint->firstNameMessage)
                ->atPath('firstName')
                ->addViolation();
            $this->context->buildViolation($constraint->lastNameMessage)
                ->atPath('lastName')
                ->addViolation();
            $this->context->buildViolation($constraint->organizationMessage)
                ->atPath('organization')
                ->addViolation();
        }
    }

    private function isNameOrOrganizationNotValid(AbstractAddress $entity): bool
    {
        return $this->isEmpty($entity->getOrganization())
            && ($this->isEmpty($entity->getFirstName()) || $this->isEmpty($entity->getLastName()));
    }

    private function isEmpty($value): bool
    {
        return false === $value
            || (empty($value) && '0' != $value);
    }
}
