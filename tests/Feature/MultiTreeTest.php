<?php

use SolutionForest\FilamentNestableTree\Tests\Fixtures\Models\User;
use SolutionForest\FilamentNestableTree\Tests\Fixtures\Pages\MultiTreePage;

use function Pest\Livewire\livewire;

beforeEach(function () {
    $this->actingAs(User::factory()->create());

    // Reset per-tree storage before every test.
    MultiTreePage::$treeStorage = [
        'tree_a' => [
            ['id' => 1, 'title' => 'A-Root 1', 'parent_id' => null, 'children' => []],
            ['id' => 2, 'title' => 'A-Root 2', 'parent_id' => null, 'children' => [
                ['id' => 3, 'title' => 'A-Child 2.1', 'parent_id' => 2, 'children' => []],
            ]],
        ],
        'tree_b' => [
            ['id' => 4, 'title' => 'B-Root 1', 'parent_id' => null, 'children' => []],
        ],
    ];
    MultiTreePage::$crossMoveLog = [];
});

it('renders the multi-tree page without errors', function () {
    livewire(MultiTreePage::class)
        ->assertSuccessful();
});

it('handleCrossTreeMove moves a node from tree_a to tree_b', function () {
    $page = livewire(MultiTreePage::class);

    $page->dispatch('tree-cross-move', 'tree_a', 'tree_b', 1, null);
    $treeAIds = array_column(MultiTreePage::$treeStorage['tree_a'], 'id');
    $treeBIds = array_column(MultiTreePage::$treeStorage['tree_b'], 'id');

    expect($treeAIds)->not->toContain(1)
        ->and($treeBIds)->toContain(1);
});

it('handleCrossTreeMove records the move in the cross-move log', function () {
    $page = livewire(MultiTreePage::class);

    $page->dispatch('tree-cross-move', 'tree_a', 'tree_b', 3, 4);

    expect(MultiTreePage::$crossMoveLog)->toHaveCount(1)
        ->and(MultiTreePage::$crossMoveLog[0])->toMatchArray([
            'fromTreeKey' => 'tree_a',
            'toTreeKey' => 'tree_b',
            'nodeId' => 3,
            'destinationParentId' => 4,
        ]);
});

it('handleCrossTreeMove dispatches tree-refresh after the move', function () {
    livewire(MultiTreePage::class)
        ->dispatch('tree-cross-move', 'tree_a', 'tree_b', 1, null)
        ->assertDispatched('tree-refresh');
});

it('handleCrossTreeMove updates the destination parent_id on the moved node', function () {
    livewire(MultiTreePage::class)
        ->dispatch('tree-cross-move', 'tree_a', 'tree_b', 1, 4);

    $movedNode = collect(MultiTreePage::$treeStorage['tree_b'])
        ->firstWhere('id', 1);

    expect($movedNode)->not->toBeNull()
        ->and($movedNode['parent_id'])->toBe(4);
});
