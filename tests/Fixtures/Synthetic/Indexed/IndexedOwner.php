<?php declare(strict_types = 1);

namespace ShipMonkTests\DoctrineEntityPreloader\Fixtures\Synthetic\Indexed;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\JoinTable;
use Doctrine\ORM\Mapping\ManyToMany;
use Doctrine\ORM\Mapping\OneToMany;
use LogicException;
use ShipMonkTests\DoctrineEntityPreloader\Fixtures\Synthetic\TestEntityWithId;

#[Entity]
class IndexedOwner extends TestEntityWithId
{

    /**
     * @var Collection<string, IndexedItem>
     */
    #[OneToMany(targetEntity: IndexedItem::class, mappedBy: 'ownerByCode', indexBy: 'code')]
    private Collection $itemsByCode;

    /**
     * @var Collection<int, IndexedItem>
     */
    #[OneToMany(targetEntity: IndexedItem::class, mappedBy: 'ownerByPosition', indexBy: 'position')]
    private Collection $itemsByPosition;

    /**
     * @var Collection<string, IndexedItem>
     */
    #[OneToMany(targetEntity: IndexedItem::class, mappedBy: 'ownerByStatus', indexBy: 'status')]
    private Collection $itemsByStatus;

    /**
     * @var Collection<int|string, IndexedItem>
     */
    #[OneToMany(targetEntity: IndexedItem::class, mappedBy: 'ownerByCategory', indexBy: 'category_id')]
    private Collection $itemsByCategory;

    /**
     * @var Collection<string, IndexedTag>
     */
    #[ManyToMany(targetEntity: IndexedTag::class, indexBy: 'label')]
    #[JoinTable(name: 'indexed_owner_tags_by_label')]
    private Collection $tagsByLabel;

    /**
     * @var Collection<int|string, IndexedTag>
     */
    #[ManyToMany(targetEntity: IndexedTag::class, indexBy: 'category_id')]
    #[JoinTable(name: 'indexed_owner_tags_by_category')]
    private Collection $tagsByCategory;

    /**
     * @var Collection<string, IndexedTag>
     */
    #[ManyToMany(targetEntity: IndexedTag::class, indexBy: 'createdAt')]
    #[JoinTable(name: 'indexed_owner_tags_by_created_at')]
    private Collection $tagsByCreatedAt;

    /**
     * @var Collection<int, IndexedTag>
     */
    #[ManyToMany(targetEntity: IndexedTag::class, indexBy: 'category')]
    #[JoinTable(name: 'indexed_owner_tags_by_category_association')]
    private Collection $tagsByCategoryAssociation;

    /**
     * @var Collection<int, IndexedTag>
     */
    #[ManyToMany(targetEntity: IndexedTag::class, indexBy: 'unknown')]
    #[JoinTable(name: 'indexed_owner_tags_by_unknown')]
    private Collection $tagsByUnknown;

    public function __construct()
    {
        $this->itemsByCode = new ArrayCollection();
        $this->itemsByPosition = new ArrayCollection();
        $this->itemsByStatus = new ArrayCollection();
        $this->itemsByCategory = new ArrayCollection();
        $this->tagsByLabel = new ArrayCollection();
        $this->tagsByCategory = new ArrayCollection();
        $this->tagsByCreatedAt = new ArrayCollection();
        $this->tagsByCategoryAssociation = new ArrayCollection();
        $this->tagsByUnknown = new ArrayCollection();
    }

    /**
     * @return iterable<array-key, IndexedItem|IndexedTag>
     */
    public function getCollection(string $propertyName): iterable
    {
        return match ($propertyName) {
            'itemsByCode' => $this->itemsByCode,
            'itemsByPosition' => $this->itemsByPosition,
            'itemsByStatus' => $this->itemsByStatus,
            'itemsByCategory' => $this->itemsByCategory,
            'tagsByLabel' => $this->tagsByLabel,
            'tagsByCategory' => $this->tagsByCategory,
            'tagsByCreatedAt' => $this->tagsByCreatedAt,
            'tagsByCategoryAssociation' => $this->tagsByCategoryAssociation,
            'tagsByUnknown' => $this->tagsByUnknown,
            default => throw new LogicException("Unknown collection {$propertyName}"),
        };
    }

    public function addTag(IndexedTag $tag): void
    {
        $this->tagsByLabel->add($tag);
        $this->tagsByCategory->add($tag);
    }

}
