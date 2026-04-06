@use('App\Enums\StatusRplMataKuliahEnum')

@props(['permohonan'])

@php
    $totalSksDiakui = $permohonan->rplMataKuliah->where('status', StatusRplMataKuliahEnum::Diakui)->sum(fn($m) => $m->mataKuliah->sks);
    $totalSksProdi  = $permohonan->programStudi->total_sks ?? 0;
    $batas70        = (int) floor($totalSksProdi * 0.70);
    $batas50        = (int) ceil($totalSksProdi * 0.50);
    $persentase     = $totalSksProdi > 0 ? round($totalSksDiakui / $totalSksProdi * 100, 1) : 0;
    $melebihi70     = $totalSksDiakui > $batas70;
    $dibawah50      = $totalSksProdi > 0 && $totalSksDiakui < $batas50;
    $barWidth       = $totalSksProdi > 0 ? min(100, round($totalSksDiakui / $totalSksProdi * 100)) : 0;
    $barColor       = $melebihi70 ? '#e37400' : '#004B5F';
@endphp

<div class="bg-white rounded-xl border border-[#E5E8EC] px-5 py-4 mb-5">
    <div class="text-[11px] font-semibold text-[#8a9ba8] uppercase tracking-[0.8px] mb-4">Rekognisi SKS</div>
    <div class="flex items-start gap-6 mb-4">
        <div class="text-center">
            <div class="text-[26px] font-bold text-[#1a2a35] leading-none">{{ $totalSksDiakui }}</div>
            <div class="text-[10px] text-[#8a9ba8] mt-1">SKS Diakui</div>
        </div>
        <div class="text-center">
            <div class="text-[26px] font-bold text-[#b45309] leading-none">{{ $batas70 }}</div>
            <div class="text-[10px] text-[#8a9ba8] mt-1">Batas Maks. 70%</div>
        </div>
        <div class="text-center">
            <div class="text-[26px] font-bold text-[#5a6a75] leading-none">{{ $batas50 }}</div>
            <div class="text-[10px] text-[#8a9ba8] mt-1">Batas Min. 50%</div>
        </div>
        <div class="text-center">
            <div class="text-[26px] font-bold text-[#5a6a75] leading-none">{{ $totalSksProdi }}</div>
            <div class="text-[10px] text-[#8a9ba8] mt-1">Total SKS Prodi</div>
        </div>
    </div>
    <div class="mb-3">
        <div class="flex items-center justify-between mb-1">
            <span class="text-[11px] text-[#8a9ba8]">{{ $persentase }}% dari total SKS prodi</span>
            <span class="text-[11px] font-semibold" style="color: {{ $barColor }}">{{ $totalSksDiakui }}/{{ $totalSksProdi }} SKS</span>
        </div>
        <div class="h-2 bg-[#F0F2F5] rounded-full overflow-hidden">
            <div class="h-full rounded-full transition-all" style="width: {{ $barWidth }}%; background-color: {{ $barColor }}"></div>
        </div>
    </div>
    @if ($melebihi70)
    <div class="flex items-center gap-2 text-[11px] text-[#b45309]">
        <svg class="w-3.5 h-3.5 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/>
        </svg>
        Total SKS melampaui batas maksimal 70% ({{ $batas70 }} SKS)
    </div>
    @endif
    @if ($dibawah50)
    <div class="flex items-center gap-2 text-[11px] text-[#5a6a75] {{ $melebihi70 ? 'mt-1.5' : '' }}">
        <svg class="w-3.5 h-3.5 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/>
        </svg>
        Total SKS belum mencapai batas minimal 50% ({{ $batas50 }} SKS)
    </div>
    @endif
</div>
