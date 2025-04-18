<?php

namespace Oro\Bundle\NavigationBundle\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Oro\Bundle\EntityBundle\EntityProperty\DatesAwareInterface;
use Oro\Bundle\EntityBundle\EntityProperty\DatesAwareTrait;
use Oro\Bundle\NavigationBundle\Model\UrlAwareInterface;
use Oro\Bundle\NavigationBundle\Model\UrlAwareTrait;
use Oro\Bundle\OrganizationBundle\Entity\OrganizationAwareInterface;
use Oro\Bundle\OrganizationBundle\Entity\Ownership\OrganizationAwareTrait;
use Oro\Bundle\UserBundle\Entity\AbstractUser;

/**
* AbstractNavigationItem abstract class
*
*/
#[ORM\MappedSuperclass]
abstract class AbstractNavigationItem implements
    NavigationItemInterface,
    OrganizationAwareInterface,
    UrlAwareInterface,
    DatesAwareInterface
{
    use OrganizationAwareTrait;
    use UrlAwareTrait;
    use DatesAwareTrait;

    #[ORM\Id]
    #[ORM\Column(name: 'id', type: Types::INTEGER)]
    #[ORM\GeneratedValue(strategy: 'AUTO')]
    protected ?int $id = null;

    protected ?AbstractUser $user = null;

    protected ?string $type = null;

    #[ORM\Column(name: 'title', type: Types::TEXT)]
    protected ?string $title = null;

    #[ORM\Column(name: 'position', type: Types::SMALLINT)]
    protected ?int $position = null;

    public function __construct(?array $values = null)
    {
        if (!empty($values)) {
            $this->setValues($values);
        }
    }

    /**
     * @return integer
     */
    #[\Override]
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param string $type
     * @return AbstractNavigationItem
     */
    public function setType($type)
    {
        $this->type = $type;

        return $this;
    }

    /**
     * @return string
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * @param string $title
     * @return AbstractNavigationItem
     */
    public function setTitle($title)
    {
        $this->title = $title;

        return $this;
    }

    /**
     * @return string
     */
    public function getTitle()
    {
        return $this->title;
    }

    /**
     * @param integer $position
     * @return AbstractNavigationItem
     */
    public function setPosition($position)
    {
        $this->position = $position;

        return $this;
    }

    /**
     * @return integer
     */
    public function getPosition()
    {
        return $this->position;
    }

    /**
     * @param AbstractUser|null $user
     * @return AbstractNavigationItem
     */
    public function setUser(?AbstractUser $user = null)
    {
        $this->user = $user;

        return $this;
    }

    /**
     * @return AbstractUser
     */
    #[\Override]
    public function getUser()
    {
        return $this->user;
    }

    /**
     * Set entity properties
     */
    #[\Override]
    public function setValues(array $values)
    {
        if (isset($values['title'])) {
            $this->setTitle($values['title']);
        }
        if (isset($values['url'])) {
            $this->setUrl($values['url']);
        }
        if (isset($values['position'])) {
            $this->setPosition($values['position']);
        }
        if (isset($values['user'])) {
            $this->setUser($values['user']);
        }
        if (isset($values['organization'])) {
            $this->setOrganization($values['organization']);
        }
    }

    /**
     * Pre persist event handler
     */
    #[ORM\PrePersist]
    public function doPrePersist()
    {
        $this->createdAt = new \DateTime('now', new \DateTimeZone('UTC'));
        $this->updatedAt = $this->createdAt;
    }

    /**
     * Pre update event handler
     */
    #[ORM\PreUpdate]
    public function doPreUpdate()
    {
        $this->updatedAt = new \DateTime('now', new \DateTimeZone('UTC'));
    }
}
