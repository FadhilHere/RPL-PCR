@props(['berkaslist'])

<div x-data="{ viewer: { open: false, url: '', type: '', name: '' } }"
     @open-berkas-viewer.window="viewer.open = true; viewer.url = $event.detail.url; viewer.type = $event.detail.type; viewer.name = $event.detail.name"
     class="bg-white rounded-xl border border-[#E5E8EC] overflow-hidden mb-5">
    <div class="flex items-center justify-between px-5 py-3.5 border-b border-[#F0F2F5]">
        <span class="text-[13px] font-semibold text-[#1a2a35]">Berkas Pendukung Peserta</span>
        <span class="text-[11px] text-[#8a9ba8]">{{ $berkaslist->count() }} berkas</span>
    </div>
    @if ($berkaslist->isEmpty())
    <div class="py-6 text-center text-[12px] text-[#8a9ba8]">Peserta belum mengunggah berkas pendukung.</div>
    @else
    <div class="divide-y divide-[#F6F8FA]">
        @foreach ($berkaslist as $berkas)
        @php $vt = in_array(strtolower(pathinfo($berkas->berkas, PATHINFO_EXTENSION)), ['jpg','jpeg','png']) ? 'image' : 'pdf'; @endphp
        <div class="flex items-center gap-3.5 px-5 py-3" wire:key="berkas-{{ $berkas->id }}">
            <div class="w-8 h-8 rounded-lg bg-[#E8F4F8] flex items-center justify-center shrink-0">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="#004B5F" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V8z"/>
                    <polyline points="14 2 14 8 20 8"/>
                </svg>
            </div>
            <div class="flex-1 min-w-0">
                <div class="text-[12px] font-medium text-[#1a2a35] truncate">{{ $berkas->nama_dokumen }}</div>
                <div class="text-[11px] text-[#8a9ba8]">{{ $berkas->jenis_dokumen->label() }}</div>
            </div>
            <div class="flex items-center gap-1 shrink-0">
                <button @click="viewer = { open: true, url: '{{ route('berkas.view', $berkas->id) }}', type: '{{ $vt }}', name: '{{ addslashes($berkas->nama_dokumen) }}' }"
                        class="w-[28px] h-[28px] rounded-md border border-[#D0D5DD] text-[#5a6a75] hover:border-primary hover:text-primary hover:bg-[#E8F4F8] transition-colors flex items-center justify-center">
                    <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
                </button>
                <a href="{{ route('berkas.download', $berkas->id) }}"
                   class="w-[28px] h-[28px] rounded-md border border-[#D0D5DD] text-[#5a6a75] hover:border-primary hover:text-primary hover:bg-[#E8F4F8] transition-colors flex items-center justify-center no-underline">
                    <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15v4a2 2 0 01-2 2H5a2 2 0 01-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
                </a>
            </div>
        </div>
        @endforeach
    </div>
    @endif

    {{-- Modal Viewer --}}
    <div x-show="viewer.open"
         x-cloak
         x-transition.opacity
         class="fixed inset-0 z-[60] flex items-center justify-center bg-black/60"
         @click.self="viewer.open = false; viewer.url = ''">
        <div class="bg-white rounded-2xl shadow-2xl w-full max-w-3xl mx-4 flex flex-col overflow-hidden" style="max-height: 90vh">
            <div class="flex items-center justify-between px-5 py-3.5 border-b border-[#F0F2F5] shrink-0">
                <span x-text="viewer.name" class="text-[13px] font-semibold text-[#1a2a35] truncate max-w-[60%]"></span>
                <div class="flex items-center gap-3">
                    <a :href="viewer.url.replace('/view', '/download')"
                       class="inline-flex items-center gap-1.5 text-[11px] font-semibold text-primary hover:text-[#005f78] transition-colors no-underline">
                        <svg class="w-3.5 h-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M21 15v4a2 2 0 01-2 2H5a2 2 0 01-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/>
                        </svg>
                        Unduh
                    </a>
                    <button @click="viewer.open = false; viewer.url = ''"
                            class="text-[#8a9ba8] hover:text-[#1a2a35] transition-colors p-1">
                        <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/>
                        </svg>
                    </button>
                </div>
            </div>
            <div class="flex-1 min-h-0 bg-[#F0F2F5]" style="height: 75vh">
                <template x-if="viewer.type === 'pdf'">
                    <iframe :src="viewer.url" class="w-full border-0" style="height: 75vh"></iframe>
                </template>
                <template x-if="viewer.type === 'image'">
                    <div class="flex items-center justify-center p-6 min-h-full">
                        <img :src="viewer.url" class="max-w-full max-h-full object-contain rounded-lg shadow" />
                    </div>
                </template>
            </div>
        </div>
    </div>
</div>
