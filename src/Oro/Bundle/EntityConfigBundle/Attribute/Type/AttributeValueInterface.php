<?php

namespace Oro\Bundle\EntityConfigBundle\Attribute\Type;

use Oro\Bundle\EntityConfigBundle\Entity\FieldConfigModel;
use Oro\Bundle\LocaleBundle\Entity\Localization;

/**
 * Entity attribute that can be used to search data, filter and sort result set
 */
interface AttributeValueInterface
{
    /**
     * @param FieldConfigModel $attribute
     * @param mixed $originalValue
     * @param Localization|null $localization
     *
     * @return string
     */
    public function getSearchableValue(FieldConfigModel $attribute, $originalValue, ?Localization $localization = null);

    /**
     * @param FieldConfigModel $attribute
     * @param mixed $originalValue
     * @param Localization|null $localization
     *
     * @return string|array
     */
    public function getFilterableValue(FieldConfigModel $attribute, $originalValue, ?Localization $localization = null);

    /**
     * @param FieldConfigModel $attribute
     * @param mixed $originalValue
     * @param Localization|null $localization
     *
     * @return string
     */
    public function getSortableValue(FieldConfigModel $attribute, $originalValue, ?Localization $localization = null);
}
