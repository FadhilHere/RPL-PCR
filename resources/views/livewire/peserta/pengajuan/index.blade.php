<?php

use Livewire\Attributes\Layout;
use Livewire\Volt\Component;
use App\Models\PermohonanRpl;
use App\Enums\StatusPermohonanEnum;
use App\Enums\StatusRplMataKuliahEnum;
use App\Enums\StatusVerifikasiEnum;

new #[Layout('components.layouts.peserta')] class extends Component {
    public function with(): array
    {
        $peserta = auth()->user()->peserta;

        return [
            'permohonanList' => $peserta
                ? PermohonanRpl::with(['programStudi', 'verifikasiBersama', 'rplMataKuliah.mataKuliah'])
                    ->where('peserta_id', $peserta->id)
                    ->latest()
                    ->get()
                : collect(),
        ];
    }
}; ?>

<x-slot:title>Pengajuan RPL</x-slot:title>
<x-slot:subtitle>Daftar permohonan RPL Anda</x-slot:subtitle>

<div>

    <div class="flex items-center justify-between mb-5">
        <div class="text-[13px] text-[#5a6a75]">
            <span class="font-semibold text-[#1a2a35]">{{ count($permohonanList) }}</span> pengajuan
        </div>
        <a href="{{ route('peserta.pengajuan.buat') }}"
           class="bg-primary hover:bg-[#005f78] text-white text-[13px] font-semibold px-4 py-2 rounded-lg transition-colors no-underline flex items-center gap-2">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                <line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/>
            </svg>
            Buat Pengajuan Baru
        </a>
    </div>

    <div class="bg-white rounded-[10px] border border-[#E5E8EC] overflow-hidden">
        @forelse ($permohonanList as $p)
        @php
            $status      = $p->status;
            $isDisproses = $status === StatusPermohonanEnum::Diproses;
            $jadwalVb    = $p->verifikasiBersama
                ->where('status', StatusVerifikasiEnum::Terjadwal)
                ->sortByDesc('id')
                ->first();
            $showJadwal  = $jadwalVb && in_array($status, [StatusPermohonanEnum::Diproses, StatusPermohonanEnum::Verifikasi]);
            $showSks     = $status === StatusPermohonanEnum::Disetujui;
        @endphp
        <div class="flex items-center gap-4 px-5 py-4 border-b border-[#F6F8FA] last:border-0 hover:bg-[#FAFBFC] transition-colors">
            <div class="flex-1 min-w-0">
                <div class="flex items-center gap-2 mb-0.5">
                    <span class="text-[12px] font-semibold text-[#1a2a35]">{{ $p->nomor_permohonan }}</span>
                    <span class="text-[10px] font-semibold px-2 py-0.5 rounded-full {{ $status->badgeClass() }}">
                        {{ $status->label() }}
                    </span>
                </div>
                <div class="text-[12px] text-[#5a6a75]">{{ $p->programStudi->nama }}</div>
                @if ($showJadwal)
                <div class="flex items-center gap-1.5 mt-1">
                    <svg class="w-3 h-3 text-primary shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <rect x="3" y="4" width="18" height="18" rx="2" ry="2"/>
                        <line x1="16" y1="2" x2="16" y2="6"/>
                        <line x1="8" y1="2" x2="8" y2="6"/>
                        <line x1="3" y1="10" x2="21" y2="10"/>
                    </svg>
                    <span class="text-[11px] text-primary font-medium">
                        Verifikasi {{ $jadwalVb->jadwal->locale('id')->translatedFormat('d M Y') }},
                        {{ $jadwalVb->jadwal->format('H:i') }} WIB
                    </span>
                    <span class="text-[10px] text-[#8a9ba8]">· {{ $jadwalVb->jadwal->diffForHumans() }}</span>
                </div>
                @endif
                @if ($showSks)
                @php
                    $sksDiakuiRow  = $p->rplMataKuliah->where('status', StatusRplMataKuliahEnum::Diakui)->sum(fn($m) => $m->mataKuliah->sks);
                    $sksTotalRow   = $p->programStudi->total_sks ?? 0;
                    $sksPersenRow  = $sksTotalRow > 0 ? round($sksDiakuiRow / $sksTotalRow * 100) : 0;
                @endphp
                <div class="flex items-center gap-1.5 mt-1">
                    <svg class="w-3 h-3 text-[#1e7e3e] shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <polyline points="22 12 18 12 15 21 9 3 6 12 2 12"/>
                    </svg>
                    <span class="text-[11px] text-[#1e7e3e] font-medium">{{ $sksDiakuiRow }} SKS diakui</span>
                    <span class="text-[10px] text-[#8a9ba8]">· {{ $sksPersenRow }}% dari {{ $sksTotalRow }} SKS</span>
                </div>
                @endif
            </div>
            <div class="text-[11px] text-[#8a9ba8] shrink-0">
                {{ $p->tanggal_pengajuan?->format('d M Y') ?? $p->created_at->format('d M Y') }}
            </div>
            <div class="flex items-center gap-2 shrink-0">
                @if ($isDisproses)
                    <a href="{{ route('peserta.pengajuan.asesmen', $p->id) }}"
                       class="text-[12px] text-primary font-semibold hover:underline no-underline">
                        Isi Asesmen →
                    </a>
                @else
                    <a href="{{ route('peserta.pengajuan.asesmen', $p->id) }}"
                       class="text-[12px] text-primary font-medium hover:underline no-underline">
                        Lihat Detail →
                    </a>
                @endif
            </div>
        </div>
        @empty
        <div class="flex flex-col items-center justify-center py-16 text-center">
            <div class="w-12 h-12 rounded-full bg-[#E8F4F8] flex items-center justify-center mb-3">
                <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="#004B5F" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V8z"/>
                    <polyline points="14 2 14 8 20 8"/>
                    <line x1="12" y1="18" x2="12" y2="12"/><line x1="9" y1="15" x2="15" y2="15"/>
                </svg>
            </div>
            <p class="text-[13px] font-medium text-[#1a2a35] mb-1">Belum ada pengajuan</p>
            <p class="text-[12px] text-[#8a9ba8] mb-5">Buat pengajuan RPL pertama Anda sekarang.</p>
            <a href="{{ route('peserta.pengajuan.buat') }}"
               class="bg-primary hover:bg-[#005f78] text-white text-[13px] font-semibold px-5 py-2.5 rounded-lg transition-colors no-underline">
                Buat Pengajuan
            </a>
        </div>
        @endforelse
    </div>

</div>
