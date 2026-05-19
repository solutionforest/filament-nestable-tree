<?php

use SolutionForest\FilamentNestableTree\Tests\Fixtures\Models\User;
use SolutionForest\FilamentNestableTree\Tests\Fixtures\Pages\CategoryPartitionedTreePage;

use function Pest\Livewire\livewire;

// ── Shared test data ──────────────────────────────────────────────────────────
//
//  category 1: node 1 (root) → node 2 (child of 1)
//  category 2: node 3 (root) → node 4 (child of 3)

beforeEach(function () {
    $this->actingAs(User::factory()->create());

    CategoryPartitionedTreePage::$nodes = [
        ['id' => 1, 'title' => 'Cat1-Root',  'parent_id' => null, 'category_id' => 1],
        ['id' => 2, 'title' => 'Cat1-Child', 'parent_id' => 1,    'category_id' => 1],
        ['id' => 3, 'title' => 'Cat2-Root',  'parent_id' => null, 'category_id' => 2],
        ['id' => 4, 'title' => 'Cat2-Child', 'parent_id' => 3,    'category_id' => 2],
    ];

    CategoryPartitionedTreePage::$saveOrderLog = [];
});

// ── Rendering ────────────────────────────────────────────────────────────────

it('renders the category-partitioned multi-tree page without errors', function () {
    livewire(CategoryPartitionedTreePage::class)
        ->assertSuccessful();
});

// ── Category isolation ────────────────────────────────────────────────────────

it('tree1 only loads nodes for category 1', function () {
    $component = livewire(CategoryPartitionedTreePage::class);

    $tree1Nodes = $component->get('namedTreeNodes')['tree1'];
    $allIds = array_column(CategoryPartitionedTreePage::asFlatten($tree1Nodes), 'id');

    expect($allIds)->toContain(1)
        ->toContain(2)
        ->not->toContain(3)
        ->not->toContain(4);
});

it('tree2 only loads nodes for category 2', function () {
    $component = livewire(CategoryPartitionedTreePage::class);

    $tree2Nodes = $component->get('namedTreeNodes')['tree2'];
    $allIds = array_column(CategoryPartitionedTreePage::asFlatten($tree2Nodes), 'id');

    expect($allIds)->toContain(3)
        ->toContain(4)
        ->not->toContain(1)
        ->not->toContain(2);
});

it('tree1 preserves parent-child hierarchy for its category', function () {
    $component = livewire(CategoryPartitionedTreePage::class);

    $tree1 = $component->get('namedTreeNodes')['tree1'];

    // Node 1 is the root; node 2 is nested as its child.
    expect($tree1)->toHaveCount(1)
        ->and($tree1[0]['id'])->toBe(1)
        ->and($tree1[0]['children'])->toHaveCount(1)
        ->and($tree1[0]['children'][0]['id'])->toBe(2);
});

// ── Cross-tree move ───────────────────────────────────────────────────────────

it('handleCrossTreeMove updates category_id of the moved node', function () {
    livewire(CategoryPartitionedTreePage::class)
        ->dispatch('tree-cross-move', 'tree1', 'tree2', 1, null);

    $movedNode = collect(CategoryPartitionedTreePage::$nodes)->firstWhere('id', 1);

    expect($movedNode['category_id'])->toBe(2);
});

it('handleCrossTreeMove sets the destination parent_id on the moved node', function () {
    livewire(CategoryPartitionedTreePage::class)
        ->dispatch('tree-cross-move', 'tree1', 'tree2', 1, 3);

    $movedNode = collect(CategoryPartitionedTreePage::$nodes)->firstWhere('id', 1);

    expect($movedNode['parent_id'])->toBe(3);
});

it('handleCrossTreeMove sets parent_id to null when appended at root level', function () {
    livewire(CategoryPartitionedTreePage::class)
        ->dispatch('tree-cross-move', 'tree1', 'tree2', 1, null);

    $movedNode = collect(CategoryPartitionedTreePage::$nodes)->firstWhere('id', 1);

    expect($movedNode['parent_id'])->toBeNull();
});

