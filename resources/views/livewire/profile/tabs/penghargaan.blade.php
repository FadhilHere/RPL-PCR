<?php

use App\Livewire\Concerns\HandlesProfilRiwayatCrud;
use App\Models\Penghargaan;
use Livewire\Attributes\Locked;
use Livewire\Volt\Component;

new class extends Component {
    use HandlesProfilRiwayatCrud;

    #[Locked]
    public int $pesertaId;
    public bool $enforceOwnership = true;

    public string $title = 'Penghargaan / Piagam';
    public string $desc = 'Penghargaan atau piagam yang pernah diterima.';
    public string $emptyLabel = 'data penghargaan.';

    public function mount(int $pesertaId, string $title = 'Penghargaan / Piagam', string $desc = 'Penghargaan atau piagam yang pernah diterima.', string $emptyLabel = 'data penghargaan.', bool $enforceOwnership = true): void
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

    public function simpanPenghargaan(?int $id, string $tahun, string $bentukPenghargaan, string $pemberi): void
    {
        abort_if(blank($bentukPenghargaan), 422);

        $this->simpanRiwayat(Penghargaan::class, $id, [
            'tahun' => $tahun,
            'bentuk_penghargaan' => $bentukPenghargaan,
            'pemberi' => $pemberi,
        ]);
    }

    public function hapusPenghargaan(int $id): void
    {
        $this->hapusRiwayat(Penghargaan::class, $id);
    }

    public function with(): array
    {
        return [
            'penghargaan' => Penghargaan::query()
                ->where('peserta_id', $this->pesertaId)
                ->latest()
                ->get(),
        ];
    }
};
?>

<div x-data="{
    modal: { open: false, editId: null, tahun: '', bentukPenghargaan: '', pemberi: '' },
    hapusModal: { open: false, id: null },
    openTambah() { this.modal = { open: true, editId: null, tahun: '', bentukPenghargaan: '', pemberi: '' }; },
    openEdit(id, tahun, bentukPenghargaan, pemberi) { this.modal = { open: true, editId: id, tahun: tahun ?? '', bentukPenghargaan, pemberi }; },
    async simpan() { await $wire.simpanPenghargaan(this.modal.editId, this.modal.tahun, this.modal.bentukPenghargaan, this.modal.pemberi); this.modal.open = false; },
    openHapus(id) { this.hapusModal = { open: true, id }; },
    doHapus() { $wire.hapusPenghargaan(this.hapusModal.id); this.hapusModal.open = false; }
}">
    @include('livewire.peserta.profil.partials.subtabel-header', ['title' => $title, 'desc' => $desc])

    @if ($penghargaan->isNotEmpty())
    <div class="bg-white rounded-xl border border-[#E5E8EC] overflow-hidden mb-4">
        <table class="w-full text-[12px]">
            <thead><tr class="border-b border-[#F0F2F5] bg-[#FAFBFC]">
                <th class="text-left font-semibold text-[#8a9ba8] px-5 py-2.5">Bentuk Penghargaan</th>
                <th class="text-left font-semibold text-[#8a9ba8] px-3 py-2.5">Pemberi</th>
                <th class="text-left font-semibold text-[#8a9ba8] px-3 py-2.5">Tahun</th>
                <th class="px-3 py-2.5 pr-5"></th>
            </tr></thead>
            <tbody>
                @foreach ($penghargaan as $row)
                <tr class="border-b border-[#F6F8FA] last:border-0" wire:key="phg-{{ $row->id }}">
                    <td class="px-5 py-3 font-medium text-[#1a2a35]">{{ $row->bentuk_penghargaan }}</td>
                    <td class="px-3 py-3 text-[#5a6a75]">{{ $row->pemberi }}</td>
                    <td class="px-3 py-3 text-[#5a6a75]">{{ $row->tahun }}</td>
                    <td class="px-3 py-3 pr-5">
                        <div class="flex items-center gap-1 justify-end">
                            <button @click="openEdit({{ $row->id }}, @js($row->tahun), @js($row->bentuk_penghargaan), @js($row->pemberi))"
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
            <div class="text-[14px] font-semibold text-[#1a2a35] mb-4" x-text="modal.editId ? 'Edit Penghargaan' : 'Tambah Penghargaan'"></div>
            <div class="space-y-3">
                <div>
                    <label class="block text-[11px] font-semibold text-[#5a6a75] uppercase tracking-[0.7px] mb-1">Bentuk Penghargaan <span class="text-[#D2092F]">*</span></label>
                    <input x-model="modal.bentukPenghargaan" type="text" placeholder="Nama / jenis penghargaan"
                        class="w-full h-[40px] px-3.5 text-[13px] text-[#1a2a35] bg-white border border-[#E0E5EA] rounded-xl outline-none focus:border-primary focus:ring-2 focus:ring-primary/10 placeholder:text-[#b0bec5]" />
                </div>
                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-[11px] font-semibold text-[#5a6a75] uppercase tracking-[0.7px] mb-1">Pemberi</label>
                        <input x-model="modal.pemberi" type="text" placeholder="Instansi pemberi"
                            class="w-full h-[40px] px-3.5 text-[13px] text-[#1a2a35] bg-white border border-[#E0E5EA] rounded-xl outline-none focus:border-primary focus:ring-2 focus:ring-primary/10 placeholder:text-[#b0bec5]" />
                    </div>
                    <div>
                        <label class="block text-[11px] font-semibold text-[#5a6a75] uppercase tracking-[0.7px] mb-1">Tahun</label>
                        <input x-model="modal.tahun" type="text" placeholder="2022" maxlength="4"
                            class="w-full h-[40px] px-3.5 text-[13px] text-[#1a2a35] bg-white border border-[#E0E5EA] rounded-xl outline-none focus:border-primary focus:ring-2 focus:ring-primary/10 placeholder:text-[#b0bec5]" />
                    </div>
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
