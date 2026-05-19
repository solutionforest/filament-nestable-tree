<?php

use SolutionForest\FilamentNestableTree\Tests\Fixtures\Models\User;
use SolutionForest\FilamentNestableTree\Tests\Fixtures\Pages\StaticRecordsTreePage;

use function Pest\Livewire\livewire;

beforeEach(function () {
    $this->actingAs(User::factory()->create());

    // Create a fresh temp JSON file with default nodes before every test.
    StaticRecordsTreePage::$jsonFile = sys_get_temp_dir() . '/fi-tree-test-' . uniqid() . '.json';
    StaticRecordsTreePage::writeNodes(StaticRecordsTreePage::defaultNodes());
});

afterEach(function () {
    if (StaticRecordsTreePage::$jsonFile !== '' && file_exists(StaticRecordsTreePage::$jsonFile)) {
        unlink(StaticRecordsTreePage::$jsonFile);
    }
});

it('renders the static-records tree page without errors', function () {
    livewire(StaticRecordsTreePage::class)
        ->assertSuccessful();
});

it('static records tree page loads nodes from the JSON file', function () {
    $expected = StaticRecordsTreePage::asTree(StaticRecordsTreePage::defaultNodes());

    livewire(StaticRecordsTreePage::class)
        ->assertSet('treeNodes', $expected);
});

it('saveTreeOrder persists the new order to the JSON file', function () {
    $reordered = [
        ['id' => 2, 'title' => 'Root B', 'parent_id' => null, 'children' => [
            ['id' => 3, 'title' => 'Child B1', 'parent_id' => 2, 'children' => []],
        ]],
        ['id' => 1, 'title' => 'Root A', 'parent_id' => null, 'children' => []],
    ];

    livewire(StaticRecordsTreePage::class)
        ->call('saveTreeOrder', $reordered);

    $saved = StaticRecordsTreePage::readNodes();
    $ids = array_column($saved, 'id');

    // Root B (id=2) should now appear before Root A (id=1).
    expect($ids[0])->toBe(2)
        ->and($ids[1])->toBe(3)
        ->and($ids[2])->toBe(1);
});

it('saveTreeOrder dispatches tree-order-saved event', function () {
    livewire(StaticRecordsTreePage::class)
        ->call('saveTreeOrder')
        ->assertDispatched('tree-order-saved');
});

it('resetTreeOrder dispatches tree-reset event', function () {
    livewire(StaticRecordsTreePage::class)
        ->call('resetTreeOrder')
        ->assertDispatched('tree-reset');
});

it('refreshes nodes from JSON on tree-refresh event', function () {
    $component = livewire(StaticRecordsTreePage::class);

    // Write new data to the JSON file (simulates external update / reset).
    $newFlat = [
        ['id' => 99, 'title' => 'New Root', 'parent_id' => null],
    ];
    StaticRecordsTreePage::writeNodes($newFlat);

    $component->dispatch('tree-refresh');

    $nodes = $component->get('treeNodes');
    expect($nodes)->toHaveCount(1)
        ->and($nodes[0]['title'])->toBe('New Root');
});

it('asTree and asFlatten are inverse operations', function () {
    $flat = StaticRecordsTreePage::defaultNodes();
    $tree = StaticRecordsTreePage::asTree($flat);
    $back = StaticRecordsTreePage::asFlatten($tree);

    $originalIds = array_column($flat, 'id');
    $roundIds = array_column($back, 'id');

    expect(sort($originalIds))->toBe(sort($roundIds));
});
