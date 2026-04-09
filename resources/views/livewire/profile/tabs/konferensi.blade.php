<?php

use App\Livewire\Concerns\HandlesProfilRiwayatCrud;
use App\Models\KonferensiSeminar;
use Livewire\Attributes\Locked;
use Livewire\Volt\Component;

new class extends Component {
    use HandlesProfilRiwayatCrud;

    #[Locked]
    public int $pesertaId;
    public bool $enforceOwnership = true;

    public string $title = 'Konferensi / Seminar / Lokakarya';
    public string $desc = 'Kegiatan konferensi, seminar, lokakarya, atau simposium.';
    public string $emptyLabel = 'data konferensi / seminar.';

    public function mount(int $pesertaId, string $title = 'Konferensi / Seminar / Lokakarya', string $desc = 'Kegiatan konferensi, seminar, lokakarya, atau simposium.', string $emptyLabel = 'data konferensi / seminar.', bool $enforceOwnership = true): void
    {
        $this->pesertaId = $pesertaId;
        $this->title = $title;
        $this->desc = $desc;
        $this->emptyLabel = $emptyLabel;
        $this->enforceOwnership = $enforceOwnership;

        if ($this->enforceOwnership) {
            $this->guardPesertaScope();
        }
    }

    protected function currentProfilPesertaId(): ?int
    {
        return $this->pesertaId;
    }

    protected function guardPesertaScope(): void
    {
        $authPesertaId = auth()->user()?->peserta?->id;

        if ($authPesertaId !== null) {
            abort_if((int) $authPesertaId !== $this->pesertaId, 403);
        }
    }

    public function simpanKonferensi(?int $id, string $tahun, string $judulKegiatan, string $penyelenggara, ?string $peran): void
    {
        abort_if(blank($judulKegiatan), 422);

        $this->simpanRiwayat(KonferensiSeminar::class, $id, [
            'tahun' => $tahun,
            'judul_kegiatan' => $judulKegiatan,
            'penyelenggara' => $penyelenggara,
            'peran' => $peran ?: null,
        ]);
    }

    public function hapusKonferensi(int $id): void
    {
        $this->hapusRiwayat(KonferensiSeminar::class, $id);
    }

    public function with(): array
    {
        return [
            'konferensi' => KonferensiSeminar::query()
                ->where('peserta_id', $this->pesertaId)
                ->latest()
                ->get(),
        ];
    }
};
?>

