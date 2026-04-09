@props([
    'placeholder' => 'Pilih tanggal...',
    'enableTime'  => true,
])

@php
    $wireModel = $attributes->wire('model');
    $modelName = $wireModel->value();
    $isLive = $wireModel->hasModifier('live');
@endphp

<div
    x-data="{
        value: {{ $modelName ? '$wire.entangle(\'' . $modelName . '\', ' . ($isLive ? 'true' : 'false') . ')' : '""' }},
        open: false,
        viewYear: new Date().getFullYear(),
        viewMonth: new Date().getMonth(),
        selYear: null, selMonth: null, selDay: null,
        selHour: 8, selMinute: 0,
        rect: { top: 0, bottom: 0, left: 0, width: 0 },
        enableTime: {{ $enableTime ? 'true' : 'false' }},
        yearPickerOpen: false,

        get years() {
            const cur = new Date().getFullYear();
            const list = [];
            for (let y = cur; y >= cur - 100; y--) list.push(y);
            return list;
        },

        get daysInGrid() {
            const firstDow = new Date(this.viewYear, this.viewMonth, 1).getDay();
            const dim     = new Date(this.viewYear, this.viewMonth + 1, 0).getDate();
            const prevDim = new Date(this.viewYear, this.viewMonth, 0).getDate();
            const cells   = [];
            let py = this.viewYear, pm = this.viewMonth - 1;
            if (pm < 0) { pm = 11; py--; }
            for (let i = firstDow - 1; i >= 0; i--)
                cells.push({ y: py, m: pm, d: prevDim - i, cur: false });
            for (let d = 1; d <= dim; d++)
                cells.push({ y: this.viewYear, m: this.viewMonth, d: d, cur: true });
            let ny = this.viewYear, nm = this.viewMonth + 1, nd = 1;
            if (nm > 11) { nm = 0; ny++; }
            while (cells.length < 42)
                cells.push({ y: ny, m: nm, d: nd++, cur: false });
            return cells;
        },

        get monthLabel() {
            const names = ['Januari','Februari','Maret','April','Mei','Juni',
                           'Juli','Agustus','September','Oktober','November','Desember'];
            return names[this.viewMonth] + ' ' + this.viewYear;
        },

        get displayLabel() {
            if (this.selDay === null) return '';
            const names = ['Jan','Feb','Mar','Apr','Mei','Jun','Jul','Agu','Sep','Okt','Nov','Des'];
            const d = String(this.selDay).padStart(2,'0');
            const label = d + ' ' + names[this.selMonth] + ' ' + this.selYear;
            if (!this.enableTime) return label;
            return label + ', ' + String(this.selHour).padStart(2,'0') + ':' + String(this.selMinute).padStart(2,'0');
        },

        isSelected(y, m, d) {
            return this.selDay !== null && this.selYear === y && this.selMonth === m && this.selDay === d;
        },

        isToday(y, m, d) {
            const t = new Date();
            return t.getFullYear() === y && t.getMonth() === m && t.getDate() === d;
        },

        prevMonth() {
            if (this.viewMonth === 0) { this.viewMonth = 11; this.viewYear--; }
            else this.viewMonth--;
        },

        nextMonth() {
            if (this.viewMonth === 11) { this.viewMonth = 0; this.viewYear++; }
            else this.viewMonth++;
        },

        selectYear(y) {
            this.viewYear = y;
            this.yearPickerOpen = false;
            this.$nextTick(() => {
                const el = this.$refs.yearList?.querySelector('[data-selected]');
                if (el) el.scrollIntoView({ block: 'center' });
            });
        },

        selectDay(y, m, d) {
            let ry = y, rm = m;
            if (rm < 0)  { ry--; rm = 11; }
            if (rm > 11) { ry++; rm = 0; }
            this.selYear = ry; this.selMonth = rm; this.selDay = d;
            this.viewYear = ry; this.viewMonth = rm;
            this.buildValue();
            if (!this.enableTime) this.open = false;
        },

        buildValue() {
            if (this.selDay === null) { this.value = ''; return; }
            const y   = String(this.selYear).padStart(4,'0');
            const m   = String(this.selMonth + 1).padStart(2,'0');
            const d   = String(this.selDay).padStart(2,'0');
            const h   = String(this.selHour).padStart(2,'0');
            const min = String(this.selMinute).padStart(2,'0');
            this.value = this.enableTime ? `${y}-${m}-${d}T${h}:${min}` : `${y}-${m}-${d}`;
        },

        parseValue(v) {
            if (!v) { this.selDay = null; return; }
            const str  = v.includes('T') ? v : v + 'T00:00';
            const date = new Date(str);
            if (isNaN(date.getTime())) return;
            this.selYear  = date.getFullYear();
            this.selMonth = date.getMonth();
            this.selDay   = date.getDate();
            this.selHour  = date.getHours();
            this.selMinute = date.getMinutes();
            this.viewYear  = this.selYear;
            this.viewMonth = this.selMonth;
        },

        openPicker() {
            this.rect = this.$refs.trigger.getBoundingClientRect();
            this.yearPickerOpen = false;
            this.open = true;
        },

        init() {
            this.$nextTick(() => { if (this.value) this.parseValue(this.value); });
            this.$watch('value', v => { this.parseValue(v); });
        }
    }"
    x-modelable="value"
    @click.outside="open = false; yearPickerOpen = false"
    @keydown.escape.window="open = false; yearPickerOpen = false"
    @scroll.window.passive="open = false"
    {{ $attributes->whereDoesntStartWith('wire:')->merge(['class' => 'relative']) }}