it('handleCrossTreeMove does not alter other nodes', function () {
    livewire(CategoryPartitionedTreePage::class)
        ->dispatch('tree-cross-move', 'tree1', 'tree2', 1, null);

    expect(CategoryPartitionedTreePage::$nodes)->toHaveCount(4);

    $node2 = collect(CategoryPartitionedTreePage::$nodes)->firstWhere('id', 2);
    expect($node2['category_id'])->toBe(1); // unchanged
});

it('handleCrossTreeMove dispatches tree-refresh', function () {
    livewire(CategoryPartitionedTreePage::class)
        ->dispatch('tree-cross-move', 'tree1', 'tree2', 1, null)
        ->assertDispatched('tree-refresh');
});

it('handleCrossTreeMove with an unknown destination tree key is a no-op', function () {
    $before = CategoryPartitionedTreePage::$nodes;

    livewire(CategoryPartitionedTreePage::class)
        ->dispatch('tree-cross-move', 'tree1', 'tree_unknown', 1, null);

    expect(CategoryPartitionedTreePage::$nodes)->toBe($before);
});

// ── Save order — category preservation ───────────────────────────────────────

it('saveTreeOrder for tree1 preserves category 2 nodes in storage', function () {
    livewire(CategoryPartitionedTreePage::class)
        ->call('saveTreeOrder', [], 'tree1');

    $cat2Ids = collect(CategoryPartitionedTreePage::$nodes)
        ->where('category_id', 2)
        ->pluck('id')
        ->all();

    expect($cat2Ids)->toContain(3)
        ->toContain(4);
});

it('saveTreeOrder for tree2 preserves category 1 nodes in storage', function () {
    livewire(CategoryPartitionedTreePage::class)
        ->call('saveTreeOrder', [], 'tree2');

    $cat1Ids = collect(CategoryPartitionedTreePage::$nodes)
        ->where('category_id', 1)
        ->pluck('id')
        ->all();

    expect($cat1Ids)->toContain(1)
        ->toContain(2);
});

it('saveTreeOrder invokes the saveOrderUsing callback and records it in the log', function () {
    livewire(CategoryPartitionedTreePage::class)
        ->call('saveTreeOrder', [], 'tree1');

    expect(CategoryPartitionedTreePage::$saveOrderLog)->toHaveCount(1)
        ->and(CategoryPartitionedTreePage::$saveOrderLog[0]['categoryId'])->toBe(1);
});

it('saveTreeOrder stamps category_id on every node it saves', function () {
    livewire(CategoryPartitionedTreePage::class)
        ->call('saveTreeOrder', [], 'tree1');

    $savedNodes = CategoryPartitionedTreePage::$saveOrderLog[0]['nodes'];

    foreach ($savedNodes as $node) {
        expect($node['category_id'])->toBe(1);
    }
});

it('saveTreeOrder dispatches tree-order-saved event', function () {
    livewire(CategoryPartitionedTreePage::class)
        ->call('saveTreeOrder', [], 'tree1')
        ->assertDispatched('tree-order-saved');
});

// ── CRUD helpers ──────────────────────────────────────────────────────────────

it('upsertNode creates a new node with an auto-incremented id', function () {
    livewire(CategoryPartitionedTreePage::class)
        ->call('upsertNode', ['title' => 'New Cat1 Node', 'parent_id' => null, 'category_id' => 1]);

    expect(CategoryPartitionedTreePage::$nodes)->toHaveCount(5);

    $newNode = collect(CategoryPartitionedTreePage::$nodes)->last();
    expect($newNode['id'])->toBe(5) // max(1,2,3,4) + 1
        ->and($newNode['title'])->toBe('New Cat1 Node');
});

