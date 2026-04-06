<?php

use Livewire\Attributes\Layout;
use Livewire\Volt\Component;
use Livewire\WithPagination;
use App\Enums\StatusPermohonanEnum;
use App\Enums\StatusRplMataKuliahEnum;
use App\Models\PermohonanRpl;
use App\Models\ProgramStudi;

new #[Layout('components.layouts.admin')] class extends Component {
    use WithPagination;

    public string $search       = '';
    public string $filterProdi  = '';
    public string $filterStatus = '';

    public function updatedSearch(): void       { $this->resetPage(); }
    public function updatedFilterProdi(): void  { $this->resetPage(); }
    public function updatedFilterStatus(): void { $this->resetPage(); }

    public function with(): array
    {
        $list = PermohonanRpl::with([
                'peserta.user',
                'programStudi',
                'tahunAjaran',
                'rplMataKuliah.mataKuliah',
            ])
            ->whereNot('status', StatusPermohonanEnum::Draf)
            ->when($this->search, fn($q) =>
                $q->whereHas('peserta.user', fn($q2) =>
                    $q2->where('nama', 'like', "%{$this->search}%")
                )
            )
            ->when($this->filterProdi, fn($q) =>
                $q->where('program_studi_id', $this->filterProdi)
            )
            ->when($this->filterStatus, fn($q) =>
                $q->where('status', $this->filterStatus)
            )
            ->latest('tanggal_pengajuan')
            ->paginate(20);

        $prodiOptions = ProgramStudi::where('aktif', true)
            ->orderBy('nama')
            ->get()
            ->mapWithKeys(fn($p) => [$p->id => $p->nama . ' (' . $p->kode . ')'])
            ->toArray();

        $statusOptions = collect(StatusPermohonanEnum::cases())
            ->filter(fn($e) => $e !== StatusPermohonanEnum::Draf)
            ->mapWithKeys(fn($e) => [$e->value => $e->label()])
            ->toArray();

        return compact('list', 'prodiOptions', 'statusOptions');
    }
}; ?>

<x-slot:title>Resume Pleno</x-slot:title>
<x-slot:subtitle>Daftar resume pleno seluruh peserta RPL</x-slot:subtitle>

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
        <x-form.select wire:model.live="filterProdi"
                       placeholder="Semua Prodi"
                       :options="$prodiOptions"
                       class="w-[220px]" />
        <x-form.select wire:model.live="filterStatus"
                       placeholder="Semua Status"
                       :options="$statusOptions"
                       class="w-[160px]" />
    </div>

    {{-- Tabel --}}
    <div class="bg-white rounded-[10px] border border-[#E5E8EC] overflow-hidden">
        <table class="w-full">
            <thead>
                <tr class="border-b border-[#F0F2F5]">
                    <th class="text-left px-5 py-3 text-[11px] font-semibold text-[#8a9ba8] uppercase tracking-[0.5px]">No. Permohonan</th>
                    <th class="text-left px-4 py-3 text-[11px] font-semibold text-[#8a9ba8] uppercase tracking-[0.5px]">Peserta</th>
                    <th class="text-left px-4 py-3 text-[11px] font-semibold text-[#8a9ba8] uppercase tracking-[0.5px]">Program Studi</th>
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
                        <a href="{{ route('admin.pleno.detail', $p) }}"
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
                    <td colspan="8" class="px-5 py-10 text-center text-[13px] text-[#8a9ba8]">Belum ada data permohonan.</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if ($list->hasPages())
    <div class="mt-4">{{ $list->links() }}</div>
    @endif

</div>
