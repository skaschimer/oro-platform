<?php

namespace Oro\Bundle\LocaleBundle\Validator\Constraints;

use Oro\Bundle\LocaleBundle\Entity;
use Oro\Bundle\LocaleBundle\Validator\Constraints;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;

/**
 * Localization value validator.
 */
class LocalizationValidator extends ConstraintValidator
{
    /**
     *
     * @param Entity\Localization $localization
     * @param Constraints\Localization $constraint
     */
    #[\Override]
    public function validate($localization, Constraint $constraint)
    {
        if (!$localization instanceof Entity\Localization) {
            throw new UnexpectedTypeException(
                $localization,
                'Oro\Bundle\LocaleBundle\Entity\Localization'
            );
        }
        $parentLocalization = $localization->getParentLocalization();

        if (!$parentLocalization) {
            return;
        }
        if ((null !== $localization->getId() && $localization->getId() === $parentLocalization->getId())
            || $this->localizationExists($parentLocalization, $localization)) {
            $this->context->buildViolation($constraint->messageCircularReference)
                ->atPath('parentLocalization')
                ->addViolation();
        }
    }

    /**
     * @param Entity\Localization $localizationNeedle
     * @param Entity\Localization $localization
     *
     * @return bool
     */
    private function localizationExists(Entity\Localization $localizationNeedle, Entity\Localization $localization)
    {
        $childLocalizations = $localization->getChildLocalizations();
        if ($childLocalizations->contains($localizationNeedle)) {
            return true;
        }

        foreach ($childLocalizations as $childLocalization) {
            if ($this->localizationExists($localizationNeedle, $childLocalization)) {
                return true;
            }
        }

        return false;
    }
}
