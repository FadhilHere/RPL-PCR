{{-- Verifikasi Bersama (asesor view — read-only jadwal) --}}
@php $latestVb = $permohonan->verifikasiBersama->sortByDesc('id')->first(); @endphp
<div class="bg-white rounded-xl border border-[#E5E8EC] overflow-hidden mb-5"
     x-data="{ konfirmasiOpen: false, catatanHasil: '', showViewer: false, viewUrl: '', viewType: '', viewName: '' }">

    <div class="flex items-center justify-between px-5 py-3.5 border-b border-[#F0F2F5]">
        <div class="text-[13px] font-semibold text-[#1a2a35]">Verifikasi Bersama</div>
        @if ($latestVb)
        <span class="text-[11px] font-semibold px-2 py-0.5 rounded-full {{ $latestVb->status->badgeClass() }}">
            {{ $latestVb->status->label() }}
        </span>
        @endif
    </div>

    <div class="px-5 py-4">

        @if ($latestVb)
        {{-- Jadwal info (read-only) --}}
        <div class="flex items-start gap-8 mb-4">
            <div>
                <div class="text-[11px] text-[#8a9ba8] mb-0.5">Jadwal</div>
                <div class="text-[13px] font-semibold text-[#1a2a35]">{{ $latestVb->jadwal->format('d M Y, H:i') }} WIB</div>
            </div>
            @if ($latestVb->catatan)
            <div class="flex-1">
                <div class="text-[11px] text-[#8a9ba8] mb-0.5">Catatan Jadwal</div>
                <div class="text-[12px] text-[#5a6a75]">{{ $latestVb->catatan }}</div>
            </div>
            @endif
            @if ($latestVb->catatan_hasil)
            <div class="flex-1">
                <div class="text-[11px] text-[#8a9ba8] mb-0.5">Catatan Hasil</div>
                <div class="text-[12px] text-[#5a6a75]">{{ $latestVb->catatan_hasil }}</div>
            </div>
            @endif
        </div>
        @else
        <div class="py-1 text-[12px] text-[#8a9ba8]">Jadwal verifikasi bersama belum diatur oleh admin.</div>
        @endif

        {{-- Selesaikan Verifikasi (saat status permohonan = asesmen/verifikasi, jadwal sudah ada, vb belum selesai) --}}
        @if (in_array($permohonan->status, [\App\Enums\StatusPermohonanEnum::Asesmen, \App\Enums\StatusPermohonanEnum::Verifikasi]) && $latestVb && $latestVb->status === \App\Enums\StatusVerifikasiEnum::Terjadwal)
        <div class="{{ $latestVb ? 'border-t border-[#F0F2F5] pt-4 mt-1' : 'mt-3' }}">
            <div class="text-[12px] font-semibold text-[#1a2a35] mb-3">Selesaikan Verifikasi</div>
            <div class="flex items-center gap-3 mb-3">
                <label class="flex items-center gap-2 px-3 py-2 border border-[#D8DDE2] rounded-lg cursor-pointer hover:border-primary transition-colors text-[12px] text-[#5a6a75]">
                    <svg class="w-4 h-4 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M21.44 11.05l-9.19 9.19a6 6 0 01-8.49-8.49l9.19-9.19a4 4 0 015.66 5.66l-9.2 9.19a2 2 0 01-2.83-2.83l8.49-8.48"/>
                    </svg>
                    <span wire:loading.remove wire:target="berkasBA">
                        @if ($berkasBA) {{ $berkasBA->getClientOriginalName() }} @else Lampirkan berkas verifikasi (PDF/JPG/PNG, maks. 10 MB) @endif
                    </span>
                    <span wire:loading wire:target="berkasBA" class="text-[#8a9ba8]">Mengunggah...</span>
                    <input type="file" wire:model="berkasBA" accept=".pdf,.jpg,.jpeg,.png" class="hidden">
                </label>
                @error('berkasBA') <span class="text-[11px] text-[#c62828]">{{ $message }}</span> @enderror
            </div>
            <div class="mb-3">
                <label class="block text-[11px] font-semibold text-[#5a6a75] mb-1.5">Catatan Hasil Verifikasi (opsional)</label>
                <textarea x-model="catatanHasil" rows="2"
                          class="w-full px-3 py-2 text-[13px] text-[#1a2a35] bg-white border border-[#E0E5EA] rounded-lg outline-none focus:border-primary focus:ring-2 focus:ring-primary/10 transition resize-none"
                          placeholder="Ringkasan hasil verifikasi bersama..."></textarea>
            </div>
            <button @click="konfirmasiOpen = true"
                    class="h-[42px] px-6 bg-primary hover:bg-[#005f78] text-white text-[13px] font-semibold rounded-xl transition-colors">
                Selesai
            </button>
        </div>
        @endif

        {{-- Berkas BA (kapan saja vb sudah selesai dan ada berkas) --}}
        @if ($latestVb?->berkas && $latestVb?->status === \App\Enums\StatusVerifikasiEnum::Selesai)
        @php $vbExt = strtolower(pathinfo($latestVb->berkas, PATHINFO_EXTENSION)); $vbViewType = in_array($vbExt, ['jpg','jpeg','png']) ? 'image' : 'pdf'; @endphp
        <div class="border-t border-[#F0F2F5] pt-4 mt-1 flex items-center gap-2">
            <svg class="w-4 h-4 text-primary shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V8z"/><polyline points="14 2 14 8 20 8"/>
            </svg>
            <span class="text-[12px] font-medium text-[#1a2a35]">Berkas verifikasi</span>
            <span class="text-[#c8d1d8]">·</span>
            <button @click="viewUrl = '{{ route('verifikasi-bersama.view', $latestVb) }}'; viewType = '{{ $vbViewType }}'; viewName = 'Berkas Verifikasi'; showViewer = true"
                   class="w-[28px] h-[28px] rounded-md border border-[#D0D5DD] text-[#5a6a75] hover:border-primary hover:text-primary hover:bg-[#E8F4F8] transition-colors flex items-center justify-center">
                <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
            </button>
            <span class="text-[#c8d1d8]">·</span>
            <a href="{{ route('verifikasi-bersama.download', $latestVb) }}"
               class="w-[28px] h-[28px] rounded-md border border-[#D0D5DD] text-[#5a6a75] hover:border-primary hover:text-primary hover:bg-[#E8F4F8] transition-colors flex items-center justify-center no-underline">
                <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15v4a2 2 0 01-2 2H5a2 2 0 01-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
            </a>
        </div>
        @endif

    </div>

    {{-- Modal Lihat Berkas Verifikasi --}}
    <div x-show="showViewer" x-cloak x-transition.opacity
         class="fixed inset-0 z-[60] flex items-center justify-center bg-black/60"
         @click.self="showViewer = false">
        <div class="bg-white rounded-2xl shadow-2xl w-full max-w-3xl mx-4 flex flex-col overflow-hidden" style="max-height: 90vh">
            <div class="flex items-center justify-between px-5 py-3.5 border-b border-[#F0F2F5] shrink-0">
                <span x-text="viewName" class="text-[13px] font-semibold text-[#1a2a35] truncate max-w-[60%]"></span>
                <div class="flex items-center gap-3">
                    <a :href="viewUrl.replace('/view', '/download')" class="inline-flex items-center gap-1.5 text-[11px] font-semibold text-primary hover:text-[#005f78] no-underline">
                        <svg class="w-3.5 h-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M21 15v4a2 2 0 01-2 2H5a2 2 0 01-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/>
                        </svg>
                        Unduh
                    </a>
                    <button @click="showViewer = false" class="text-[#8a9ba8] hover:text-[#1a2a35] p-1">
                        <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
                    </button>
                </div>
            </div>
            <div class="flex-1 min-h-0 bg-[#F0F2F5]" style="height: 75vh">
                <template x-if="viewType === 'pdf'"><iframe :src="viewUrl" class="w-full border-0" style="height: 75vh"></iframe></template>
                <template x-if="viewType === 'image'"><div class="flex items-center justify-center p-6 h-full"><img :src="viewUrl" class="max-w-full max-h-full object-contain rounded-lg shadow" /></div></template>
            </div>
        </div>
    </div>

    @include('livewire.asesor.evaluasi.partials.modal-selesai-verifikasi')
</div>
