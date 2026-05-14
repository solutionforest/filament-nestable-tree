<?php

namespace SolutionForest\FilamentNestableTree\Tests\Fixtures\Pages;

use SolutionForest\FilamentNestableTree\Filament\Pages\TreePage;
use SolutionForest\FilamentNestableTree\Tree;

/**
 * A Filament tree page that defines two named trees (multi-tree).
 * Mirrors the multi-tree usage in Test.php from the demo app.
 */
class MultiTreePage extends TreePage
{
    protected static ?string $slug = 'multi-tree';

    protected static bool $shouldRegisterNavigation = false;

    /** @var array<string, array<int, mixed>> Storage per tree key */
    public static array $treeStorage = [
        'tree_a' => [],
        'tree_b' => [],
    ];

    /** Tracks cross-tree move calls for assertion. */
    public static array $crossMoveLog = [];

    protected $listeners = [
        'tree-cross-move' => 'handleCrossTreeMove',
    ];

    public function trees(): array
    {
        return [
            'tree_a' => Tree::make()
                ->labelField('title')
                ->records(fn () => static::$treeStorage['tree_a'])
                ->saveOrderUsing(function (array $nodes) {
                    static::$treeStorage['tree_a'] = $nodes;
                }),

            'tree_b' => Tree::make()
                ->labelField('title')
                ->records(fn () => static::$treeStorage['tree_b'])
                ->saveOrderUsing(function (array $nodes) {
                    static::$treeStorage['tree_b'] = $nodes;
                }),
        ];
    }

    /**
     * Handle a node dragged from one tree to another.
     * In the real app this would update a category_id or similar.
     */
    public function handleCrossTreeMove(
        string $fromTreeKey,
        string $toTreeKey,
        int | string $nodeId,
        mixed $destinationParentId = null,
    ): void {
        static::$crossMoveLog[] = compact('fromTreeKey', 'toTreeKey', 'nodeId', 'destinationParentId');

        // Remove from source storage, add to destination storage.
        $node = null;
        static::$treeStorage[$fromTreeKey] = array_values(
            array_filter(static::$treeStorage[$fromTreeKey], function ($n) use ($nodeId, &$node) {
                if ((string) ($n['id'] ?? '') === (string) $nodeId) {
                    $node = $n;

                    return false;
                }

                return true;
            }),
        );

        if ($node !== null) {
            $node['parent_id'] = $destinationParentId;
            static::$treeStorage[$toTreeKey][] = $node;
        }

        $this->dispatch('tree-refresh');
    }
}
