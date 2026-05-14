<?php

use Livewire\Attributes\Layout;
use Livewire\Volt\Component;
use Livewire\WithPagination;
use App\Actions\Admin\RilisHasilPermohonanAction;
use App\Enums\JenisRplEnum;
use App\Enums\RoleEnum;
use App\Models\PermohonanRpl;
use App\Models\ProgramStudi;

new #[Layout('components.layouts.admin')] class extends Component {
    use WithPagination;

    public string $search         = '';
    public string $filterStatus   = '';
    public string $filterProdi    = '';
    public string $filterJenisRpl = '';

    // State modal rilis — lazy (diisi saat muatDaftarRilis dipanggil)
    public array $rilisSelected    = [];
    public bool  $adaSiapDirilis   = false;
    public int   $jumlahSiapDirilis = 0;

    public function mount(): void
    {
        // Hanya cek count di mount supaya tombol enable/disable tanpa load full data
        $this->refreshRilisCount();
    }

    public function updatedSearch(): void { $this->resetPage(); }
    public function updatedFilterStatus(): void { $this->resetPage(); }
    public function updatedFilterProdi(): void { $this->resetPage(); }
    public function updatedFilterJenisRpl(): void { $this->resetPage(); }

    private function refreshRilisCount(): void
    {
        $this->jumlahSiapDirilis = PermohonanRpl::query()->siapDirilis()->count();
        $this->adaSiapDirilis    = $this->jumlahSiapDirilis > 0;
    }

    /**
     * Lazy load daftar siap-dirilis — hanya dipanggil saat modal dibuka.
     * Returns collection sebagai computed property sementara via event.
     */
    public function muatDaftarRilis(): void
    {
        $list = PermohonanRpl::query()
            ->siapDirilis()
            ->with(['peserta.user', 'tahunAjaran'])
            ->latest('updated_at')
            ->get();

        $this->dispatch('rilis-list-loaded', items: $list->map(fn($p) => [
            'id'                => $p->id,
            'nomor_permohonan'  => $p->nomor_permohonan,
            'nama_peserta'      => $p->peserta->user->nama ?? '-',
            'jenis_rpl'         => $p->jenis_rpl->label(),
            'tahun_ajaran'      => $p->tahunAjaran?->nama ?? '-',
            'semester'          => $p->semester?->label() ?? '-',
            'status'            => $p->status->label(),
            'status_badge'      => $p->status->badgeClass(),
        ])->values()->all());
    }

    public function rilisTerpilih(RilisHasilPermohonanAction $action): void
    {
        abort_unless(
            in_array(auth()->user()->role, [RoleEnum::Admin, RoleEnum::AdminBaak]),
            403
        );

        $count = $action->execute($this->rilisSelected, auth()->user());

        $this->rilisSelected = [];
        $this->refreshRilisCount();
        $this->dispatch('rilis-selesai', count: $count);
        $this->dispatch('notify-saved');
    }

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
            'permohonanList'  => $query->paginate(15),
            'prodiOptions'    => $prodiOptions,
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
        <button type="button"
                @click="$dispatch('buka-rilis-modal')"
                @if (! $adaSiapDirilis) disabled @endif
                class="h-[42px] px-4 bg-primary hover:bg-[#005f78] text-white text-[13px] font-semibold rounded-xl transition-colors inline-flex items-center gap-2 disabled:opacity-50 disabled:cursor-not-allowed">
            <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                <path d="M5 12h14"/><path d="m12 5 7 7-7 7"/>
            </svg>
            Rilis Hasil
            @if ($adaSiapDirilis)
                <span class="bg-white/25 text-[11px] font-bold px-1.5 py-0.5 rounded-full">{{ $jumlahSiapDirilis }}</span>
            @endif
        </button>
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
                    <th class="text-left px-5 py-3.5 text-[11px] font-semibold text-[#8a9ba8] uppercase tracking-[0.5px]">Dirilis Pada</th>
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
                    <td class="px-5 py-3.5 text-[#5a6a75]">
                        {{ $p->dirilis_pada ? $p->dirilis_pada->format('d M Y') : '-' }}
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
                    <td colspan="7" class="px-5 py-12 text-center text-[13px] text-[#8a9ba8]">
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

    {{-- Modal Rilis Hasil — lazy load data saat dibuka --}}
    <div x-data="{
            open: false,
            loading: false,
            items: [],
            get jumlahTerpilih() { return $wire.rilisSelected.length; },
            get semuaTerpilih() { return this.items.length > 0 && this.jumlahTerpilih === this.items.length; },
            toggleSemua(checked) {
                $wire.set('rilisSelected', checked ? this.items.map(i => i.id) : []);
            },
            buka() {
                this.open = true;
                this.loading = true;
                $wire.muatDaftarRilis();
            }
         }"
         @buka-rilis-modal.window="buka()"
         @rilis-list-loaded.window="items = $event.detail.items; loading = false"
         @rilis-selesai.window="items = items.filter(i => !$wire.rilisSelected.includes(i.id)); open = false"
         x-show="open" x-cloak style="display:none"
         class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4"
         x-transition:enter="transition ease-out duration-150" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
         x-transition:leave="transition ease-in duration-100" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0">
        <div @click.outside="open = false" @keydown.escape.window="open = false"
             class="bg-white rounded-2xl shadow-xl w-full max-w-3xl max-h-[85vh] flex flex-col"
             x-transition:enter="transition ease-out duration-150" x-transition:enter-start="opacity-0 scale-95" x-transition:enter-end="opacity-100 scale-100">

            <div class="px-6 py-5 border-b border-[#F0F2F5]">
                <div class="text-[15px] font-semibold text-[#1a2a35]">Rilis Hasil Permohonan</div>
                <div class="text-[12px] text-[#8a9ba8] mt-1">Pilih permohonan yang akan dirilis ke peserta. Setelah dirilis, peserta dapat melihat hasil di dashboard & halaman pengajuan.</div>
            </div>

            {{-- Header tabel + select all --}}
            <div class="px-6 py-3 border-b border-[#F0F2F5] flex items-center gap-3">
                <label class="flex items-center gap-2 text-[12px] text-[#5a6a75] cursor-pointer select-none">
                    <input type="checkbox"
                           :checked="semuaTerpilih"
                           :disabled="loading || items.length === 0"
                           @change="toggleSemua($event.target.checked)"
                           class="w-4 h-4 rounded text-primary border-[#D0D5DD]"/>
                    <span x-text="loading ? 'Memuat...' : 'Pilih Semua (' + items.length + ')'"></span>
                </label>
                <div class="ml-auto text-[12px] text-[#5a6a75]">
                    Terpilih: <span class="font-semibold text-[#1a2a35]" x-text="jumlahTerpilih"></span>
                </div>
            </div>

            {{-- Scrollable list --}}
            <div class="flex-1 overflow-y-auto">
                {{-- Loading state --}}
                <div x-show="loading" class="flex items-center justify-center py-16">
                    <svg class="animate-spin w-6 h-6 text-primary" viewBox="0 0 24 24" fill="none">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="3"/>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/>
                    </svg>
                </div>

                {{-- Empty state --}}
                <div x-show="!loading && items.length === 0" class="flex flex-col items-center justify-center py-16 text-center">
                    <div class="text-[13px] text-[#8a9ba8]">Tidak ada permohonan yang siap dirilis.</div>
                </div>

                {{-- Data table --}}
                <table x-show="!loading && items.length > 0" class="w-full text-[12px]">
                    <thead class="sticky top-0 bg-white z-10 border-b border-[#F0F2F5]">
                        <tr>
                            <th class="px-5 py-2.5 w-8"></th>
                            <th class="text-left px-3 py-2.5 text-[11px] font-semibold text-[#8a9ba8] uppercase tracking-[0.5px]">No. Permohonan</th>
                            <th class="text-left px-3 py-2.5 text-[11px] font-semibold text-[#8a9ba8] uppercase tracking-[0.5px]">Peserta</th>
                            <th class="text-left px-3 py-2.5 text-[11px] font-semibold text-[#8a9ba8] uppercase tracking-[0.5px]">Jenis</th>
                            <th class="text-left px-3 py-2.5 text-[11px] font-semibold text-[#8a9ba8] uppercase tracking-[0.5px]">TA / Semester</th>
                            <th class="text-left px-3 py-2.5 text-[11px] font-semibold text-[#8a9ba8] uppercase tracking-[0.5px]">Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <template x-for="item in items" :key="item.id">
                            <tr class="border-b border-[#F6F8FA] last:border-0 hover:bg-[#FAFBFC]">
                                <td class="px-5 py-3">
                                    <input type="checkbox"
                                           wire:model.live="rilisSelected"
                                           :value="item.id"
                                           class="w-4 h-4 rounded text-primary border-[#D0D5DD]"/>
                                </td>
                                <td class="px-3 py-3 font-medium text-[#1a2a35]" x-text="item.nomor_permohonan"></td>
                                <td class="px-3 py-3 text-[#5a6a75]" x-text="item.nama_peserta"></td>
                                <td class="px-3 py-3 text-[#5a6a75]" x-text="item.jenis_rpl"></td>
                                <td class="px-3 py-3 text-[#5a6a75]" x-text="item.tahun_ajaran + ' / ' + item.semester"></td>
                                <td class="px-3 py-3">
                                    <span class="text-[10px] font-semibold px-2 py-0.5 rounded-full" :class="item.status_badge" x-text="item.status"></span>
                                </td>
                            </tr>
                        </template>
                    </tbody>
                </table>
            </div>

            <div class="px-6 py-4 border-t border-[#F0F2F5] flex items-center justify-end gap-2">
                <button type="button" @click="open = false"
                        class="h-[38px] px-4 bg-white border border-[#D0D5DD] text-[#5a6a75] text-[13px] font-semibold rounded-lg hover:bg-[#F4F6F8] transition-colors">
                    Batal
                </button>
                <button type="button"
                        @click="$wire.rilisTerpilih()"
                        :disabled="jumlahTerpilih === 0"
                        class="h-[38px] px-4 bg-primary hover:bg-[#005f78] text-white text-[13px] font-semibold rounded-lg transition-colors disabled:opacity-50 disabled:cursor-not-allowed">
                    Rilis Terpilih (<span x-text="jumlahTerpilih"></span>)
                </button>
            </div>
        </div>
    </div>

</div>
