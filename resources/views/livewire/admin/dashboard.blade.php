<?php

use Livewire\Attributes\Layout;
use Livewire\Volt\Component;
use App\Models\PermohonanRpl;
use App\Models\User;
use App\Enums\StatusPermohonanEnum;
use App\Enums\RoleEnum;

new #[Layout('components.layouts.admin')] class extends Component {
    public function with(): array
    {
        $totalPengajuan = PermohonanRpl::count();
        $menunggu       = PermohonanRpl::where('status', StatusPermohonanEnum::Diajukan)->count();
        $aktif          = PermohonanRpl::whereIn('status', [
            StatusPermohonanEnum::Diproses,
            StatusPermohonanEnum::Verifikasi,
            StatusPermohonanEnum::DalamReview,
        ])->count();
        $selesai        = PermohonanRpl::whereIn('status', [
            StatusPermohonanEnum::Disetujui,
            StatusPermohonanEnum::Ditolak,
        ])->count();

        $distribusi = collect(StatusPermohonanEnum::cases())->map(fn($s) => [
            'status' => $s,
            'count'  => PermohonanRpl::where('status', $s)->count(),
        ])->filter(fn($d) => $d['count'] > 0)->values();

        $pengajuanTerbaru = PermohonanRpl::with(['peserta.user', 'programStudi'])
            ->orderByDesc('created_at')
            ->limit(7)
            ->get();

        $totalAsesor  = User::where('role', RoleEnum::Asesor)->count();
        $totalPeserta = User::where('role', RoleEnum::Peserta)->count();

        return compact(
            'totalPengajuan', 'menunggu', 'aktif', 'selesai',
            'distribusi', 'pengajuanTerbaru', 'totalAsesor', 'totalPeserta'
        );
    }
}; ?>

<x-slot:title>Selamat datang, {{ auth()->user()->nama }}</x-slot:title>
<x-slot:subtitle>{{ \Carbon\Carbon::now()->locale('id')->translatedFormat('l\, d F Y') }}</x-slot:subtitle>

