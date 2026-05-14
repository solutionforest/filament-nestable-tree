<?php

namespace SolutionForest\FilamentNestableTree\Tests\Fixtures\Pages;

use SolutionForest\FilamentNestableTree\Filament\Pages\TreePage;
use SolutionForest\FilamentNestableTree\Tree;

/**
 * A Filament tree page backed by static (in-memory) records.
 * Mirrors the approach in Test.php from the demo app.
 */
class StaticRecordsTreePage extends TreePage
{
    protected static ?string $slug = 'static-tree';

    protected static bool $shouldRegisterNavigation = false;

    public static array $staticNodes = [];

    public function tree(Tree $tree): Tree
    {
        return $tree
            ->labelField('title')
            ->records(fn () => static::$staticNodes)
            ->saveOrderUsing(function (array $nodes) {
                static::$staticNodes = $nodes;
            })
            ->searchable();
    }
}
