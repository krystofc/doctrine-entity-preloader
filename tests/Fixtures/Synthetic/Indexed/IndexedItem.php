<?php declare(strict_types = 1);

namespace ShipMonkTests\DoctrineEntityPreloader\Fixtures\Synthetic\Indexed;

use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\ManyToOne;
use ShipMonkTests\DoctrineEntityPreloader\Fixtures\Synthetic\TestEntityWithId;

#[Entity]
class IndexedItem extends TestEntityWithId
{

    #[Column]
    private string $code;

    #[Column]
    private int $position;

    #[Column(enumType: IndexedItemStatus::class)]
    private IndexedItemStatus $status;

    #[ManyToOne(targetEntity: IndexedCategory::class)]
    private ?IndexedCategory $category;

    #[ManyToOne(targetEntity: IndexedOwner::class, inversedBy: 'itemsByCode')]
    private IndexedOwner $ownerByCode;

    #[ManyToOne(targetEntity: IndexedOwner::class, inversedBy: 'itemsByPosition')]
    private IndexedOwner $ownerByPosition;

    #[ManyToOne(targetEntity: IndexedOwner::class, inversedBy: 'itemsByStatus')]
    private IndexedOwner $ownerByStatus;

    #[ManyToOne(targetEntity: IndexedOwner::class, inversedBy: 'itemsByCategory')]
    private IndexedOwner $ownerByCategory;

    public function __construct(
        IndexedOwner $owner,
        string $code,
        int $position,
        IndexedItemStatus $status,
        ?IndexedCategory $category,
    )
    {
        $this->code = $code;
        $this->position = $position;
        $this->status = $status;
        $this->category = $category;
        $this->ownerByCode = $owner;
        $this->ownerByPosition = $owner;
        $this->ownerByStatus = $owner;
        $this->ownerByCategory = $owner;
    }

    public function getCode(): string
    {
        return $this->code;
    }

}
