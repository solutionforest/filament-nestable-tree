<?php

namespace SolutionForest\FilamentNestableTree\Tests\Fixtures\Pages;

use SolutionForest\FilamentNestableTree\Filament\Pages\TreePage;
use SolutionForest\FilamentNestableTree\Tests\Fixtures\Models\Tag;
use SolutionForest\FilamentNestableTree\Tree;

/**
 * Two trees backed by the same Tag model, each filtered by category_id.
 *
 * Cross-tree moves update the tag's category_id to match the destination
 * tree — the canonical "same model / partition by foreign key" pattern.
 */
class TagCrossTreePage extends TreePage
{
    protected static ?string $slug = 'tag-cross-tree';

    protected static bool $shouldRegisterNavigation = false;

    /** Category IDs set up by the test beforeEach. */
    public static ?int $categoryAId = null;

    public static ?int $categoryBId = null;

    protected $listeners = ['tree-cross-move' => 'handleCrossTreeMove'];

    public function trees(): array
    {
        return [
            'cat_a' => Tree::make()
                ->records(fn () => Tag::where('category_id', static::$categoryAId)
                    ->defaultOrder()->get()->toTree()->toArray())
                ->labelField('name')
                ->allowCrossCategory()
                ->saveOrderUsing(fn (array $nodes) => Tag::rebuildTree($nodes)),

            'cat_b' => Tree::make()
                ->records(fn () => Tag::where('category_id', static::$categoryBId)
                    ->defaultOrder()->get()->toTree()->toArray())
                ->labelField('name')
                ->allowCrossCategory()
                ->saveOrderUsing(fn (array $nodes) => Tag::rebuildTree($nodes)),
        ];
    }

    public function handleCrossTreeMove(
        string $fromTreeKey,
        string $toTreeKey,
        int | string $nodeId,
        mixed $destinationParentId = null,
    ): void {
        $tag = Tag::find($nodeId);

        if (! $tag) {
            return;
        }

        $newCategoryId = $toTreeKey === 'cat_a' ? static::$categoryAId : static::$categoryBId;

        if ($destinationParentId) {
            $parent = Tag::find($destinationParentId);
            if ($parent) {
                $tag->appendToNode($parent)->save();
            }
        } else {
            $tag->saveAsRoot();
        }

        $tag->update(['category_id' => $newCategoryId]);

        $this->dispatch('tree-refresh');
    }
}
