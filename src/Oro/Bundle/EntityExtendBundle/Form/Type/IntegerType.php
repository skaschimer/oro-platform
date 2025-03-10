<?php

namespace Oro\Bundle\EntityExtendBundle\Form\Type;

use Symfony\Component\Form\Extension\Core\Type\IntegerType as SymfonyIntegerType;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * This form type is just a wrapper around standard 'integer' form type, but
 * this form type can handle 'require_schema_update' option that
 * allows to mark an entity as "Required Update" in case when a value of
 * an entity config attribute is changed.
 * An example of usage in entity_config.yml:
 * my_attr:
 *      options:
 *          require_schema_update: true
 *      form:
 *          type: oro_entity_extend_integer
 */
class IntegerType extends AbstractConfigType
{
    #[\Override]
    public function configureOptions(OptionsResolver $resolver)
    {
        parent::configureOptions($resolver);

        $resolver->setDefaults(
            [
                'schema_update_required' => static fn ($newVal, $oldVal) => (int)$newVal !== (int)$oldVal,
            ]
        );
    }

    public function getName()
    {
        return $this->getBlockPrefix();
    }

    #[\Override]
    public function getBlockPrefix(): string
    {
        return 'oro_entity_extend_integer';
    }

    #[\Override]
    public function getParent(): ?string
    {
        return SymfonyIntegerType::class;
    }
}
