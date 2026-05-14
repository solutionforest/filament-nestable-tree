<template x-if="flattenedVisibleNodes.length === 0">
    <div class="fi-tree-empty-state">
        <p x-text="searchQuery ? '{{ __('No nodes match your search.') }}' : '{{ __('No nodes found.') }}'"></p>
    </div>
</template>
