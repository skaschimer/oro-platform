<?php

namespace Oro\Bundle\FormBundle\Form\Type;

use Doctrine\ORM\EntityManager;
use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\FormBundle\Form\DataTransformer\ArrayToStringTransformer;
use Oro\Bundle\FormBundle\Form\DataTransformer\EntitiesToIdsTransformer;
use Oro\Bundle\FormBundle\Form\DataTransformer\EntityToIdTransformer;
use Oro\Bundle\FormBundle\Form\EventListener\FixArrayToStringListener;
use Oro\Bundle\FormBundle\Form\Exception\FormException;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\OptionsResolver\OptionsResolver;

class EntityIdentifierType extends AbstractType
{
    const NAME = 'oro_entity_identifier';

    /** @var ManagerRegistry */
    protected $registry;

    public function __construct(ManagerRegistry $registry)
    {
        $this->registry = $registry;
    }

    #[\Override]
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->addViewTransformer($this->createEntitiesToIdsTransformer($options));
        if ($options['multiple']) {
            $builder
                ->addViewTransformer(new ArrayToStringTransformer($options['values_delimiter'], true))
                ->addEventSubscriber(new FixArrayToStringListener($options['values_delimiter']));
        }
    }

    /**
     * @param array $options
     * @return EntitiesToIdsTransformer
     */
    protected function createEntitiesToIdsTransformer(array $options)
    {
        if ($options['multiple']) {
            return new EntitiesToIdsTransformer(
                $options['em'],
                $options['class'],
                $options['property'],
                $options['queryBuilder']
            );
        } else {
            return new EntityToIdTransformer(
                $options['em'],
                $options['class'],
                $options['property'],
                $options['queryBuilder']
            );
        }
    }

    #[\Override]
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(
            array(
                'em'               => null,
                'property'         => null,
                'queryBuilder'     => null,
                'multiple'         => true,
                'values_delimiter' => ','
            )
        )
        ->setAllowedValues('multiple', [true, false]);
        $resolver->setRequired(array('class'));

        $registry = $this->registry;
        $emNormalizer = function (Options $options, $em) use ($registry) {
            if (null !== $em) {
                if ($em instanceof EntityManager) {
                    return $em;
                } elseif (is_string($em)) {
                    $em = $registry->getManager($em);
                } else {
                    throw new FormException(
                        sprintf(
                            'Option "em" should be a string or entity manager object, %s given',
                            is_object($em) ? get_class($em) : gettype($em)
                        )
                    );
                }
            } else {
                $em = $registry->getManagerForClass($options['class']);
            }

            if (null === $em) {
                throw new FormException(
                    sprintf(
                        'Class "%s" is not a managed Doctrine entity. Did you forget to map it?',
                        $options['class']
                    )
                );
            }

            return $em;
        };

        $queryBuilderNormalizer = function (Options $options, $queryBuilder) {
            if (null !== $queryBuilder && !is_callable($queryBuilder)) {
                throw new FormException(
                    sprintf(
                        'Option "queryBuilder" should be a callable, %s given',
                        is_object($queryBuilder) ? get_class($queryBuilder) : gettype($queryBuilder)
                    )
                );
            }

            return $queryBuilder;
        };

        $resolver->setNormalizer('em', $emNormalizer)
            ->setNormalizer('queryBuilder', $queryBuilderNormalizer);
    }

    public function getName()
    {
        return $this->getBlockPrefix();
    }

    #[\Override]
    public function getBlockPrefix(): string
    {
        return self::NAME;
    }

    #[\Override]
    public function getParent(): ?string
    {
        return HiddenType::class;
    }
}
