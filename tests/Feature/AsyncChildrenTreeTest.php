<?php

use SolutionForest\FilamentNestableTree\Tests\Fixtures\Models\Category;
use SolutionForest\FilamentNestableTree\Tests\Fixtures\Models\User;
use SolutionForest\FilamentNestableTree\Tests\Fixtures\Pages\AsyncChildrenTreePage;

use function Pest\Livewire\livewire;

beforeEach(function () {
    $this->actingAs(User::factory()->create());
});

it('renders the async-children tree page without errors', function () {
    livewire(AsyncChildrenTreePage::class)
        ->assertSuccessful();
});

it('initial load returns only root nodes when asyncChildren is enabled', function () {
    $root1 = Category::create(['title' => 'Root 1']);
    $root2 = Category::create(['title' => 'Root 2']);
    $child = Category::create(['title' => 'Child']);
    $child->appendToNode($root1)->save();

    $nodes = livewire(AsyncChildrenTreePage::class)->get('treeNodes');

    // Only root nodes should be returned; children are NOT embedded.
    expect($nodes)->toHaveCount(2);

    $titles = array_column($nodes, 'title');
    expect($titles)->toContain('Root 1')
        ->and($titles)->toContain('Root 2')
        ->and($titles)->not->toContain('Child');

    // Root nodes must NOT have nested children embedded.
    foreach ($nodes as $node) {
        expect($node)->not->toHaveKey('children');
    }
});

it('loadChildren returns immediate children of the given parent node', function () {
    $root = Category::create(['title' => 'Root']);
    $child1 = Category::create(['title' => 'Child 1']);
    $child2 = Category::create(['title' => 'Child 2']);
    $child1->appendToNode($root)->save();
    $child2->appendToNode($root)->save();

    // Call loadChildren directly on the component instance to get the return value.
    $children = livewire(AsyncChildrenTreePage::class)->instance()->loadChildren($root->id);

    $titles = array_column($children, 'title');

    expect($children)->toHaveCount(2)
        ->and($titles)->toContain('Child 1')
        ->and($titles)->toContain('Child 2');
});

it('loadChildren returns an empty array for a leaf node', function () {
    $root = Category::create(['title' => 'Root']);

    $children = livewire(AsyncChildrenTreePage::class)->instance()->loadChildren($root->id);

    expect($children)->toHaveCount(0);
});

it('loadChildren does not return grandchildren', function () {
    $root = Category::create(['title' => 'Root']);
    $child = Category::create(['title' => 'Child']);
    $grandchild = Category::create(['title' => 'Grandchild']);
    $child->appendToNode($root)->save();
    $grandchild->appendToNode($child)->save();

    // Only the direct child; grandchild is not included.
    $children = livewire(AsyncChildrenTreePage::class)->instance()->loadChildren($root->id);

    expect($children)->toHaveCount(1)
        ->and($children[0]['title'])->toBe('Child');
});
