<?php

namespace Oro\Bundle\TestFrameworkBundle\Behat\Element;

use Behat\Mink\Element\NodeElement;
use Behat\Mink\Session;

/**
 * The implementation of element that represents a group of checkboxes or radio buttons.
 */
class GroupChoiceField extends Element
{
    /**
     * @var array
     */
    protected $choices;

    public function __construct(Session $session, OroElementFactory $elementFactory, $selector = ['xpath' => '//'])
    {
        parent::__construct($session, $elementFactory, $selector);

        $this->choices = $this->findChoices();
    }

    #[\Override]
    public function setValue($values)
    {
        $values = true === is_array($values) ? $values : [$values];

        foreach ($this->choices as $label => $element) {
            if ('checkbox' === strtolower($element->getAttribute('type'))) {
                $element->uncheck();
            }
        }

        foreach ($values as $label) {
            $choice = $this->getChoice($label);

            if ('checkbox' === strtolower($choice->getAttribute('type'))) {
                $choice->check();
            } else {
                $choice->click();
            }
        }
    }

    /**
     * Return array labels that was checked
     *
     * @return array
     */
    #[\Override]
    public function getValue()
    {
        return array_keys(array_filter($this->findChoices(), function (NodeElement $element) {
            return $element->isChecked();
        }));
    }

    /**
     * Find all checkboxes or radio buttons and return them with label as array like:
     * array('Label Name' => NodeElement)
     * @return NodeElement[]
     */
    protected function findChoices()
    {
        $radioElements = $this->findAll('css', 'input[type=radio]');
        $checkboxElements = $this->findAll('css', 'input[type=checkbox]');

        $elements = array_merge($radioElements, $checkboxElements);

        self::assertNotCount(0, $elements, 'Not found any checkboxes or radio buttons in GroupChoiceField');
        $choices = [];

        /** @var NodeElement $element */
        foreach ($elements as $element) {
            $label = $this->find('css', sprintf('label[for=%s]', $element->getAttribute('id')));
            self::assertNotNull($label, 'Can\'t find label for checkbox or radio button');

            $choices[$label->getText()] = $element;
        }

        return $choices;
    }

    /**
     * @param string $label
     * @return NodeElement
     */
    protected function getChoice($label)
    {
        $choices = array_intersect_key(
            $this->choices,
            array_flip(preg_grep(sprintf('/%s/i', preg_quote($label)), array_keys($this->choices)))
        );
        if (count($choices) > 1 && isset($choices[$label])) {
            $choices = [$label => $choices[$label]];
        }
        self::assertCount(1, $choices, sprintf('Too many results for "%s" label', $label));

        $choice = array_shift($choices);
        self::assertNotNull($choice, sprintf('Can\'t find checkbox or radio button with "%s" label', $label));

        return $choice;
    }
}
