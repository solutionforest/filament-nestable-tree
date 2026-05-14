<?php

use SolutionForest\FilamentNestableTree\Tests\Fixtures\Models\User;
use SolutionForest\FilamentNestableTree\Tests\Fixtures\Pages\StaticRecordsTreePage;

use function Pest\Livewire\livewire;

beforeEach(function () {
    $this->actingAs(User::factory()->create());

    // Reset shared static storage before every test.
    StaticRecordsTreePage::$staticNodes = [
        ['id' => 1, 'title' => 'Root A', 'children' => []],
        [
            'id' => 2, 'title' => 'Root B', 'children' => [
                ['id' => 3, 'title' => 'Child B1', 'children' => []],
            ],
        ],
    ];
});

it('renders the static-records tree page without errors', function () {
    livewire(StaticRecordsTreePage::class)
        ->assertSuccessful();
});

it('static records tree page loads nodes', function () {
    livewire(StaticRecordsTreePage::class)
        ->assertSet('treeNodes', StaticRecordsTreePage::$staticNodes);
});

it('saveTreeOrder persists order via saveOrderUsing callback', function () {
    $reordered = [
        [
            'id' => 2, 'title' => 'Root B', 'children' => [
                ['id' => 3, 'title' => 'Child B1', 'children' => []],
            ],
        ],
        ['id' => 1, 'title' => 'Root A', 'children' => []],
    ];

    livewire(StaticRecordsTreePage::class)
        ->call('saveTreeOrder', $reordered);

    expect(StaticRecordsTreePage::$staticNodes)->toBe($reordered);
});

it('refreshes nodes on tree-refresh event', function () {
    $component = livewire(StaticRecordsTreePage::class);

    $component->assertSet('treeNodes', StaticRecordsTreePage::$staticNodes);

    // Simulate what a CreateAction/EditAction would do: mutate static storage.
    StaticRecordsTreePage::$staticNodes = [
        ['id' => 1, 'title' => 'Root A', 'children' => []],
        ['id' => 2, 'title' => 'Root B', 'children' => []],
        ['id' => 99, 'title' => 'New Node', 'children' => []],
    ];

    $component->dispatch('tree-refresh');

    $component->assertSet('treeNodes', StaticRecordsTreePage::$staticNodes);
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
