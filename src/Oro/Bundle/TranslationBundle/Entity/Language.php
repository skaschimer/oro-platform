<?php

namespace Oro\Bundle\TranslationBundle\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Oro\Bundle\EntityBundle\EntityProperty\DatesAwareInterface;
use Oro\Bundle\EntityBundle\EntityProperty\DatesAwareTrait;
use Oro\Bundle\EntityConfigBundle\Metadata\Attribute\Config;
use Oro\Bundle\OrganizationBundle\Entity\OrganizationAwareInterface;
use Oro\Bundle\OrganizationBundle\Entity\Ownership\OrganizationAwareTrait;
use Oro\Bundle\TranslationBundle\Entity\Repository\LanguageRepository;

/**
 * Store Language in a database
 */
#[ORM\Entity(repositoryClass: LanguageRepository::class)]
#[ORM\Table(name: 'oro_language')]
#[ORM\HasLifecycleCallbacks]
#[Config(
    defaultValues: [
        'entity' => ['icon' => 'fa-flag'],
        'ownership' => [
            'owner_type' => 'ORGANIZATION',
            'owner_field_name' => 'organization',
            'owner_column_name' => 'organization_id'
        ],
        'security' => ['type' => 'ACL', 'group_name' => '']
    ]
)]
class Language implements DatesAwareInterface, OrganizationAwareInterface
{
    use DatesAwareTrait;
    use OrganizationAwareTrait;

    #[ORM\Id]
    #[ORM\Column(type: Types::INTEGER)]
    #[ORM\GeneratedValue(strategy: 'AUTO')]
    protected ?int $id = null;

    #[ORM\Column(type: Types::STRING, length: 16, unique: true)]
    protected ?string $code = null;

    #[ORM\Column(type: Types::BOOLEAN, options: ['default' => false])]
    protected ?bool $enabled = false;

    #[ORM\Column(name: 'installed_build_date', type: Types::DATETIME_MUTABLE, nullable: true)]
    protected ?\DateTimeInterface $installedBuildDate = null;

    #[ORM\Column(name: 'local_files_language', type: Types::BOOLEAN, options: ['default' => false])]
    private ?bool $localFilesLanguage = false;

    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    public function setCode(string $code): Language
    {
        $this->code = $code;

        return $this;
    }

    /**
     * @return string
     */
    public function getCode()
    {
        return $this->code;
    }

    /**
     * @param \DateTime|null $installedBuildDate
     *
     * @return $this
     */
    public function setInstalledBuildDate(?\DateTime $installedBuildDate = null)
    {
        $this->installedBuildDate = $installedBuildDate;

        return $this;
    }

    /**
     * @return \DateTime
     */
    public function getInstalledBuildDate()
    {
        return $this->installedBuildDate;
    }

    /**
     * @return bool
     */
    public function isEnabled()
    {
        return $this->enabled;
    }

    /**
     * @param bool $enabled
     *
     * @return $this
     */
    public function setEnabled($enabled)
    {
        $this->enabled = (bool)$enabled;

        return $this;
    }

    public function isLocalFilesLanguage(): bool
    {
        return (bool) $this->localFilesLanguage;
    }

    public function setLocalFilesLanguage(bool $localFilesLanguage): Language
    {
        $this->localFilesLanguage = $localFilesLanguage;

        return $this;
    }
}
