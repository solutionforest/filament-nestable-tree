<?php

namespace SolutionForest\FilamentNestableTree\Filament\Pages;

use Filament\Actions\Action;
use Filament\Actions\Concerns\InteractsWithActions;
use Filament\Actions\Contracts\HasActions;
use Filament\Pages\Page;
use Filament\Schemas\Components\View;
use Filament\Schemas\Concerns\InteractsWithSchemas;
use Filament\Schemas\Contracts\HasSchemas;
use Filament\Schemas\Schema;
use SolutionForest\FilamentNestableTree\Concerns\InteractsWithTree;
use SolutionForest\FilamentNestableTree\Tree;

abstract class TreePage extends Page implements HasActions, HasSchemas
{
    use InteractsWithActions {
        getMountedAction as getFilamentMountedAction;
    }
    use InteractsWithSchemas;
    use InteractsWithTree;

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

    public function content(Schema $schema): Schema
    {
        $namedTrees = $this->trees();

        if (! empty($namedTrees)) {
            $components = [];

            foreach ($namedTrees as $key => $treeConfig) {
                $components[] = View::make('filament-nestable-tree::livewire.components.tree')
                    ->viewData($this->buildTreeViewData($treeConfig, 'namedTreeNodes.' . $key, (string) $key))
                    ->key((string) $key);
            }

            return $schema->components($components);
        }

        return $schema->components([
            View::make('filament-nestable-tree::livewire.components.tree')
                ->viewData($this->buildTreeViewData($this->getCachedTree(), 'treeNodes'))
                ->key('default'),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function buildTreeViewData(Tree $treeConfig, string $wireProperty, ?string $treeKeyName = null): array
    {
        return [
            'wireNodesProperty' => $wireProperty,
            'treeConfig' => $treeConfig,
            'treeKeyName' => $treeKeyName,
            'isSearchable' => $treeConfig->isSearchable(),
            'allowDragDrop' => $treeConfig->isDraggable(),
            'allowCrossCategory' => $treeConfig->isCrossCategoryAllowed(),
            'toolbarActions' => $treeConfig->getToolbarActions(),
            'lazy' => $treeConfig->isLazy(),
            'hasNodeActions' => ! empty($treeConfig->getNodeActions()),
        ];
    }
}
