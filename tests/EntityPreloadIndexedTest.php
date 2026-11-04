<?php declare(strict_types = 1);

namespace ShipMonkTests\DoctrineEntityPreloader;

use Doctrine\DBAL\Types\IntegerType;
use LogicException;
use PHPUnit\Framework\Attributes\DataProvider;
use ShipMonkTests\DoctrineEntityPreloader\Fixtures\Synthetic\Indexed\IndexedCategory;
use ShipMonkTests\DoctrineEntityPreloader\Fixtures\Synthetic\Indexed\IndexedItem;
use ShipMonkTests\DoctrineEntityPreloader\Fixtures\Synthetic\Indexed\IndexedItemStatus;
use ShipMonkTests\DoctrineEntityPreloader\Fixtures\Synthetic\Indexed\IndexedOwner;
use ShipMonkTests\DoctrineEntityPreloader\Fixtures\Synthetic\Indexed\IndexedTag;
use ShipMonkTests\DoctrineEntityPreloader\Lib\TestCase;
use function array_map;
use function ksort;

class EntityPreloadIndexedTest extends TestCase
{

    protected function setUp(): void
    {
        parent::setUp();

        $this->initializeEntityManager(new IntegerType(), $this->getQueryLogger());
    }

    /**
     * @return iterable<string, array{literal-string, int, array<int|string, string>, array<int|string, string>}>
     */
    public static function provideIndexedAssociations(): iterable
    {
        yield 'one-to-many by string field' => ['itemsByCode', 1, ['a' => 'a', 'b' => 'b'], ['c' => 'c', 'd' => 'd']];
        yield 'one-to-many by int field' => ['itemsByPosition', 1, [10 => 'a', 20 => 'b'], [30 => 'c', 40 => 'd']];
        yield 'one-to-many by enum field' => ['itemsByStatus', 1, ['active' => 'a', 'inactive' => 'b'], ['active' => 'c', 'inactive' => 'd']];
        yield 'one-to-many by join column' => ['itemsByCategory', 1, [1 => 'a', 2 => 'b'], [1 => 'd', 2 => 'c']];
        yield 'many-to-many by string field' => ['tagsByLabel', 2, ['t1' => 't1', 't2' => 't2'], ['t2' => 't2', 't3' => 't3']];
        yield 'many-to-many by join column' => ['tagsByCategory', 2, [1 => 't1', 3 => 't2'], [2 => 't3', 3 => 't2']];
    }

    /**
     * @param literal-string $property
     * @param array<int|string, string> $expectedFirstOwnerKeys
     * @param array<int|string, string> $expectedSecondOwnerKeys
     */
    #[DataProvider('provideIndexedAssociations')]
    public function testPreloadIndexedCollectionHasSameKeysAsLazyLoad(
        string $property,
        int $expectedQueryCount,
        array $expectedFirstOwnerKeys,
        array $expectedSecondOwnerKeys,
    ): void
    {
        $this->givenOwnersWithItemsAndTags();

        $lazyLoaded = array_map(
            fn (IndexedOwner $owner): array => $this->describeCollection($owner->getCollection($property)),
            $this->findAllOwners(),
        );

        $this->getEntityManager()->clear();
        $owners = $this->findAllOwners();
        $this->getQueryLogger()->clear();

        $this->getEntityPreloader()->preload($owners, $property); // @phpstan-ignore shipmonk.entityPreloader.nonConstantPropertyName (data provider)

        self::assertCount($expectedQueryCount, $this->getQueryLogger()->getQueries());
        $this->getQueryLogger()->clear();

        $preloaded = array_map(
            fn (IndexedOwner $owner): array => $this->describeCollection($owner->getCollection($property)),
            $owners,
        );

        self::assertCount(0, $this->getQueryLogger()->getQueries());
        self::assertSame([$expectedFirstOwnerKeys, $expectedSecondOwnerKeys], $preloaded);
        self::assertSame($lazyLoaded, $preloaded);
    }

