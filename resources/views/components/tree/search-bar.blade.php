<div {{ $attributes->class(['fi-tree-search']) }}>
    <x-filament::input.wrapper
        prefix-icon="heroicon-m-magnifying-glass"
        prefix-icon-alias="panels::global-search.field"
        inline-prefix
    >
        <x-filament::input
            autocomplete="off"
            inline-prefix
            maxlength="1000"
            type="search"
            x-model="searchQuery"
            placeholder="{{ __('filament-nestable-tree::messages.search_placeholder') }}"
        />
    </x-filament::input.wrapper>
</div>
