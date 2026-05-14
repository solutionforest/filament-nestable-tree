<?php

namespace SolutionForest\FilamentNestableTree\Tree\Concerns;

use Closure;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Support\Icons\Heroicon;

trait HasToolbar
{
    /**
     * When not null, replaces ALL toolbar actions (including the defaults).
     *
     * @var array<Action>|null|Closure
     */
    protected array | Closure | null $toolbarActions = null;

    /** @var array<Action>|Closure */
    protected array | Closure $prependedToolbarActions = [];

    /** @var array<Action>|Closure */
    protected array | Closure $appendedToolbarActions = [];

    /**
     * Replace ALL toolbar actions (removes the built-in expand/collapse defaults).
     *
     * @param  array<Action|ActionGroup>|Closure  $actions
     */
    public function toolbarActions(array | Closure $actions): static
    {
        $this->toolbarActions = $actions;

        return $this;
    }

    /**
     * Add actions before the default expand/collapse actions.
     *
     * @param  array<Action|ActionGroup>|Closure  $actions
     */
    public function prependToolbarActions(array | Closure $actions): static
    {
        $this->prependedToolbarActions = $actions;

        return $this;
    }

    /**
     * Add actions after the default expand/collapse actions.
     *
     * @param  array<Action|ActionGroup>|Closure  $actions
     */
    public function appendToolbarActions(array | Closure $actions): static
    {
        $this->appendedToolbarActions = $actions;

        return $this;
    }

    /**
     * @return array<Action|ActionGroup>
     */
    public function getToolbarActions(): array
    {
        $override = $this->toolbarActions ? $this->evaluate($this->toolbarActions) : null;
        if ($override !== null) {
            return $override;
        }

        $defaultActions = $this->getDefaultToolbarActions();

        $prependedToolbarActions = $this->prependedToolbarActions ? $this->evaluate($this->prependedToolbarActions, [
            'defaultActions' => $defaultActions,
        ]) : null;
        $appendedToolbarActions = $this->appendedToolbarActions ? $this->evaluate($this->appendedToolbarActions, [
            'defaultActions' => $defaultActions,
        ]) : null;

        return [
            ...($prependedToolbarActions ?? []),
            ...$defaultActions,
            ...($appendedToolbarActions ?? []),
        ];
    }

    /**
     * @return array<Action|ActionGroup>
     */
    protected function getDefaultToolbarActions(): array
    {
        return [
            // TODO: add translation keys for these default actions
            ActionGroup::make([

                Action::make('collapse_all')
                    ->label(__('Collapse All'))
                    ->alpineClickHandler('collapseAll()')
                    ->icon(Heroicon::ChevronUp)
                    ->color('gray'),

                Action::make('expand_all')
                    ->label(__('Expand All'))
                    ->alpineClickHandler('expandAll()')
                    ->icon(Heroicon::ChevronDown)
                    ->color('gray'),
            ])->buttonGroup(),

            Action::make('save')
                ->label(__('Save'))
                ->extraAttributes(['x-show' => 'hasUnsavedOrder', 'x-cloak' => true])
                ->alpineClickHandler('$wire.saveTreeOrder([], treeKey)'),

            Action::make('reset')
                ->label(__('Reset'))
                ->color('gray')
                ->icon(Heroicon::ArrowPath)
                ->iconButton()
                ->alpineClickHandler('$wire.resetTreeOrder(treeKey)'),
        ];
    }
}
