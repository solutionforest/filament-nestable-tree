<?php

namespace SolutionForest\FilamentNestableTree\Filament\Widgets;

use Filament\Actions\Action;
use Filament\Actions\Concerns\InteractsWithActions;
use Filament\Actions\Contracts\HasActions;
use Filament\Schemas\Concerns\InteractsWithSchemas;
use Filament\Schemas\Contracts\HasSchemas;
use Filament\Widgets\Widget;
use SolutionForest\FilamentNestableTree\Concerns\InteractsWithTree;
use SolutionForest\FilamentNestableTree\Tree as TreeConfig;

abstract class Tree extends Widget implements HasActions, HasSchemas
{
    use InteractsWithActions {
        getMountedAction as getFilamentMountedAction;
    }
    use InteractsWithSchemas;
    use InteractsWithTree;

    protected string $view = 'filament-nestable-tree::livewire.widgets.tree';

    /**
     * Override in subclasses to configure the tree.
     */
    abstract public function tree(TreeConfig $tree): TreeConfig;

    public function mount(): void
    {
        $this->mountTree();
    }

    public function getMountedAction(?int $actionNestingIndex = null): ?Action
    {
        $action = $this->getFilamentMountedAction($actionNestingIndex);
        $this->injectNodeRecordIntoAction($action);

        return $action;
    }

    /**
     * @return array<string, mixed>
     */
    protected function getViewData(): array
    {
        $treeConfig = $this->getCachedTree();

        return [
            'wireNodesProperty' => 'treeNodes',
            'treeConfig' => $treeConfig,
            'isSearchable' => $treeConfig->isSearchable(),
            'allowDragDrop' => $treeConfig->isDraggable(),
            'allowCrossCategory' => $treeConfig->isCrossCategoryAllowed(),
            'toolbarActions' => $treeConfig->getToolbarActions(),
            'lazy' => $treeConfig->isLazy(),
            'hasNodeActions' => ! empty($treeConfig->getNodeActions()),
        ];
    }
}