    public function testPreloadIndexedCollectionKeepsAlreadyInitializedCollection(): void
    {
        $this->givenOwnersWithItemsAndTags();

        $owners = $this->findAllOwners();
        $this->describeCollection($owners[0]->getCollection('itemsByCode')); // initialize the first collection the Doctrine way

        $this->getEntityPreloader()->preload($owners, 'itemsByCode');

        self::assertSame(
            [['a' => 'a', 'b' => 'b'], ['c' => 'c', 'd' => 'd']],
            array_map(fn (IndexedOwner $owner): array => $this->describeCollection($owner->getCollection('itemsByCode')), $owners),
        );
    }

    /**
     * @return iterable<string, array{literal-string, string}>
     */
    public static function provideUnsupportedIndexedAssociations(): iterable
    {
        yield 'unsupported field type' => ['tagsByCreatedAt', "Preloading of association '%s::\$tagsByCreatedAt' indexed by 'createdAt' of type 'datetime_immutable' is not supported%A"];
        yield 'association name' => ['tagsByCategoryAssociation', "Preloading of association '%s::\$tagsByCategoryAssociation' is not possible, indexBy 'category' is neither a field nor a join column of '%s'%A"];
        yield 'unknown name' => ['tagsByUnknown', "Preloading of association '%s::\$tagsByUnknown' is not possible, indexBy 'unknown' is neither a field nor a join column of '%s'%A"];
    }

    /**
     * @param literal-string $property
     */
    #[DataProvider('provideUnsupportedIndexedAssociations')]
    public function testPreloadUnsupportedIndexedCollectionThrows(
        string $property,
        string $expectedMessage,
    ): void
    {
        $this->givenOwnersWithItemsAndTags();
        $owners = $this->findAllOwners();
        $this->getQueryLogger()->clear();

        self::assertException(
            LogicException::class,
            $expectedMessage,
            fn () => $this->getEntityPreloader()->preload($owners, $property), // @phpstan-ignore shipmonk.entityPreloader.nonConstantPropertyName (data provider)
        );

        self::assertCount(0, $this->getQueryLogger()->getQueries());
    }

    private function givenOwnersWithItemsAndTags(): void
    {
        $entityManager = $this->getEntityManager();

        $categoryX = new IndexedCategory();
        $categoryY = new IndexedCategory();
        $categoryZ = new IndexedCategory();
        $entityManager->persist($categoryX); // id 1
        $entityManager->persist($categoryY); // id 2
        $entityManager->persist($categoryZ); // id 3

        $tag1 = new IndexedTag('t1', $categoryX);
        $tag2 = new IndexedTag('t2', $categoryZ);
        $tag3 = new IndexedTag('t3', $categoryY);
        $entityManager->persist($tag1);
        $entityManager->persist($tag2);
        $entityManager->persist($tag3);

        $owner1 = new IndexedOwner();
        $owner1->addTag($tag1);
        $owner1->addTag($tag2);
        $entityManager->persist($owner1);
        $entityManager->persist(new IndexedItem($owner1, 'a', 10, IndexedItemStatus::Active, $categoryX));
        $entityManager->persist(new IndexedItem($owner1, 'b', 20, IndexedItemStatus::Inactive, $categoryY));

        $owner2 = new IndexedOwner();
        $owner2->addTag($tag2);
        $owner2->addTag($tag3);
        $entityManager->persist($owner2);
        $entityManager->persist(new IndexedItem($owner2, 'c', 30, IndexedItemStatus::Active, $categoryY));
        $entityManager->persist(new IndexedItem($owner2, 'd', 40, IndexedItemStatus::Inactive, $categoryX));

        $entityManager->flush();
        $entityManager->clear();
        $this->getQueryLogger()->clear();
    }

    /**
     * @return list<IndexedOwner>
     */
    private function findAllOwners(): array
    {
        return $this->getEntityManager()->getRepository(IndexedOwner::class)->findBy([], ['id' => 'ASC']);
    }

    /**
     * Collection key => item code / tag label, sorted by key (to-many collections without orderBy have no guaranteed order)
     *
     * @param iterable<array-key, IndexedItem|IndexedTag> $collection
     * @return array<int|string, string>
     */
    private function describeCollection(iterable $collection): array
    {
        $result = [];

        foreach ($collection as $key => $element) {
            $result[$key] = $element instanceof IndexedItem ? $element->getCode() : $element->getLabel();
        }

        ksort($result);

        return $result;
    }

}
