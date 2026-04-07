<?php

use Livewire\Attributes\Layout;
use Livewire\Volt\Component;
use App\Models\PermohonanRpl;
use App\Enums\JenisRplEnum;
use App\Enums\StatusPermohonanEnum;

new #[Layout('components.layouts.asesor')] class extends Component {
    public function with(): array
    {
        $asesor   = auth()->user()->asesor;
        $prodiIds = $asesor ? $asesor->programStudi->pluck('id') : collect();

        $pengajuanAktif = PermohonanRpl::whereIn('program_studi_id', $prodiIds)
            ->whereNotIn('status', [
                StatusPermohonanEnum::Draf,
                StatusPermohonanEnum::Disetujui,
                StatusPermohonanEnum::Ditolak,
            ])->count();

        $butuhTindakan = PermohonanRpl::whereIn('program_studi_id', $prodiIds)
            ->whereIn('status', [StatusPermohonanEnum::Diproses, StatusPermohonanEnum::Verifikasi])
            ->count();

        $dalamReview = PermohonanRpl::whereIn('program_studi_id', $prodiIds)
            ->where('status', StatusPermohonanEnum::DalamReview)
            ->count();

        $disetujui = PermohonanRpl::whereIn('program_studi_id', $prodiIds)
            ->where('status', StatusPermohonanEnum::Disetujui)
            ->count();

        $ditolak = PermohonanRpl::whereIn('program_studi_id', $prodiIds)
            ->where('status', StatusPermohonanEnum::Ditolak)
            ->count();

        $pengajuanPerhatian = PermohonanRpl::with(['peserta.user', 'programStudi'])
            ->whereIn('program_studi_id', $prodiIds)
            ->whereIn('status', [
                StatusPermohonanEnum::Diajukan,
                StatusPermohonanEnum::Diproses,
                StatusPermohonanEnum::Verifikasi,
                StatusPermohonanEnum::DalamReview,
            ])
            ->orderByDesc('created_at')
            ->limit(5)
            ->get();

        $prodiList = $asesor ? $asesor->programStudi : collect();

        return compact(
            'pengajuanAktif', 'butuhTindakan', 'dalamReview', 'disetujui', 'ditolak',
            'pengajuanPerhatian', 'prodiList'
        );
    }
}; ?>

<x-slot:title>Selamat datang, {{ auth()->user()->nama }}</x-slot:title>
<x-slot:subtitle>{{ \Carbon\Carbon::now()->locale('id')->translatedFormat('l\, d F Y') }}</x-slot:subtitle>

