<?php

declare(strict_types=1);

namespace Oro\Bundle\CommentBundle\EntityExtend;

use Oro\Bundle\CommentBundle\Entity\Comment;
use Oro\Bundle\EntityExtendBundle\EntityExtend\AbstractAssociationEntityFieldExtension;
use Oro\Bundle\EntityExtendBundle\EntityExtend\EntityFieldProcessTransport;
use Oro\Bundle\EntityExtendBundle\Extend\RelationType;

/**
 * Extended Entity Field Processor Extension for comment associations
 */
class CommentEntityFieldExtension extends AbstractAssociationEntityFieldExtension
{
    #[\Override]
    public function isApplicable(EntityFieldProcessTransport $transport): bool
    {
        return $transport->getClass() === Comment::class;
    }

    #[\Override]
    public function getRelationKind(): ?string
    {
        return null;
    }

    #[\Override]
    public function getRelationType(): string
    {
        return RelationType::MANY_TO_ONE;
    }
}
