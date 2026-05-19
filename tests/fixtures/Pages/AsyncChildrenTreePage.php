<?php

namespace SolutionForest\FilamentNestableTree\Tests\Fixtures\Pages;

use SolutionForest\FilamentNestableTree\Filament\Pages\TreePage;
use SolutionForest\FilamentNestableTree\Tests\Fixtures\Models\Category;
use SolutionForest\FilamentNestableTree\Tree;

/**
 * A Filament tree page that uses asyncChildren mode on a NodeTrait model.
 * On first load, only root nodes are returned.
 * Children are fetched on-demand via loadChildren($parentId).
 */
class AsyncChildrenTreePage extends TreePage
{
    protected static ?string $slug = 'async-children-tree';

    protected static bool $shouldRegisterNavigation = false;

    public function tree(Tree $tree): Tree
    {
        return $tree
            ->model(Category::class)
            ->labelField('title')
            ->asyncChildren(function (int | string $parentId) {
                return Category::where('parent_id', $parentId)
                    ->defaultOrder()
                    ->get()
                    ->toArray();
            });
    }
}
