<?php

namespace Oro\Bundle\UserBundle\Form\Extension\JsValidation;

use Oro\Bundle\FormBundle\Form\Extension\JsValidation\ConstraintsProviderInterface;
use Oro\Bundle\UserBundle\Provider\PasswordComplexityConfigProvider;
use Oro\Bundle\UserBundle\Validator\Constraints\PasswordComplexity;
use Symfony\Component\Form\AbstractTypeExtension;
use Symfony\Component\Form\Extension\Core\Type\RepeatedType;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;

/**
 * Extension of 'repeated' type to configure PasswordComplexity constraint
 * before its properties are handled by jsValidationExtension
 */
class PasswordComplexityExtension extends AbstractTypeExtension
{
    /** @var ConstraintsProviderInterface */
    protected $constraintsProvider;

    /** @var PasswordComplexityConfigProvider */
    protected $configProvider;

    public function __construct(
        ConstraintsProviderInterface $constraintsProvider,
        PasswordComplexityConfigProvider $configProvider
    ) {
        $this->constraintsProvider = $constraintsProvider;
        $this->configProvider = $configProvider;
    }

    #[\Override]
    public function buildView(FormView $view, FormInterface $form, array $options)
    {
        $constraints = $this->constraintsProvider->getFormConstraints($form);

        foreach ($constraints as $constraint) {
            if ($constraint instanceof PasswordComplexity) {
                $this->configureConstraint($constraint);
            }
        }
    }

    /**
     * Sets the properties of the constraint to the system configured Password Complexity Rules
     */
    protected function configureConstraint(PasswordComplexity $constraint)
    {
        $constraint->requireMinLength = $this->configProvider->getMinLength();
        $constraint->requireNumbers = $this->configProvider->getNumbers();
        $constraint->requireSpecialCharacter = $this->configProvider->getSpecialChars();
        $constraint->requireLowerCase = $this->configProvider->getLowerCase();
        $constraint->requireUpperCase = $this->configProvider->getUpperCase();
    }

    /**
     * Returns the name of the type being extended.
     *
     */
    #[\Override]
    public static function getExtendedTypes(): iterable
    {
        return [RepeatedType::class];
    }
}