<div>

    {{-- ===== STAT CARDS ===== --}}
    <div class="grid grid-cols-2 lg:grid-cols-5 gap-4 mb-4">

        {{-- Pengajuan Aktif --}}
        <div class="bg-white rounded-[10px] border border-[#E5E8EC] px-5 py-4">
            <div class="flex items-center justify-between mb-3">
                <div class="text-[11px] font-medium text-[#8a9ba8]">Pengajuan Aktif</div>
                <div class="w-8 h-8 rounded-lg bg-[#E8F4F8] flex items-center justify-center">
                    <svg class="w-4 h-4 text-primary" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M22 12h-4l-3 9L9 3l-3 9H2"/>
                    </svg>
                </div>
            </div>
            <div class="text-[28px] font-bold text-[#004B5F] leading-none mb-1">{{ $pengajuanAktif }}</div>
            <div class="text-[11px] text-[#8a9ba8]">Di prodi saya</div>
        </div>

        {{-- Butuh Tindakan --}}
        <div class="bg-white rounded-[10px] border border-[#E5E8EC] px-5 py-4">
            <div class="flex items-center justify-between mb-3">
                <div class="text-[11px] font-medium text-[#8a9ba8]">Butuh Tindakan</div>
                <div class="w-8 h-8 rounded-lg bg-[#FFF8E1] flex items-center justify-center">
                    <svg class="w-4 h-4 text-[#b45309]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/>
                    </svg>
                </div>
            </div>
            <div class="text-[28px] font-bold text-[#b45309] leading-none mb-1">{{ $butuhTindakan }}</div>
            <div class="text-[11px] text-[#8a9ba8]">Diproses &amp; verifikasi</div>
        </div>

        {{-- Dalam Review --}}
        <div class="bg-white rounded-[10px] border border-[#E5E8EC] px-5 py-4">
            <div class="flex items-center justify-between mb-3">
                <div class="text-[11px] font-medium text-[#8a9ba8]">Dalam Review</div>
                <div class="w-8 h-8 rounded-lg bg-[#EEF2FF] flex items-center justify-center">
                    <svg class="w-4 h-4 text-[#6366f1]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/>
                    </svg>
                </div>
            </div>
            <div class="text-[28px] font-bold text-[#6366f1] leading-none mb-1">{{ $dalamReview }}</div>
            <div class="text-[11px] text-[#8a9ba8]">Evaluasi VATM</div>
        </div>

        {{-- Disetujui --}}
        <div class="bg-white rounded-[10px] border border-[#E5E8EC] px-5 py-4">
            <div class="flex items-center justify-between mb-3">
                <div class="text-[11px] font-medium text-[#8a9ba8]">Disetujui</div>
                <div class="w-8 h-8 rounded-lg bg-[#E6F4EA] flex items-center justify-center">
                    <svg class="w-4 h-4 text-[#1e7e3e]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M22 11.08V12a10 10 0 11-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/>
                    </svg>
                </div>
            </div>
            <div class="text-[28px] font-bold text-[#1e7e3e] leading-none mb-1">{{ $disetujui }}</div>
            <div class="text-[11px] text-[#8a9ba8]">SK diterbitkan</div>
        </div>

        {{-- Ditolak --}}
        <div class="bg-white rounded-[10px] border border-[#E5E8EC] px-5 py-4">
            <div class="flex items-center justify-between mb-3">
                <div class="text-[11px] font-medium text-[#8a9ba8]">Ditolak</div>
                <div class="w-8 h-8 rounded-lg bg-[#FCE8E6] flex items-center justify-center">
                    <svg class="w-4 h-4 text-[#c62828]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <circle cx="12" cy="12" r="10"/><line x1="15" y1="9" x2="9" y2="15"/><line x1="9" y1="9" x2="15" y2="15"/>
                    </svg>
                </div>
            </div>
            <div class="text-[28px] font-bold text-[#c62828] leading-none mb-1">{{ $ditolak }}</div>
            <div class="text-[11px] text-[#8a9ba8]">Tidak disetujui</div>
        </div>

    </div>

    {{-- ===== BOTTOM GRID ===== --}}
    <div class="flex flex-col lg:flex-row gap-4">

        {{-- Tabel pengajuan perlu perhatian --}}
        <div class="flex-1 bg-white rounded-[10px] border border-[#E5E8EC] overflow-hidden">
            <div class="flex items-center justify-between px-[18px] py-3.5 border-b border-[#F0F2F5]">
                <div class="text-[13px] font-semibold text-[#1a2a35]">Pengajuan</div>
                <a href="{{ route('asesor.pengajuan.index') }}" class="text-[12px] text-primary font-medium hover:underline no-underline">Lihat semua</a>
            </div>
            @if ($pengajuanPerhatian->isEmpty())
            <div class="px-[18px] py-8 text-center text-[12px] text-[#8a9ba8]">
                Tidak ada pengajuan yang memerlukan tindakan saat ini.
            </div>
            @else
            <div class="overflow-x-auto">
                <table class="w-full text-[12px]">
                    <thead>
                        <tr class="border-b border-[#F0F2F5]">
                            <th class="text-left font-semibold text-[#8a9ba8] px-[18px] py-2.5">No. Permohonan</th>
                            <th class="text-left font-semibold text-[#8a9ba8] px-3 py-2.5">Peserta</th>
                            <th class="text-left font-semibold text-[#8a9ba8] px-3 py-2.5">Prodi</th>
                            <th class="text-left font-semibold text-[#8a9ba8] px-3 py-2.5">Status</th>
                            <th class="text-left font-semibold text-[#8a9ba8] px-3 py-2.5">Tanggal</th>
                            <th class="text-left font-semibold text-[#8a9ba8] px-3 py-2.5 pr-[18px]">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($pengajuanPerhatian as $p)
                        <tr class="border-b border-[#F6F8FA] last:border-0 hover:bg-[#FAFBFC]">
                            <td class="px-[18px] py-3">
                                <span class="text-[12px] font-medium text-[#1a2a35]">{{ $p->nomor_permohonan ?? '—' }}</span>
                            </td>
                            <td class="px-3 py-3 text-[#1a2a35]">{{ $p->peserta?->user?->nama ?? '—' }}</td>
                            <td class="px-3 py-3 text-[#5a6a75]">{{ $p->programStudi?->nama ?? '—' }}</td>
                            <td class="px-3 py-3">
                                <span class="text-[10px] font-semibold px-2 py-[3px] rounded-full {{ $p->status->badgeClass() }}">
                                    {{ $p->status->label() }}
                                </span>
                            </td>
                            <td class="px-3 py-3 text-[#8a9ba8]">
                                {{ $p->created_at->format('d M Y') }}
                            </td>
                            <td class="px-3 py-3 pr-[18px]">
                                <a href="{{ $p->jenis_rpl === JenisRplEnum::RplI ? route('asesor.evaluasi.transfer', $p) : route('asesor.evaluasi.index', $p) }}"
                                   class="inline-flex items-center gap-1.5 text-[11px] font-semibold text-primary hover:text-white hover:bg-primary px-2.5 py-1.5 rounded-lg border border-primary/30 hover:border-primary transition-all no-underline">
                                    <svg class="w-3 h-3" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                        <polyline points="9 18 15 12 9 6"/>
                                    </svg>
                                    {{ $p->jenis_rpl === JenisRplEnum::RplI ? 'Nilai Transfer' : 'Evaluasi' }}
                                </a>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            @endif
        </div>

        {{-- Sidebar: prodi info + quick link --}}
        <div class="w-full lg:w-[240px] lg:shrink-0 flex flex-col gap-3">

            {{-- Program studi saya --}}
            <div class="bg-white rounded-[10px] border border-[#E5E8EC] overflow-hidden">
                <div class="px-[18px] py-3.5 border-b border-[#F0F2F5]">
                    <div class="text-[13px] font-semibold text-[#1a2a35]">Program Studi</div>
                </div>
                @if ($prodiList->isEmpty())
                <div class="px-[18px] py-4 text-[12px] text-[#8a9ba8]">Belum ada prodi yang ditugaskan.</div>
                @else
                <div class="px-[18px] py-3 flex flex-col gap-2">
                    @foreach ($prodiList as $prodi)
                    <div class="flex items-center gap-2.5">
                        <div class="w-1.5 h-1.5 rounded-full bg-primary shrink-0"></div>
                        <span class="text-[12px] text-[#1a2a35]">{{ $prodi->nama }}</span>
                    </div>
                    @endforeach
                </div>
                @endif
            </div>

            {{-- Quick link --}}
            <a href="{{ route('asesor.materi.index') }}"
               class="flex items-center justify-between bg-primary hover:bg-[#005f78] rounded-[10px] px-5 py-4 transition-colors no-underline group">
                <div>
                    <div class="text-[12px] font-semibold text-white mb-0.5">Kelola Materi</div>
                    <div class="text-[11px] text-white/65">Materi asesmen prodi</div>
                </div>
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="opacity-70 group-hover:translate-x-0.5 transition-transform">
                    <line x1="5" y1="12" x2="19" y2="12"/>
                    <polyline points="12 5 19 12 12 19"/>
                </svg>
            </a>

        </div>

    </div>

</div>
