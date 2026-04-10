<?php

use Livewire\Attributes\Layout;
use Livewire\Volt\Component;
use Livewire\WithPagination;
use App\Enums\JenisRplEnum;
use App\Enums\SemesterEnum;
use App\Enums\StatusPermohonanEnum;
use App\Enums\StatusRplMataKuliahEnum;
use App\Models\PermohonanRpl;

new #[Layout('components.layouts.asesor')] class extends Component {
    use WithPagination;

    public string $search       = '';
    public string $filterJenisRpl = '';
    public string $filterSemester = '';
    public string $filterStatus = '';
    public string $filterTanggalDari = '';
    public string $filterTanggalSampai = '';

    public function updatedSearch(): void       { $this->resetPage(); }
    public function updatedFilterJenisRpl(): void { $this->resetPage(); }
    public function updatedFilterSemester(): void { $this->resetPage(); }
    public function updatedFilterStatus(): void { $this->resetPage(); }
    public function updatedFilterTanggalDari(): void { $this->resetPage(); }
    public function updatedFilterTanggalSampai(): void { $this->resetPage(); }

    public function clearDateFilter(): void
    {
        $this->filterTanggalDari = '';
        $this->filterTanggalSampai = '';
        $this->resetPage();
    }

    public function with(): array
    {
        $asesor = auth()->user()->asesor;
        // Hanya render permohonan yang telah diassign ke asesor ini.
        $baseQuery = $asesor ? $asesor->permohonan() : PermohonanRpl::query()->where('id', 0);

        $list = (clone $baseQuery)
            ->with([
                'peserta.user',
                'programStudi',
                'tahunAjaran',
                'rplMataKuliah.mataKuliah',
            ])
            ->whereIn('status', [
                StatusPermohonanEnum::Diproses,
                StatusPermohonanEnum::Asesmen,
                StatusPermohonanEnum::Verifikasi,
                StatusPermohonanEnum::Disetujui,
                StatusPermohonanEnum::Ditolak,
            ])
            ->when($this->search, fn($q) =>
                $q->whereHas('peserta.user', fn($q2) =>
                    $q2->where('nama', 'like', "%{$this->search}%")
                )
            )
            ->when($this->filterJenisRpl, fn($q) =>
                $q->where('jenis_rpl', $this->filterJenisRpl)
            )
            ->when($this->filterSemester, fn($q) =>
                $q->where('semester', $this->filterSemester)
            )
            ->when($this->filterStatus, fn($q) =>
                $q->where('status', $this->filterStatus)
            )
            ->when($this->filterTanggalDari, fn($q) =>
                $q->whereDate('tanggal_pengajuan', '>=', $this->filterTanggalDari)
            )
            ->when($this->filterTanggalSampai, fn($q) =>
                $q->whereDate('tanggal_pengajuan', '<=', $this->filterTanggalSampai)
            )
            ->latest('tanggal_pengajuan')
            ->paginate(20);

        $statusOptions = collect([
            StatusPermohonanEnum::Diproses,
            StatusPermohonanEnum::Asesmen,
            StatusPermohonanEnum::Verifikasi,
            StatusPermohonanEnum::Disetujui,
            StatusPermohonanEnum::Ditolak,
        ])->mapWithKeys(fn($e) => [$e->value => $e->label()])->toArray();

        $jenisRplOptions = collect(JenisRplEnum::cases())
            ->mapWithKeys(fn($e) => [$e->value => $e->label()])
            ->toArray();

        $semesterOptions = SemesterEnum::options();

        $tidakAdaAssignment = ! $asesor;

        return compact('list', 'statusOptions', 'jenisRplOptions', 'semesterOptions', 'tidakAdaAssignment');
    }
}; ?>

<x-slot:title>Resume</x-slot:title>
<x-slot:subtitle>Daftar resume peserta RPL yang di-assign ke Anda</x-slot:subtitle>

