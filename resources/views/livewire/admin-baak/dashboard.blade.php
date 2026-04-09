<?php

use Livewire\Attributes\Layout;
use Livewire\Volt\Component;
use App\Models\PermohonanRpl;
use App\Enums\StatusPermohonanEnum;

new #[Layout('components.layouts.admin')] class extends Component {
    public function with(): array
    {
        // Pengajuan yang sudah diproses tapi belum dijadwalkan
        $belumDijadwalkan = PermohonanRpl::whereIn('status', [
                StatusPermohonanEnum::Diproses,
            ])
            ->whereDoesntHave('verifikasiBersama')
            ->count();

        $aktif = PermohonanRpl::whereIn('status', [
            StatusPermohonanEnum::Diproses,
            StatusPermohonanEnum::Asesmen,
            StatusPermohonanEnum::Verifikasi,
        ])->count();

        $selesai = PermohonanRpl::whereIn('status', [
            StatusPermohonanEnum::Disetujui,
            StatusPermohonanEnum::Ditolak,
        ])->count();

        $pengajuanTerbaru = PermohonanRpl::with(['peserta.user', 'programStudi'])
            ->whereIn('status', [
                StatusPermohonanEnum::Diproses,
                StatusPermohonanEnum::Asesmen,
                StatusPermohonanEnum::Verifikasi,
            ])
            ->orderByDesc('created_at')
            ->limit(7)
            ->get();

        return compact('belumDijadwalkan', 'aktif', 'selesai', 'pengajuanTerbaru');
    }
}; ?>

<x-slot:title>Selamat datang, {{ auth()->user()->nama }}</x-slot:title>
<x-slot:subtitle>{{ \Carbon\Carbon::now()->locale('id')->translatedFormat('l\, d F Y') }}</x-slot:subtitle>

<div>

    {{-- Warning card: belum dijadwalkan --}}
    @if ($belumDijadwalkan > 0)
    <div class="flex items-start gap-3 bg-[#FFF8E1] border border-[#FCD34D] rounded-[10px] px-5 py-4 mb-4">
        <svg class="w-5 h-5 text-[#b45309] shrink-0 mt-0.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <path d="M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/>
            <line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/>
        </svg>
        <div>
            <div class="text-[13px] font-semibold text-[#92400e]">
                {{ $belumDijadwalkan }} pengajuan belum dijadwalkan verifikasinya
            </div>
            <div class="text-[12px] text-[#b45309] mt-0.5">
                Segera atur jadwal verifikasi di menu Jadwal Verifikasi.
            </div>
        </div>
        <a href="{{ route('admin.jadwal.index') }}"
           class="ml-auto shrink-0 text-[12px] font-semibold text-[#b45309] hover:underline no-underline">
            Atur Jadwal &rarr;
        </a>
    </div>
    @endif

    {{-- Stat cards --}}
    <div class="grid grid-cols-3 gap-4 mb-4">
        <div class="bg-white rounded-[10px] border border-[#E5E8EC] px-5 py-4">
            <div class="text-[11px] font-medium text-[#8a9ba8] mb-1">Belum Dijadwalkan</div>
            <div class="text-[28px] font-bold {{ $belumDijadwalkan > 0 ? 'text-[#b45309]' : 'text-[#1e7e3e]' }} leading-none mb-1">
                {{ $belumDijadwalkan }}
            </div>
            <div class="text-[11px] text-[#8a9ba8]">Perlu dijadwalkan</div>
        </div>

        <div class="bg-white rounded-[10px] border border-[#E5E8EC] px-5 py-4">
            <div class="text-[11px] font-medium text-[#8a9ba8] mb-1">Sedang Berjalan</div>
            <div class="text-[28px] font-bold text-[#1557b0] leading-none mb-1">{{ $aktif }}</div>
            <div class="text-[11px] text-[#8a9ba8]">Diproses, verifikasi, review</div>
        </div>

        <div class="bg-white rounded-[10px] border border-[#E5E8EC] px-5 py-4">
            <div class="text-[11px] font-medium text-[#8a9ba8] mb-1">Selesai</div>
            <div class="text-[28px] font-bold text-[#1e7e3e] leading-none mb-1">{{ $selesai }}</div>
            <div class="text-[11px] text-[#8a9ba8]">Disetujui atau ditolak</div>
        </div>
    </div>

    {{-- Pengajuan aktif terbaru --}}
    <div class="bg-white rounded-[10px] border border-[#E5E8EC] overflow-hidden">
        <div class="flex items-center justify-between px-[18px] py-3.5 border-b border-[#F0F2F5]">
            <div class="text-[13px] font-semibold text-[#1a2a35]">Pengajuan Aktif</div>
            <a href="{{ route('admin.jadwal.index') }}" class="text-[12px] text-primary font-medium hover:underline no-underline">Atur Jadwal</a>
        </div>
        @if ($pengajuanTerbaru->isEmpty())
        <div class="px-[18px] py-8 text-center text-[12px] text-[#8a9ba8]">Tidak ada pengajuan aktif saat ini.</div>
        @else
        <div class="overflow-x-auto">
            <table class="w-full text-[12px]">
                <thead>
                    <tr class="border-b border-[#F0F2F5]">
                        <th class="text-left font-semibold text-[#8a9ba8] px-[18px] py-2.5">No. Permohonan</th>
                        <th class="text-left font-semibold text-[#8a9ba8] px-3 py-2.5">Peserta</th>
                        <th class="text-left font-semibold text-[#8a9ba8] px-3 py-2.5">Prodi</th>
                        <th class="text-left font-semibold text-[#8a9ba8] px-3 py-2.5">Status</th>
                        <th class="text-left font-semibold text-[#8a9ba8] px-3 py-2.5 pr-[18px]">Tanggal</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($pengajuanTerbaru as $p)
                    <tr class="border-b border-[#F6F8FA] last:border-0 hover:bg-[#FAFBFC]">
                        <td class="px-[18px] py-3">
                            <a href="{{ route('admin.pengajuan.detail', $p) }}"
                               class="font-medium text-primary hover:underline no-underline">
                                {{ $p->nomor_permohonan ?? '—' }}
                            </a>
                        </td>
                        <td class="px-3 py-3 text-[#1a2a35]">{{ $p->peserta?->user?->nama ?? '—' }}</td>
                        <td class="px-3 py-3 text-[#5a6a75]">{{ $p->programStudi?->nama ?? '—' }}</td>
                        <td class="px-3 py-3">
                            <span class="text-[10px] font-semibold px-2 py-[3px] rounded-full {{ $p->status->badgeClass() }}">
                                {{ $p->status->label() }}
                            </span>
                        </td>
                        <td class="px-3 py-3 pr-[18px] text-[#8a9ba8]">{{ $p->created_at->format('d M Y') }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        @endif
    </div>

</div>
