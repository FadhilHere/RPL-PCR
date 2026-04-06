@use('App\Enums\StatusPermohonanEnum')

@props(['permohonan', 'totalMk', 'totalDokumen', 'sksDiakui', 'sksTotalProdi', 'sksPersen', 'sksBarColor'])

<div class="grid grid-cols-1 sm:grid-cols-3 gap-3.5 mb-6">

    <div class="flex-1 flex items-start gap-3.5 bg-white rounded-lg border border-[#E5E8EC] p-[16px_18px]">
        <div class="w-[38px] h-[38px] rounded-lg bg-[#E8F0FE] flex items-center justify-center shrink-0">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#1a73e8" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M22 11.08V12a10 10 0 11-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/>
            </svg>
        </div>
        <div>
            <div class="text-[11px] text-[#8a9ba8] mb-1">Mata Kuliah Diajukan</div>
            <div class="text-[22px] font-semibold text-[#1a2a35] leading-none mb-0.5">{{ $totalMk }}</div>
            <div class="text-[11px] text-[#8a9ba8]">prodi {{ $permohonan->programStudi->kode }}</div>
        </div>
    </div>

    <div class="flex-1 flex items-start gap-3.5 bg-white rounded-lg border border-[#E5E8EC] p-[16px_18px]">
        <div class="w-[38px] h-[38px] rounded-lg bg-[#E6F4EA] flex items-center justify-center shrink-0">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#1e8e3e" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M9 11l3 3L22 4"/><path d="M21 12v7a2 2 0 01-2 2H5a2 2 0 01-2-2V5a2 2 0 012-2h11"/>
            </svg>
        </div>
        <div>
            <div class="text-[11px] text-[#8a9ba8] mb-1">Dokumen Diunggah</div>
            <div class="text-[22px] font-semibold text-[#1a2a35] leading-none mb-0.5">{{ $totalDokumen }}</div>
            <div class="text-[11px] text-[#8a9ba8]">total dokumen bukti</div>
        </div>
    </div>

    @if ($permohonan->status === StatusPermohonanEnum::Disetujui)
    <div class="flex-1 flex items-start gap-3.5 bg-white rounded-lg border border-[#E5E8EC] p-[16px_18px]">
        <div class="w-[38px] h-[38px] rounded-lg bg-[#E6F4EA] flex items-center justify-center shrink-0">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#1e7e3e" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <polyline points="22 12 18 12 15 21 9 3 6 12 2 12"/>
            </svg>
        </div>
        <div class="flex-1 min-w-0">
            <div class="text-[11px] text-[#8a9ba8] mb-1">SKS Diakui</div>
            <div class="flex items-baseline gap-1 mb-1.5">
                <span class="text-[22px] font-semibold text-[#1a2a35] leading-none">{{ $sksDiakui }}</span>
                <span class="text-[11px] text-[#8a9ba8]">/ {{ $sksTotalProdi }} SKS ({{ $sksPersen }}%)</span>
            </div>
            <div class="h-1.5 bg-[#F0F2F5] rounded-full overflow-hidden">
                <div class="h-full rounded-full" style="width: {{ min(100, $sksPersen) }}%; background-color: {{ $sksBarColor }}"></div>
            </div>
        </div>
    </div>
    @else
    <div class="flex-1 flex items-start gap-3.5 bg-white rounded-lg border border-[#E5E8EC] p-[16px_18px]">
        <div class="w-[38px] h-[38px] rounded-lg bg-[#FEF3E2] flex items-center justify-center shrink-0">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#e37400" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/>
            </svg>
        </div>
        <div>
            <div class="text-[11px] text-[#8a9ba8] mb-1">Status Pengajuan</div>
            <div class="mt-1 mb-0.5">
                <span class="text-[10px] font-semibold px-2 py-0.5 rounded-full {{ $permohonan->status->badgeClass() }}">{{ $permohonan->status->label() }}</span>
            </div>
            <div class="text-[11px] text-[#8a9ba8]">{{ $permohonan->updated_at->diffForHumans() }}</div>
        </div>
    </div>
    @endif

</div>
