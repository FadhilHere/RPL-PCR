<?php

use Livewire\Attributes\Layout;
use Livewire\Volt\Component;
use Livewire\WithPagination;
use Illuminate\Validation\Rule;
use App\Models\ProgramStudi;

new #[Layout('components.layouts.admin')] class extends Component {
    use WithPagination;

    public string $search = '';

    public function updatedSearch(): void { $this->resetPage(); }

    public function with(): array
    {
        return [
            'prodis' => ProgramStudi::withCount('mataKuliah')
                ->when($this->search, fn($q) => $q->where(function ($q) {
                    $q->where('nama', 'like', "%{$this->search}%")
                      ->orWhere('kode', 'like', "%{$this->search}%");
                }))
                ->orderBy('nama')
                ->paginate(12),
        ];
    }

    public function saveProdi(?int $id, string $kode, string $nama, string $jenjang, int $totalSks): void
    {
        $validator = validator(
            compact('kode', 'nama', 'jenjang', 'totalSks'),
            [
                'kode'     => ['required', 'string', 'max:20', Rule::unique('program_studi', 'kode')->ignore($id)],
                'nama'     => 'required|string|max:255',
                'jenjang'  => 'required|in:D3,D4,S2',
                'totalSks' => 'required|integer|min:1|max:300',
            ],
            [
                'kode.required'     => 'Kode prodi wajib diisi.',
                'kode.unique'       => 'Kode prodi sudah digunakan prodi lain.',
                'nama.required'     => 'Nama prodi wajib diisi.',
                'jenjang.required'  => 'Jenjang wajib dipilih.',
                'jenjang.in'        => 'Jenjang tidak valid.',
                'totalSks.required' => 'Total SKS wajib diisi.',
                'totalSks.integer'  => 'Total SKS harus berupa angka.',
                'totalSks.min'      => 'Total SKS minimal 1.',
                'totalSks.max'      => 'Total SKS maksimal 300.',
            ]
        );

        if ($validator->fails()) {
            $this->dispatch('prodi-validation-errors', errors: $validator->errors()->toArray());
            return;
        }

        $data = [
            'kode'      => strtoupper(trim($kode)),
            'nama'      => trim($nama),
            'jenjang'   => $jenjang,
            'total_sks' => $totalSks,
        ];

        $id
            ? ProgramStudi::findOrFail($id)->update($data)
            : ProgramStudi::create($data + ['aktif' => true]);

        $this->dispatch('prodi-saved');
    }

    public function toggleAktif(int $id): void
    {
        $prodi = ProgramStudi::findOrFail($id);
        $prodi->update(['aktif' => ! $prodi->aktif]);
    }

    public function deleteProdi(int $id): void
    {
        ProgramStudi::findOrFail($id)->delete();
    }
}; ?>

<x-slot:title>Program Studi</x-slot:title>
<x-slot:subtitle>Daftar program studi yang tersedia di sistem RPL</x-slot:subtitle>

<div
    x-data="{
        modal: false,
        confirm: { open: false, id: null, nama: '' },
        editId: null,
        errors: {},
        form: { kode: '', nama: '', jenjang: 'D4', totalSks: '' },
        jenjangOpen: false,
        jenjangOptions: ['D3', 'D4', 'S2'],

        openTambah() {
            this.editId = null;
            this.errors = {};
            this.form = { kode: '', nama: '', jenjang: 'D4', totalSks: '' };
            this.modal = true;
        },
        openEdit(prodi) {
            this.editId = prodi.id;
            this.errors = {};
            this.form = {
                kode: prodi.kode,
                nama: prodi.nama,
                jenjang: prodi.jenjang,
                totalSks: prodi.total_sks,
            };
            this.modal = true;
        },
        simpan() {
            this.errors = {};
            $wire.saveProdi(this.editId, this.form.kode, this.form.nama, this.form.jenjang, parseInt(this.form.totalSks) || 0);
        },
        askDelete(id, nama) {
            this.confirm = { open: true, id, nama };
        },
    }"
    @prodi-saved.window="modal = false; confirm.open = false;"
    @prodi-validation-errors.window="errors = $event.detail.errors"
    @keydown.escape.window="modal = false; confirm.open = false; jenjangOpen = false"
