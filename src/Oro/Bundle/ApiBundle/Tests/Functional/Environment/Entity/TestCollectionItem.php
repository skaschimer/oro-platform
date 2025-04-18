<?php

namespace Oro\Bundle\ApiBundle\Tests\Functional\Environment\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\Common\Collections\Criteria;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Oro\Bundle\TestFrameworkBundle\Entity\TestFrameworkEntityInterface;

/**
 * @SuppressWarnings(PHPMD.TooManyFields)
 * @SuppressWarnings(PHPMD.TooManyMethods)
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 * @SuppressWarnings(PHPMD.ExcessivePublicCount)
 * @SuppressWarnings(PHPMD.ExcessiveClassComplexity)
 */
#[ORM\Entity]
#[ORM\Table(name: 'test_api_coll_item')]
class TestCollectionItem implements TestFrameworkEntityInterface
{
    #[ORM\Column(type: Types::INTEGER)]
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'AUTO')]
    private ?int $id = null;

    #[ORM\Column(name: 'name', type: Types::STRING, length: 255, nullable: true)]
    private ?string $name = null;

    #[ORM\ManyToOne(targetEntity: TestCollection::class, inversedBy: 'withOrphanRemovalItems')]
    #[ORM\JoinColumn(name: 'p_or_id', referencedColumnName: 'id', onDelete: 'CASCADE')]
    private ?TestCollection $withOrphanRemovalParent = null;

    #[ORM\ManyToOne(targetEntity: TestCollection::class, inversedBy: 'withoutOrphanRemovalItems')]
    #[ORM\JoinColumn(name: 'p_nor_id', referencedColumnName: 'id', onDelete: 'CASCADE')]
    private ?TestCollection $withoutOrphanRemovalParent = null;

    #[ORM\ManyToOne(targetEntity: TestCollection::class, inversedBy: 'lazyWithOrphanRemovalItems')]
    #[ORM\JoinColumn(name: 'p_l_or_id', referencedColumnName: 'id', onDelete: 'CASCADE')]
    private ?TestCollection $lazyWithOrphanRemovalParent = null;

    #[ORM\ManyToOne(targetEntity: TestCollection::class, inversedBy: 'lazyWithoutOrphanRemovalItems')]
    #[ORM\JoinColumn(name: 'p_l_nor_id', referencedColumnName: 'id', onDelete: 'CASCADE')]
    private ?TestCollection $lazyWithoutOrphanRemovalParent = null;

    #[ORM\ManyToOne(targetEntity: TestCollection::class, inversedBy: 'extraLazyWithOrphanRemovalItems')]
    #[ORM\JoinColumn(name: 'p_el_or_id', referencedColumnName: 'id', onDelete: 'CASCADE')]
    private ?TestCollection $extraLazyWithOrphanRemovalParent = null;

    #[ORM\ManyToOne(targetEntity: TestCollection::class, inversedBy: 'extraLazyWithoutOrphanRemovalItems')]
    #[ORM\JoinColumn(name: 'p_el_nor_id', referencedColumnName: 'id', onDelete: 'CASCADE')]
    private ?TestCollection $extraLazyWithoutOrphanRemovalParent = null;

    /**
     * @var Collection<int, TestCollection>
     */
    #[ORM\ManyToMany(targetEntity: TestCollection::class, mappedBy: 'manyToManyWithOrphanRemovalItems')]
    #[ORM\OrderBy(['id' => Criteria::ASC])]
    private ?Collection $manyToManyWithOrphanRemovalParents = null;

    /**
     * @var Collection<int, TestCollection>
     */
    #[ORM\ManyToMany(targetEntity: TestCollection::class, mappedBy: 'manyToManyWithoutOrphanRemovalItems')]
    #[ORM\OrderBy(['id' => Criteria::ASC])]
    private ?Collection $manyToManyWithoutOrphanRemovalParents = null;

    /**
     * @var Collection<int, TestCollection>
     */
    #[ORM\ManyToMany(targetEntity: TestCollection::class, mappedBy: 'manyToManyLazyWithOrphanRemovalItems')]
    #[ORM\OrderBy(['id' => Criteria::ASC])]
    private ?Collection $manyToManyLazyWithOrphanRemovalParents = null;

    /**
     * @var Collection<int, TestCollection>
     */
    #[ORM\ManyToMany(targetEntity: TestCollection::class, mappedBy: 'manyToManyLazyWithoutOrphanRemovalItems')]
    #[ORM\OrderBy(['id' => Criteria::ASC])]
    private ?Collection $manyToManyLazyWithoutOrphanRemovalParents = null;

    /**
     * @var Collection<int, TestCollection>
     */
    #[ORM\ManyToMany(targetEntity: TestCollection::class, mappedBy: 'manyToManyExtraLazyWithOrphanRemovalItems')]
    #[ORM\OrderBy(['id' => Criteria::ASC])]
    private ?Collection $manyToManyExtraLazyWithOrphanRemovalParents = null;

    /**
     * @var Collection<int, TestCollection>
     */
    #[ORM\ManyToMany(targetEntity: TestCollection::class, mappedBy: 'manyToManyExtraLazyWithoutOrphanRemovalItems')]
    #[ORM\OrderBy(['id' => Criteria::ASC])]
    private ?Collection $manyToManyExtraLazyWithoutOrphanRemovalParents = null;

    /**
     * @var Collection<int, TestCollection>
     */
    #[ORM\ManyToMany(targetEntity: TestCollection::class, inversedBy: 'inverseManyToManyWithOrphanRemovalItems')]
    #[ORM\JoinTable(name: 'test_api_coll_imtm_or')]
    #[ORM\JoinColumn(name: 'item_id', referencedColumnName: 'id', onDelete: 'CASCADE')]
    #[ORM\InverseJoinColumn(name: 'parent_id', referencedColumnName: 'id', onDelete: 'CASCADE')]
    #[ORM\OrderBy(['id' => Criteria::ASC])]
    private ?Collection $inverseManyToManyWithOrphanRemovalParents = null;

    /**
     * @var Collection<int, TestCollection>
     */
    #[ORM\ManyToMany(targetEntity: TestCollection::class, inversedBy: 'inverseManyToManyWithoutOrphanRemovalItems')]
    #[ORM\JoinTable(name: 'test_api_coll_imtm_nor')]
    #[ORM\JoinColumn(name: 'item_id', referencedColumnName: 'id', onDelete: 'CASCADE')]
    #[ORM\InverseJoinColumn(name: 'parent_id', referencedColumnName: 'id', onDelete: 'CASCADE')]
    #[ORM\OrderBy(['id' => Criteria::ASC])]
    private ?Collection $inverseManyToManyWithoutOrphanRemovalParents = null;

    /**
     * @var Collection<int, TestCollection>
     */
    #[ORM\ManyToMany(targetEntity: TestCollection::class, inversedBy: 'inverseManyToManyLazyWithOrphanRemovalItems')]
    #[ORM\JoinTable(name: 'test_api_coll_imtm_l_or')]
    #[ORM\JoinColumn(name: 'item_id', referencedColumnName: 'id', onDelete: 'CASCADE')]
    #[ORM\InverseJoinColumn(name: 'parent_id', referencedColumnName: 'id', onDelete: 'CASCADE')]
    #[ORM\OrderBy(['id' => Criteria::ASC])]
    private ?Collection $inverseManyToManyLazyWithOrphanRemovalParents = null;

    /**
     * @var Collection<int, TestCollection>
     */
    #[ORM\ManyToMany(targetEntity: TestCollection::class, inversedBy: 'inverseManyToManyLazyWithoutOrphanRemovalItems')]
    #[ORM\JoinTable(name: 'test_api_coll_imtm_l_nor')]
    #[ORM\JoinColumn(name: 'item_id', referencedColumnName: 'id', onDelete: 'CASCADE')]
    #[ORM\InverseJoinColumn(name: 'parent_id', referencedColumnName: 'id', onDelete: 'CASCADE')]
    #[ORM\OrderBy(['id' => Criteria::ASC])]
    private ?Collection $inverseManyToManyLazyWithoutOrphanRemovalParents = null;

    /**
     * @var Collection<int, TestCollection>
     */
    #[ORM\ManyToMany(
        targetEntity: TestCollection::class,
        inversedBy: 'inverseManyToManyExtraLazyWithOrphanRemovalItems'
    )]
    #[ORM\JoinTable(name: 'test_api_coll_imtm_el_or')]
    #[ORM\JoinColumn(name: 'item_id', referencedColumnName: 'id', onDelete: 'CASCADE')]
    #[ORM\InverseJoinColumn(name: 'parent_id', referencedColumnName: 'id', onDelete: 'CASCADE')]
    #[ORM\OrderBy(['id' => Criteria::ASC])]
    private ?Collection $inverseManyToManyExtraLazyWithOrphanRemovalParents = null;

    /**
     * @var Collection<int, TestCollection>
     */
    #[ORM\ManyToMany(
        targetEntity: TestCollection::class,
        inversedBy: 'inverseManyToManyExtraLazyWithoutOrphanRemovalItems'
    )]
    #[ORM\JoinTable(name: 'test_api_coll_imtm_el_nor')]
    #[ORM\JoinColumn(name: 'item_id', referencedColumnName: 'id', onDelete: 'CASCADE')]
    #[ORM\InverseJoinColumn(name: 'parent_id', referencedColumnName: 'id', onDelete: 'CASCADE')]
    #[ORM\OrderBy(['id' => Criteria::ASC])]
    private ?Collection $inverseManyToManyExtraLazyWithoutOrphanRemovalParents = null;

    public function __construct()
    {
        $this->manyToManyWithOrphanRemovalParents = new ArrayCollection();
        $this->manyToManyWithoutOrphanRemovalParents = new ArrayCollection();
        $this->manyToManyLazyWithOrphanRemovalParents = new ArrayCollection();
        $this->manyToManyLazyWithoutOrphanRemovalParents = new ArrayCollection();
        $this->manyToManyExtraLazyWithOrphanRemovalParents = new ArrayCollection();
        $this->manyToManyExtraLazyWithoutOrphanRemovalParents = new ArrayCollection();
        $this->inverseManyToManyWithOrphanRemovalParents = new ArrayCollection();
        $this->inverseManyToManyWithoutOrphanRemovalParents = new ArrayCollection();
        $this->inverseManyToManyLazyWithOrphanRemovalParents = new ArrayCollection();
        $this->inverseManyToManyLazyWithoutOrphanRemovalParents = new ArrayCollection();
        $this->inverseManyToManyExtraLazyWithOrphanRemovalParents = new ArrayCollection();
        $this->inverseManyToManyExtraLazyWithoutOrphanRemovalParents = new ArrayCollection();
    }

    /**
     * @return int|null
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @return string|null
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @param string|null $name
     */
    public function setName($name)
    {
        $this->name = $name;
    }

    /**
     * @return TestCollection|null
     */
    public function getWithOrphanRemovalParent()
    {
        return $this->withOrphanRemovalParent;
    }

    public function setWithOrphanRemovalParent(?TestCollection $parent = null)
    {
        $this->withOrphanRemovalParent = $parent;
    }

    /**
     * @return TestCollection|null
     */
    public function getWithoutOrphanRemovalParent()
    {
        return $this->withoutOrphanRemovalParent;
    }

    public function setWithoutOrphanRemovalParent(?TestCollection $parent = null)
    {
        $this->withoutOrphanRemovalParent = $parent;
    }

    /**
     * @return TestCollection|null
     */
    public function getLazyWithOrphanRemovalParent()
    {
        return $this->lazyWithOrphanRemovalParent;
    }

    public function setLazyWithOrphanRemovalParent(?TestCollection $parent = null)
    {
        $this->lazyWithOrphanRemovalParent = $parent;
    }

    /**
     * @return TestCollection|null
     */
    public function getLazyWithoutOrphanRemovalParent()
    {
        return $this->lazyWithoutOrphanRemovalParent;
    }

    public function setLazyWithoutOrphanRemovalParent(?TestCollection $parent = null)
    {
        $this->lazyWithoutOrphanRemovalParent = $parent;
    }

    /**
     * @return TestCollection|null
     */
    public function getExtraLazyWithOrphanRemovalParent()
    {
        return $this->extraLazyWithOrphanRemovalParent;
    }

    public function setExtraLazyWithOrphanRemovalParent(?TestCollection $parent = null)
    {
        $this->extraLazyWithOrphanRemovalParent = $parent;
    }

    /**
     * @return TestCollection|null
     */
    public function getExtraLazyWithoutOrphanRemovalParent()
    {
        return $this->extraLazyWithoutOrphanRemovalParent;
    }

    public function setExtraLazyWithoutOrphanRemovalParent(?TestCollection $parent = null)
    {
        $this->extraLazyWithoutOrphanRemovalParent = $parent;
    }

    /**
     * @return Collection|TestCollection[]
     */
    public function getManyToManyWithOrphanRemovalParents()
    {
        return $this->manyToManyWithOrphanRemovalParents;
    }

    /**
     * @param Collection|TestCollection[] $parents
     */
    public function setManyToManyWithOrphanRemovalParents(Collection $parents)
    {
        $this->manyToManyWithOrphanRemovalParents = $parents;
    }

    public function addManyToManyWithOrphanRemovalParent(TestCollection $parent)
    {
        if (!$this->getManyToManyWithOrphanRemovalParents()->contains($parent)) {
            $this->getManyToManyWithOrphanRemovalParents()->add($parent);
            $parent->addManyToManyWithOrphanRemovalItem($this);
        }
    }

    public function removeManyToManyWithOrphanRemovalParent(TestCollection $parent)
    {
        if ($this->getManyToManyWithOrphanRemovalParents()->contains($parent)) {
            $this->getManyToManyWithOrphanRemovalParents()->removeElement($parent);
            $parent->removeManyToManyWithOrphanRemovalItem($this);
        }
    }

    /**
     * @return Collection|TestCollection[]
     */
    public function getManyToManyWithoutOrphanRemovalParents()
    {
        return $this->manyToManyWithoutOrphanRemovalParents;
    }

    /**
     * @param Collection|TestCollection[] $parents
     */
    public function setManyToManyWithoutOrphanRemovalParents(Collection $parents)
    {
        $this->manyToManyWithoutOrphanRemovalParents = $parents;
    }

    public function addManyToManyWithoutOrphanRemovalParent(TestCollection $parent)
    {
        if (!$this->getManyToManyWithoutOrphanRemovalParents()->contains($parent)) {
            $this->getManyToManyWithoutOrphanRemovalParents()->add($parent);
            $parent->addManyToManyWithoutOrphanRemovalItem($this);
        }
    }

    public function removeManyToManyWithoutOrphanRemovalParent(TestCollection $parent)
    {
        if ($this->getManyToManyWithoutOrphanRemovalParents()->contains($parent)) {
            $this->getManyToManyWithoutOrphanRemovalParents()->removeElement($parent);
            $parent->removeManyToManyWithoutOrphanRemovalItem($this);
        }
    }

    /**
     * @return Collection|TestCollection[]
     */
    public function getManyToManyLazyWithOrphanRemovalParents()
    {
        return $this->manyToManyLazyWithOrphanRemovalParents;
    }

    /**
     * @param Collection|TestCollection[] $parents
     */
    public function setManyToManyLazyWithOrphanRemovalParents(Collection $parents)
    {
        $this->manyToManyLazyWithOrphanRemovalParents = $parents;
    }

    public function addManyToManyLazyWithOrphanRemovalParent(TestCollection $parent)
    {
        if (!$this->getManyToManyLazyWithOrphanRemovalParents()->contains($parent)) {
            $this->getManyToManyLazyWithOrphanRemovalParents()->add($parent);
            $parent->addManyToManyLazyWithOrphanRemovalItem($this);
        }
    }

    public function removeManyToManyLazyWithOrphanRemovalParent(TestCollection $parent)
    {
        if ($this->getManyToManyLazyWithOrphanRemovalParents()->contains($parent)) {
            $this->getManyToManyLazyWithOrphanRemovalParents()->removeElement($parent);
            $parent->removeManyToManyLazyWithOrphanRemovalItem($this);
        }
    }

    /**
     * @return Collection|TestCollection[]
     */
    public function getManyToManyLazyWithoutOrphanRemovalParents()
    {
        return $this->manyToManyLazyWithoutOrphanRemovalParents;
    }

    /**
     * @param Collection|TestCollection[] $parents
     */
    public function setManyToManyLazyWithoutOrphanRemovalParents(Collection $parents)
    {
        $this->manyToManyLazyWithoutOrphanRemovalParents = $parents;
    }

    public function addManyToManyLazyWithoutOrphanRemovalParent(TestCollection $parent)
    {
        if (!$this->getManyToManyLazyWithoutOrphanRemovalParents()->contains($parent)) {
            $this->getManyToManyLazyWithoutOrphanRemovalParents()->add($parent);
            $parent->addManyToManyLazyWithoutOrphanRemovalItem($this);
        }
    }

    public function removeManyToManyLazyWithoutOrphanRemovalParent(TestCollection $parent)
    {
        if ($this->getManyToManyLazyWithoutOrphanRemovalParents()->contains($parent)) {
            $this->getManyToManyLazyWithoutOrphanRemovalParents()->removeElement($parent);
            $parent->removeManyToManyLazyWithoutOrphanRemovalItem($this);
        }
    }

    /**
     * @return Collection|TestCollection[]
     */
    public function getManyToManyExtraLazyWithOrphanRemovalParents()
    {
        return $this->manyToManyExtraLazyWithOrphanRemovalParents;
    }

    /**
     * @param Collection|TestCollection[] $parents
     */
    public function setManyToManyExtraLazyWithOrphanRemovalParents(Collection $parents)
    {
        $this->manyToManyExtraLazyWithOrphanRemovalParents = $parents;
    }

    public function addManyToManyExtraLazyWithOrphanRemovalParent(TestCollection $parent)
    {
        if (!$this->getManyToManyExtraLazyWithOrphanRemovalParents()->contains($parent)) {
            $this->getManyToManyExtraLazyWithOrphanRemovalParents()->add($parent);
            $parent->addManyToManyExtraLazyWithOrphanRemovalItem($this);
        }
    }

    public function removeManyToManyExtraLazyWithOrphanRemovalParent(TestCollection $parent)
    {
        if ($this->getManyToManyExtraLazyWithOrphanRemovalParents()->contains($parent)) {
            $this->getManyToManyExtraLazyWithOrphanRemovalParents()->removeElement($parent);
            $parent->removeManyToManyExtraLazyWithOrphanRemovalItem($this);
        }
    }

    /**
     * @return Collection|TestCollection[]
     */
    public function getManyToManyExtraLazyWithoutOrphanRemovalParents()
    {
        return $this->manyToManyExtraLazyWithoutOrphanRemovalParents;
    }

    /**
     * @param Collection|TestCollection[] $parents
     */
    public function setManyToManyExtraLazyWithoutOrphanRemovalParents(Collection $parents)
    {
        $this->manyToManyExtraLazyWithoutOrphanRemovalParents = $parents;
    }

    public function addManyToManyExtraLazyWithoutOrphanRemovalParent(TestCollection $parent)
    {
        if (!$this->getManyToManyExtraLazyWithoutOrphanRemovalParents()->contains($parent)) {
            $this->getManyToManyExtraLazyWithoutOrphanRemovalParents()->add($parent);
            $parent->addManyToManyExtraLazyWithoutOrphanRemovalItem($this);
        }
    }

    public function removeManyToManyExtraLazyWithoutOrphanRemovalParent(TestCollection $parent)
    {
        if ($this->getManyToManyExtraLazyWithoutOrphanRemovalParents()->contains($parent)) {
            $this->getManyToManyExtraLazyWithoutOrphanRemovalParents()->removeElement($parent);
            $parent->removeManyToManyExtraLazyWithoutOrphanRemovalItem($this);
        }
    }

    /**
     * @return Collection|TestCollection[]
     */
    public function getInverseManyToManyWithOrphanRemovalParents()
    {
        return $this->inverseManyToManyWithOrphanRemovalParents;
    }

    /**
     * @param Collection|TestCollection[] $parents
     */
    public function setInverseManyToManyWithOrphanRemovalParents(Collection $parents)
    {
        $this->inverseManyToManyWithOrphanRemovalParents = $parents;
    }

    public function addInverseManyToManyWithOrphanRemovalParent(TestCollection $parent)
    {
        if (!$this->getInverseManyToManyWithOrphanRemovalParents()->contains($parent)) {
            $this->getInverseManyToManyWithOrphanRemovalParents()->add($parent);
            $parent->addInverseManyToManyWithOrphanRemovalItem($this);
        }
    }

    public function removeInverseManyToManyWithOrphanRemovalParent(TestCollection $parent)
    {
        if ($this->getInverseManyToManyWithOrphanRemovalParents()->contains($parent)) {
            $this->getInverseManyToManyWithOrphanRemovalParents()->removeElement($parent);
            $parent->removeInverseManyToManyWithOrphanRemovalItem($this);
        }
    }

    /**
     * @return Collection|TestCollection[]
     */
    public function getInverseManyToManyWithoutOrphanRemovalParents()
    {
        return $this->inverseManyToManyWithoutOrphanRemovalParents;
    }

    /**
     * @param Collection|TestCollection[] $parents
     */
    public function setInverseManyToManyWithoutOrphanRemovalParents(Collection $parents)
    {
        $this->inverseManyToManyWithoutOrphanRemovalParents = $parents;
    }

    public function addInverseManyToManyWithoutOrphanRemovalParent(TestCollection $parent)
    {
        if (!$this->getInverseManyToManyWithoutOrphanRemovalParents()->contains($parent)) {
            $this->getInverseManyToManyWithoutOrphanRemovalParents()->add($parent);
            $parent->addInverseManyToManyWithoutOrphanRemovalItem($this);
        }
    }

    public function removeInverseManyToManyWithoutOrphanRemovalParent(TestCollection $parent)
    {
        if ($this->getInverseManyToManyWithoutOrphanRemovalParents()->contains($parent)) {
            $this->getInverseManyToManyWithoutOrphanRemovalParents()->removeElement($parent);
            $parent->removeInverseManyToManyWithoutOrphanRemovalItem($this);
        }
    }

    /**
     * @return Collection|TestCollection[]
     */
    public function getInverseManyToManyLazyWithOrphanRemovalParents()
    {
        return $this->inverseManyToManyLazyWithOrphanRemovalParents;
    }

    /**
     * @param Collection|TestCollection[] $parents
     */
    public function setInverseManyToManyLazyWithOrphanRemovalParents(Collection $parents)
    {
        $this->inverseManyToManyLazyWithOrphanRemovalParents = $parents;
    }

    public function addInverseManyToManyLazyWithOrphanRemovalParent(TestCollection $parent)
    {
        if (!$this->getInverseManyToManyLazyWithOrphanRemovalParents()->contains($parent)) {
            $this->getInverseManyToManyLazyWithOrphanRemovalParents()->add($parent);
            $parent->addInverseManyToManyLazyWithOrphanRemovalItem($this);
        }
    }

    public function removeInverseManyToManyLazyWithOrphanRemovalParent(TestCollection $parent)
    {
        if ($this->getInverseManyToManyLazyWithOrphanRemovalParents()->contains($parent)) {
            $this->getInverseManyToManyLazyWithOrphanRemovalParents()->removeElement($parent);
            $parent->removeInverseManyToManyLazyWithOrphanRemovalItem($this);
        }
    }

    /**
     * @return Collection|TestCollection[]
     */
    public function getInverseManyToManyLazyWithoutOrphanRemovalParents()
    {
        return $this->inverseManyToManyLazyWithoutOrphanRemovalParents;
    }

    /**
     * @param Collection|TestCollection[] $parents
     */
    public function setInverseManyToManyLazyWithoutOrphanRemovalParents(Collection $parents)
    {
        $this->inverseManyToManyLazyWithoutOrphanRemovalParents = $parents;
    }

    public function addInverseManyToManyLazyWithoutOrphanRemovalParent(TestCollection $parent)
    {
        if (!$this->getInverseManyToManyLazyWithoutOrphanRemovalParents()->contains($parent)) {
            $this->getInverseManyToManyLazyWithoutOrphanRemovalParents()->add($parent);
            $parent->addInverseManyToManyLazyWithoutOrphanRemovalItem($this);
        }
    }

    public function removeInverseManyToManyLazyWithoutOrphanRemovalParent(TestCollection $parent)
    {
        if ($this->getInverseManyToManyLazyWithoutOrphanRemovalParents()->contains($parent)) {
            $this->getInverseManyToManyLazyWithoutOrphanRemovalParents()->removeElement($parent);
            $parent->removeInverseManyToManyLazyWithoutOrphanRemovalItem($this);
        }
    }

    /**
     * @return Collection|TestCollection[]
     */
    public function getInverseManyToManyExtraLazyWithOrphanRemovalParents()
    {
        return $this->inverseManyToManyExtraLazyWithOrphanRemovalParents;
    }

    /**
     * @param Collection|TestCollection[] $parents
     */
    public function setInverseManyToManyExtraLazyWithOrphanRemovalParents(Collection $parents)
    {
        $this->inverseManyToManyExtraLazyWithOrphanRemovalParents = $parents;
    }

    public function addInverseManyToManyExtraLazyWithOrphanRemovalParent(TestCollection $parent)
    {
        if (!$this->getInverseManyToManyExtraLazyWithOrphanRemovalParents()->contains($parent)) {
            $this->getInverseManyToManyExtraLazyWithOrphanRemovalParents()->add($parent);
            $parent->addInverseManyToManyExtraLazyWithOrphanRemovalItem($this);
        }
    }

    public function removeInverseManyToManyExtraLazyWithOrphanRemovalParent(TestCollection $parent)
    {
        if ($this->getInverseManyToManyExtraLazyWithOrphanRemovalParents()->contains($parent)) {
            $this->getInverseManyToManyExtraLazyWithOrphanRemovalParents()->removeElement($parent);
            $parent->removeInverseManyToManyExtraLazyWithOrphanRemovalItem($this);
        }
    }

    /**
     * @return Collection|TestCollection[]
     */
    public function getInverseManyToManyExtraLazyWithoutOrphanRemovalParents()
    {
        return $this->inverseManyToManyExtraLazyWithoutOrphanRemovalParents;
    }

    /**
     * @param Collection|TestCollection[] $parents
     */
    public function setInverseManyToManyExtraLazyWithoutOrphanRemovalParents(Collection $parents)
    {
        $this->inverseManyToManyExtraLazyWithoutOrphanRemovalParents = $parents;
    }

    public function addInverseManyToManyExtraLazyWithoutOrphanRemovalParent(TestCollection $parent)
    {
        if (!$this->getInverseManyToManyExtraLazyWithoutOrphanRemovalParents()->contains($parent)) {
            $this->getInverseManyToManyExtraLazyWithoutOrphanRemovalParents()->add($parent);
            $parent->addInverseManyToManyExtraLazyWithoutOrphanRemovalItem($this);
        }
    }

    public function removeInverseManyToManyExtraLazyWithoutOrphanRemovalParent(TestCollection $parent)
    {
        if ($this->getInverseManyToManyExtraLazyWithoutOrphanRemovalParents()->contains($parent)) {
            $this->getInverseManyToManyExtraLazyWithoutOrphanRemovalParents()->removeElement($parent);
            $parent->removeInverseManyToManyExtraLazyWithoutOrphanRemovalItem($this);
        }
    }
}
