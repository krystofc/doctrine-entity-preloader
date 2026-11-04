<?php declare(strict_types = 1);

namespace ShipMonkTests\DoctrineEntityPreloader\Fixtures\Synthetic\Indexed;

use DateTimeImmutable;
use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\ManyToOne;
use ShipMonkTests\DoctrineEntityPreloader\Fixtures\Synthetic\TestEntityWithId;

#[Entity]
class IndexedTag extends TestEntityWithId
{

    #[Column]
    private string $label;

    #[Column]
    private DateTimeImmutable $createdAt;

    #[ManyToOne(targetEntity: IndexedCategory::class)]
    private ?IndexedCategory $category;

    public function __construct(
        string $label,
        ?IndexedCategory $category,
    )
    {
        $this->label = $label;
        $this->createdAt = new DateTimeImmutable('2026-01-01 00:00:00');
        $this->category = $category;
    }

    public function getLabel(): string
    {
        return $this->label;
    }

}