>

    {{-- ===== TOOLBAR ===== --}}
    <div class="flex items-center justify-between mb-5">
        <div class="relative">
            <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-[#8a9ba8]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/>
            </svg>
            <input wire:model.live.debounce.300ms="search" type="text" placeholder="Cari kode atau nama prodi..."
                   class="h-[38px] pl-9 pr-4 w-[280px] text-[13px] bg-white border border-[#E0E5EA] rounded-lg outline-none focus:border-primary focus:ring-2 focus:ring-primary/10 placeholder:text-[#b0bec5]" />
        </div>
        <button @click="openTambah()"
                class="flex items-center gap-2 h-[38px] px-4 bg-primary hover:bg-[#005f78] text-white text-[13px] font-semibold rounded-lg transition-colors">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round">
                <line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/>
            </svg>
            Tambah Program Studi
        </button>
    </div>

    {{-- ===== TABEL ===== --}}
    <div class="bg-white rounded-[10px] border border-[#E5E8EC] overflow-hidden">
        <table class="w-full">
            <thead>
                <tr class="border-b border-[#F0F2F5]">
                    <th class="text-left px-5 py-3 text-[11px] font-semibold text-[#8a9ba8] uppercase tracking-[0.5px] w-[100px]">Kode</th>
                    <th class="text-left px-5 py-3 text-[11px] font-semibold text-[#8a9ba8] uppercase tracking-[0.5px]">Nama Program Studi</th>
                    <th class="text-left px-5 py-3 text-[11px] font-semibold text-[#8a9ba8] uppercase tracking-[0.5px] w-[80px]">Jenjang</th>
                    <th class="text-left px-5 py-3 text-[11px] font-semibold text-[#8a9ba8] uppercase tracking-[0.5px] w-[90px]">Total SKS</th>
                    <th class="text-left px-5 py-3 text-[11px] font-semibold text-[#8a9ba8] uppercase tracking-[0.5px] w-[80px]">MK</th>
                    <th class="text-left px-5 py-3 text-[11px] font-semibold text-[#8a9ba8] uppercase tracking-[0.5px] w-[80px]">Status</th>
                    <th class="px-5 py-3 w-[100px]"></th>
                </tr>
            </thead>
            <tbody>
                @forelse ($prodis as $prodi)
                <tr class="border-b border-[#F6F8FA] last:border-0 hover:bg-[#FAFBFC] transition-colors" wire:key="prodi-{{ $prodi->id }}">
                    <td class="px-5 py-3.5">
                        <div class="text-[12px] font-semibold text-primary">{{ $prodi->kode }}</div>
                    </td>
                    <td class="px-5 py-3.5">
                        <div class="text-[13px] text-[#1a2a35] font-medium">{{ $prodi->nama }}</div>
                    </td>
                    <td class="px-5 py-3.5">
                        <span class="text-[11px] font-semibold px-2 py-0.5 rounded-full bg-[#E8F0FE] text-[#1557b0]">{{ $prodi->jenjang }}</span>
                    </td>
                    <td class="px-5 py-3.5">
                        <div class="text-[13px] text-[#1a2a35]">{{ $prodi->total_sks }} <span class="text-[#8a9ba8]">SKS</span></div>
                    </td>
                    <td class="px-5 py-3.5">
                        <a href="{{ route('admin.materi.prodi', $prodi) }}"
                           class="text-[13px] text-primary font-semibold hover:underline no-underline">
                            {{ $prodi->mata_kuliah_count }}
                        </a>
                    </td>
                    <td class="px-5 py-3.5">
                        <button wire:click="toggleAktif({{ $prodi->id }})" wire:loading.attr="disabled" wire:target="toggleAktif({{ $prodi->id }})"
                                class="text-[10px] font-semibold px-2.5 py-1 rounded-full transition-colors
                                       {{ $prodi->aktif
                                            ? 'bg-[#E6F4EA] text-[#1e7e3e] hover:bg-[#d4edda]'
                                            : 'bg-[#F1F3F4] text-[#5f6368] hover:bg-[#e2e5e8]' }}">
                            {{ $prodi->aktif ? 'Aktif' : 'Nonaktif' }}
                        </button>
                    </td>
                    <td class="px-5 py-3.5">
                        <div class="flex items-center gap-1 justify-end">
                            <button @click="openEdit(@js(['id' => $prodi->id, 'kode' => $prodi->kode, 'nama' => $prodi->nama, 'jenjang' => $prodi->jenjang, 'total_sks' => $prodi->total_sks]))"
                                    class="w-7 h-7 flex items-center justify-center rounded-md text-[#8a9ba8] hover:bg-[#E8F4F8] hover:text-primary transition-colors" title="Edit">
                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M11 4H4a2 2 0 00-2 2v14a2 2 0 002 2h14a2 2 0 002-2v-7"/>
                                    <path d="M18.5 2.5a2.121 2.121 0 013 3L12 15l-4 1 1-4 9.5-9.5z"/>
                                </svg>
                            </button>
                            <button @click="askDelete({{ $prodi->id }}, '{{ addslashes($prodi->nama) }}')"
                                    class="w-7 h-7 flex items-center justify-center rounded-md text-[#8a9ba8] hover:bg-[#FCE8E6] hover:text-[#c62828] transition-colors" title="Hapus">
                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <polyline points="3 6 5 6 21 6"/><path d="M19 6l-1 14a2 2 0 01-2 2H8a2 2 0 01-2-2L5 6"/>
                                    <path d="M10 11v6"/><path d="M14 11v6"/><path d="M9 6V4a1 1 0 011-1h4a1 1 0 011 1v2"/>
                                </svg>
                            </button>
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="7" class="px-5 py-12 text-center">
                        <div class="text-[13px] text-[#8a9ba8]">
                            {{ $search ? 'Tidak ada prodi yang cocok dengan pencarian.' : 'Belum ada program studi.' }}
                        </div>
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>

        @if ($prodis->hasPages())
        <div class="px-5 py-3 border-t border-[#F0F2F5]">
            {{ $prodis->links() }}
        </div>
        @endif
    </div>

    {{-- ===== MODAL TAMBAH / EDIT ===== --}}
    <div x-show="modal" style="display:none"
         class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 p-4"
         x-transition:enter="transition ease-out duration-150"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition ease-in duration-100"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         @click.self="modal = false">

        <div x-show="modal"
             x-transition:enter="transition ease-out duration-150"
             x-transition:enter-start="opacity-0 scale-95"
             x-transition:enter-end="opacity-100 scale-100"
             x-transition:leave="transition ease-in duration-100"
             x-transition:leave-start="opacity-100 scale-100"
             x-transition:leave-end="opacity-0 scale-95"
             class="bg-white rounded-2xl shadow-xl w-full max-w-md flex flex-col" style="max-height:90vh">

            {{-- Header --}}
            <div class="flex items-center justify-between px-6 pt-5 pb-4 border-b border-[#F0F2F5] shrink-0">
                <h3 class="text-[15px] font-semibold text-[#1a2a35]" x-text="editId ? 'Edit Program Studi' : 'Tambah Program Studi'"></h3>
                <button @click="modal = false" class="text-[#8a9ba8] hover:text-[#1a2a35] transition-colors p-1">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round">
                        <line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/>
                    </svg>
                </button>
            </div>

            {{-- Body --}}
            <div class="px-6 py-5 overflow-y-auto flex-1 space-y-4">

                {{-- Kode Prodi --}}
                <div>
                    <label class="block text-[11px] font-semibold text-[#5a6a75] uppercase tracking-[0.7px] mb-1.5">Kode Resmi Prodi</label>
                    <input x-model="form.kode" type="text" placeholder="cth: TI, AKTP, SI"
                           class="w-full h-[42px] px-3.5 text-[13px] text-[#1a2a35] bg-white border border-[#E0E5EA] rounded-xl outline-none focus:border-primary focus:ring-2 focus:ring-primary/10 placeholder:text-[#b0bec5] uppercase"
                           :class="errors.kode ? 'border-[#c62828]' : ''" />
                    <p x-show="errors.kode" x-text="errors.kode?.[0]" class="mt-1 text-[11px] text-[#c62828]" style="display:none"></p>
                </div>

                {{-- Nama --}}
                <div>
                    <label class="block text-[11px] font-semibold text-[#5a6a75] uppercase tracking-[0.7px] mb-1.5">Nama Program Studi</label>
                    <input x-model="form.nama" type="text" placeholder="cth: PS Teknik Informatika"
                           class="w-full h-[42px] px-3.5 text-[13px] text-[#1a2a35] bg-white border border-[#E0E5EA] rounded-xl outline-none focus:border-primary focus:ring-2 focus:ring-primary/10 placeholder:text-[#b0bec5]"
                           :class="errors.nama ? 'border-[#c62828]' : ''" />
                    <p x-show="errors.nama" x-text="errors.nama?.[0]" class="mt-1 text-[11px] text-[#c62828]" style="display:none"></p>
                </div>

                {{-- Jenjang (custom dropdown Alpine) --}}
                <div>
                    <label class="block text-[11px] font-semibold text-[#5a6a75] uppercase tracking-[0.7px] mb-1.5">Jenjang</label>
                    <div class="relative" @click.outside="jenjangOpen = false">
                        <button type="button" @click="jenjangOpen = !jenjangOpen"
                                class="w-full h-[42px] px-3.5 text-[13px] text-left bg-white border rounded-xl outline-none flex items-center justify-between gap-2 transition-all duration-150"
                                :class="jenjangOpen ? 'border-primary ring-2 ring-primary/10' : (errors.jenjang ? 'border-[#c62828]' : 'border-[#E0E5EA] hover:border-[#C5CDD5]')">
                            <span x-text="form.jenjang || 'Pilih jenjang'" class="text-[#1a2a35]"></span>
                            <svg class="w-4 h-4 text-[#8a9ba8] shrink-0 transition-transform duration-200" :class="jenjangOpen && 'rotate-180'" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <polyline points="6 9 12 15 18 9"/>
                            </svg>
                        </button>
                        <div x-show="jenjangOpen" x-cloak
                             x-transition:enter="transition ease-out duration-100"
                             x-transition:enter-start="opacity-0 -translate-y-1"
                             x-transition:enter-end="opacity-100 translate-y-0"
                             x-transition:leave="transition ease-in duration-75"
                             x-transition:leave-start="opacity-100 translate-y-0"
                             x-transition:leave-end="opacity-0 -translate-y-1"
                             class="absolute top-[calc(100%+4px)] left-0 right-0 z-10 bg-white border border-[#E0E5EA] rounded-xl shadow-lg overflow-hidden">
                            <div class="py-1">
                                <template x-for="opt in jenjangOptions" :key="opt">
                                    <button type="button"
                                            @click="form.jenjang = opt; jenjangOpen = false"
                                            class="w-full text-left px-3.5 py-2 text-[13px] transition-colors"
                                            :class="form.jenjang === opt ? 'bg-[#E8F4F8] text-primary font-semibold' : 'text-[#1a2a35] hover:bg-[#F4F6F8]'"
                                            x-text="opt">
                                    </button>
                                </template>
                            </div>
                        </div>
                    </div>
                    <p x-show="errors.jenjang" x-text="errors.jenjang?.[0]" class="mt-1 text-[11px] text-[#c62828]" style="display:none"></p>
                </div>

                {{-- Total SKS --}}
                <div>
                    <label class="block text-[11px] font-semibold text-[#5a6a75] uppercase tracking-[0.7px] mb-1.5">Total SKS</label>
                    <input x-model="form.totalSks" type="number" min="1" max="300" placeholder="cth: 144"
                           class="w-full h-[42px] px-3.5 text-[13px] text-[#1a2a35] bg-white border border-[#E0E5EA] rounded-xl outline-none focus:border-primary focus:ring-2 focus:ring-primary/10 placeholder:text-[#b0bec5]"
                           :class="errors.totalSks ? 'border-[#c62828]' : ''" />
                    <p x-show="errors.totalSks" x-text="errors.totalSks?.[0]" class="mt-1 text-[11px] text-[#c62828]" style="display:none"></p>
                </div>

            </div>

            {{-- Footer --}}
            <div class="flex gap-3 px-6 py-4 border-t border-[#F0F2F5] shrink-0">
                <button @click="modal = false"
                        class="flex-1 h-[42px] bg-white border border-[#D8DDE2] text-[#1a2a35] text-[13px] font-semibold rounded-xl hover:bg-[#F4F6F8] transition-colors">
                    Batal
                </button>
                <button @click="simpan()"
                        wire:loading.attr="disabled"
                        wire:target="saveProdi"
                        class="flex-1 h-[42px] bg-primary hover:bg-[#005f78] text-white text-[13px] font-semibold rounded-xl transition-colors disabled:opacity-60">
                    <span wire:loading.remove wire:target="saveProdi" x-text="editId ? 'Simpan Perubahan' : 'Tambah Prodi'"></span>
                    <span wire:loading wire:target="saveProdi">Menyimpan...</span>
                </button>
            </div>

        </div>
    </div>

    {{-- ===== MODAL KONFIRMASI HAPUS ===== --}}
    <div x-show="confirm.open" style="display:none"
         class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 p-4"
         x-transition:enter="transition ease-out duration-150"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition ease-in duration-100"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         @click.self="confirm.open = false">

        <div x-show="confirm.open"
             x-transition:enter="transition ease-out duration-150"
             x-transition:enter-start="opacity-0 scale-95"
             x-transition:enter-end="opacity-100 scale-100"
             x-transition:leave="transition ease-in duration-100"
             x-transition:leave-start="opacity-100 scale-100"
             x-transition:leave-end="opacity-0 scale-95"
             class="bg-white rounded-2xl shadow-xl w-full max-w-sm p-6">

            <div class="flex flex-col items-center text-center">
                <div class="w-10 h-10 rounded-full bg-[#FCE8E6] flex items-center justify-center mb-3">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#c62828" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <polyline points="3 6 5 6 21 6"/><path d="M19 6l-1 14a2 2 0 01-2 2H8a2 2 0 01-2-2L5 6"/>
                        <path d="M10 11v6"/><path d="M14 11v6"/><path d="M9 6V4a1 1 0 011-1h4a1 1 0 011 1v2"/>
                    </svg>
                </div>
                <h4 class="text-[14px] font-semibold text-[#1a2a35] mb-1">Hapus Program Studi?</h4>
                <p class="text-[12px] text-[#8a9ba8] leading-[1.6]">
                    Prodi <span class="font-semibold text-[#1a2a35]" x-text="'&quot;' + confirm.nama + '&quot;'"></span> akan dihapus permanen beserta semua data mata kuliah-nya.
                </p>
            </div>

            <div class="flex gap-3 mt-5">
                <button @click="confirm.open = false"
                        class="flex-1 h-[40px] bg-white border border-[#D8DDE2] text-[#1a2a35] text-[13px] font-semibold rounded-xl hover:bg-[#F4F6F8] transition-colors">
                    Batal
                </button>
                <button @click="$wire.deleteProdi(confirm.id); confirm.open = false"
                        wire:loading.attr="disabled"
                        wire:target="deleteProdi"
                        class="flex-1 h-[40px] bg-[#D2092F] hover:bg-[#b8082a] text-white text-[13px] font-semibold rounded-xl transition-colors disabled:opacity-60">
                    Hapus
                </button>
            </div>

        </div>
    </div>

</div>
