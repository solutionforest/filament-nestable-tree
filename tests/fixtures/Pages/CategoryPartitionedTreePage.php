<?php

namespace SolutionForest\FilamentNestableTree\Tests\Fixtures\Pages;

use Closure;
use SolutionForest\FilamentNestableTree\Filament\Pages\TreePage;
use SolutionForest\FilamentNestableTree\Tree;

/**
 * Multi-tree page backed by a flat static array partitioned by category_id.
 *
 * Mirrors the pattern from the demo app's Test.php:
 *  - Two named trees each showing nodes for one category.
 *  - Cross-tree drag dispatches 'tree-cross-move'; the handler updates category_id.
 *  - saveOrderUsing for each tree preserves nodes belonging to other categories.
 *  - Utility helpers asTree() / asFlatten() convert between flat ↔ nested.
 *  - upsertNode() / removeNode() for CRUD on the flat storage.
 *
 * Uses public static storage so individual tests can reset state with no side-effects.
 *
 * @var array<int, array{id: int, title: string, parent_id: int|null, category_id: int}> $nodes
 */
class CategoryPartitionedTreePage extends TreePage
{
    protected static ?string $slug = 'category-partitioned-tree';

    protected static bool $shouldRegisterNavigation = false;

    /** Flat node storage. Reset in beforeEach via ::$nodes = [...]. */
    public static array $nodes = [];

    /** Records each saveOrder call: [['categoryId' => int, 'nodes' => array], ...] */
    public static array $saveOrderLog = [];

    /**
     * Maps tree key → category_id.
     * Must match the keys returned by trees().
     */
    private const TREE_CATEGORY_MAP = [
        'tree1' => 1,
        'tree2' => 2,
    ];

    protected $listeners = [
        'tree-cross-move' => 'handleCrossTreeMove',
    ];

    public function trees(): array
    {
        return [
            'tree1' => Tree::make()
                ->labelField('title')
                ->allowCrossCategory()
                ->records(fn () => static::asTree(
                    collect(static::$nodes)->where('category_id', 1)->values()->all()
                ))
                ->saveOrderUsing($this->saveOrderNodeUsing(1)),

            'tree2' => Tree::make()
                ->labelField('title')
                ->allowCrossCategory()
                ->records(fn () => static::asTree(
                    collect(static::$nodes)->where('category_id', 2)->values()->all()
                ))
                ->saveOrderUsing($this->saveOrderNodeUsing(2)),
        ];
    }

    /**
     * Build the saveOrderUsing closure for a given category.
     *
     * When tree1 saves, category 2 nodes are preserved in static storage, and
     * vice versa — matching Test.php's "preserve other categories on save" pattern.
     */
    private function saveOrderNodeUsing(int $categoryId): Closure
    {
        return function (array $nodes) use ($categoryId): void {
            // Flatten the nested tree and tag each node with its category.
            $formatted = collect(static::asFlatten($nodes))
                ->map(fn ($node) => array_merge($node, ['category_id' => $categoryId]))
                ->all();

            // Preserve nodes that belong to other categories.
            $others = collect(static::$nodes)
                ->filter(fn ($n) => ($n['category_id'] ?? null) != $categoryId)
                ->values()
                ->all();

            static::$nodes = array_merge($others, $formatted);
            static::$saveOrderLog[] = ['categoryId' => $categoryId, 'nodes' => $formatted];
        };
    }

    /**
     * Handle a node being dragged from one named tree to another.
     *
     * Updates category_id (to the destination tree's category) and parent_id
     * on the moved node, then dispatches tree-refresh so all trees reload.
     *
     * When the destination tree key is not registered in TREE_CATEGORY_MAP the
     * move is silently ignored — matching Test.php's guard.
     */
    public function handleCrossTreeMove(
        string $fromTreeKey,
        string $toTreeKey,
        int | string $nodeId,
        mixed $destinationParentId = null,
    ): void {
        $destCategoryId = self::TREE_CATEGORY_MAP[$toTreeKey] ?? null;

        if ($destCategoryId === null) {
            return;
        }

        static::$nodes = collect(static::$nodes)
            ->map(function ($node) use ($nodeId, $destCategoryId, $destinationParentId) {
                if ((string) ($node['id'] ?? '') === (string) $nodeId) {
                    $node['category_id'] = $destCategoryId;
                    $node['parent_id'] = $destinationParentId;
                }

                return $node;
            })
            ->all();

        $this->dispatch('tree-refresh');
    }

    /**
     * Create a new node (auto-incremented id) or update an existing one.
     *
     * @param  array<string, mixed>  $data
     * @param  array<string, mixed>|null  $existingRecord  null = create, non-null = update
     */
    public function upsertNode(array $data, string $keyField = 'id', ?array $existingRecord = null): void
    {
        if ($existingRecord !== null) {
            static::$nodes = collect(static::$nodes)
                ->map(function ($node) use ($data, $keyField, $existingRecord) {
                    if ((string) ($node[$keyField] ?? '') === (string) ($existingRecord[$keyField] ?? '')) {
                        return array_merge($node, $data);
                    }

                    return $node;
                })
                ->all();
        } else {
            $ids = array_column(static::$nodes, $keyField);
            $maxId = $ids !== [] ? (int) max($ids) : 0;
            $data[$keyField] = $maxId + 1;
            static::$nodes[] = $data;
        }
    }

    /** Remove a single node by its primary-key value. */
    public function removeNode(int | string $id, string $keyField = 'id'): void
    {
        static::$nodes = collect(static::$nodes)
            ->reject(fn ($node) => (string) ($node[$keyField] ?? '') === (string) $id)
            ->values()
            ->all();
    }

    // ── Tree / flatten utilities (mirrors Test.php) ───────────────────────────

    /**
     * Convert a flat array (nodes with parent_id) to a nested tree.
     *
     * @param  array<int, array<string, mixed>>  $flat
     * @return array<int, array<string, mixed>>
     */
    public static function asTree(array $flat): array
    {
        $lookup = [];

        foreach ($flat as $item) {
            $lookup[$item['id']] = $item + ['children' => []];
        }

        $tree = [];

        foreach ($lookup as $id => &$node) {
            if ($node['parent_id'] === null || ! isset($lookup[$node['parent_id']])) {
                $tree[] = &$node;
            } else {
                $lookup[$node['parent_id']]['children'][] = &$node;
            }
        }

        return $tree;
    }

    /**
     * Flatten a nested tree back to a flat array, recursively stripping children.
     *
     * @param  array<int, array<string, mixed>>  $tree
     * @return array<int, array<string, mixed>>
     */
    public static function asFlatten(array $tree): array
    {
        $flat = [];

        foreach ($tree as $item) {
            $children = $item['children'] ?? [];
            unset($item['children']);
            $flat[] = $item;

            if (! empty($children)) {
                $flat = array_merge($flat, static::asFlatten($children));
            }
        }

        return $flat;
    }
}
