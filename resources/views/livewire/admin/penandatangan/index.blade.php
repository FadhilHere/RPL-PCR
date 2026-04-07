<?php

use Livewire\Attributes\Layout;
use Livewire\Volt\Component;
use App\Actions\Admin\KelolaPenandatanganAction;
use App\Enums\PosisiPenandatanganEnum;
use App\Models\Penandatangan;

new #[Layout('components.layouts.admin')] class extends Component {
    public bool $showForm  = false;
    public ?int $editId    = null;
    public string $nama    = '';
    public string $jabatan = '';
    public string $nip     = '';
    public string $posisi  = 'kiri';
    public bool   $aktif   = true;
    public int    $urutan  = 1;

    public function openCreate(): void
    {
        $this->reset(['editId', 'nama', 'jabatan', 'nip', 'posisi', 'aktif', 'urutan']);
        $this->posisi = 'kiri';
        $this->aktif  = true;
        $this->urutan = 1;
        $this->showForm = true;
    }

    public function openEdit(int $id): void
    {
        $p = Penandatangan::findOrFail($id);
        $this->editId  = $id;
        $this->nama    = $p->nama;
        $this->jabatan = $p->jabatan;
        $this->nip     = $p->nip ?? '';
        $this->posisi  = $p->posisi->value;
        $this->aktif   = $p->aktif;
        $this->urutan  = $p->urutan;
        $this->showForm = true;
    }

    public function save(KelolaPenandatanganAction $action): void
    {
        $this->validate([
            'nama'    => 'required|string|max:255',
            'jabatan' => 'required|string|max:255',
            'posisi'  => 'required|in:kiri,kanan',
            'urutan'  => 'required|integer|min:1',
        ]);

        $posisiEnum = PosisiPenandatanganEnum::from($this->posisi);

        if ($this->editId) {
            $action->update(
                Penandatangan::findOrFail($this->editId),
                $this->nama, $this->jabatan, $this->nip ?: null,
                $posisiEnum, $this->aktif, $this->urutan
            );
        } else {
            $action->create($this->nama, $this->jabatan, $this->nip ?: null, $posisiEnum, $this->urutan);
        }

        $this->showForm = false;
    }

    public function delete(int $id, KelolaPenandatanganAction $action): void
    {
        $action->delete(Penandatangan::findOrFail($id));
    }

    public function with(): array
    {
        return [
            'kiri'  => Penandatangan::where('posisi', PosisiPenandatanganEnum::Kiri)->orderBy('urutan')->get(),
            'kanan' => Penandatangan::where('posisi', PosisiPenandatanganEnum::Kanan)->orderBy('urutan')->get(),
        ];
    }
}; ?>

<x-slot:title>Penandatangan Berita Acara</x-slot:title>
<x-slot:subtitle>Kelola data penandatangan yang muncul di berita acara asesmen</x-slot:subtitle>

