@php
    /**
     * Rendered when Tree::make() is used directly as a Filament schema component.
     *
     * The host Livewire component MUST use the InteractsWithTree trait and have
     * called mountTree() (or have it auto-called via mountInteractsWithTree()).
     *
     * @var \SolutionForest\FilamentNestableTree\Tree $tree  The Tree config instance.
     */
    $treeConfig = $tree;
@endphp

@include('filament-nestable-tree::livewire.components.tree', [
    'wireNodesProperty'  => 'treeNodes',
    'treeKeyName'        => null,
    'treeConfig'         => $treeConfig,
    'isSearchable'       => $treeConfig->isSearchable(),
    'allowDragDrop'      => $treeConfig->isDraggable(),
    'allowCrossCategory' => $treeConfig->isCrossCategoryAllowed(),
    'toolbarActions'     => $treeConfig->getToolbarActions(),
    'lazy'               => $treeConfig->isLazy(),
    'hasNodeActions'     => ! empty($treeConfig->getNodeActions()),
])
