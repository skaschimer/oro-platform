<?php

namespace Oro\Bundle\EntityExtendBundle\Validator\Constraints;

use Oro\Bundle\EntityExtendBundle\Model\EnumOption as EnumOptionEntity;
use Oro\Bundle\EntityExtendBundle\Tools\ExtendHelper;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;

/**
 * Checks that the enum value matches alphabetic characters, underscores, hyphens, spaces, and numbers.
 */
class EnumOptionValidator extends ConstraintValidator
{
    #[\Override]
    public function validate($entity, Constraint $constraint)
    {
        if ($entity instanceof EnumOptionEntity) {
            $entity = $entity->toArray();
        }

        if (!is_array($entity)) {
            throw new UnexpectedTypeException(
                $entity,
                'Oro\Bundle\EntityExtendBundle\Model\EnumOption|array'
            );
        }

        if (!empty($entity['id']) || empty($entity['label'])) {
            return;
        }

        $valueId = ExtendHelper::buildEnumInternalId($entity['label'], false);

        if (strlen(trim($valueId)) === 0) {
            $this->context->buildViolation($constraint->message)
                ->atPath('[label]')
                ->setParameters(['{{ value }}' => $entity['label']])
                ->addViolation();
        }
    }
}
