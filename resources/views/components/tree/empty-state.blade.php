<template x-if="flattenedVisibleNodes.length === 0">
    <div class="fi-tree-empty-state">
        <p x-text="searchQuery ? '{{ __('filament-nestable-tree::messages.empty_no_results') }}' : '{{ __('filament-nestable-tree::messages.empty_no_nodes') }}'"></p>
    </div>
</template>
