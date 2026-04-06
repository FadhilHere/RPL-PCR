{{-- Jadwal Verifikasi Bersama (admin view) --}}
<div class="bg-white rounded-xl border border-[#E5E8EC] overflow-hidden mb-5"
     x-data="{
         showJadwalForm: {{ (!$latestVb) && in_array($permohonan->status, [\App\Enums\StatusPermohonanEnum::Diproses, \App\Enums\StatusPermohonanEnum::Verifikasi]) ? 'true' : 'false' }},
         jadwal: '{{ $latestVb?->jadwal?->format('Y-m-d\TH:i') ?? '' }}',
         catatan: '{{ addslashes($latestVb?->catatan ?? '') }}',
         jadwalErrors: {},
         showViewer: false, viewUrl: '', viewType: '', viewName: ''
     }"
     @jadwal-errors.window="jadwalErrors = $event.detail.errors"
     @jadwal-saved.window="showJadwalForm = false; jadwalErrors = {}">

    <div class="flex items-center justify-between px-5 py-3.5 border-b border-[#F0F2F5]">
        <div class="text-[13px] font-semibold text-[#1a2a35]">Verifikasi Bersama</div>
        @if (in_array($permohonan->status, [\App\Enums\StatusPermohonanEnum::Diproses, \App\Enums\StatusPermohonanEnum::Verifikasi]) && $latestVb && $latestVb->status === \App\Enums\StatusVerifikasiEnum::Terjadwal)
        <button @click="showJadwalForm = !showJadwalForm; jadwal = '{{ $latestVb->jadwal->format('Y-m-d\TH:i') }}'; catatan = '{{ addslashes($latestVb->catatan ?? '') }}'"
                class="text-[11px] font-semibold text-primary hover:text-[#005f78] transition-colors">
            Edit Jadwal
        </button>
        @endif
    </div>

    <div class="px-5 py-4">
        {{-- Jadwal info --}}
        @if ($latestVb)
        <div class="flex items-start gap-8 mb-4">
            <div>
                <div class="text-[11px] text-[#8a9ba8] mb-0.5">Jadwal</div>
                <div class="text-[13px] font-semibold text-[#1a2a35]">{{ $latestVb->jadwal->format('d M Y, H:i') }} WIB</div>
            </div>
            <div>
                <div class="text-[11px] text-[#8a9ba8] mb-0.5">Status</div>
                <span class="text-[11px] font-semibold px-2 py-0.5 rounded-full {{ $latestVb->status->badgeClass() }}">
                    {{ $latestVb->status->label() }}
                </span>
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
        @endif

        {{-- Form jadwal (diproses / verifikasi) --}}
        @if (in_array($permohonan->status, [\App\Enums\StatusPermohonanEnum::Diproses, \App\Enums\StatusPermohonanEnum::Verifikasi]))
        <div x-show="showJadwalForm" x-transition>
            <div class="grid grid-cols-2 gap-4 mb-3">
                <div>
                    <label class="block text-[11px] font-semibold text-[#5a6a75] mb-1.5">Jadwal Verifikasi <span class="text-[#c62828]">*</span></label>
                    <x-form.date-picker x-model="jadwal" placeholder="Pilih tanggal & waktu..." :enable-time="true" />
                    <p x-show="jadwalErrors.jadwal" x-text="jadwalErrors.jadwal?.[0]" class="text-[#c62828] text-[11px] mt-1"></p>
                </div>
            </div>
            <div class="mb-3">
                <label class="block text-[11px] font-semibold text-[#5a6a75] mb-1.5">Catatan (opsional)</label>
                <textarea x-model="catatan" rows="2"
                          class="w-full px-3 py-2 text-[13px] text-[#1a2a35] bg-white border border-[#E0E5EA] rounded-lg outline-none focus:border-primary focus:ring-2 focus:ring-primary/10 transition resize-none"
                          placeholder="Lokasi, link meeting, dll."></textarea>
            </div>
            <button @click="$wire.simpanJadwal(jadwal, catatan)"
                    class="bg-primary hover:bg-[#005f78] text-white text-[12px] font-semibold px-4 py-2 rounded-lg transition-colors">
                Simpan Jadwal
            </button>
        </div>
        @if (!$latestVb)
        <div x-show="!showJadwalForm" class="py-1 text-[12px] text-[#8a9ba8]">
            Belum ada jadwal. <button @click="showJadwalForm = true" class="text-primary hover:underline font-medium">Jadwalkan sekarang →</button>
        </div>
        @endif
        @elseif (! $latestVb)
        <div class="py-1 text-[12px] text-[#8a9ba8]">Belum ada jadwal verifikasi.</div>
        @endif

        {{-- Berkas verifikasi --}}
        @if (in_array($permohonan->status, [\App\Enums\StatusPermohonanEnum::DalamReview, \App\Enums\StatusPermohonanEnum::Disetujui]) && $latestVb?->berkas)
        @php $vbExt = strtolower(pathinfo($latestVb->berkas, PATHINFO_EXTENSION)); $vbViewType = in_array($vbExt, ['jpg','jpeg','png']) ? 'image' : 'pdf'; @endphp
        <div class="{{ $latestVb ? 'border-t border-[#F0F2F5] pt-3 mt-1' : '' }} flex items-center gap-2">
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
</div>