>
    {{-- Trigger --}}
    <button type="button" x-ref="trigger"
            @click="open ? (open = false) : openPicker()"
            class="w-full h-[38px] px-3 text-[13px] text-left bg-white border rounded-lg outline-none transition-all duration-150 flex items-center gap-2 cursor-pointer"
            :class="open ? 'border-primary ring-2 ring-primary/10' : 'border-[#E0E5EA] hover:border-[#C5CDD5]'"
    >
        <svg class="w-3.5 h-3.5 text-[#8a9ba8] shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <rect x="3" y="4" width="18" height="18" rx="2" ry="2"/>
            <line x1="16" y1="2" x2="16" y2="6"/>
            <line x1="8" y1="2" x2="8" y2="6"/>
            <line x1="3" y1="10" x2="21" y2="10"/>
        </svg>
        <span class="flex-1 truncate"
              :class="displayLabel ? 'text-[#1a2a35]' : 'text-[#b0bec5]'"
              x-text="displayLabel || '{{ $placeholder }}'">
        </span>
        <svg class="w-4 h-4 text-[#8a9ba8] shrink-0 transition-transform duration-200"
             :class="open && 'rotate-180'"
             viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <polyline points="6 9 12 15 18 9"/>
        </svg>
    </button>

    {{-- Popup — teleport ke body agar lolos overflow-hidden --}}
    <template x-teleport="body">
        <div x-show="open" x-cloak
             @click.stop
             :style="`position:fixed;top:${rect.bottom+4}px;left:${rect.left}px;width:256px;z-index:9999`"
             x-transition:enter="transition ease-out duration-100"
             x-transition:enter-start="opacity-0 -translate-y-1"
             x-transition:enter-end="opacity-100 translate-y-0"
             x-transition:leave="transition ease-in duration-75"
             x-transition:leave-start="opacity-100 translate-y-0"
             x-transition:leave-end="opacity-0 -translate-y-1"
             class="bg-white border border-[#E0E5EA] rounded-xl shadow-lg overflow-hidden select-none">

            {{-- Month/Year navigation --}}
            <div class="flex items-center justify-between px-2 pt-2.5 pb-1">
                <button type="button" @click.stop="prevMonth()" x-show="!yearPickerOpen"
                        class="w-7 h-7 rounded-lg flex items-center justify-center text-[#5a6a75] hover:bg-[#F0F2F5] hover:text-[#1a2a35] transition-colors">
                    <svg class="w-3.5 h-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                        <polyline points="15 18 9 12 15 6"/>
                    </svg>
                </button>
                <button type="button" @click.stop="yearPickerOpen = !yearPickerOpen; if(yearPickerOpen) $nextTick(() => { const el = $refs.yearList?.querySelector('[data-selected]'); if(el) el.scrollIntoView({ block: 'center' }); })"
                        class="flex-1 text-[12px] font-semibold text-[#1a2a35] hover:bg-[#F0F2F5] rounded-lg px-2 py-1 flex items-center justify-center gap-1.5 transition-colors">
                    <span x-text="monthLabel"></span>
                    <svg class="w-3 h-3 text-[#8a9ba8] transition-transform duration-150" :class="yearPickerOpen && 'rotate-180'"
                         viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                        <polyline points="6 9 12 15 18 9"/>
                    </svg>
                </button>
                <button type="button" @click.stop="nextMonth()" x-show="!yearPickerOpen"
                        class="w-7 h-7 rounded-lg flex items-center justify-center text-[#5a6a75] hover:bg-[#F0F2F5] hover:text-[#1a2a35] transition-colors">
                    <svg class="w-3.5 h-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                        <polyline points="9 18 15 12 9 6"/>
                    </svg>
                </button>
            </div>

            {{-- Year Picker --}}
            <div x-show="yearPickerOpen" x-cloak
                 x-ref="yearList"
                 class="max-h-[210px] overflow-y-auto border-t border-[#F0F2F5] py-1">
                <template x-for="y in years" :key="y">
                    <button type="button"
                            @click.stop="selectYear(y)"
                            :data-selected="y === viewYear ? '' : null"
                            :class="y === viewYear
                                ? 'bg-primary text-white font-semibold'
                                : (y === new Date().getFullYear()
                                    ? 'text-primary font-semibold hover:bg-[#E8F4F8]'
                                    : 'text-[#1a2a35] hover:bg-[#F4F6F8]')"
                            class="w-full px-4 py-1.5 text-[12px] text-left transition-colors"
                            x-text="y">
                    </button>
                </template>
            </div>

            {{-- Calendar (hidden when year picker open) --}}
            <div x-show="!yearPickerOpen">
                {{-- Day-of-week headers --}}
                <div class="grid grid-cols-7 px-2 pb-0.5">
                    @foreach (['Min','Sen','Sel','Rab','Kam','Jum','Sab'] as $dow)
                    <div class="text-center text-[9px] font-semibold text-[#8a9ba8] py-0.5">{{ $dow }}</div>
                    @endforeach
                </div>

                {{-- Day grid --}}
                <div class="grid grid-cols-7 px-2 pb-2">
                    <template x-for="(cell, i) in daysInGrid" :key="i">
                        <button type="button"
                                @click.stop="selectDay(cell.y, cell.m, cell.d)"
                                class="w-full h-8 flex items-center justify-center text-[11px] rounded-lg transition-colors leading-none"
                                :class="{
                                    'bg-primary text-white font-semibold hover:bg-[#004B5F]/80': isSelected(cell.y, cell.m, cell.d),
                                    'ring-1 ring-primary text-primary font-semibold hover:bg-[#E8F4F8]': isToday(cell.y, cell.m, cell.d) && !isSelected(cell.y, cell.m, cell.d),
                                    'text-[#c5cdd5] hover:bg-[#F4F6F8]': !cell.cur && !isSelected(cell.y, cell.m, cell.d),
                                    'text-[#1a2a35] hover:bg-[#E8F4F8]': cell.cur && !isSelected(cell.y, cell.m, cell.d) && !isToday(cell.y, cell.m, cell.d),
                                }"
                                x-text="cell.d">
                        </button>
                    </template>
                </div>
            </div>

            @if ($enableTime)
            {{-- Time picker --}}
            <div class="border-t border-[#F0F2F5] px-4 py-2.5 flex items-center justify-center gap-3">
                {{-- Hour --}}
                <div class="flex flex-col items-center gap-0.5">
                    <button type="button" @click.stop="selHour = (selHour + 1) % 24; buildValue()"
                            class="w-6 h-5 flex items-center justify-center text-[#8a9ba8] hover:text-primary transition-colors">
                        <svg class="w-3.5 h-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                            <polyline points="18 15 12 9 6 15"/>
                        </svg>
                    </button>
                    <span x-text="String(selHour).padStart(2,'0')"
                          class="text-[15px] font-semibold text-[#1a2a35] w-9 text-center tabular-nums"></span>
                    <button type="button" @click.stop="selHour = (selHour + 23) % 24; buildValue()"
                            class="w-6 h-5 flex items-center justify-center text-[#8a9ba8] hover:text-primary transition-colors">
                        <svg class="w-3.5 h-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                            <polyline points="6 9 12 15 18 9"/>
                        </svg>
                    </button>
                </div>

                <span class="text-[18px] font-semibold text-[#5a6a75] leading-none pb-0.5">:</span>

                {{-- Minute --}}
                <div class="flex flex-col items-center gap-0.5">
                    <button type="button" @click.stop="selMinute = (selMinute + 5) % 60; buildValue()"
                            class="w-6 h-5 flex items-center justify-center text-[#8a9ba8] hover:text-primary transition-colors">
                        <svg class="w-3.5 h-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                            <polyline points="18 15 12 9 6 15"/>
                        </svg>
                    </button>
                    <span x-text="String(selMinute).padStart(2,'0')"
                          class="text-[15px] font-semibold text-[#1a2a35] w-9 text-center tabular-nums"></span>
                    <button type="button" @click.stop="selMinute = (selMinute + 55) % 60; buildValue()"
                            class="w-6 h-5 flex items-center justify-center text-[#8a9ba8] hover:text-primary transition-colors">
                        <svg class="w-3.5 h-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                            <polyline points="6 9 12 15 18 9"/>
                        </svg>
                    </button>
                </div>

                <span class="text-[11px] font-medium text-[#8a9ba8] self-center ml-1">WIB</span>
            </div>
            @endif

        </div>
    </template>
</div>
