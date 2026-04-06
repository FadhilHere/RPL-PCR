<?php

use Livewire\Attributes\Layout;
use Livewire\Volt\Component;
use App\Models\TahunAjaran;
use Illuminate\Support\Facades\DB;

new #[Layout('components.layouts.admin')] class extends Component {

    public bool   $showForm  = false;
    public ?int   $editId    = null;
    public string $nama      = '';
    public bool   $aktif     = false;

    // ── Buka form tambah ──────────────────────────────────────

    public function openForm(): void
    {
        $this->reset('editId', 'nama', 'aktif');
        $this->showForm = true;
    }

    // ── Buka form edit ────────────────────────────────────────

    public function openEdit(int $id): void
    {
        $ta          = TahunAjaran::findOrFail($id);
        $this->editId = $id;
        $this->nama   = $ta->nama;
        $this->aktif  = $ta->aktif;
        $this->showForm = true;
    }

    // ── Simpan (tambah / edit) ────────────────────────────────

    public function simpan(): void
    {
        $this->validate([
            'nama' => 'required|string|max:20|unique:tahun_ajaran,nama' . ($this->editId ? ',' . $this->editId : ''),
        ], [
            'nama.required' => 'Nama tahun ajaran wajib diisi.',
            'nama.unique'   => 'Tahun ajaran sudah ada.',
        ]);

        DB::transaction(function () {
            // Jika aktif dicentang, nonaktifkan semua yang lain
            if ($this->aktif) {
                TahunAjaran::where('id', '!=', $this->editId ?? 0)->update(['aktif' => false]);
            }

            if ($this->editId) {
                TahunAjaran::findOrFail($this->editId)->update([
                    'nama'  => $this->nama,
                    'aktif' => $this->aktif,
                ]);
            } else {
                TahunAjaran::create([
                    'nama'  => $this->nama,
                    'aktif' => $this->aktif,
                ]);
            }
        });

        $this->reset('showForm', 'editId', 'nama', 'aktif');
    }

    // ── Toggle Aktif ──────────────────────────────────────────

    public function toggleAktif(int $id): void
    {
        $ta = TahunAjaran::findOrFail($id);

        DB::transaction(function () use ($ta) {
            if (! $ta->aktif) {
                TahunAjaran::where('aktif', true)->update(['aktif' => false]);
            }
            $ta->update(['aktif' => ! $ta->aktif]);
        });
    }

    // ── Hapus ─────────────────────────────────────────────────

    public function hapus(int $id): void
    {
        TahunAjaran::findOrFail($id)->delete();
    }

    public function with(): array
    {
        return [
            'tahunAjaranList' => TahunAjaran::orderByDesc('nama')->get(),
        ];
    }
}; ?>

<x-slot:title>Tahun Ajaran</x-slot:title>
<x-slot:subtitle>Kelola periode tahun ajaran RPL</x-slot:subtitle>

