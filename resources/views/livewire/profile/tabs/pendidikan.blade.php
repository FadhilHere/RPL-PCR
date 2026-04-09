<?php

use App\Livewire\Concerns\HandlesProfilRiwayatCrud;
use App\Models\RiwayatPendidikan;
use Livewire\Attributes\Locked;
use Livewire\Volt\Component;

new class extends Component {
    use HandlesProfilRiwayatCrud;

    #[Locked]
    public int $pesertaId;
    public bool $enforceOwnership = true;

    public string $title = 'Riwayat Pendidikan';
    public string $desc = 'Riwayat pendidikan formal Anda.';
    public string $emptyLabel = 'riwayat pendidikan';

    public function mount(int $pesertaId, string $title = 'Riwayat Pendidikan', string $desc = 'Riwayat pendidikan formal Anda.', string $emptyLabel = 'riwayat pendidikan', bool $enforceOwnership = true): void
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

    public function simpanPendidikan(?int $id, string $namaSekolah, ?string $tahunLulus, ?string $jurusan): void
    {
        abort_if(blank($namaSekolah), 422);

        $this->simpanRiwayat(RiwayatPendidikan::class, $id, [
            'nama_sekolah' => $namaSekolah,
            'tahun_lulus' => $tahunLulus ?: null,
            'jurusan' => $jurusan ?: null,
        ]);
    }

    public function hapusPendidikan(int $id): void
    {
        $this->hapusRiwayat(RiwayatPendidikan::class, $id);
    }

    public function with(): array
    {
        return [
            'riwayatPendidikan' => RiwayatPendidikan::query()
                ->where('peserta_id', $this->pesertaId)
                ->latest()
                ->get(),
        ];
    }
};
?>

<div x-data="{
    modal: { open: false, editId: null, namaSekolah: '', tahunLulus: '', jurusan: '' },
    hapusModal: { open: false, id: null },
    openTambah() { this.modal = { open: true, editId: null, namaSekolah: '', tahunLulus: '', jurusan: '' }; },
    openEdit(id, namaSekolah, tahunLulus, jurusan) { this.modal = { open: true, editId: id, namaSekolah, tahunLulus: tahunLulus ?? '', jurusan: jurusan ?? '' }; },
    async simpan() { await $wire.simpanPendidikan(this.modal.editId, this.modal.namaSekolah, this.modal.tahunLulus || null, this.modal.jurusan || null); this.modal.open = false; },
    openHapus(id) { this.hapusModal = { open: true, id }; },
    doHapus() { $wire.hapusPendidikan(this.hapusModal.id); this.hapusModal.open = false; }
}">
    @include('livewire.peserta.profil.partials.subtabel-header', ['title' => $title, 'desc' => $desc])

    @if ($riwayatPendidikan->isNotEmpty())
    <div class="bg-white rounded-xl border border-[#E5E8EC] overflow-hidden mb-4">
        <table class="w-full text-[12px]">
            <thead><tr class="border-b border-[#F0F2F5] bg-[#FAFBFC]">
                <th class="text-left font-semibold text-[#8a9ba8] px-5 py-2.5">Nama Sekolah / Institusi</th>
                <th class="text-left font-semibold text-[#8a9ba8] px-3 py-2.5">Jurusan</th>
                <th class="text-left font-semibold text-[#8a9ba8] px-3 py-2.5">Tahun Lulus</th>
                <th class="px-3 py-2.5 pr-5"></th>
            </tr></thead>
            <tbody>
                @foreach ($riwayatPendidikan as $row)
                <tr class="border-b border-[#F6F8FA] last:border-0" wire:key="rp-{{ $row->id }}">
                    <td class="px-5 py-3 font-medium text-[#1a2a35]">{{ $row->nama_sekolah }}</td>
                    <td class="px-3 py-3 text-[#5a6a75]">{{ $row->jurusan ?? '—' }}</td>
                    <td class="px-3 py-3 text-[#5a6a75]">{{ $row->tahun_lulus ?? '—' }}</td>
                    <td class="px-3 py-3 pr-5">
                        @include('livewire.peserta.profil.partials.aksi-buttons', [
                            'editClick' => "openEdit({$row->id}, " . 
                                \Illuminate\Support\Js::from($row->nama_sekolah) . ", " .
                                \Illuminate\Support\Js::from($row->tahun_lulus) . ", " .
                                \Illuminate\Support\Js::from($row->jurusan) . ")",
                            'hapusClick' => "openHapus({$row->id})",
                        ])
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
            <div class="text-[14px] font-semibold text-[#1a2a35] mb-4" x-text="modal.editId ? 'Edit Pendidikan' : 'Tambah Pendidikan'"></div>
            <div class="space-y-3">
                <div>
                    <label class="block text-[11px] font-semibold text-[#5a6a75] uppercase tracking-[0.7px] mb-1">Nama Sekolah / Institusi <span class="text-[#D2092F]">*</span></label>
                    <input x-model="modal.namaSekolah" type="text" placeholder="Universitas, SMA, dll."
                        class="w-full h-[40px] px-3.5 text-[13px] text-[#1a2a35] bg-white border border-[#E0E5EA] rounded-xl outline-none focus:border-primary focus:ring-2 focus:ring-primary/10 placeholder:text-[#b0bec5]" />
                </div>
                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-[11px] font-semibold text-[#5a6a75] uppercase tracking-[0.7px] mb-1">Jurusan</label>
                        <input x-model="modal.jurusan" type="text" placeholder="Program studi"
                            class="w-full h-[40px] px-3.5 text-[13px] text-[#1a2a35] bg-white border border-[#E0E5EA] rounded-xl outline-none focus:border-primary focus:ring-2 focus:ring-primary/10 placeholder:text-[#b0bec5]" />
                    </div>
                    <div>
                        <label class="block text-[11px] font-semibold text-[#5a6a75] uppercase tracking-[0.7px] mb-1">Tahun Lulus</label>
                        <input x-model="modal.tahunLulus" type="text" placeholder="2020" maxlength="4"
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