<div>

    {{-- Filter toolbar --}}
    <div class="flex items-center gap-3 mb-5 flex-wrap">
        <div class="relative">
            <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-3.5 h-3.5 text-[#8a9ba8]"
                 viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/>
            </svg>
            <input wire:model.live.debounce.300ms="search" type="text"
                   placeholder="Cari nama peserta..."
                   class="h-[38px] pl-8 pr-3 text-[13px] text-[#1a2a35] bg-white border border-[#E0E5EA] rounded-lg outline-none focus:border-primary focus:ring-2 focus:ring-primary/10 w-[220px] placeholder:text-[#b0bec5]" />
        </div>
        <x-form.select wire:model.live="filterStatus"
                       placeholder="Semua Status"
                       :options="$statusOptions"
                       class="w-[160px]" />
        <x-form.select wire:model.live="filterJenisRpl"
                       placeholder="Semua Jenis RPL"
                       :options="$jenisRplOptions"
                       class="w-[220px]" />
        <x-form.select wire:model.live="filterSemester"
                       placeholder="Semua Semester"
                       :options="$semesterOptions"
                       class="w-[170px]" />
        <div class="flex items-center gap-2">
            <x-form.date-picker wire:model.live="filterTanggalDari"
                                placeholder="Dari tanggal..."
                                :enable-time="false"
                                class="w-[175px]" />
            <span class="text-[12px] text-[#8a9ba8]">—</span>
            <x-form.date-picker wire:model.live="filterTanggalSampai"
                                placeholder="Sampai tanggal..."
                                :enable-time="false"
                                class="w-[175px]" />
            @if ($filterTanggalDari || $filterTanggalSampai)
            <button wire:click="clearDateFilter"
                    title="Reset filter tanggal"
                    class="w-[38px] h-[38px] flex items-center justify-center rounded-lg border border-[#D0D5DD] text-[#8a9ba8] hover:border-[#c62828] hover:text-[#c62828] hover:bg-[#FCE8E6] transition-colors">
                <svg class="w-3.5 h-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
            </button>
            @endif
        </div>
        <div class="ml-auto flex items-center gap-2">
            <a href="{{ route('export.resume.asesor.excel', array_filter(['jenis_rpl' => $filterJenisRpl, 'semester' => $filterSemester, 'tanggal_dari' => $filterTanggalDari, 'tanggal_sampai' => $filterTanggalSampai])) }}"
               class="flex items-center gap-1.5 h-[38px] px-3.5 text-[12px] font-semibold text-[#1e7e3e] border border-[#A8D5B5] rounded-lg hover:bg-[#E6F4EA] transition-colors no-underline">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M21 15v4a2 2 0 01-2 2H5a2 2 0 01-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/>
                </svg>
                Excel
            </a>
                <a href="{{ route('export.resume.asesor.pdf', array_filter(['jenis_rpl' => $filterJenisRpl, 'semester' => $filterSemester, 'tanggal_dari' => $filterTanggalDari, 'tanggal_sampai' => $filterTanggalSampai])) }}"
               class="flex items-center gap-1.5 h-[38px] px-3.5 text-[12px] font-semibold text-[#c62828] border border-[#F5C6C6] rounded-lg hover:bg-[#FCE8E6] transition-colors no-underline">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M21 15v4a2 2 0 01-2 2H5a2 2 0 01-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/>
                </svg>
                PDF
            </a>
        </div>
    </div>

    {{-- Tabel --}}
    <div class="bg-white rounded-[10px] border border-[#E5E8EC] overflow-hidden">
        <table class="w-full">
            <thead>
                <tr class="border-b border-[#F0F2F5]">
                    <th class="text-left px-5 py-3 text-[11px] font-semibold text-[#8a9ba8] uppercase tracking-[0.5px]">No. Permohonan</th>
                    <th class="text-left px-4 py-3 text-[11px] font-semibold text-[#8a9ba8] uppercase tracking-[0.5px]">Peserta</th>
                    <th class="text-left px-4 py-3 text-[11px] font-semibold text-[#8a9ba8] uppercase tracking-[0.5px]">Program Studi</th>
                    <th class="text-center px-4 py-3 text-[11px] font-semibold text-[#8a9ba8] uppercase tracking-[0.5px]">Jenis RPL</th>
                    <th class="text-left px-4 py-3 text-[11px] font-semibold text-[#8a9ba8] uppercase tracking-[0.5px]">Tahun Ajaran</th>
                    <th class="text-center px-4 py-3 text-[11px] font-semibold text-[#8a9ba8] uppercase tracking-[0.5px]">Status</th>
                    <th class="text-center px-4 py-3 text-[11px] font-semibold text-[#8a9ba8] uppercase tracking-[0.5px]">MK Diakui</th>
                    <th class="text-center px-4 py-3 text-[11px] font-semibold text-[#8a9ba8] uppercase tracking-[0.5px]">SKS</th>
                    <th class="text-center px-4 py-3 text-[11px] font-semibold text-[#8a9ba8] uppercase tracking-[0.5px]">Aksi</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($list as $p)
                @php
                    $totalMk   = $p->rplMataKuliah->count();
                    $totalSks  = $p->rplMataKuliah->sum(fn($m) => $m->mataKuliah->sks ?? 0);
                    $mkDiakui  = $p->rplMataKuliah->where('status', \App\Enums\StatusRplMataKuliahEnum::Diakui)->count();
                    $sksDiakui = $p->rplMataKuliah->where('status', \App\Enums\StatusRplMataKuliahEnum::Diakui)->sum(fn($m) => $m->mataKuliah->sks ?? 0);
                    $jenisRplTextClass = match ($p->jenis_rpl) {
                        JenisRplEnum::RplI => 'text-[#0f5c8b]',
                        JenisRplEnum::RplII => 'text-[#8b5e00]',
                        default => 'text-[#5a6a75]',
                    };
                @endphp
                <tr class="border-b border-[#F6F8FA] last:border-0 hover:bg-[#FAFBFC] transition-colors" wire:key="p-{{ $p->id }}">
                    <td class="px-5 py-3.5">
                        <div class="text-[12px] font-mono font-medium text-[#1a2a35]">{{ $p->nomor_permohonan }}</div>
                        <div class="text-[11px] text-[#8a9ba8]">{{ $p->tanggal_pengajuan?->format('d M Y') ?? '—' }}</div>
                    </td>
                    <td class="px-4 py-3.5">
                        <div class="text-[12px] font-medium text-[#1a2a35]">{{ $p->peserta?->user?->nama ?? '—' }}</div>
                    </td>
                    <td class="px-4 py-3.5 text-[12px] text-[#5a6a75]">{{ $p->programStudi?->nama ?? '—' }}</td>
                    <td class="px-4 py-3.5 text-center">
                        <span class="text-[11px] font-semibold {{ $jenisRplTextClass }}">
                            {{ $p->jenis_rpl?->label() ?? '—' }}
                        </span>
                    </td>
                    <td class="px-4 py-3.5 text-[12px] text-[#5a6a75]">
                        {{ $p->tahunAjaran?->nama ?? '—' }}
                        @if ($p->semester)
                        <span class="text-[#b0bec5]">· {{ $p->semester->label() }}</span>
                        @endif
                    </td>
                    <td class="px-4 py-3.5 text-center">
                        <span class="text-[10px] font-semibold px-2 py-0.5 rounded-full {{ $p->status->badgeClass() }}">
                            {{ $p->status->label() }}
                        </span>
                    </td>
                    <td class="px-4 py-3.5 text-center">
                        @if ($totalMk > 0)
                        <span class="text-[12px] font-semibold {{ $mkDiakui > 0 ? 'text-[#1e7e3e]' : 'text-[#5a6a75]' }}">{{ $mkDiakui }}</span>
                        <span class="text-[11px] text-[#b0bec5]"> / {{ $totalMk }}</span>
                        @else
                        <span class="text-[11px] text-[#b0bec5]">—</span>
                        @endif
                    </td>
                    <td class="px-4 py-3.5 text-center">
                        @if ($totalMk > 0)
                        <span class="text-[12px] font-semibold {{ $sksDiakui > 0 ? 'text-[#1e7e3e]' : 'text-[#5a6a75]' }}">{{ $sksDiakui }}</span>
                        <span class="text-[11px] text-[#b0bec5]"> / {{ $totalSks }}</span>
                        @else
                        <span class="text-[11px] text-[#b0bec5]">—</span>
                        @endif
                    </td>
                    <td class="px-4 py-3.5 text-center">
                        <a href="{{ route('asesor.evaluasi.resume', $p) }}"
                           class="inline-flex items-center gap-1.5 h-[30px] px-3 text-[11px] font-semibold text-primary border border-primary/30 rounded-lg hover:bg-primary hover:text-white transition-colors no-underline">
                            <svg class="w-3 h-3" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V8z"/><polyline points="14 2 14 8 20 8"/>
                                <line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/>
                            </svg>
                            Resume
                        </a>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="9" class="px-5 py-10 text-center text-[13px] text-[#8a9ba8]">
                        @if ($tidakAdaAssignment)
                            Anda belum memiliki profil Asesor yang aktif. Hubungi admin untuk pengaturan.
                        @else
                            Tidak ada permohonan yang di-assign ke Anda saat ini.
                        @endif
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>


    @if ($list->hasPages())
    <div class="mt-4">{{ $list->links() }}</div>
    @endif

</div>
