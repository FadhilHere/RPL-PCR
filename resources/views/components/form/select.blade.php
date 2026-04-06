@props([
    'options' => [],
    'placeholder' => null,
])

@php
    $wireModel = $attributes->wire('model');
    $modelName = $wireModel->value();
    $isLive = $wireModel->hasModifier('live');
@endphp

<div
    x-data="{
        open: false,
        value: $wire.entangle('{{ $modelName }}', {{ $isLive ? 'true' : 'false' }}),
        options: {{ Js::from($options) }},
        hasPlaceholder: {{ $placeholder ? 'true' : 'false' }},
        placeholderText: {{ Js::from($placeholder ?? 'Pilih...') }},
        rect: { top: 0, bottom: 0, left: 0, width: 0 },
        get isBlank() {
            return this.value === '' || this.value === null || this.value === undefined;
        },
        get label() {
            const found = this.options[this.value] ?? this.options[String(this.value)];
            if (found !== undefined) return found;
            if (this.isBlank) return this.placeholderText;
            return this.value;
        },
        get showMuted() {
            if (!this.isBlank) return false;
            return this.hasPlaceholder || !('' in this.options);
        },
        openDropdown() {
            this.rect = this.$refs.btn.getBoundingClientRect();
            this.open = true;
        },
        select(val) {
            this.value = val;
            this.open = false;
        }
    }"
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
            <div class="py-1 max-h-[220px] overflow-y-auto">
                @if ($placeholder)
                <button type="button" @click="select('')"
                        class="w-full text-left px-3 py-2 text-[13px] transition-colors"
                        :class="isBlank
                            ? 'bg-[#E8F4F8] text-[#004B5F] font-semibold'
                            : 'text-[#8a9ba8] hover:bg-[#F4F6F8]'"
                >
                    {{ $placeholder }}
                </button>
                @endif
                @foreach ($options as $val => $label)
                <button type="button" @click="select({{ Js::from($val) }})"
                        class="w-full text-left px-3 py-2 text-[13px] transition-colors"
                        :class="String(value) === {{ Js::from((string)$val) }}
                            ? 'bg-[#E8F4F8] text-[#004B5F] font-semibold'
                            : 'text-[#1a2a35] hover:bg-[#F4F6F8]'"
                >
                    {{ $label }}
                </button>
                @endforeach
            </div>
        </div>
    </template>
</div>