<div x-data="{
    modal: { open: false, editId: null, tahun: '', judulKegiatan: '', penyelenggara: '', peran: '' },
    hapusModal: { open: false, id: null },
    openTambah() { this.modal = { open: true, editId: null, tahun: '', judulKegiatan: '', penyelenggara: '', peran: '' }; },
    openEdit(id, tahun, judulKegiatan, penyelenggara, peran) { this.modal = { open: true, editId: id, tahun: tahun ?? '', judulKegiatan, penyelenggara, peran: peran ?? '' }; },
    async simpan() { await $wire.simpanKonferensi(this.modal.editId, this.modal.tahun, this.modal.judulKegiatan, this.modal.penyelenggara, this.modal.peran || null); this.modal.open = false; },
    openHapus(id) { this.hapusModal = { open: true, id }; },
    doHapus() { $wire.hapusKonferensi(this.hapusModal.id); this.hapusModal.open = false; }
}">
    @include('livewire.peserta.profil.partials.subtabel-header', ['title' => $title, 'desc' => $desc])

    @if ($konferensi->isNotEmpty())
    <div class="bg-white rounded-xl border border-[#E5E8EC] overflow-hidden mb-4">
        <table class="w-full text-[12px]">
            <thead><tr class="border-b border-[#F0F2F5] bg-[#FAFBFC]">
                <th class="text-left font-semibold text-[#8a9ba8] px-5 py-2.5">Judul Kegiatan</th>
                <th class="text-left font-semibold text-[#8a9ba8] px-3 py-2.5">Penyelenggara</th>
                <th class="text-left font-semibold text-[#8a9ba8] px-3 py-2.5">Peran</th>
                <th class="text-left font-semibold text-[#8a9ba8] px-3 py-2.5">Tahun</th>
                <th class="px-3 py-2.5 pr-5"></th>
            </tr></thead>
            <tbody>
                @foreach ($konferensi as $row)
                <tr class="border-b border-[#F6F8FA] last:border-0" wire:key="kon-{{ $row->id }}">
                    <td class="px-5 py-3 font-medium text-[#1a2a35]">{{ $row->judul_kegiatan }}</td>
                    <td class="px-3 py-3 text-[#5a6a75]">{{ $row->penyelenggara }}</td>
                    <td class="px-3 py-3 text-[#5a6a75]">{{ $row->peran ?? '—' }}</td>
                    <td class="px-3 py-3 text-[#5a6a75]">{{ $row->tahun }}</td>
                    <td class="px-3 py-3 pr-5">
                        <div class="flex items-center gap-1 justify-end">
                            <button @click="openEdit({{ $row->id }}, @js($row->tahun), @js($row->judul_kegiatan), @js($row->penyelenggara), @js($row->peran))"
                                class="w-[28px] h-[28px] rounded-md border border-[#D0D5DD] text-[#5a6a75] hover:border-primary hover:text-primary hover:bg-[#E8F4F8] flex items-center justify-center transition-colors">
                                <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M11 4H4a2 2 0 00-2 2v14a2 2 0 002 2h14a2 2 0 002-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 013 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
                            </button>
                            <button @click="openHapus({{ $row->id }})"
                                class="w-[28px] h-[28px] rounded-md border border-[#D0D5DD] text-[#5a6a75] hover:border-[#c62828] hover:text-[#c62828] hover:bg-[#FCE8E6] flex items-center justify-center transition-colors">
                                <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="3 6 5 6 21 6"/><path d="M19 6l-1 14a2 2 0 01-2 2H8a2 2 0 01-2-2L5 6"/></svg>
                            </button>
                        </div>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    @else
    @include('livewire.peserta.profil.partials.empty-state', ['label' => $emptyLabel])
    @endif

    <div x-show="modal.open" x-cloak class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 p-4"
         x-transition:enter="transition ease-out duration-150" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
         x-transition:leave="transition ease-in duration-100" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0">
        <div @click.outside="modal.open = false" @keydown.escape.window="modal.open = false"
             class="bg-white rounded-2xl shadow-xl w-full max-w-md p-6"
             x-transition:enter="transition ease-out duration-150" x-transition:enter-start="opacity-0 scale-95" x-transition:enter-end="opacity-100 scale-100"
             x-transition:leave="transition ease-in duration-100" x-transition:leave-start="opacity-100 scale-100" x-transition:leave-end="opacity-0 scale-95">
            <div class="text-[14px] font-semibold text-[#1a2a35] mb-4" x-text="modal.editId ? 'Edit Konferensi' : 'Tambah Konferensi'"></div>
            <div class="space-y-3">
                <div>
                    <label class="block text-[11px] font-semibold text-[#5a6a75] uppercase tracking-[0.7px] mb-1">Judul Kegiatan <span class="text-[#D2092F]">*</span></label>
                    <input x-model="modal.judulKegiatan" type="text" placeholder="Nama konferensi / seminar"
                        class="w-full h-[40px] px-3.5 text-[13px] text-[#1a2a35] bg-white border border-[#E0E5EA] rounded-xl outline-none focus:border-primary focus:ring-2 focus:ring-primary/10 placeholder:text-[#b0bec5]" />
                </div>
                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-[11px] font-semibold text-[#5a6a75] uppercase tracking-[0.7px] mb-1">Penyelenggara</label>
                        <input x-model="modal.penyelenggara" type="text" placeholder="Nama lembaga"
                            class="w-full h-[40px] px-3.5 text-[13px] text-[#1a2a35] bg-white border border-[#E0E5EA] rounded-xl outline-none focus:border-primary focus:ring-2 focus:ring-primary/10 placeholder:text-[#b0bec5]" />
                    </div>
                    <div>
                        <label class="block text-[11px] font-semibold text-[#5a6a75] uppercase tracking-[0.7px] mb-1">Peran</label>
                        <input x-model="modal.peran" type="text" placeholder="Panitia / Peserta / Pembicara"
                            class="w-full h-[40px] px-3.5 text-[13px] text-[#1a2a35] bg-white border border-[#E0E5EA] rounded-xl outline-none focus:border-primary focus:ring-2 focus:ring-primary/10 placeholder:text-[#b0bec5]" />
                    </div>
                </div>
                <div>
                    <label class="block text-[11px] font-semibold text-[#5a6a75] uppercase tracking-[0.7px] mb-1">Tahun</label>
                    <input x-model="modal.tahun" type="text" placeholder="2023" maxlength="4"
                        class="w-full h-[40px] px-3.5 text-[13px] text-[#1a2a35] bg-white border border-[#E0E5EA] rounded-xl outline-none focus:border-primary focus:ring-2 focus:ring-primary/10 placeholder:text-[#b0bec5]" />
                </div>
            </div>
            <div class="flex gap-3 mt-5">
                <button @click="modal.open = false" class="flex-1 h-[40px] border border-[#D8DDE2] text-[#1a2a35] text-[13px] font-semibold rounded-xl hover:bg-[#F4F6F8] transition-colors">Batal</button>
                <button @click="simpan()" class="flex-1 h-[40px] bg-primary hover:bg-[#005f78] text-white text-[13px] font-semibold rounded-xl transition-colors">Simpan</button>
            </div>
        </div>
    </div>

    @include('livewire.peserta.profil.partials.hapus-modal-inline')
</div>
