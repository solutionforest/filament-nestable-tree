<?php

namespace SolutionForest\FilamentNestableTree;

use Filament\Support\Components\ViewComponent;
use SolutionForest\FilamentNestableTree\Tree\Concerns\HasFields;
use SolutionForest\FilamentNestableTree\Tree\Concerns\HasNodeActions;
use SolutionForest\FilamentNestableTree\Tree\Concerns\HasOptions;
use SolutionForest\FilamentNestableTree\Tree\Concerns\HasToolbar;
use SolutionForest\FilamentNestableTree\Tree\Concerns\ResolvesNodes;

class Tree extends ViewComponent
{
    use HasFields;
    use HasNodeActions;
    use HasOptions;
    use HasToolbar;
    use ResolvesNodes;

    protected string $view = 'filament-nestable-tree::tree';

    protected string $viewIdentifier = 'tree';

    protected string $evaluationIdentifier = 'tree';

    final public function __construct() {}

    public static function make(): static
    {
        $instance = app(static::class);
        $instance->configure();

        return $instance;
    }
}
