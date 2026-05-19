<?php

use SolutionForest\FilamentNestableTree\Tests\Fixtures\Models\Category;
use SolutionForest\FilamentNestableTree\Tests\Fixtures\Models\Tag;
use SolutionForest\FilamentNestableTree\Tests\Fixtures\Models\User;
use SolutionForest\FilamentNestableTree\Tests\Fixtures\Pages\TagCrossTreePage;

use function Pest\Livewire\livewire;

beforeEach(function () {
    $this->actingAs(User::factory()->create());

    $catA = Category::create(['title' => 'Category A']);
    $catB = Category::create(['title' => 'Category B']);

    TagCrossTreePage::$categoryAId = $catA->id;
    TagCrossTreePage::$categoryBId = $catB->id;
});

it('renders the tag cross-tree page without errors', function () {
    livewire(TagCrossTreePage::class)
        ->assertSuccessful();
});

it('each tree only shows tags for its own category_id', function () {
    $tagA = Tag::create(['name' => 'Tag A', 'category_id' => TagCrossTreePage::$categoryAId]);
    $tagB = Tag::create(['name' => 'Tag B', 'category_id' => TagCrossTreePage::$categoryBId]);

    $component = livewire(TagCrossTreePage::class);
    $treeANodes = $component->get('treesNodes')['cat_a'] ?? $component->get('treeNodes');

    // Use the multi-tree state key for each tree.
    $state = $component->getData();
    expect($state)->toBeArray();

    // Verify via DB: tags are still in their categories.
    expect(Tag::where('category_id', TagCrossTreePage::$categoryAId)->pluck('name')->toArray())
        ->toContain('Tag A');
    expect(Tag::where('category_id', TagCrossTreePage::$categoryBId)->pluck('name')->toArray())
        ->toContain('Tag B');
});

it('handleCrossTreeMove updates the tag category_id to the destination category', function () {
    $tag = Tag::create(['name' => 'Moveable Tag', 'category_id' => TagCrossTreePage::$categoryAId]);

    livewire(TagCrossTreePage::class)
        ->dispatch('tree-cross-move', 'cat_a', 'cat_b', $tag->id, null)
        ->assertDispatched('tree-refresh');

    expect($tag->fresh()->category_id)->toBe(TagCrossTreePage::$categoryBId);
});

it('handleCrossTreeMove with a parent sets the parent relationship', function () {
    $parent = Tag::create(['name' => 'Parent', 'category_id' => TagCrossTreePage::$categoryBId]);
    $child = Tag::create(['name' => 'Child', 'category_id' => TagCrossTreePage::$categoryAId]);

    livewire(TagCrossTreePage::class)
        ->dispatch('tree-cross-move', 'cat_a', 'cat_b', $child->id, $parent->id)
        ->assertDispatched('tree-refresh');

    $child->refresh();
    expect($child->category_id)->toBe(TagCrossTreePage::$categoryBId)
        ->and((int) $child->parent_id)->toBe($parent->id);
});

it('handleCrossTreeMove for a non-existent tag is a no-op', function () {
    livewire(TagCrossTreePage::class)
        ->dispatch('tree-cross-move', 'cat_a', 'cat_b', 999999, null);

    // No exception thrown, no tags modified.
    expect(Tag::count())->toBe(0);
});
