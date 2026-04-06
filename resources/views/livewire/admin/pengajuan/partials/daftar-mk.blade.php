{{-- Daftar Mata Kuliah yang Di-assign --}}
<div class="bg-white rounded-xl border border-[#E5E8EC] overflow-hidden mb-5">
    <div class="flex items-center justify-between px-5 py-3.5 border-b border-[#F0F2F5]">
        <div>
            <span class="text-[13px] font-semibold text-[#1a2a35]">Daftar Mata Kuliah</span>
            <span class="text-[11px] text-[#8a9ba8] ml-2">{{ $permohonan->rplMataKuliah->count() }} MK</span>
        </div>
        @if ($canEditMk && $mkTersedia->isNotEmpty())
        <button @click="tambahMkOpen = true"
                class="flex items-center gap-1.5 h-[32px] px-3 text-[12px] font-semibold text-primary border border-primary rounded-lg hover:bg-[#E8F4F8] transition-colors">
            <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round">
                <line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/>
            </svg>
            Tambah MK
        </button>
        @endif
    </div>

    @if ($permohonan->rplMataKuliah->isEmpty())
    <div class="py-8 text-center text-[12px] text-[#8a9ba8]">Belum ada mata kuliah yang di-assign.</div>
    @else
    <table class="w-full text-[13px]">
        <thead>
            <tr class="border-b border-[#F0F2F5]">
                <th class="text-left px-5 py-3 text-[11px] font-semibold text-[#8a9ba8] uppercase tracking-[0.5px]">Mata Kuliah</th>
                <th class="text-left px-5 py-3 text-[11px] font-semibold text-[#8a9ba8] uppercase tracking-[0.5px]">Status VATM</th>
                <th class="text-left px-5 py-3 text-[11px] font-semibold text-[#8a9ba8] uppercase tracking-[0.5px]">Catatan Asesor</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($permohonan->rplMataKuliah as $rplMk)
            @php
                $mk           = $rplMk->mataKuliah;
                $mkStatus     = $rplMk->status ?? \App\Enums\StatusRplMataKuliahEnum::Menunggu;
                $mkBadgeCls   = $mkStatus->badgeClass();
                $mkBadgeLabel = $mkStatus->label();
            @endphp
            <tr class="border-b border-[#F6F8FA] last:border-0 hover:bg-[#FAFBFC] transition-colors" wire:key="mk-{{ $rplMk->id }}">
                <td class="px-5 py-3.5">
                    <div class="text-[12px] font-medium text-[#1a2a35]">{{ $mk->nama }}</div>
                    <div class="text-[11px] text-[#8a9ba8]">{{ $mk->kode }} · {{ $mk->sks }} SKS · Sem {{ $mk->semester }}</div>
                </td>
                <td class="px-5 py-3.5">
                    <span class="text-[11px] font-semibold px-2.5 py-1 rounded-full {{ $mkBadgeCls }}">{{ $mkBadgeLabel }}</span>
                </td>
                <td class="px-5 py-3.5 max-w-[180px]">
                    <span class="text-[12px] text-[#5a6a75] line-clamp-2">{{ $rplMk->catatan_asesor ?? '—' }}</span>
                </td>
            </tr>
            @endforeach
        </tbody>
    </table>
    @endif
</div>
