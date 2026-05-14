<?php

namespace SolutionForest\FilamentNestableTree\Tests\Fixtures\Pages;

use SolutionForest\FilamentNestableTree\Filament\Pages\TreePage;
use SolutionForest\FilamentNestableTree\Tests\Fixtures\Models\Category;
use SolutionForest\FilamentNestableTree\Tree;

/**
 * A Filament tree page backed by an Eloquent Category model (uses NodeTrait).
 * Mirrors the approach in ListCategories.php from the demo app.
 */
class CategoryTreePage extends TreePage
{
    protected static ?string $slug = 'category-tree';

    protected static bool $shouldRegisterNavigation = false;

    public function tree(Tree $tree): Tree
    {
        return $tree
            ->model(Category::class)
            ->labelField('title')
            ->searchable()
            ->maxDepth(5)
            ->maxVisibleDepth(5);
    }
}
