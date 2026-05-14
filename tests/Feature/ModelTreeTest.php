<?php

use SolutionForest\FilamentNestableTree\Tests\Fixtures\Models\Category;
use SolutionForest\FilamentNestableTree\Tests\Fixtures\Models\User;
use SolutionForest\FilamentNestableTree\Tests\Fixtures\Pages\CategoryTreePage;

use function Pest\Livewire\livewire;

beforeEach(function () {
    $this->actingAs(User::factory()->create());
});

it('renders the category tree page without errors', function () {
    livewire(CategoryTreePage::class)
        ->assertSuccessful();
});

it('category tree page loads nested nodes from model', function () {
    $root = Category::create(['title' => 'Root']);
    $child = Category::create(['title' => 'Child']);
    $child->appendToNode($root)->save();

    $component = livewire(CategoryTreePage::class);
    $nodes = $component->get('treeNodes');

    // Root node is at the top level.
    expect($nodes)->toHaveCount(1)
        ->and($nodes[0]['title'])->toBe('Root')
        ->and($nodes[0]['children'])->toHaveCount(1)
        ->and($nodes[0]['children'][0]['title'])->toBe('Child');
});

it('category tree page loads a flat list of root categories', function () {
    Category::create(['title' => 'Electronics']);
    Category::create(['title' => 'Clothing']);

    $component = livewire(CategoryTreePage::class);
    $nodes = $component->get('treeNodes');
    $titles = array_column($nodes, 'title');

    expect($nodes)->toHaveCount(2)
        ->and($titles)->toContain('Electronics')
        ->and($titles)->toContain('Clothing');
});

it('saveTreeOrder calls rebuildTree and persists order', function () {
    $a = Category::create(['title' => 'A']);
    $b = Category::create(['title' => 'B']);
    $c = Category::create(['title' => 'C']);

    // Reorder to: C (with A as child), B.
    $newOrder = [
        ['id' => $c->id, 'title' => 'C', 'children' => [
            ['id' => $a->id, 'title' => 'A', 'children' => []],
        ]],
        ['id' => $b->id, 'title' => 'B', 'children' => []],
    ];

    livewire(CategoryTreePage::class)
        ->call('saveTreeOrder', $newOrder)
        ->assertDispatched('tree-order-saved');

    // After rebuildTree(), A should have C as its parent.
    $a->refresh();
    expect((int) $a->parent_id)->toBe($c->id);
});

it('refreshes category nodes on tree-refresh event', function () {
    Category::create(['title' => 'Initial']);

    $component = livewire(CategoryTreePage::class);
    expect($component->get('treeNodes'))->toHaveCount(1);

    // Add a new category outside the component (simulates a CreateAction).
    Category::create(['title' => 'Added Later']);

    $component->dispatch('tree-refresh');

    expect($component->get('treeNodes'))->toHaveCount(2);
});
