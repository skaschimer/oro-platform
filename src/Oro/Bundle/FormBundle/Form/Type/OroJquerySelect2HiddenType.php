<?php

namespace Oro\Bundle\FormBundle\Form\Type;

use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\EntityConfigBundle\Provider\ConfigProvider;
use Oro\Bundle\FormBundle\Autocomplete\ConverterInterface;
use Oro\Bundle\FormBundle\Autocomplete\SearchRegistry;
use Oro\Bundle\FormBundle\Form\DataTransformer\EntityToIdTransformer;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\DataTransformerInterface;
use Symfony\Component\Form\Exception\InvalidConfigurationException;
use Symfony\Component\Form\Exception\TransformationFailedException;
use Symfony\Component\Form\Exception\UnexpectedTypeException;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * The form type to select an entity.
 */
class OroJquerySelect2HiddenType extends AbstractType
{
    public function __construct(
        protected ManagerRegistry $doctrine,
        protected SearchRegistry $searchRegistry,
        protected ConfigProvider $configProvider
    ) {
    }

    #[\Override]
    public function configureOptions(OptionsResolver $resolver): void
    {
        $defaultConfig = [
            'placeholder'        => 'oro.form.choose_value',
            'allowClear'         => true,
            'minimumInputLength' => 0,
        ];

        $resolver
            ->setDefaults(
                [
                    'placeholder'        => '',
                    'empty_data'         => null,
                    'data_class'         => null,
                    'entity_class'       => null,
                    'configs'            => $defaultConfig,
                    'converter'          => null,
                    'autocomplete_alias' => null,
                    'excluded'           => null,
                    'random_id'          => true,
                    'error_bubbling'     => false,
                ]
            );

        $this->setConverterNormalizer($resolver);
        $this->setConfigsNormalizer($resolver, $defaultConfig);

        $resolver->setNormalizer(
            'entity_class',
            function (Options $options, $entityClass) {
                if (!empty($entityClass)) {
                    return $entityClass;
                }

                if (!empty($options['autocomplete_alias'])) {
                    $searchHandler = $this->searchRegistry->getSearchHandler($options['autocomplete_alias']);

                    return $searchHandler->getEntityName();
                }

                throw new InvalidConfigurationException('The option "entity_class" must be set.');
            }
        )
        ->setNormalizer(
            'transformer',
            function (Options $options, $value) {
                if (!$value && !empty($options['entity_class'])) {
                    $value = $this->createDefaultTransformer($options['entity_class']);
                }

                if (!$value instanceof DataTransformerInterface) {
                    throw new TransformationFailedException(\sprintf(
                        'The option "transformer" must be an instance of "%s".',
                        DataTransformerInterface::class
                    ));
                }

                return $value;
            }
        );
    }

    protected function setConverterNormalizer(OptionsResolver $resolver): void
    {
        $resolver->setNormalizer(
            'converter',
            function (Options $options, $value) {
                if (!$value && !empty($options['autocomplete_alias'])) {
                    $value = $this->searchRegistry->getSearchHandler($options['autocomplete_alias']);
                }

                if (!$value) {
                    throw new InvalidConfigurationException('The option "converter" must be set.');
                }

                if (!$value instanceof ConverterInterface) {
                    throw new UnexpectedTypeException($value, ConverterInterface::class);
                }

                return $value;
            }
        );
    }

    protected function setConfigsNormalizer(OptionsResolver $resolver, array $defaultConfig): void
    {
        $resolver->setNormalizer(
            'configs',
            function (Options $options, $configs) use ($defaultConfig) {
                $result = array_replace_recursive($defaultConfig, $configs);

                if (!empty($options['autocomplete_alias'])) {
                    $autoCompleteAlias = $options['autocomplete_alias'];
                    $result['autocomplete_alias'] = $autoCompleteAlias;
                    if (empty($result['properties'])) {
                        $searchHandler = $this->searchRegistry->getSearchHandler($autoCompleteAlias);
                        $result['properties'] = $searchHandler->getProperties();
                    }
                    if (empty($result['route_name'])) {
                        $result['route_name'] = 'oro_form_autocomplete_search';
                    }
                    if (empty($result['component'])) {
                        $result['component'] = 'autocomplete';
                    }
                }

                if (!\array_key_exists('route_parameters', $result)) {
                    $result['route_parameters'] = [];
                }

                if (empty($result['route_name'])) {
                    throw new InvalidConfigurationException('Option "configs[route_name]" must be set.');
                }

                return $result;
            }
        );
    }

    protected function createDefaultTransformer(string $entityClass): DataTransformerInterface
    {
        return new EntityToIdTransformer($this->doctrine, $entityClass);
    }

    #[\Override]
    public function buildView(FormView $view, FormInterface $form, array $options): void
    {
        parent::buildView($view, $form, $options);

        $vars = [
            'configs'  => $options['configs'],
            'excluded' => (array)$options['excluded']
        ];

        if ($form->getData()) {
            /** @var ConverterInterface $converter */
            $converter = $options['converter'];
            if (isset($options['configs']['multiple']) && $options['configs']['multiple']) {
                $result = [];
                foreach ($form->getData() as $item) {
                    $result[] = $converter->convertItem($item);
                }
            } else {
                $result = $converter->convertItem($form->getData());
            }

            $vars['attr'] = [
                'data-selected-data' => json_encode($result)
            ];
        }

        $view->vars = array_replace_recursive($view->vars, $vars);
    }

    #[\Override]
    public function getParent(): ?string
    {
        return Select2HiddenType::class;
    }

    #[\Override]
    public function getBlockPrefix(): string
    {
        return 'oro_jqueryselect2_hidden';
    }
}