<div>

    {{-- Toolbar --}}
    <div class="flex items-center justify-between mb-5">
        <p class="text-[12px] text-[#8a9ba8]">
            Hanya satu tahun ajaran yang dapat aktif pada satu waktu.
        </p>
        <button wire:click="openForm"
                class="bg-primary hover:bg-[#005f78] text-white text-[13px] font-semibold px-4 py-2 rounded-lg transition-colors flex items-center gap-2">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                <line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/>
            </svg>
            Tambah Tahun Ajaran
        </button>
    </div>

    {{-- Tabel --}}
    <div class="bg-white rounded-[10px] border border-[#E5E8EC] overflow-hidden">
        <table class="w-full">
            <thead>
                <tr class="border-b border-[#F0F2F5]">
                    <th class="text-left px-5 py-3 text-[11px] font-semibold text-[#8a9ba8] uppercase tracking-[0.5px]">Tahun Ajaran</th>
                    <th class="text-center px-4 py-3 text-[11px] font-semibold text-[#8a9ba8] uppercase tracking-[0.5px]">Status</th>
                    <th class="text-center px-4 py-3 text-[11px] font-semibold text-[#8a9ba8] uppercase tracking-[0.5px]">Aksi</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($tahunAjaranList as $ta)
                <tr class="border-b border-[#F6F8FA] last:border-0 hover:bg-[#FAFBFC] transition-colors" wire:key="ta-{{ $ta->id }}">
                    <td class="px-5 py-3.5">
                        <span class="text-[13px] font-semibold text-[#1a2a35]">{{ $ta->nama }}</span>
                    </td>
                    <td class="px-4 py-3.5 text-center">
                        <button wire:click="toggleAktif({{ $ta->id }})"
                                title="{{ $ta->aktif ? 'Nonaktifkan' : 'Aktifkan' }}"
                                class="relative inline-flex h-[22px] w-[40px] items-center rounded-full transition-colors focus:outline-none
                                       {{ $ta->aktif ? 'bg-[#1e7e3e]' : 'bg-[#D0D5DD]' }}">
                            <span class="inline-block h-[16px] w-[16px] transform rounded-full bg-white shadow transition-transform
                                         {{ $ta->aktif ? 'translate-x-[21px]' : 'translate-x-[3px]' }}"></span>
                        </button>
                        @if ($ta->aktif)
                        <div class="text-[10px] text-[#1e7e3e] font-semibold mt-1">Aktif</div>
                        @endif
                    </td>
                    <td class="px-4 py-3.5 text-center">
                        <div class="flex items-center justify-center gap-1.5">
                            <button wire:click="openEdit({{ $ta->id }})"
                                    title="Edit"
                                    class="w-[32px] h-[32px] rounded-md border border-[#D0D5DD] text-[#5a6a75] hover:border-primary hover:text-primary hover:bg-[#E8F4F8] transition-colors flex items-center justify-center">
                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M11 4H4a2 2 0 00-2 2v14a2 2 0 002 2h14a2 2 0 002-2v-7"/>
                                    <path d="M18.5 2.5a2.121 2.121 0 013 3L12 15l-4 1 1-4 9.5-9.5z"/>
                                </svg>
                            </button>
                            <button wire:click="hapus({{ $ta->id }})"
                                    wire:confirm="Hapus tahun ajaran '{{ $ta->nama }}'?"
                                    title="Hapus"
                                    class="w-[32px] h-[32px] rounded-md border border-[#D0D5DD] text-[#5a6a75] hover:border-[#c62828] hover:text-[#c62828] hover:bg-[#FCE8E6] transition-colors flex items-center justify-center">
                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <polyline points="3 6 5 6 21 6"/>
                                    <path d="M19 6l-1 14a2 2 0 01-2 2H8a2 2 0 01-2-2L5 6"/>
                                    <path d="M10 11v6M14 11v6"/>
                                    <path d="M9 6V4a1 1 0 011-1h4a1 1 0 011 1v2"/>
                                </svg>
                            </button>
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="3" class="px-5 py-10 text-center text-[13px] text-[#8a9ba8]">
                        Belum ada tahun ajaran. Tambahkan yang pertama.
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{-- Modal Form --}}
    @if ($showForm)
    <div class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 p-4"
         @keydown.escape.window="$wire.set('showForm', false)">
        <div class="bg-white rounded-2xl shadow-xl w-full max-w-sm p-6">
            <h3 class="text-[15px] font-semibold text-[#1a2a35] mb-4">
                {{ $editId ? 'Edit Tahun Ajaran' : 'Tambah Tahun Ajaran' }}
            </h3>

            <div class="space-y-4">
                <div>
                    <label class="block text-[11px] font-semibold text-[#5a6a75] uppercase tracking-[0.7px] mb-1.5">
                        Nama Tahun Ajaran
                    </label>
                    <input wire:model="nama" type="text" placeholder="contoh: 2025/2026"
                           class="w-full h-[42px] px-3.5 text-[13px] text-[#1a2a35] bg-white border border-[#E0E5EA] rounded-xl outline-none focus:border-primary focus:ring-2 focus:ring-primary/10 placeholder:text-[#b0bec5]" />
                    @error('nama') <p class="mt-1 text-[11px] text-[#c62828]">{{ $message }}</p> @enderror
                </div>

                <label class="flex items-center gap-2.5 cursor-pointer select-none">
                    <input wire:model="aktif" type="checkbox"
                           class="w-4 h-4 rounded border-[#D0D5DD] accent-primary cursor-pointer" />
                    <span class="text-[13px] text-[#5a6a75]">Jadikan aktif sekarang</span>
                </label>
            </div>

            <div class="flex gap-3 mt-6">
                <button wire:click="$set('showForm', false)"
                        class="flex-1 h-[42px] bg-white border border-[#D8DDE2] text-[#1a2a35] text-[13px] font-semibold rounded-xl hover:bg-[#F4F6F8] transition-colors">
                    Batal
                </button>
                <button wire:click="simpan"
                        wire:loading.attr="disabled"
                        wire:target="simpan"
                        class="flex-1 h-[42px] bg-primary hover:bg-[#005f78] text-white text-[13px] font-semibold rounded-xl transition-colors disabled:opacity-60">
                    <span wire:loading.remove wire:target="simpan">Simpan</span>
                    <span wire:loading wire:target="simpan">Menyimpan...</span>
                </button>
            </div>
        </div>
    </div>
    @endif

</div>
