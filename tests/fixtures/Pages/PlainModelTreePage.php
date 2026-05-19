<?php

namespace SolutionForest\FilamentNestableTree\Tests\Fixtures\Pages;

use SolutionForest\FilamentNestableTree\Filament\Pages\TreePage;
use SolutionForest\FilamentNestableTree\Tests\Fixtures\Models\Post;
use SolutionForest\FilamentNestableTree\Tree;

/**
 * A Filament tree page backed by a plain Eloquent model (no NodeTrait).
 * Uses a simple parent_id / order structure with a children() HasMany.
 */
class PlainModelTreePage extends TreePage
{
    protected static ?string $slug = 'plain-model-tree';

    protected static bool $shouldRegisterNavigation = false;

    public function tree(Tree $tree): Tree
    {
        return $tree
            ->model(Post::class)
            ->labelField('name')
            ->parentKeyField('parent_id')
            ->saveOrderUsing(function (array $nodes) {
                $this->saveOrder($nodes);
            });
    }

    private function saveOrder(array $nodes, ?int $parentId = null, int $startOrder = 0): void
    {
        foreach ($nodes as $index => $node) {
            Post::where('id', $node['id'])->update([
                'parent_id' => $parentId,
                'order' => $startOrder + $index,
            ]);

            if (! empty($node['children'])) {
                $this->saveOrder($node['children'], (int) $node['id'], 0);
            }
        }
    }
}