<div>
    <div class="flex justify-end mb-5">
        <button wire:click="openCreate"
                class="bg-primary hover:bg-[#005f78] text-white text-[13px] font-semibold px-4 py-2 rounded-lg transition-colors flex items-center gap-2">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
            Tambah Penandatangan
        </button>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-5">
        @foreach (['kiri' => 'Penandatangan Kiri', 'kanan' => 'Penandatangan Kanan'] as $key => $label)
        <div class="bg-white rounded-[10px] border border-[#E5E8EC] overflow-hidden">
            <div class="px-5 py-3.5 border-b border-[#F0F2F5]">
                <div class="text-[13px] font-semibold text-[#1a2a35]">{{ $label }}</div>
            </div>
            <div class="divide-y divide-[#F6F8FA]">
                @forelse ($$key as $p)
                <div class="flex items-center justify-between px-5 py-3.5" wire:key="p-{{ $p->id }}">
                    <div>
                        <div class="text-[13px] font-medium text-[#1a2a35]">{{ $p->nama }}</div>
                        <div class="text-[11px] text-[#8a9ba8]">{{ $p->jabatan }}{{ $p->nip ? ' · NIP ' . $p->nip : '' }}</div>
                    </div>
                    <div class="flex items-center gap-1.5">
                        @if (!$p->aktif)
                        <span class="text-[10px] font-medium px-2 py-0.5 rounded-full bg-[#F1F3F4] text-[#5f6368]">Nonaktif</span>
                        @endif
                        <button wire:click="openEdit({{ $p->id }})"
                                class="w-[30px] h-[30px] rounded-md border border-[#D0D5DD] text-[#5a6a75] hover:border-primary hover:text-primary hover:bg-[#E8F4F8] transition-colors flex items-center justify-center">
                            <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M11 4H4a2 2 0 00-2 2v14a2 2 0 002 2h14a2 2 0 002-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 013 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
                        </button>
                        <button wire:click="delete({{ $p->id }})" wire:confirm="Hapus penandatangan ini?"
                                class="w-[30px] h-[30px] rounded-md border border-[#D0D5DD] text-[#5a6a75] hover:border-[#c62828] hover:text-[#c62828] hover:bg-[#FCE8E6] transition-colors flex items-center justify-center">
                            <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="3 6 5 6 21 6"/><path d="M19 6l-1 14a2 2 0 01-2 2H8a2 2 0 01-2-2L5 6"/></svg>
                        </button>
                    </div>
                </div>
                @empty
                <div class="px-5 py-6 text-center text-[12px] text-[#8a9ba8]">Belum ada penandatangan {{ strtolower($label) }}.</div>
                @endforelse
            </div>
        </div>
        @endforeach
    </div>

    {{-- Modal Form --}}
    @if ($showForm)
    <div class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 p-4"
         wire:click.self="$set('showForm', false)">
        <div class="bg-white rounded-2xl shadow-xl w-full max-w-md p-6">
            <h3 class="text-[15px] font-semibold text-[#1a2a35] mb-4">{{ $editId ? 'Edit' : 'Tambah' }} Penandatangan</h3>
            <div class="space-y-4">
                <div>
                    <label class="block text-[11px] font-semibold text-[#5a6a75] uppercase tracking-[0.7px] mb-1.5">Posisi</label>
                    <div class="flex gap-1.5 p-1 bg-[#F4F6F8] rounded-xl">
                        <button type="button" wire:click="$set('posisi', 'kiri')"
                                class="flex-1 py-2 rounded-lg text-[12px] font-semibold transition-all {{ $posisi === 'kiri' ? 'bg-white text-primary shadow-sm' : 'text-[#8a9ba8]' }}">Kiri</button>
                        <button type="button" wire:click="$set('posisi', 'kanan')"
                                class="flex-1 py-2 rounded-lg text-[12px] font-semibold transition-all {{ $posisi === 'kanan' ? 'bg-white text-primary shadow-sm' : 'text-[#8a9ba8]' }}">Kanan</button>
                    </div>
                </div>
                <div>
                    <label class="block text-[11px] font-semibold text-[#5a6a75] uppercase tracking-[0.7px] mb-1.5">Nama Lengkap</label>
                    <input wire:model="nama" type="text" placeholder="Nama lengkap"
                           class="w-full h-[42px] px-3.5 text-[13px] text-[#1a2a35] bg-white border border-[#E0E5EA] rounded-xl outline-none focus:border-primary focus:ring-2 focus:ring-primary/10" />
                    @error('nama') <p class="mt-1 text-[11px] text-[#c62828]">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="block text-[11px] font-semibold text-[#5a6a75] uppercase tracking-[0.7px] mb-1.5">Jabatan</label>
                    <input wire:model="jabatan" type="text" placeholder="Jabatan/gelar"
                           class="w-full h-[42px] px-3.5 text-[13px] text-[#1a2a35] bg-white border border-[#E0E5EA] rounded-xl outline-none focus:border-primary focus:ring-2 focus:ring-primary/10" />
                    @error('jabatan') <p class="mt-1 text-[11px] text-[#c62828]">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="block text-[11px] font-semibold text-[#5a6a75] uppercase tracking-[0.7px] mb-1.5">
                        NIP <span class="normal-case font-normal text-[#b0bec5]">(opsional)</span>
                    </label>
                    <input wire:model="nip" type="text" placeholder="NIP jika ada"
                           class="w-full h-[42px] px-3.5 text-[13px] text-[#1a2a35] bg-white border border-[#E0E5EA] rounded-xl outline-none focus:border-primary focus:ring-2 focus:ring-primary/10" />
                </div>
                <div class="flex gap-4">
                    <div class="flex-1">
                        <label class="block text-[11px] font-semibold text-[#5a6a75] uppercase tracking-[0.7px] mb-1.5">Urutan</label>
                        <input wire:model="urutan" type="number" min="1"
                               class="w-full h-[42px] px-3.5 text-[13px] text-[#1a2a35] bg-white border border-[#E0E5EA] rounded-xl outline-none focus:border-primary focus:ring-2 focus:ring-primary/10" />
                    </div>
                    @if ($editId)
                    <div class="flex items-end pb-1">
                        <label class="flex items-center gap-2 cursor-pointer select-none">
                            <input type="checkbox" wire:model="aktif" class="w-4 h-4 rounded accent-primary" />
                            <span class="text-[13px] text-[#5a6a75]">Aktif</span>
                        </label>
                    </div>
                    @endif
                </div>
            </div>
            <div class="flex gap-3 mt-6">
                <button wire:click="$set('showForm', false)"
                        class="flex-1 h-[42px] bg-white border border-[#D8DDE2] text-[#1a2a35] text-[13px] font-semibold rounded-xl hover:bg-[#F4F6F8] transition-colors">
                    Batal
                </button>
                <button wire:click="save"
                        class="flex-1 h-[42px] bg-primary hover:bg-[#005f78] text-white text-[13px] font-semibold rounded-xl transition-colors">
                    Simpan
                </button>
            </div>
        </div>
    </div>
    @endif
</div>
