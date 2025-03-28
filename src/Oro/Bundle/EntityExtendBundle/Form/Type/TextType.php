<?php

namespace Oro\Bundle\EntityExtendBundle\Form\Type;

use Symfony\Component\Form\Extension\Core\Type\TextType as SymfonyTextType;

/**
 * This form type is just a wrapper around standard 'text' form type, but
 * this form type can handle 'require_schema_update' option that
 * allows to mark an entity as "Required Update" in case when a value of
 * an entity config attribute is changed.
 * An example of usage in entity_config.yml:
 * my_attr:
 *      options:
 *          require_schema_update: true
 *      form:
 *          type: oro_entity_extend_text
 */
class TextType extends AbstractConfigType
{
    public function getName()
    {
        return $this->getBlockPrefix();
    }

    #[\Override]
    public function getBlockPrefix(): string
    {
        return 'oro_entity_extend_text';
    }

    #[\Override]
    public function getParent(): ?string
    {
        return SymfonyTextType::class;
    }
}
