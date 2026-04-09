@props([
    'options' => [],
    'placeholder' => null,
    'searchable' => false,
    'searchPlaceholder' => 'Cari opsi...',
])

@php
    $wireModel = $attributes->wire('model');
    $modelName = $wireModel->value();
    $isLive = $wireModel->hasModifier('live');
    $valueBinding = $modelName
        ? '$wire.entangle(' . Js::from($modelName) . ', ' . ($isLive ? 'true' : 'false') . ')'
        : "''";
    $optionItems = collect($options)
        ->map(fn($label, $value) => [
            'value' => $value,
            'key' => (string) $value,
            'label' => $label,
        ])
        ->values();
@endphp

<div
    x-data="{
        open: false,
        value: {!! $valueBinding !!},
        items: {{ Js::from($optionItems) }},
        hasPlaceholder: {{ $placeholder ? 'true' : 'false' }},
        placeholderText: {{ Js::from($placeholder ?? 'Pilih...') }},
        searchable: {{ $searchable ? 'true' : 'false' }},
        searchPlaceholder: {{ Js::from($searchPlaceholder) }},
        searchTerm: '',
        rect: { top: 0, bottom: 0, left: 0, width: 0 },
        get isBlank() {
            return this.value === '' || this.value === null || this.value === undefined;
        },
        get label() {
            const selected = this.items.find((item) => item.key === String(this.value));
            if (selected) return selected.label;
            if (this.isBlank) return this.placeholderText;
            return this.value;
        },
        get showMuted() {
            if (!this.isBlank) return false;
            return this.hasPlaceholder || !this.items.some((item) => item.key === '');
        },
        get filteredItems() {
            if (!this.searchable || this.searchTerm.trim() === '') {
                return this.items;
            }

            const keyword = this.searchTerm.toLowerCase();
            return this.items.filter((item) => String(item.label).toLowerCase().includes(keyword));
        },
        openDropdown() {
            this.rect = this.$refs.btn.getBoundingClientRect();
            this.searchTerm = '';
            this.open = true;
        },
        select(val) {
            this.value = val;
            this.searchTerm = '';
            this.open = false;
        },
        selectPlaceholder() {
            this.value = '';
            this.searchTerm = '';
            this.open = false;
        }
    }"
    x-modelable="value"
    @click.outside="open = false"
    @keydown.escape.window="open = false"
    {{ $attributes->whereDoesntStartWith('wire:')->merge(['class' => 'relative']) }}
>
    {{-- Trigger --}}
    <button type="button" x-ref="btn"
            @click="open ? (open = false) : openDropdown()"
            class="w-full h-[42px] px-3 text-[13px] text-left bg-white border rounded-xl outline-none transition-all duration-150 flex items-center justify-between gap-2 cursor-pointer"
            :class="open
                ? 'border-[#004B5F] ring-2 ring-[#004B5F]/10'
                : 'border-[#E0E5EA] hover:border-[#C5CDD5]'"
    >
        <span x-text="label"
              class="truncate"
              :class="showMuted ? 'text-[#b0bec5]' : 'text-[#1a2a35]'"
        ></span>
        <svg class="w-4 h-4 text-[#8a9ba8] shrink-0 transition-transform duration-200"
             :class="open && 'rotate-180'"
             viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <polyline points="6 9 12 15 18 9"/>
        </svg>
    </button>

    {{-- Dropdown Panel — teleport ke body agar lolos dari overflow-hidden parent --}}
    <template x-teleport="body">
        <div x-show="open" x-cloak
             @click.stop
             :style="`position:fixed;top:${rect.bottom+4}px;left:${rect.left}px;width:${rect.width}px;z-index:9999`"
             x-transition:enter="transition ease-out duration-100"
             x-transition:enter-start="opacity-0 -translate-y-1"
             x-transition:enter-end="opacity-100 translate-y-0"
             x-transition:leave="transition ease-in duration-75"
             x-transition:leave-start="opacity-100 translate-y-0"
             x-transition:leave-end="opacity-0 -translate-y-1"
             class="bg-white border border-[#E0E5EA] rounded-xl shadow-lg overflow-hidden"
        >
            <template x-if="searchable">
                <div class="px-2.5 pt-2 pb-1 border-b border-[#F0F2F5]">
                    <input type="text"
                           x-model.debounce.300ms="searchTerm"
                           :placeholder="searchPlaceholder"
                           class="w-full h-[34px] px-3 text-[12px] text-[#1a2a35] bg-white border border-[#E0E5EA] rounded-lg outline-none focus:border-[#004B5F] focus:ring-2 focus:ring-[#004B5F]/10 placeholder:text-[#b0bec5]" />
                </div>
            </template>

            <div class="py-1 max-h-[220px] overflow-y-auto">
                @if ($placeholder)
                <button type="button" @click="selectPlaceholder()"
                        class="w-full text-left px-3 py-2 text-[13px] transition-colors"
                        :class="isBlank
                            ? 'bg-[#E8F4F8] text-[#004B5F] font-semibold'
                            : 'text-[#8a9ba8] hover:bg-[#F4F6F8]'"
                >
                    {{ $placeholder }}
                </button>
                @endif

                <template x-for="item in filteredItems" :key="item.key">
                    <button type="button" @click="select(item.value)"
                            class="w-full text-left px-3 py-2 text-[13px] transition-colors"
                            :class="String(value) === item.key
                                ? 'bg-[#E8F4F8] text-[#004B5F] font-semibold'
                                : 'text-[#1a2a35] hover:bg-[#F4F6F8]'"
                            x-text="item.label">
                    </button>
                </template>

                <div x-show="filteredItems.length === 0"
                        class="w-full text-left px-3 py-2 text-[13px] transition-colors"
                        style="display:none"
                        :class="'text-[#8a9ba8]'"
                >
                    Tidak ada opsi yang cocok.
                </div>
            </div>
        </div>
    </template>
</div>
