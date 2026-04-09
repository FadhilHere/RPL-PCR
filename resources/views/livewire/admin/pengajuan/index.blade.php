<?php

use Livewire\Attributes\Layout;
use Livewire\Volt\Component;
use Livewire\WithPagination;
use App\Enums\JenisRplEnum;
use App\Models\PermohonanRpl;
use App\Models\ProgramStudi;

new #[Layout('components.layouts.admin')] class extends Component {
    use WithPagination;

    public string $search        = '';
    public string $filterStatus  = '';
    public string $filterProdi   = '';
    public string $filterJenisRpl = '';

    public function updatedSearch(): void { $this->resetPage(); }
    public function updatedFilterStatus(): void { $this->resetPage(); }
    public function updatedFilterProdi(): void { $this->resetPage(); }
    public function updatedFilterJenisRpl(): void { $this->resetPage(); }

    public function with(): array
    {
        $query = PermohonanRpl::query()
            ->with(['peserta.user', 'programStudi'])
            ->when($this->search, fn($q) => $q->whereHas('peserta.user', function ($q) {
                $q->where('nama', 'like', "%{$this->search}%");
            }))
            ->when($this->filterStatus, fn($q) => $q->where('status', $this->filterStatus))
            ->when($this->filterJenisRpl, fn($q) => $q->where('jenis_rpl', $this->filterJenisRpl))
            ->when($this->filterProdi,  fn($q) => $q->where('program_studi_id', $this->filterProdi))
            ->latest('tanggal_pengajuan');

        $prodiOptions = ProgramStudi::where('aktif', true)
            ->orderBy('nama')
            ->pluck('nama', 'id')
            ->toArray();

        $jenisRplOptions = collect(JenisRplEnum::cases())
            ->mapWithKeys(fn($e) => [$e->value => $e->label()])
            ->toArray();

        return [
            'permohonanList' => $query->paginate(15),
            'prodiOptions'   => $prodiOptions,
            'jenisRplOptions' => $jenisRplOptions,
        ];
    }
}; ?>

<x-slot:title>Semua Pengajuan</x-slot:title>
<x-slot:subtitle>Daftar seluruh permohonan RPL yang masuk</x-slot:subtitle>

<div>

    {{-- Toolbar --}}
    <div class="flex flex-wrap items-center gap-3 mb-5">
        <div class="flex-1 relative">
            <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-[#8a9ba8]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/>
            </svg>
            <input wire:model.live.debounce.300ms="search" type="text" placeholder="Cari nama peserta..."
                   class="w-full h-[42px] pl-9 pr-4 text-[13px] text-[#1a2a35] bg-white border border-[#E0E5EA] rounded-xl outline-none focus:border-primary focus:ring-2 focus:ring-primary/10 transition" />
        </div>
        <x-form.select wire:model.live="filterStatus" placeholder="Semua Status"
            :options="collect(\App\Enums\StatusPermohonanEnum::cases())->mapWithKeys(fn($e) => [$e->value => $e->label()])->all()"
            class="w-[180px]" />
        <x-form.select wire:model.live="filterJenisRpl" placeholder="Semua Jenis RPL"
            :options="$jenisRplOptions"
            class="w-[220px]" />
        <x-form.select wire:model.live="filterProdi" placeholder="Semua Prodi"
            :options="$prodiOptions"
            class="w-[200px]" />
    </div>

    {{-- Tabel --}}
    <div class="bg-white rounded-xl border border-[#E5E8EC] overflow-hidden">
        <table class="w-full text-[13px]">
            <thead>
                <tr class="border-b border-[#F0F2F5]">
                    <th class="text-left px-5 py-3.5 text-[11px] font-semibold text-[#8a9ba8] uppercase tracking-[0.5px]">No. Permohonan</th>
                    <th class="text-left px-5 py-3.5 text-[11px] font-semibold text-[#8a9ba8] uppercase tracking-[0.5px]">Peserta</th>
                    <th class="text-left px-5 py-3.5 text-[11px] font-semibold text-[#8a9ba8] uppercase tracking-[0.5px]">Program Studi</th>
                    <th class="text-left px-5 py-3.5 text-[11px] font-semibold text-[#8a9ba8] uppercase tracking-[0.5px]">Tgl Pengajuan</th>
                    <th class="text-left px-5 py-3.5 text-[11px] font-semibold text-[#8a9ba8] uppercase tracking-[0.5px]">Status</th>
                    <th class="px-5 py-3.5"></th>
                </tr>
            </thead>
            <tbody>
                @forelse ($permohonanList as $p)
                <tr class="border-b border-[#F6F8FA] last:border-0 hover:bg-[#FAFBFC] transition-colors" wire:key="prm-{{ $p->id }}">
                    <td class="px-5 py-3.5 font-medium text-[#1a2a35]">{{ $p->nomor_permohonan }}</td>
                    <td class="px-5 py-3.5 text-[#1a2a35]">{{ $p->peserta->user->nama ?? '-' }}</td>
                    <td class="px-5 py-3.5 text-[#5a6a75]">{{ $p->programStudi->nama ?? '-' }}</td>
                    <td class="px-5 py-3.5 text-[#5a6a75]">
                        {{ $p->tanggal_pengajuan ? $p->tanggal_pengajuan->format('d M Y') : '-' }}
                    </td>
                    <td class="px-5 py-3.5">
                        <span class="text-[11px] font-semibold px-2.5 py-1 rounded-full {{ $p->status->badgeClass() }}">{{ $p->status->label() }}</span>
                    </td>
                    <td class="px-5 py-3.5 text-right">
                        <a href="{{ route('admin.pengajuan.detail', $p->id) }}"
                           class="text-[12px] font-semibold text-primary hover:text-[#005f78] transition-colors no-underline">
                            Lihat Detail →
                        </a>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="6" class="px-5 py-12 text-center text-[13px] text-[#8a9ba8]">
                        Belum ada permohonan RPL.
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>

        @if ($permohonanList->hasPages())
        <div class="px-5 py-3 border-t border-[#F0F2F5]">
            {{ $permohonanList->links() }}
        </div>
        @endif
    </div>

</div>
