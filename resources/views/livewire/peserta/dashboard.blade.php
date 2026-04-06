<?php

use Livewire\Attributes\Layout;
use Livewire\Volt\Component;
use App\Models\PermohonanRpl;
use App\Models\RplMataKuliah;
use App\Models\DokumenBukti;
use App\Enums\StatusPermohonanEnum;
use App\Enums\StatusRplMataKuliahEnum;
use App\Enums\StatusVerifikasiEnum;

new #[Layout('components.layouts.peserta')] class extends Component {
    public function with(): array
    {
        $peserta = auth()->user()->peserta;

        if (! $peserta) {
            return [
                'permohonan'    => null,
                'allRplMk'      => collect(),
                'totalMk'       => 0,
                'totalDokumen'  => 0,
                'semuaMkSelesai' => false,
                'jadwalVb'      => null,
                'statusLabel'   => '',
                'sksDiakui'     => 0,
                'sksTotalProdi' => 0,
                'sksPersen'     => 0,
                'sksBarColor'   => '#004B5F',
                'allStatuses'   => [],
                'statusOrder'   => false,
                'stepLabels'    => [],
                'stepDates'     => [],
            ];
        }

        $permohonan = PermohonanRpl::with(['programStudi', 'verifikasiBersama', 'rplMataKuliah.mataKuliah'])
            ->where('peserta_id', $peserta->id)
            ->latest()
            ->first();

        $allRplMk = RplMataKuliah::with(['mataKuliah', 'permohonanRpl'])
            ->whereHas('permohonanRpl', fn($q) => $q->where('peserta_id', $peserta->id))
            ->orderByDesc('created_at')
            ->get();

        $semuaMkSelesai = $permohonan
            ? ! RplMataKuliah::where('permohonan_rpl_id', $permohonan->id)
                ->where('status', StatusRplMataKuliahEnum::Menunggu)
                ->exists()
            : false;

        $jadwalVb = $permohonan
            ? $permohonan->verifikasiBersama
                ->where('status', StatusVerifikasiEnum::Terjadwal)
                ->sortByDesc('id')
                ->first()
            : null;

        $statusLabel = $permohonan ? match($permohonan->status) {
            StatusPermohonanEnum::Diajukan    => 'Permohonan Telah Diajukan',
            StatusPermohonanEnum::Diproses    => 'Asesmen Mandiri Dapat Dimulai',
            StatusPermohonanEnum::Verifikasi  => 'Menunggu Verifikasi Bersama Asesor',
            StatusPermohonanEnum::DalamReview => 'Sedang Dievaluasi oleh Asesor',
            StatusPermohonanEnum::Disetujui   => 'Permohonan Disetujui',
            StatusPermohonanEnum::Ditolak     => 'Permohonan Ditolak',
            default                           => $permohonan->status->label(),
        } : '';

        $sksDiakui    = $permohonan ? $permohonan->rplMataKuliah->where('status', StatusRplMataKuliahEnum::Diakui)->sum(fn($m) => $m->mataKuliah->sks) : 0;
        $sksTotalProdi = $permohonan ? ($permohonan->programStudi->total_sks ?? 0) : 0;
        $sksPersen    = $sksTotalProdi > 0 ? round($sksDiakui / $sksTotalProdi * 100) : 0;
        $sksBarColor  = ($sksDiakui > floor($sksTotalProdi * 0.70)) ? '#e37400' : '#004B5F';

        $allStatuses  = [
            StatusPermohonanEnum::Diajukan->value,
            StatusPermohonanEnum::Diproses->value,
            StatusPermohonanEnum::Verifikasi->value,
            StatusPermohonanEnum::DalamReview->value,
            StatusPermohonanEnum::Disetujui->value,
        ];
        $effectiveVal = $permohonan && $permohonan->status === StatusPermohonanEnum::Disetujui && ! $semuaMkSelesai
            ? StatusPermohonanEnum::DalamReview->value
            : ($permohonan?->status?->value ?? StatusPermohonanEnum::Diajukan->value);
        $statusOrder  = array_search($effectiveVal, $allStatuses);
        $stepLabels   = [
            StatusPermohonanEnum::Diajukan->value    => 'Pengajuan Dikirim',
            StatusPermohonanEnum::Diproses->value    => 'Asesmen Mandiri',
            StatusPermohonanEnum::Verifikasi->value  => 'Verifikasi Bersama Asesor',
            StatusPermohonanEnum::DalamReview->value => 'Evaluasi VATM oleh Asesor',
            StatusPermohonanEnum::Disetujui->value   => 'SK Rekognisi Diterbitkan',
        ];
        $stepDates    = [
            StatusPermohonanEnum::Diajukan->value    => $permohonan?->tanggal_pengajuan?->format('d M Y') ?? '—',
            StatusPermohonanEnum::Diproses->value    => 'Menunggu',
            StatusPermohonanEnum::Verifikasi->value  => 'Menunggu',
            StatusPermohonanEnum::DalamReview->value => 'Sedang berjalan',
            StatusPermohonanEnum::Disetujui->value   => 'Menunggu',
        ];

        return [
            'permohonan'    => $permohonan,
            'allRplMk'      => $allRplMk,
            'totalMk'       => $allRplMk->count(),
            'totalDokumen'  => DokumenBukti::where('peserta_id', $peserta->id)->count(),
            'semuaMkSelesai' => $semuaMkSelesai,
            'jadwalVb'      => $jadwalVb,
            'statusLabel'   => $statusLabel,
            'sksDiakui'     => $sksDiakui,
            'sksTotalProdi' => $sksTotalProdi,
            'sksPersen'     => $sksPersen,
            'sksBarColor'   => $sksBarColor,
            'allStatuses'   => $allStatuses,
            'statusOrder'   => $statusOrder,
            'stepLabels'    => $stepLabels,
            'stepDates'     => $stepDates,
        ];
    }
}; ?>

