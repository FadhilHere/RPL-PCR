<?php

use Livewire\Attributes\Layout;
use Livewire\Volt\Component;
use Livewire\WithPagination;
use App\Actions\Asesor\SimpanJadwalVerifikasiAction;
use App\Enums\StatusVerifikasiEnum;
use App\Models\PermohonanRpl;
use App\Models\ProgramStudi;
use App\Models\VerifikasiBersama;

new #[Layout('components.layouts.admin')] class extends Component {
    use WithPagination;

    public string $filterProdi      = '';
    public string $filterStatus     = '';
    public string $filterTanggalDari = '';
    public string $filterTanggalSampai = '';
    public ?int $formPermohonanId   = null;
    public string $searchPermohonan = '';

    public function updatedFilterProdi(): void  { $this->resetPage(); }
    public function updatedFilterStatus(): void { $this->resetPage(); }
    public function updatedFilterTanggalDari(): void { $this->resetPage(); }
    public function updatedFilterTanggalSampai(): void { $this->resetPage(); }

    public function clearDateFilter(): void
    {
        $this->filterTanggalDari   = '';
        $this->filterTanggalSampai = '';
        $this->resetPage();
    }

    public function simpanJadwal(?string $permohonanId, string $tanggal, ?string $catatan, ?string $editId, SimpanJadwalVerifikasiAction $action): void
    {
        if (empty($tanggal)) {
            $this->addError('tanggal', 'Tanggal jadwal wajib diisi.');
            return;
        }

        if ($editId) {
            VerifikasiBersama::findOrFail((int) $editId)->update([
                'jadwal'  => $tanggal,
                'catatan' => $catatan ?: null,
            ]);
        } else {
            abort_if(empty($permohonanId), 422);
            $permohonan = PermohonanRpl::findOrFail((int) $permohonanId);
            $action->execute($permohonan, $tanggal, $catatan ?: null, asesorId: null);
        }
    }

    public function hapusJadwal(int $vbId): void
    {
        VerifikasiBersama::findOrFail($vbId)->delete();
    }

    public function with(): array
    {
        $jadwalList = VerifikasiBersama::with(['permohonanRpl.peserta.user', 'permohonanRpl.programStudi', 'asesor.user'])
            ->when($this->filterProdi, fn($q) =>
                $q->whereHas('permohonanRpl', fn($q2) => $q2->where('program_studi_id', $this->filterProdi))
            )
            ->when($this->filterStatus, fn($q) => $q->where('status', $this->filterStatus))
            ->when($this->filterTanggalDari, fn($q) => $q->whereDate('jadwal', '>=', $this->filterTanggalDari))
            ->when($this->filterTanggalSampai, fn($q) => $q->whereDate('jadwal', '<=', $this->filterTanggalSampai))
            ->orderBy('jadwal', 'desc')
            ->paginate(20);

        $prodiOptions = ProgramStudi::where('aktif', true)
            ->orderBy('nama')
            ->get()
            ->mapWithKeys(fn($p) => [$p->id => $p->nama . ' (' . $p->kode . ')'])
            ->toArray();

        $statusOptions = collect(StatusVerifikasiEnum::cases())
            ->mapWithKeys(fn($e) => [$e->value => $e->label()])
            ->toArray();

        $permohonanOptions = PermohonanRpl::with(['peserta.user', 'programStudi'])
            ->whereIn('status', ['diproses', 'verifikasi'])
            ->get()
            ->mapWithKeys(fn($p) => [$p->id => $p->nomor_permohonan . ' — ' . ($p->peserta->user->nama ?? '?')])
            ->toArray();

        return compact('jadwalList', 'prodiOptions', 'statusOptions', 'permohonanOptions');
    }
}; ?>

<x-slot:title>Jadwal Verifikasi</x-slot:title>
<x-slot:subtitle>Kelola semua jadwal verifikasi bersama RPL</x-slot:subtitle>

