<?php

use SolutionForest\FilamentNestableTree\Tests\Fixtures\Models\Post;
use SolutionForest\FilamentNestableTree\Tests\Fixtures\Models\User;
use SolutionForest\FilamentNestableTree\Tests\Fixtures\Pages\PlainModelTreePage;

use function Pest\Livewire\livewire;

beforeEach(function () {
    $this->actingAs(User::factory()->create());
});

it('renders the plain model tree page without errors', function () {
    livewire(PlainModelTreePage::class)
        ->assertSuccessful();
});

it('loads root nodes and nested children via the children() relationship', function () {
    $root = Post::create(['name' => 'Root', 'order' => 0, 'parent_id' => null]);
    $child = Post::create(['name' => 'Child', 'order' => 0, 'parent_id' => $root->id]);

    $component = livewire(PlainModelTreePage::class);
    $nodes = $component->get('treeNodes');

    expect($nodes)->toHaveCount(1)
        ->and($nodes[0]['name'])->toBe('Root')
        ->and($nodes[0]['children'])->toHaveCount(1)
        ->and($nodes[0]['children'][0]['name'])->toBe('Child');
});

it('loads multiple root posts as a flat top-level list', function () {
    Post::create(['name' => 'Alpha', 'order' => 0]);
    Post::create(['name' => 'Beta', 'order' => 1]);

    $nodes = livewire(PlainModelTreePage::class)->get('treeNodes');
    $names = array_column($nodes, 'name');

    expect($nodes)->toHaveCount(2)
        ->and($names)->toContain('Alpha')
        ->and($names)->toContain('Beta');
});

it('saveTreeOrder updates parent_id and order for plain model nodes', function () {
    $a = Post::create(['name' => 'A', 'order' => 0]);
    $b = Post::create(['name' => 'B', 'order' => 1]);
    $c = Post::create(['name' => 'C', 'order' => 2]);

    // Reorder: B at root, C as child of B, A at root after B.
    $newOrder = [
        ['id' => $b->id, 'name' => 'B', 'children' => [
            ['id' => $c->id, 'name' => 'C', 'children' => []],
        ]],
        ['id' => $a->id, 'name' => 'A', 'children' => []],
    ];

    livewire(PlainModelTreePage::class)
        ->call('saveTreeOrder', $newOrder)
        ->assertDispatched('tree-order-saved');

    $b->refresh();
    $c->refresh();
    $a->refresh();

    expect($b->parent_id)->toBeNull()
        ->and($b->order)->toBe(0)
        ->and($c->parent_id)->toBe($b->id)
        ->and($c->order)->toBe(0)
        ->and($a->parent_id)->toBeNull()
        ->and($a->order)->toBe(1);
});

it('saveTreeOrder moves a node to root and clears its parent_id', function () {
    $root = Post::create(['name' => 'Root', 'order' => 0]);
    $child = Post::create(['name' => 'Child', 'order' => 0, 'parent_id' => $root->id]);

    // Promote child to root level.
    $newOrder = [
        ['id' => $root->id, 'name' => 'Root', 'children' => []],
        ['id' => $child->id, 'name' => 'Child', 'children' => []],
    ];

    livewire(PlainModelTreePage::class)
        ->call('saveTreeOrder', $newOrder);

    expect($child->refresh()->parent_id)->toBeNull();
});

it('refreshes plain model nodes on tree-refresh event', function () {
    Post::create(['name' => 'Initial', 'order' => 0]);

    $component = livewire(PlainModelTreePage::class);
    expect($component->get('treeNodes'))->toHaveCount(1);

    Post::create(['name' => 'Added Later', 'order' => 1]);
    $component->dispatch('tree-refresh');

    expect($component->get('treeNodes'))->toHaveCount(2);
});
