<?php

namespace SolutionForest\FilamentNestableTree\Tree\Concerns;

use Closure;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;

trait HasNodeActions
{
    /** @var array<Action|ActionGroup> | Closure | null */
    protected array | Closure | null $nodeActions = null;

    /**
     * @param  array<Action|ActionGroup>|Closure|null  $actions
     */
    public function nodeActions(array | Closure | null $actions): static
    {
        $this->nodeActions = $actions;

        return $this;
    }

    /**
     * @return array<Action|ActionGroup>
     */
    public function getNodeActions(): array
    {
        $actions = $this->evaluate($this->nodeActions);

        if (is_array($actions)) {
            return $actions;
        }

        return [];
    }
}