<div x-data="{
    form: { open: false, editId: null, tanggal: '', catatan: '' },
    confirmHapus: { open: false, id: null },

    openTambah() {
        this.form = { open: true, editId: null, tanggal: '', catatan: '' };
        $wire.set('formPermohonanId', null);
        $wire.set('searchPermohonan', '');
    },
    openEdit(id, permohonanId, tanggal, catatan) {
        this.form = { open: true, editId: id, tanggal: tanggal || '', catatan: catatan || '' };
    },
    async simpan() {
        await $wire.simpanJadwal($wire.formPermohonanId, this.form.tanggal, this.form.catatan, this.form.editId);
        this.form.open = false;
    },
    openHapus(id) { this.confirmHapus = { open: true, id }; },
    doHapus() { $wire.hapusJadwal(this.confirmHapus.id); this.confirmHapus.open = false; }
}">

    {{-- Toolbar --}}
    <div class="flex items-center gap-3 mb-5 flex-wrap">
        <x-form.select wire:model.live="filterProdi"
                       placeholder="Semua Prodi"
                       :options="$prodiOptions"
                       class="w-[220px]" />
        <x-form.select wire:model.live="filterStatus"
                       placeholder="Semua Status"
                       :options="$statusOptions"
                       class="w-[160px]" />
        <div class="flex items-center gap-2">
            <x-form.date-picker x-model="$wire.filterTanggalDari"
                                placeholder="Dari tanggal..."
                                :enable-time="false"
                                class="w-[175px]" />
            <span class="text-[12px] text-[#8a9ba8]">—</span>
            <x-form.date-picker x-model="$wire.filterTanggalSampai"
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
        <div class="flex-1"></div>
        <button @click="openTambah()"
                class="bg-primary hover:bg-[#005f78] text-white text-[13px] font-semibold px-4 py-2 rounded-lg transition-colors flex items-center gap-2">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                <line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/>
            </svg>
            Tambah Jadwal
        </button>
    </div>

    {{-- Tabel --}}
    <div class="bg-white rounded-[10px] border border-[#E5E8EC] overflow-hidden">
        <table class="w-full">
            <thead>
                <tr class="border-b border-[#F0F2F5]">
                    <th class="text-left px-5 py-3 text-[11px] font-semibold text-[#8a9ba8] uppercase tracking-[0.5px]">Jadwal</th>
                    <th class="text-left px-4 py-3 text-[11px] font-semibold text-[#8a9ba8] uppercase tracking-[0.5px]">Peserta / No. Permohonan</th>
                    <th class="text-left px-4 py-3 text-[11px] font-semibold text-[#8a9ba8] uppercase tracking-[0.5px]">Program Studi</th>
                    <th class="text-left px-4 py-3 text-[11px] font-semibold text-[#8a9ba8] uppercase tracking-[0.5px]">Asesor</th>
                    <th class="text-center px-4 py-3 text-[11px] font-semibold text-[#8a9ba8] uppercase tracking-[0.5px]">Status</th>
                    <th class="text-center px-4 py-3 text-[11px] font-semibold text-[#8a9ba8] uppercase tracking-[0.5px]">Aksi</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($jadwalList as $vb)
                <tr class="border-b border-[#F6F8FA] last:border-0 hover:bg-[#FAFBFC] transition-colors" wire:key="vb-{{ $vb->id }}">
                    <td class="px-5 py-3.5">
                        @if ($vb->jadwal)
                        <div class="text-[13px] font-semibold text-[#1a2a35]">{{ $vb->jadwal->format('d M Y') }}</div>
                        <div class="text-[11px] text-[#8a9ba8]">{{ $vb->jadwal->format('H:i') }} WIB</div>
                        @else
                        <span class="text-[12px] text-[#b0bec5]">—</span>
                        @endif
                    </td>
                    <td class="px-4 py-3.5">
                        <div class="text-[12px] font-medium text-[#1a2a35]">{{ $vb->permohonanRpl?->peserta?->user?->nama ?? '—' }}</div>
                        <div class="text-[11px] text-[#8a9ba8]">{{ $vb->permohonanRpl?->nomor_permohonan ?? '—' }}</div>
                    </td>
                    <td class="px-4 py-3.5 text-[12px] text-[#5a6a75]">{{ $vb->permohonanRpl?->programStudi?->nama ?? '—' }}</td>
                    <td class="px-4 py-3.5 text-[12px] text-[#5a6a75]">{{ $vb->asesor?->user?->nama ?? '—' }}</td>
                    <td class="px-4 py-3.5 text-center">
                        @if ($vb->status)
                        <span class="text-[10px] font-semibold px-2 py-0.5 rounded-full {{ $vb->status->badgeClass() }}">
                            {{ $vb->status->label() }}
                        </span>
                        @else
                        <span class="text-[11px] text-[#b0bec5]">—</span>
                        @endif
                    </td>
                    <td class="px-4 py-3.5 text-center">
                        <div class="flex items-center justify-center gap-1.5">
                            <a href="{{ route('admin.pengajuan.detail', $vb->permohonan_rpl_id) }}"
                               title="Lihat pengajuan"
                               class="w-[32px] h-[32px] rounded-md border border-[#D0D5DD] text-[#5a6a75] hover:border-primary hover:text-primary hover:bg-[#E8F4F8] transition-colors flex items-center justify-center no-underline">
                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
                            </a>
                            <button
                                @click="openEdit({{ $vb->id }}, {{ $vb->permohonan_rpl_id }}, '{{ $vb->jadwal?->format('Y-m-d\TH:i') ?? '' }}', @js($vb->catatan))"
                                title="Edit jadwal"
                                class="w-[32px] h-[32px] rounded-md border border-[#D0D5DD] text-[#5a6a75] hover:border-primary hover:text-primary hover:bg-[#E8F4F8] transition-colors flex items-center justify-center">
                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M11 4H4a2 2 0 00-2 2v14a2 2 0 002 2h14a2 2 0 002-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 013 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
                            </button>
                            <button @click="openHapus({{ $vb->id }})"
                                    title="Hapus jadwal"
                                    class="w-[32px] h-[32px] rounded-md border border-[#D0D5DD] text-[#5a6a75] hover:border-[#c62828] hover:text-[#c62828] hover:bg-[#FCE8E6] transition-colors flex items-center justify-center">
                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="3 6 5 6 21 6"/><path d="M19 6l-1 14a2 2 0 01-2 2H8a2 2 0 01-2-2L5 6"/><path d="M10 11v6M14 11v6"/><path d="M9 6V4a1 1 0 011-1h4a1 1 0 011 1v2"/></svg>
                            </button>
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="6" class="px-5 py-10 text-center text-[13px] text-[#8a9ba8]">Belum ada jadwal verifikasi.</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if ($jadwalList->hasPages())
    <div class="mt-4">{{ $jadwalList->links() }}</div>
    @endif

    {{-- Modal Form Jadwal (Alpine-driven, instant) --}}
    <div x-show="form.open" x-cloak
         class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 p-4"
         @keydown.escape.window="form.open = false"
         x-transition:enter="transition ease-out duration-150"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100">
        <div @click.outside="form.open = false"
             x-transition:enter="transition ease-out duration-150"
             x-transition:enter-start="opacity-0 scale-95"
             x-transition:enter-end="opacity-100 scale-100"
             class="bg-white rounded-2xl shadow-xl w-full max-w-md p-6">

            <h3 class="text-[15px] font-semibold text-[#1a2a35] mb-4"
                x-text="form.editId ? 'Edit Jadwal' : 'Tambah Jadwal Verifikasi'"></h3>

            <div class="space-y-4">
                {{-- Permohonan — hanya saat tambah baru --}}
                <div x-show="!form.editId">
                    <label class="block text-[11px] font-semibold text-[#5a6a75] uppercase tracking-[0.7px] mb-1.5">Permohonan</label>
                    <div x-data="{
                        open: false,
                        search: @entangle('searchPermohonan'),
                        get filtered() {
                            const opts = {{ Js::from($permohonanOptions) }};
                            if (!this.search) return opts;
                            const results = {};
                            for (const [key, val] of Object.entries(opts)) {
                                if (val.toLowerCase().includes(this.search.toLowerCase())) {
                                    results[key] = val;
                                }
                            }
                            return results;
                        }
                    }" class="relative">
                        <div class="relative" @click.outside="open = false">
                            <input type="text"
                                   x-model="search"
                                   @focus="open = true"
                                   @keydown.escape="open = false"
                                   placeholder="Cari permohonan..."
                                   class="w-full h-[42px] px-3.5 text-[13px] bg-white border border-[#E0E5EA] rounded-xl outline-none focus:border-primary focus:ring-2 focus:ring-primary/10 transition-all" />
                            <div x-show="open" x-cloak
                                 class="absolute top-[calc(100%+4px)] left-0 right-0 bg-white border border-[#E0E5EA] rounded-xl shadow-lg overflow-hidden z-10">
                                <div class="py-1 max-h-[220px] overflow-y-auto">
                                    <template x-for="(label, id) in filtered" :key="id">
                                        <button type="button"
                                                @click="@this.set('formPermohonanId', id); open = false; search = label"
                                                class="w-full text-left px-3.5 py-2 text-[13px] text-[#1a2a35] hover:bg-[#F4F6F8] transition-colors"
                                                x-text="label">
                                        </button>
                                    </template>
                                    <div x-show="Object.keys(filtered).length === 0" class="px-3.5 py-3 text-[12px] text-[#b0bec5] text-center">
                                        Tidak ada permohonan
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Date picker --}}
                <div>
                    <label class="block text-[11px] font-semibold text-[#5a6a75] uppercase tracking-[0.7px] mb-1.5">Tanggal & Waktu</label>
                    <x-form.date-picker x-model="form.tanggal" placeholder="Pilih tanggal & waktu..." :enable-time="true" />
                </div>

                <div>
                    <label class="block text-[11px] font-semibold text-[#5a6a75] uppercase tracking-[0.7px] mb-1.5">
                        Catatan <span class="normal-case font-normal text-[#b0bec5]">(opsional)</span>
                    </label>
                    <textarea x-model="form.catatan" rows="2"
                              placeholder="Lokasi, link meeting, atau instruksi..."
                              class="w-full px-3.5 py-2.5 text-[13px] text-[#1a2a35] bg-white border border-[#E0E5EA] rounded-xl outline-none focus:border-primary focus:ring-2 focus:ring-primary/10 resize-none placeholder:text-[#b0bec5]"></textarea>
                </div>
            </div>

            <div class="flex gap-3 mt-6">
                <button @click="form.open = false"
                        class="flex-1 h-[42px] bg-white border border-[#D8DDE2] text-[#1a2a35] text-[13px] font-semibold rounded-xl hover:bg-[#F4F6F8] transition-colors">
                    Batal
                </button>
                <button @click="simpan()"
                        class="flex-1 h-[42px] bg-primary hover:bg-[#005f78] text-white text-[13px] font-semibold rounded-xl transition-colors">
                    Simpan
                </button>
            </div>
        </div>
    </div>

    {{-- Modal Konfirmasi Hapus --}}
    <div x-show="confirmHapus.open" x-cloak
         class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 p-4"
         @keydown.escape.window="confirmHapus.open = false"
         x-transition:enter="transition ease-out duration-150"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100">
        <div @click.outside="confirmHapus.open = false"
             x-transition:enter="transition ease-out duration-150"
             x-transition:enter-start="opacity-0 scale-95"
             x-transition:enter-end="opacity-100 scale-100"
             class="bg-white rounded-2xl shadow-xl w-full max-w-sm p-6">
            <div class="flex items-center gap-3 mb-5">
                <div class="w-10 h-10 rounded-full bg-[#FCE8E6] flex items-center justify-center shrink-0">
                    <svg class="w-5 h-5 text-[#c62828]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <polyline points="3 6 5 6 21 6"/><path d="M19 6l-1 14a2 2 0 01-2 2H8a2 2 0 01-2-2L5 6"/>
                    </svg>
                </div>
                <div>
                    <div class="text-[14px] font-semibold text-[#1a2a35]">Hapus Jadwal?</div>
                    <div class="text-[12px] text-[#8a9ba8]">Jadwal verifikasi akan dihapus permanen.</div>
                </div>
            </div>
            <div class="flex gap-3">
                <button @click="confirmHapus.open = false"
                        class="flex-1 h-[40px] bg-white border border-[#D8DDE2] text-[#1a2a35] text-[13px] font-semibold rounded-xl hover:bg-[#F4F6F8] transition-colors">
                    Batal
                </button>
                <button @click="doHapus()"
                        class="flex-1 h-[40px] bg-[#c62828] hover:bg-[#b71c1c] text-white text-[13px] font-semibold rounded-xl transition-colors">
                    Ya, Hapus
                </button>
            </div>
        </div>
    </div>

</div>
