<?php

namespace Oro\Bundle\ApiBundle\Processor\GetConfig\JsonApi;

use Oro\Bundle\ApiBundle\Config\EntityDefinitionConfig;
use Oro\Bundle\ApiBundle\Processor\GetConfig\ConfigContext;
use Oro\Bundle\ApiBundle\Request\JsonApi\JsonApiDocumentBuilder as JsonApiDoc;
use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;

/**
 * Tries to rename fields if they are equal to reserved words or not conform JSON:API specification.
 * * "type" field is renamed to {short class name} + "Type"
 * * "id" field is renamed to {short class name} + "Id" if it is not the identifier of an entity
 * * the single identifier field is renamed to "id" if it has a different name
 */
class FixFieldNaming implements ProcessorInterface
{
    #[\Override]
    public function process(ContextInterface $context): void
    {
        /** @var ConfigContext $context */

        $definition = $context->getResult();
        if (!$definition->hasFields()) {
            // nothing to fix
            return;
        }

        $entityClass = $context->getClassName();
        // process "type" field
        if ($definition->hasField(JsonApiDoc::TYPE)) {
            $this->renameReservedField($definition, $entityClass, JsonApiDoc::TYPE);
        }
        // process "id" field
        $idFieldNames = $definition->getIdentifierFieldNames();
        $numberOfIdFields = \count($idFieldNames);
        if ($numberOfIdFields === 1) {
            $idFieldName = reset($idFieldNames);
            if (JsonApiDoc::ID !== $idFieldName) {
                if ($definition->hasField(JsonApiDoc::ID)) {
                    $this->renameReservedField($definition, $entityClass, JsonApiDoc::ID);
                }
                $this->renameIdField($definition, $idFieldName, JsonApiDoc::ID);
            }
        } elseif ($numberOfIdFields > 1) {
            if ($definition->hasField(JsonApiDoc::ID)) {
                $this->renameReservedField($definition, $entityClass, JsonApiDoc::ID);
            }
        }
    }

    private function renameReservedField(
        EntityDefinitionConfig $definition,
        ?string $entityClass,
        string $fieldName
    ): void {
        $newFieldName = lcfirst($this->getShortClassName($entityClass)) . ucfirst($fieldName);
        if ($definition->hasField($newFieldName)) {
            throw new \RuntimeException(sprintf(
                'The "%s" reserved word cannot be used as a field name'
                . ' and it cannot be renamed to "%s" because a field with this name already exists.',
                $fieldName,
                $newFieldName
            ));
        }

        // do renaming
        $field = $definition->getField($fieldName);
        if (!$field->hasPropertyPath()) {
            $field->setPropertyPath($fieldName);
        }
        $definition->removeField($fieldName);
        $definition->addField($newFieldName, $field);
        // rename identifier field if needed
        $idFieldNames = $definition->getIdentifierFieldNames();
        $idFieldNameIndex = array_search($fieldName, $idFieldNames, true);
        if (false !== $idFieldNameIndex) {
            $idFieldNames[$idFieldNameIndex] = $newFieldName;
            $definition->setIdentifierFieldNames($idFieldNames);
        }
    }

    private function renameIdField(EntityDefinitionConfig $definition, string $fieldName, string $newFieldName): void
    {
        $field = $definition->getField($fieldName);
        if (null !== $field && !$field->hasPropertyPath()) {
            $definition->removeField($fieldName);
            $field->setPropertyPath($fieldName);
            $definition->addField($newFieldName, $field);
            $definition->setIdentifierFieldNames([$newFieldName]);
        }
    }

    /**
     * Gets the short name of the class, the part without the namespace
     */
    private function getShortClassName(string $className): string
    {
        $lastDelimiter = strrpos($className, '\\');

        return false === $lastDelimiter
            ? $className
            : substr($className, $lastDelimiter + 1);
    }
}