it('upsertNode updates an existing node when an existing record is supplied', function () {
    $existing = ['id' => 1, 'title' => 'Cat1-Root', 'parent_id' => null, 'category_id' => 1];

    livewire(CategoryPartitionedTreePage::class)
        ->call('upsertNode', ['title' => 'Renamed Root'], 'id', $existing);

    $updated = collect(CategoryPartitionedTreePage::$nodes)->firstWhere('id', 1);
    expect($updated['title'])->toBe('Renamed Root');

    // Other nodes are untouched.
    expect(CategoryPartitionedTreePage::$nodes)->toHaveCount(4);
});

it('removeNode deletes the node matching the given id', function () {
    livewire(CategoryPartitionedTreePage::class)
        ->call('removeNode', 2);

    $ids = array_column(CategoryPartitionedTreePage::$nodes, 'id');
    expect($ids)->not->toContain(2)
        ->and(CategoryPartitionedTreePage::$nodes)->toHaveCount(3);
});

it('removeNode leaves all other nodes intact', function () {
    livewire(CategoryPartitionedTreePage::class)
        ->call('removeNode', 3);

    $ids = array_column(CategoryPartitionedTreePage::$nodes, 'id');
    expect($ids)->toContain(1)
        ->toContain(2)
        ->toContain(4);
});

// ── asTree / asFlatten utilities ──────────────────────────────────────────────

it('asTree builds a nested structure from a flat parent_id array', function () {
    $flat = [
        ['id' => 10, 'title' => 'Root',       'parent_id' => null],
        ['id' => 11, 'title' => 'Child A',    'parent_id' => 10],
        ['id' => 12, 'title' => 'Grandchild', 'parent_id' => 11],
        ['id' => 13, 'title' => 'Child B',    'parent_id' => 10],
    ];

    $tree = CategoryPartitionedTreePage::asTree($flat);

    expect($tree)->toHaveCount(1)
        ->and($tree[0]['id'])->toBe(10)
        ->and($tree[0]['children'])->toHaveCount(2)
        ->and($tree[0]['children'][0]['id'])->toBe(11)
        ->and($tree[0]['children'][0]['children'])->toHaveCount(1)
        ->and($tree[0]['children'][0]['children'][0]['id'])->toBe(12)
        ->and($tree[0]['children'][1]['id'])->toBe(13);
});

it('asTree treats orphaned nodes (missing parent) as root items', function () {
    $flat = [
        ['id' => 1, 'title' => 'Real Root', 'parent_id' => null],
        ['id' => 2, 'title' => 'Orphan',    'parent_id' => 999], // parent does not exist
    ];

    $tree = CategoryPartitionedTreePage::asTree($flat);

    expect($tree)->toHaveCount(2);
});

it('asFlatten extracts all nodes from a nested tree without children keys', function () {
    $tree = [
        [
            'id' => 10, 'title' => 'Root', 'parent_id' => null, 'children' => [
                [
                    'id' => 11, 'title' => 'Child', 'parent_id' => 10, 'children' => [
                        ['id' => 12, 'title' => 'Grandchild', 'parent_id' => 11, 'children' => []],
                    ],
                ],
            ],
        ],
    ];

    $flat = CategoryPartitionedTreePage::asFlatten($tree);

    expect($flat)->toHaveCount(3)
        ->and(array_column($flat, 'id'))->toBe([10, 11, 12]);

    // Children key must be stripped.
    foreach ($flat as $node) {
        expect($node)->not->toHaveKey('children');
    }
});

it('asFlatten and asTree round-trip preserves all ids', function () {
    $flat = [
        ['id' => 1, 'title' => 'R1', 'parent_id' => null, 'category_id' => 1],
        ['id' => 2, 'title' => 'C1', 'parent_id' => 1,    'category_id' => 1],
        ['id' => 3, 'title' => 'R2', 'parent_id' => null, 'category_id' => 1],
    ];

    $roundTripped = CategoryPartitionedTreePage::asFlatten(
        CategoryPartitionedTreePage::asTree($flat)
    );

    expect(array_column($roundTripped, 'id'))->toEqualCanonicalizing([1, 2, 3]);
});
