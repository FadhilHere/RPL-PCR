<?php

use Livewire\Attributes\Layout;
use Livewire\Volt\Component;
use Livewire\WithPagination;
use App\Models\PermohonanRpl;
use App\Enums\JenisRplEnum;
use App\Enums\StatusPermohonanEnum;

new #[Layout('components.layouts.asesor')] class extends Component {
    use WithPagination;

    public string $search = '';
    public string $filterStatus = '';

    public function updatedSearch(): void { $this->resetPage(); }
    public function updatedFilterStatus(): void { $this->resetPage(); }

    public function with(): array
    {
        $asesor   = auth()->user()->asesor;
        $prodiIds = $asesor ? $asesor->programStudi()->pluck('program_studi_id')->toArray() : [];

        $query = PermohonanRpl::query()
            ->with(['peserta.user', 'programStudi'])
            ->whereIn('status', [StatusPermohonanEnum::Diproses, StatusPermohonanEnum::Verifikasi, StatusPermohonanEnum::DalamReview, StatusPermohonanEnum::Disetujui])
            ->whereIn('program_studi_id', $prodiIds)
            ->when($this->search, fn($q) => $q->whereHas('peserta.user', function ($q) {
                $q->where('nama', 'like', "%{$this->search}%");
            }))
            ->when($this->filterStatus, fn($q) => $q->where('status', $this->filterStatus))
            ->latest('tanggal_pengajuan');

        return [
            'permohonanList'     => $query->paginate(15),
            'tidakAdaAssignment' => empty($prodiIds),
        ];
    }
}; ?>

<x-slot:title>Pengajuan RPL</x-slot:title>
<x-slot:subtitle>Daftar permohonan RPL yang perlu dievaluasi</x-slot:subtitle>

<div>

    {{-- Toolbar --}}
    <div class="flex items-center gap-3 mb-5">
        <div class="flex-1 relative">
            <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-[#8a9ba8]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/>
            </svg>
            <input wire:model.live.debounce.300ms="search" type="text" placeholder="Cari nama peserta..."
                   class="w-full h-[42px] pl-9 pr-4 text-[13px] text-[#1a2a35] bg-white border border-[#E0E5EA] rounded-xl outline-none focus:border-primary focus:ring-2 focus:ring-primary/10 transition" />
        </div>
        <x-form.select wire:model.live="filterStatus" placeholder="Semua Status"
            :options="collect([\App\Enums\StatusPermohonanEnum::Diproses, \App\Enums\StatusPermohonanEnum::Verifikasi, \App\Enums\StatusPermohonanEnum::DalamReview, \App\Enums\StatusPermohonanEnum::Disetujui])->mapWithKeys(fn($e) => [$e->value => $e->label()])->all()"
            class="w-[180px]" />
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
                        @php
                            $isTransfer = $p->jenis_rpl === JenisRplEnum::RplI;
                            $evalRoute  = $isTransfer
                                ? route('asesor.evaluasi.transfer', $p->id)
                                : route('asesor.evaluasi.index', $p->id);
                        @endphp
                        @if ($isTransfer)
                        <span class="text-[10px] font-semibold px-2 py-0.5 rounded-full bg-[#FFF3E0] text-[#b45309] mr-1.5">Transfer</span>
                        @endif
                        @if ($p->status === StatusPermohonanEnum::DalamReview)
                        <a href="{{ $evalRoute }}"
                           class="text-[12px] font-semibold text-white bg-primary hover:bg-[#005f78] px-3.5 py-1.5 rounded-lg transition-colors no-underline">
                            {{ $isTransfer ? 'Nilai Transfer →' : 'Evaluasi VATM →' }}
                        </a>
                        @else
                        <a href="{{ $evalRoute }}"
                           class="text-[12px] font-semibold text-primary hover:text-[#005f78] transition-colors no-underline">
                            Lihat Detail →
                        </a>
                        @endif
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="6" class="px-5 py-12 text-center text-[13px] text-[#8a9ba8]">
                        @if ($tidakAdaAssignment)
                            Anda belum ditugaskan ke program studi manapun. Hubungi admin untuk pengaturan.
                        @else
                            Tidak ada pengajuan masuk.
                        @endif
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