<div>

    {{-- ===== STAT CARDS ROW 1 ===== --}}
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-4">

        <div class="bg-white rounded-[10px] border border-[#E5E8EC] px-5 py-4">
            <div class="text-[11px] font-medium text-[#8a9ba8] mb-1">Total Pengajuan</div>
            <div class="text-[28px] font-bold text-[#004B5F] leading-none mb-1">{{ $totalPengajuan }}</div>
            <div class="text-[11px] text-[#8a9ba8]">Semua status</div>
        </div>

        <div class="bg-white rounded-[10px] border border-[#E5E8EC] px-5 py-4">
            <div class="text-[11px] font-medium text-[#8a9ba8] mb-1">Menunggu Tindakan</div>
            <div class="text-[28px] font-bold text-[#1557b0] leading-none mb-1">{{ $menunggu }}</div>
            <div class="text-[11px] text-[#8a9ba8]">Status diajukan</div>
        </div>

        <div class="bg-white rounded-[10px] border border-[#E5E8EC] px-5 py-4">
            <div class="text-[11px] font-medium text-[#8a9ba8] mb-1">Sedang Berjalan</div>
            <div class="text-[28px] font-bold text-[#b45309] leading-none mb-1">{{ $aktif }}</div>
            <div class="text-[11px] text-[#8a9ba8]">Diproses, verifikasi, review</div>
        </div>

        <div class="bg-white rounded-[10px] border border-[#E5E8EC] px-5 py-4">
            <div class="text-[11px] font-medium text-[#8a9ba8] mb-1">Selesai</div>
            <div class="text-[28px] font-bold text-[#1e7e3e] leading-none mb-1">{{ $selesai }}</div>
            <div class="text-[11px] text-[#8a9ba8]">Disetujui atau ditolak</div>
        </div>

    </div>

    {{-- ===== STAT CARDS ROW 2 + DISTRIBUSI ===== --}}
    <div class="flex flex-col lg:flex-row gap-4 mb-4">

        {{-- User stats --}}
        <div class="flex gap-4 lg:w-[340px] lg:shrink-0">
            <div class="flex-1 bg-white rounded-[10px] border border-[#E5E8EC] px-5 py-4">
                <div class="text-[11px] font-medium text-[#8a9ba8] mb-1">Total Asesor</div>
                <div class="text-[28px] font-bold text-[#004B5F] leading-none mb-1">{{ $totalAsesor }}</div>
                <div class="text-[11px] text-[#8a9ba8]">Akun aktif</div>
            </div>
            <div class="flex-1 bg-white rounded-[10px] border border-[#E5E8EC] px-5 py-4">
                <div class="text-[11px] font-medium text-[#8a9ba8] mb-1">Total Peserta</div>
                <div class="text-[28px] font-bold text-[#004B5F] leading-none mb-1">{{ $totalPeserta }}</div>
                <div class="text-[11px] text-[#8a9ba8]">Akun aktif</div>
            </div>
        </div>

        {{-- Distribusi status --}}
        <div class="flex-1 bg-white rounded-[10px] border border-[#E5E8EC] overflow-hidden">
            <div class="px-[18px] py-3.5 border-b border-[#F0F2F5]">
                <div class="text-[13px] font-semibold text-[#1a2a35]">Distribusi Status Pengajuan</div>
            </div>
            @if ($distribusi->isEmpty())
            <div class="px-[18px] py-5 text-center text-[12px] text-[#8a9ba8]">Belum ada data pengajuan.</div>
            @else
            <div class="px-[18px] py-3 flex flex-wrap gap-2">
                @foreach ($distribusi as $d)
                <span class="inline-flex items-center gap-1.5 text-[12px] font-medium px-3 py-1.5 rounded-full {{ $d['status']->badgeClass() }}">
                    {{ $d['status']->label() }}
                    <span class="font-bold">{{ $d['count'] }}</span>
                </span>
                @endforeach
            </div>
            @endif
        </div>

    </div>

    {{-- ===== BOTTOM GRID ===== --}}
    <div class="flex flex-col lg:flex-row gap-4">

        {{-- Tabel pengajuan terbaru --}}
        <div class="flex-1 bg-white rounded-[10px] border border-[#E5E8EC] overflow-hidden">
            <div class="flex items-center justify-between px-[18px] py-3.5 border-b border-[#F0F2F5]">
                <div class="text-[13px] font-semibold text-[#1a2a35]">Pengajuan Terbaru</div>
                <a href="{{ route('admin.pengajuan.index') }}" class="text-[12px] text-primary font-medium hover:underline no-underline">Lihat semua</a>
            </div>
            @if ($pengajuanTerbaru->isEmpty())
            <div class="px-[18px] py-8 text-center text-[12px] text-[#8a9ba8]">Belum ada pengajuan.</div>
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
                                <a href="{{ route('admin.pengajuan.detail', $p) }}" class="text-primary font-medium hover:underline no-underline">
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
                            <td class="px-3 py-3 pr-[18px] text-[#8a9ba8]">
                                {{ $p->created_at->format('d M Y') }}
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            @endif
        </div>

        {{-- Quick actions --}}
        <div class="w-full lg:w-[220px] lg:shrink-0 flex flex-col gap-3">
            <div class="bg-white rounded-[10px] border border-[#E5E8EC] px-[18px] py-3.5">
                <div class="text-[13px] font-semibold text-[#1a2a35] mb-3">Aksi Cepat</div>
                <div class="flex flex-col gap-2">
                    <a href="{{ route('admin.akun.index') }}"
                       class="flex items-center gap-2.5 text-[12px] font-medium text-[#1a2a35] hover:text-primary transition-colors no-underline group">
                        <div class="w-7 h-7 rounded-lg bg-[#E8F4F8] flex items-center justify-center shrink-0 group-hover:bg-[#D0EBF4] transition-colors">
                            <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="#004B5F" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M17 21v-2a4 4 0 00-4-4H5a4 4 0 00-4 4v2"/>
                                <circle cx="9" cy="7" r="4"/>
                                <path d="M23 21v-2a4 4 0 00-3-3.87"/>
                                <path d="M16 3.13a4 4 0 010 7.75"/>
                            </svg>
                        </div>
                        Kelola Akun
                    </a>
                    <a href="{{ route('admin.materi.index') }}"
                       class="flex items-center gap-2.5 text-[12px] font-medium text-[#1a2a35] hover:text-primary transition-colors no-underline group">
                        <div class="w-7 h-7 rounded-lg bg-[#E8F4F8] flex items-center justify-center shrink-0 group-hover:bg-[#D0EBF4] transition-colors">
                            <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="#004B5F" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M4 19.5A2.5 2.5 0 016.5 17H20"/>
                                <path d="M6.5 2H20v20H6.5A2.5 2.5 0 014 19.5v-15A2.5 2.5 0 016.5 2z"/>
                            </svg>
                        </div>
                        Materi Asesmen
                    </a>
                    <a href="{{ route('admin.pengajuan.index') }}"
                       class="flex items-center gap-2.5 text-[12px] font-medium text-[#1a2a35] hover:text-primary transition-colors no-underline group">
                        <div class="w-7 h-7 rounded-lg bg-[#E8F4F8] flex items-center justify-center shrink-0 group-hover:bg-[#D0EBF4] transition-colors">
                            <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="#004B5F" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V8z"/>
                                <polyline points="14 2 14 8 20 8"/>
                                <line x1="16" y1="13" x2="8" y2="13"/>
                                <line x1="16" y1="17" x2="8" y2="17"/>
                                <polyline points="10 9 9 9 8 9"/>
                            </svg>
                        </div>
                        Semua Pengajuan
                    </a>
                    <a href="{{ route('admin.prodi.index') }}"
                       class="flex items-center gap-2.5 text-[12px] font-medium text-[#1a2a35] hover:text-primary transition-colors no-underline group">
                        <div class="w-7 h-7 rounded-lg bg-[#E8F4F8] flex items-center justify-center shrink-0 group-hover:bg-[#D0EBF4] transition-colors">
                            <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="#004B5F" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <rect x="3" y="3" width="7" height="7"/>
                                <rect x="14" y="3" width="7" height="7"/>
                                <rect x="14" y="14" width="7" height="7"/>
                                <rect x="3" y="14" width="7" height="7"/>
                            </svg>
                        </div>
                        Program Studi
                    </a>
                </div>
            </div>
        </div>

    </div>

</div>
