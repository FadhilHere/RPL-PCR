{{-- Modal Tambah Mata Kuliah --}}
@if ($canEditMk && $mkTersedia->isNotEmpty())
<div x-show="tambahMkOpen" style="display:none"
     class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 p-4"
     x-transition:enter="transition ease-out duration-150" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
     x-transition:leave="transition ease-in duration-100" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0"
     @click.self="tambahMkOpen = false">
    <div x-show="tambahMkOpen"
         x-transition:enter="transition ease-out duration-150" x-transition:enter-start="opacity-0 scale-95" x-transition:enter-end="opacity-100 scale-100"
         x-transition:leave="transition ease-in duration-100" x-transition:leave-start="opacity-100 scale-100" x-transition:leave-end="opacity-0 scale-95"
         class="bg-white rounded-2xl shadow-xl w-full max-w-md flex flex-col" style="max-height:80vh">
        <div class="flex items-center justify-between px-6 pt-5 pb-4 border-b border-[#F0F2F5] shrink-0">
            <h3 class="text-[15px] font-semibold text-[#1a2a35]">Tambah Mata Kuliah</h3>
            <button @click="tambahMkOpen = false" class="text-[#8a9ba8] hover:text-[#1a2a35] p-1 transition-colors">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round">
                    <line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/>
                </svg>
            </button>
        </div>
        <div class="px-6 py-4 overflow-y-auto flex-1">
            @php $grouped = $mkTersedia->groupBy('semester'); @endphp
            @foreach ($grouped as $sem => $items)
            <div class="mb-4">
                <div class="text-[10px] font-semibold text-[#8a9ba8] uppercase tracking-[0.5px] mb-2">Semester {{ $sem }}</div>
                <div class="space-y-1.5">
                    @foreach ($items as $mk)
                    <div class="flex items-center justify-between px-3.5 py-2.5 rounded-xl border border-[#E0E5EA] hover:border-primary/40 transition-colors">
                        <div>
                            <span class="text-[10px] font-semibold text-primary bg-[#E8F4F8] px-[6px] py-[2px] rounded mr-2">{{ $mk->kode }}</span>
                            <span class="text-[12px] text-[#1a2a35]">{{ $mk->nama }}</span>
                            <span class="text-[11px] text-[#8a9ba8] ml-1.5">{{ $mk->sks }} SKS</span>
                        </div>
                        <button wire:click="tambahMk({{ $mk->id }})"
                                wire:loading.attr="disabled" wire:target="tambahMk({{ $mk->id }})"
                                class="text-[11px] font-semibold text-primary border border-primary px-2.5 py-1 rounded-lg hover:bg-[#E8F4F8] transition-colors shrink-0 ml-3">
                            <span wire:loading.remove wire:target="tambahMk({{ $mk->id }})">Tambah</span>
                            <span wire:loading wire:target="tambahMk({{ $mk->id }})">...</span>
                        </button>
                    </div>
                    @endforeach
                </div>
            </div>
            @endforeach
        </div>
    </div>
</div>
@endif