<x-slot:title>Selamat datang, {{ auth()->user()->nama }}</x-slot:title>
<x-slot:subtitle>{{ \Carbon\Carbon::now()->locale('id')->translatedFormat('l\, d F Y') }}</x-slot:subtitle>

<div>

@if ($permohonan)
    {{-- ===== WELCOME BANNER ===== --}}
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 bg-primary rounded-xl px-6 py-5 mb-6">
        <div>
            <h2 class="text-white font-semibold text-[16px] mb-1">{{ $statusLabel }}</h2>
            <p class="text-white/65 text-[12px] leading-[1.5] max-w-[380px]">
                Pengajuan RPL untuk prodi {{ $permohonan->programStudi->nama }}.
                Pantau perkembangan di halaman Pengajuan RPL.
            </p>
        </div>
        <a href="{{ route('peserta.pengajuan.index') }}"
           class="self-start sm:self-auto shrink-0 bg-white text-primary text-[12px] font-semibold px-4 py-2 rounded-md hover:bg-[#F0F7FA] transition-colors no-underline">
            Lihat Detail →
        </a>
    </div>

    {{-- ===== JADWAL VERIFIKASI BERSAMA ===== --}}
    @if ($jadwalVb && in_array($permohonan->status, [StatusPermohonanEnum::Diproses, StatusPermohonanEnum::Verifikasi]))
    <div class="flex items-center gap-4 bg-white border border-[#D6EAF8] rounded-xl px-5 py-3.5 mb-5">
        <div class="w-9 h-9 rounded-lg bg-[#E8F4F8] flex items-center justify-center shrink-0">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#004B5F" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <rect x="3" y="4" width="18" height="18" rx="2" ry="2"/>
                <line x1="16" y1="2" x2="16" y2="6"/>
                <line x1="8" y1="2" x2="8" y2="6"/>
                <line x1="3" y1="10" x2="21" y2="10"/>
            </svg>
        </div>
        <div class="flex-1 min-w-0">
            <div class="text-[11px] font-semibold text-primary mb-0.5">Jadwal Verifikasi Bersama</div>
            <div class="text-[13px] font-semibold text-[#1a2a35]">
                {{ $jadwalVb->jadwal->locale('id')->translatedFormat('l\, d F Y') }},
                pukul {{ $jadwalVb->jadwal->format('H:i') }} WIB
            </div>
            @if ($jadwalVb->catatan)
            <div class="text-[11px] text-[#5a6a75] mt-0.5 truncate">{{ $jadwalVb->catatan }}</div>
            @endif
        </div>
        <span class="text-[10px] font-semibold px-2 py-1 rounded-full bg-[#FFF8E1] text-[#b45309] shrink-0">
            {{ $jadwalVb->jadwal->diffForHumans() }}
        </span>
    </div>
    @endif

    {{-- ===== STAT CARDS ===== --}}
    <x-peserta.stat-cards
        :permohonan="$permohonan"
        :total-mk="$totalMk"
        :total-dokumen="$totalDokumen"
        :sks-diakui="$sksDiakui"
        :sks-total-prodi="$sksTotalProdi"
        :sks-persen="$sksPersen"
        :sks-bar-color="$sksBarColor"
    />

    {{-- ===== BOTTOM GRID ===== --}}
    <div class="flex flex-col lg:flex-row gap-[18px]">

        <div class="flex-1 bg-white rounded-[10px] border border-[#E5E8EC] overflow-hidden">
            <div class="flex items-center justify-between px-[18px] py-3.5 border-b border-[#F0F2F5]">
                <div class="text-[13px] font-semibold text-[#1a2a35]">Daftar Mata Kuliah yang Diajukan</div>
                <a href="{{ route('peserta.pengajuan.index') }}" class="text-[12px] text-primary font-medium hover:underline no-underline">Lihat semua</a>
            </div>
            @forelse ($allRplMk->take(5) as $rplMk)
            @php
                $mkStatus = $rplMk->status ?? StatusRplMataKuliahEnum::Menunggu;
                $pStatus  = $rplMk->permohonanRpl?->status;
                [$badgeCls, $badgeLabel] = match(true) {
                    $mkStatus === StatusRplMataKuliahEnum::Diakui       => ['bg-[#E6F4EA] text-[#1e7e3e]', 'Diakui'],
                    $mkStatus === StatusRplMataKuliahEnum::TidakDiakui  => ['bg-[#FCE8E6] text-[#c62828]', 'Tidak Diakui'],
                    $pStatus === StatusPermohonanEnum::DalamReview      => ['bg-[#FFF8E1] text-[#b45309]', 'Direview'],
                    $pStatus === StatusPermohonanEnum::Verifikasi        => ['bg-[#FFF8E1] text-[#b45309]', 'Verifikasi'],
                    $pStatus === StatusPermohonanEnum::Diproses          => ['bg-[#E8F0FE] text-[#1557b0]', 'Diproses'],
                    $pStatus === StatusPermohonanEnum::Diajukan          => ['bg-[#E8F0FE] text-[#1557b0]', 'Diajukan'],
                    $pStatus === StatusPermohonanEnum::Ditolak           => ['bg-[#FCE8E6] text-[#c62828]', 'Ditolak'],
                    default                                              => ['bg-[#F1F3F4] text-[#5f6368]', 'Menunggu'],
                };
            @endphp
            <div class="flex items-center gap-3.5 px-[18px] py-3 border-b border-[#F6F8FA] last:border-0">
                <span class="text-[10px] font-semibold text-primary bg-[#E8F4F8] px-[7px] py-[3px] rounded shrink-0">{{ $rplMk->mataKuliah->kode }}</span>
                <span class="flex-1 text-[12px] text-[#1a2a35]">{{ $rplMk->mataKuliah->nama }}</span>
                <span class="text-[11px] text-[#8a9ba8] shrink-0">{{ $rplMk->mataKuliah->sks }} SKS</span>
                <span class="text-[10px] font-semibold px-2 py-[3px] rounded-full shrink-0 {{ $badgeCls }}">
                    {{ $badgeLabel }}
                </span>
            </div>
            @empty
            <div class="px-[18px] py-6 text-center text-[12px] text-[#8a9ba8]">Belum ada mata kuliah.</div>
            @endforelse
        </div>

        <div class="w-full lg:w-[280px] lg:shrink-0">
            <div class="bg-white rounded-[10px] border border-[#E5E8EC] overflow-hidden">
                <div class="px-[18px] py-3.5 border-b border-[#F0F2F5]">
                    <div class="text-[13px] font-semibold text-[#1a2a35]">Alur Pengajuan</div>
                </div>
                <div class="px-[18px] py-4">
                    @foreach ($allStatuses as $i => $s)
                    @php
                        $isDone    = $statusOrder !== false && $i < $statusOrder;
                        $isCurrent = $statusOrder !== false && $i === $statusOrder;
                        $dotCls    = $isDone ? 'bg-[#E6F4EA] text-[#1e8e3e]' : ($isCurrent ? 'bg-primary text-white' : 'bg-[#F1F3F4] text-[#9aa0a6]');
                        $textCls   = (!$isDone && !$isCurrent) ? 'text-[#9aa0a6]' : 'text-[#1a2a35]';
                    @endphp
                    <div class="flex gap-3 relative {{ !$loop->last ? 'pb-4' : '' }}">
                        @if (!$loop->last)<div class="absolute left-3 top-[26px] bottom-0 w-px bg-[#E5E8EC]"></div>@endif
                        <div class="w-6 h-6 rounded-full shrink-0 flex items-center justify-center text-[10px] font-semibold relative z-10 {{ $dotCls }}">{{ $isDone ? '✓' : ($i + 1) }}</div>
                        <div class="flex-1 pt-0.5">
                            <div class="text-[12px] font-medium {{ $textCls }}">{{ $stepLabels[$s] }}</div>
                            <div class="text-[11px] text-[#9aa0a6] mt-px">{{ $stepDates[$s] }}</div>
                            @if ($isCurrent)<span class="inline-block mt-1 text-[10px] font-medium text-primary bg-[#E8F4F8] px-1.5 py-px rounded">Tahap saat ini</span>@endif
                        </div>
                    </div>
                    @endforeach
                </div>
            </div>
        </div>

    </div>

@else
    <div class="flex flex-col items-center justify-center py-20 text-center">
        <div class="w-16 h-16 rounded-full bg-[#E8F4F8] flex items-center justify-center mb-4">
            <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="#004B5F" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                <path d="M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V8z"/>
                <polyline points="14 2 14 8 20 8"/>
                <line x1="12" y1="18" x2="12" y2="12"/><line x1="9" y1="15" x2="15" y2="15"/>
            </svg>
        </div>
        <h3 class="text-[15px] font-semibold text-[#1a2a35] mb-2">Belum ada pengajuan RPL</h3>
        <p class="text-[13px] text-[#8a9ba8] max-w-[340px] leading-[1.6] mb-6">
            Mulai perjalanan RPL Anda dengan membuat pengajuan baru.
        </p>
        <a href="{{ route('peserta.pengajuan.buat') }}"
           class="bg-primary hover:bg-[#005f78] text-white text-[13px] font-semibold px-5 py-2.5 rounded-lg transition-colors no-underline">
            Buat Pengajuan Baru
        </a>
    </div>
@endif

</div>
