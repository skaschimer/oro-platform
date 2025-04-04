<?php

namespace Oro\Component\Action\Action;

use Oro\Component\Action\Exception\InvalidParameterException;
use Oro\Component\ConfigExpression\ContextAccessor;
use Symfony\Component\PropertyAccess\PropertyPathInterface;

/**
 * Execute specified actions on each element of an array.
 */
class Traverse extends AbstractAction
{
    const OPTION_KEY_ARRAY   = 'array';
    const OPTION_KEY_KEY     = 'key';
    const OPTION_KEY_VALUE   = 'value';
    const OPTION_KEY_ACTIONS = 'actions';

    /**
     * @var array
     */
    protected $options;

    /**
     * @var Configurable
     */
    protected $configurableAction;

    public function __construct(ContextAccessor $contextAccessor, Configurable $configurableAction)
    {
        parent::__construct($contextAccessor);

        $this->configurableAction = $configurableAction;
    }

    #[\Override]
    protected function executeAction($context)
    {
        $array  = $this->getArray($context);
        $hasKey = $this->hasKey();

        foreach ($array as $key => $value) {
            if ($hasKey) {
                $this->setKey($context, $key);
            }

            $this->setValue($context, $value);
            $this->configurableAction->execute($context);

            if ($hasKey) {
                $this->setKey($context, null);
            }

            $this->setValue($context, null);
        }
    }

    /**
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */
    #[\Override]
    public function initialize(array $options)
    {
        if (empty($options[self::OPTION_KEY_ARRAY])) {
            throw new InvalidParameterException('Array parameter is required');
        } elseif (!is_array($options[self::OPTION_KEY_ARRAY])
            && !$options[self::OPTION_KEY_ARRAY] instanceof PropertyPathInterface
        ) {
            throw new InvalidParameterException('Array parameter must be either array or valid property definition');
        }

        if (!empty($options[self::OPTION_KEY_KEY])
            && !$options[self::OPTION_KEY_KEY] instanceof PropertyPathInterface
        ) {
            throw new InvalidParameterException('Key must be valid property definition');
        }

        if (empty($options[self::OPTION_KEY_VALUE])) {
            throw new InvalidParameterException('Value parameter is required');
        } elseif (!$options[self::OPTION_KEY_VALUE] instanceof PropertyPathInterface) {
            throw new InvalidParameterException('Value must be valid property definition');
        }

        if (empty($options[self::OPTION_KEY_ACTIONS])) {
            throw new InvalidParameterException('Actions parameter is required');
        } elseif (!is_array($options[self::OPTION_KEY_ACTIONS])) {
            throw new InvalidParameterException('Actions must be array');
        }

        $this->options = $options;
        $this->configurableAction->initialize($options[self::OPTION_KEY_ACTIONS]);
    }

    /**
     * @param mixed $context
     * @return mixed
     */
    protected function getArray($context)
    {
        return $this->contextAccessor->getValue($context, $this->options[self::OPTION_KEY_ARRAY]);
    }

    /**
     * @return bool
     */
    protected function hasKey()
    {
        return !empty($this->options[self::OPTION_KEY_KEY]);
    }

    /**
     * @param mixed $context
     * @param mixed $key
     */
    protected function setKey($context, $key)
    {
        $this->contextAccessor->setValue($context, $this->options[self::OPTION_KEY_KEY], $key);
    }

    /**
     * @param mixed $context
     * @param mixed $value
     */
    protected function setValue($context, $value)
    {
        $this->contextAccessor->setValue($context, $this->options[self::OPTION_KEY_VALUE], $value);
    }
}
