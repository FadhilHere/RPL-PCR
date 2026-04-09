<?php

use Livewire\Attributes\Layout;
use Livewire\Volt\Component;
use Livewire\WithFileUploads;
use App\Actions\Admin\KelolaPenandatanganAction;
use App\Enums\PosisiPenandatanganEnum;
use App\Models\Penandatangan;
use Illuminate\Support\Facades\Storage;

new #[Layout('components.layouts.admin')] class extends Component {
    use WithFileUploads;

    public $ttdFile        = null; // temporary uploaded file

    public function save(
        KelolaPenandatanganAction $action,
        ?int $id,
        string $nama,
        string $jabatan,
        string $nip,
        string $posisi,
        bool $aktif,
        int $urutan,
        bool $useUploadedTtd
    ): void
    {
        $nama    = trim($nama);
        $jabatan = trim($jabatan);
        $nip     = trim($nip);

        $validator = validator(
            compact('nama', 'jabatan', 'nip', 'posisi', 'urutan'),
            [
                'nama'    => 'required|string|max:255',
                'jabatan' => 'required|string|max:255',
                'nip'     => 'nullable|string|max:50',
                'posisi'  => 'required|in:kiri,kanan,wadir',
                'urutan'  => 'required|integer|min:1',
            ],
            [
                'nama.required'    => 'Nama lengkap wajib diisi.',
                'jabatan.required' => 'Jabatan wajib diisi.',
                'posisi.required'  => 'Posisi wajib dipilih.',
                'posisi.in'        => 'Posisi tidak valid.',
                'urutan.required'  => 'Urutan wajib diisi.',
                'urutan.integer'   => 'Urutan harus berupa angka.',
                'urutan.min'       => 'Urutan minimal 1.',
            ]
        );

        if ($validator->fails()) {
            $this->dispatch('penandatangan-validation-errors', errors: $validator->errors()->toArray());
            return;
        }

        if ($useUploadedTtd && $this->ttdFile) {
            $this->validate([
                'ttdFile' => 'image|mimes:jpg,jpeg,png|max:2048',
            ]);
        }

        $posisiEnum = PosisiPenandatanganEnum::from($posisi);

        if ($id) {
            $penandatangan = Penandatangan::findOrFail($id);
            $action->update(
                $penandatangan,
                $nama,
                $jabatan,
                $nip ?: null,
                $posisiEnum,
                $aktif,
                $urutan
            );
        } else {
            $penandatangan = $action->create($nama, $jabatan, $nip ?: null, $posisiEnum, $urutan);
        }

        if ($useUploadedTtd && $this->ttdFile) {
            if ($penandatangan->tanda_tangan && Storage::disk('local')->exists($penandatangan->tanda_tangan)) {
                Storage::disk('local')->delete($penandatangan->tanda_tangan);
            }
            $ext  = $this->ttdFile->getClientOriginalExtension();
            $path = $this->ttdFile->storeAs('penandatangan', 'ttd_' . $penandatangan->id . '.' . $ext, 'local');
            $penandatangan->update(['tanda_tangan' => $path]);
        }

        $this->ttdFile = null;
        $this->dispatch('penandatangan-saved');
    }

    public function hapusTtd(int $id): void
    {
        $p = Penandatangan::findOrFail($id);
        if ($p->tanda_tangan && Storage::disk('local')->exists($p->tanda_tangan)) {
            Storage::disk('local')->delete($p->tanda_tangan);
        }
        $p->update(['tanda_tangan' => null]);
    }

    public function delete(int $id, KelolaPenandatanganAction $action): void
    {
        $p = Penandatangan::findOrFail($id);
        if ($p->tanda_tangan && Storage::disk('local')->exists($p->tanda_tangan)) {
            Storage::disk('local')->delete($p->tanda_tangan);
        }
        $action->delete($p);
    }

    public function with(): array
    {
        return [
            'kiri'  => Penandatangan::where('posisi', PosisiPenandatanganEnum::Kiri)->orderBy('urutan')->get(),
            'kanan' => Penandatangan::where('posisi', PosisiPenandatanganEnum::Kanan)->orderBy('urutan')->get(),
            'wadir' => Penandatangan::where('posisi', PosisiPenandatanganEnum::Wadir)->orderBy('urutan')->get(),
        ];
    }
}; ?>

<x-slot:title>Penandatangan</x-slot:title>
<x-slot:subtitle>Kelola data penandatangan untuk berita acara (Kiri/Kanan) dan berkas Word (Wakil Direktur)</x-slot:subtitle>

<div
    x-data="{
        formModal: false,
        errors: {},
        form: {
            id: null,
            nama: '',
            jabatan: '',
            nip: '',
            posisi: 'kiri',
            aktif: true,
            urutan: 1,
            hasTtd: false,
            ttdUrl: null,
            newTtdName: '',
            newTtdPreview: '',
            ttdSelected: false,
        },
        confirmDelete: { open: false, id: null, nama: '' },
        confirmTtd: { open: false, id: null, nama: '' },
        defaultForm() {
            return {
                id: null,
                nama: '',
                jabatan: '',
                nip: '',
                posisi: 'kiri',
                aktif: true,
                urutan: 1,
                hasTtd: false,
                ttdUrl: null,
                newTtdName: '',
                newTtdPreview: '',
                ttdSelected: false,
            };
        },
        resetUploadInput() {
            if (this.$refs.ttdFileInput) {
                this.$refs.ttdFileInput.value = '';
            }
        },
        openCreate() {
            if (this.form.newTtdPreview) {
                URL.revokeObjectURL(this.form.newTtdPreview);
            }
            this.errors = {};
            this.form = this.defaultForm();
            this.formModal = true;
            this.resetUploadInput();
        },
        openEdit(item) {
            if (this.form.newTtdPreview) {
                URL.revokeObjectURL(this.form.newTtdPreview);
            }
            this.errors = {};
            this.form = {
                id: item.id,
                nama: item.nama || '',
                jabatan: item.jabatan || '',
                nip: item.nip || '',
                posisi: item.posisi || 'kiri',
                aktif: !!item.aktif,
                urutan: item.urutan || 1,
                hasTtd: !!item.has_ttd,
                ttdUrl: item.ttd_url || null,
                newTtdName: '',
                newTtdPreview: '',
                ttdSelected: false,
            };
            this.formModal = true;
            this.resetUploadInput();
        },
        closeForm() {
            if (this.form.newTtdPreview) {
                URL.revokeObjectURL(this.form.newTtdPreview);
            }
            this.formModal = false;
            this.errors = {};
            this.form = this.defaultForm();
            this.resetUploadInput();
        },
        onFileChange(event) {
            const file = event.target.files && event.target.files[0] ? event.target.files[0] : null;
            if (this.form.newTtdPreview) {
                URL.revokeObjectURL(this.form.newTtdPreview);
            }
            this.form.newTtdName = file ? file.name : '';
            this.form.newTtdPreview = file ? URL.createObjectURL(file) : '';
            this.form.ttdSelected = !!file;
        },
        simpanForm() {
            this.errors = {};
            $wire.save(
                this.form.id,
                this.form.nama,
                this.form.jabatan,
                this.form.nip,
                this.form.posisi,
                !!this.form.aktif,
                parseInt(this.form.urutan) || 1,
                !!this.form.ttdSelected
            );
        },
        askDelete(id, nama) {
            this.confirmDelete = { open: true, id, nama };
        },
        askDeleteTtd(id, nama) {
            this.confirmTtd = { open: true, id, nama };
        },
    }"
    @penandatangan-validation-errors.window="errors = $event.detail.errors"
    @penandatangan-saved.window="closeForm()"
    @keydown.escape.window="closeForm(); confirmDelete.open = false; confirmTtd.open = false"
>
    <div class="flex justify-end mb-5">
        <button @click="openCreate()"
                class="bg-primary hover:bg-[#005f78] text-white text-[13px] font-semibold px-4 py-2 rounded-lg transition-colors flex items-center gap-2">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
            Tambah Penandatangan
        </button>
    </div>

    {{-- Wakil Direktur (untuk Word docs Transfer & Perolehan) --}}
    <div class="bg-white rounded-[10px] border border-[#E5E8EC] overflow-hidden mb-5">
        <div class="px-5 py-3.5 border-b border-[#F0F2F5] flex items-center justify-between">
            <div>
                <div class="text-[13px] font-semibold text-[#1a2a35]">Wakil Direktur</div>
                <div class="text-[11px] text-[#8a9ba8] mt-0.5">TTD kiri untuk berkas Word Transfer & Perolehan Kredit</div>
            </div>
        </div>
        <div class="divide-y divide-[#F6F8FA]">
            @forelse ($wadir as $p)
            <div class="flex items-center justify-between px-5 py-3.5" wire:key="p-{{ $p->id }}">
                <div class="flex items-center gap-3">
                    <div class="w-14 h-8 border border-[#E5E8EC] rounded-md bg-[#FAFBFC] flex items-center justify-center shrink-0 overflow-hidden">
                        @if ($p->tanda_tangan && \Illuminate\Support\Facades\Storage::disk('local')->exists($p->tanda_tangan))
                        <img src="{{ route('berkas.ttd.penandatangan', $p) }}" alt="TTD" class="max-h-full max-w-full object-contain p-0.5">
                        @else
                        <span class="text-[9px] text-[#c0c8d0]">No TTD</span>
                        @endif
                    </div>
                    <div>
                        <div class="text-[13px] font-medium text-[#1a2a35]">{{ $p->nama }}</div>
                        <div class="text-[11px] text-[#8a9ba8]">{{ $p->jabatan }}{{ $p->nip ? ' · NIP ' . $p->nip : '' }}</div>
                    </div>
                </div>
                <div class="flex items-center gap-1.5">
                    @if (!$p->aktif)
                    <span class="text-[10px] font-medium px-2 py-0.5 rounded-full bg-[#F1F3F4] text-[#5f6368]">Nonaktif</span>
                    @endif
                    <button @click="openEdit(@js([
                        'id' => $p->id,
                        'nama' => $p->nama,
                        'jabatan' => $p->jabatan,
                        'nip' => $p->nip,
                        'posisi' => $p->posisi->value,
                        'aktif' => (bool) $p->aktif,
                        'urutan' => $p->urutan,
                        'has_ttd' => (bool) $p->tanda_tangan,
                        'ttd_url' => $p->tanda_tangan ? route('berkas.ttd.penandatangan', $p) : null,
                    ]))"
                            class="w-[30px] h-[30px] rounded-md border border-[#D0D5DD] text-[#5a6a75] hover:border-primary hover:text-primary hover:bg-[#E8F4F8] transition-colors flex items-center justify-center">
                        <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M11 4H4a2 2 0 00-2 2v14a2 2 0 002 2h14a2 2 0 002-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 013 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
                    </button>
                    @if ($p->tanda_tangan)
                    <button @click="askDeleteTtd({{ $p->id }}, '{{ addslashes($p->nama) }}')"
                            class="w-[30px] h-[30px] rounded-md border border-[#D0D5DD] text-[#8a9ba8] hover:border-[#b45309] hover:text-[#b45309] hover:bg-[#FFF8E1] transition-colors flex items-center justify-center" title="Hapus TTD">
                        <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 6h18M8 6V4h8v2M19 6l-1 14H6L5 6"/></svg>
                    </button>
                    @endif
                    <button @click="askDelete({{ $p->id }}, '{{ addslashes($p->nama) }}')"
                            class="w-[30px] h-[30px] rounded-md border border-[#D0D5DD] text-[#5a6a75] hover:border-[#c62828] hover:text-[#c62828] hover:bg-[#FCE8E6] transition-colors flex items-center justify-center">
                        <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="3 6 5 6 21 6"/><path d="M19 6l-1 14a2 2 0 01-2 2H8a2 2 0 01-2-2L5 6"/></svg>
                    </button>
                </div>
            </div>
            @empty
            <div class="px-5 py-6 text-center text-[12px] text-[#8a9ba8]">Belum ada data Wakil Direktur.</div>
            @endforelse
        </div>
    </div>

    {{-- Penandatangan Berita Acara (Kiri & Kanan) --}}
    <div class="text-[11px] font-semibold text-[#8a9ba8] uppercase tracking-[0.8px] mb-3">Penandatangan Berita Acara</div>
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-5">
        @foreach (['kiri' => 'Penandatangan Kiri (BA)', 'kanan' => 'Penandatangan Kanan (BA)'] as $key => $label)
        <div class="bg-white rounded-[10px] border border-[#E5E8EC] overflow-hidden">
            <div class="px-5 py-3.5 border-b border-[#F0F2F5]">
                <div class="text-[13px] font-semibold text-[#1a2a35]">{{ $label }}</div>
            </div>
            <div class="divide-y divide-[#F6F8FA]">
                @forelse ($$key as $p)
                <div class="flex items-center justify-between px-5 py-3.5" wire:key="p-{{ $p->id }}">
                    <div class="flex items-center gap-3">
                        {{-- Preview TTD --}}
                        <div class="w-14 h-8 border border-[#E5E8EC] rounded-md bg-[#FAFBFC] flex items-center justify-center shrink-0 overflow-hidden">
                            @if ($p->tanda_tangan && \Illuminate\Support\Facades\Storage::disk('local')->exists($p->tanda_tangan))
                            <img src="{{ route('berkas.ttd.penandatangan', $p) }}" alt="TTD" class="max-h-full max-w-full object-contain p-0.5">
                            @else
                            <span class="text-[9px] text-[#c0c8d0]">No TTD</span>
                            @endif
                        </div>
                        <div>
                            <div class="text-[13px] font-medium text-[#1a2a35]">{{ $p->nama }}</div>
                            <div class="text-[11px] text-[#8a9ba8]">{{ $p->jabatan }}{{ $p->nip ? ' · NIP ' . $p->nip : '' }}</div>
                        </div>
                    </div>
                    <div class="flex items-center gap-1.5">
                        @if (!$p->aktif)
                        <span class="text-[10px] font-medium px-2 py-0.5 rounded-full bg-[#F1F3F4] text-[#5f6368]">Nonaktif</span>
                        @endif
                        <button @click="openEdit(@js([
                            'id' => $p->id,
                            'nama' => $p->nama,
                            'jabatan' => $p->jabatan,
                            'nip' => $p->nip,
                            'posisi' => $p->posisi->value,
                            'aktif' => (bool) $p->aktif,
                            'urutan' => $p->urutan,
                            'has_ttd' => (bool) $p->tanda_tangan,
                            'ttd_url' => $p->tanda_tangan ? route('berkas.ttd.penandatangan', $p) : null,
                        ]))"
                                class="w-[30px] h-[30px] rounded-md border border-[#D0D5DD] text-[#5a6a75] hover:border-primary hover:text-primary hover:bg-[#E8F4F8] transition-colors flex items-center justify-center">
                            <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M11 4H4a2 2 0 00-2 2v14a2 2 0 002 2h14a2 2 0 002-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 013 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
                        </button>
                        @if ($p->tanda_tangan)
                        <button @click="askDeleteTtd({{ $p->id }}, '{{ addslashes($p->nama) }}')"
                                class="w-[30px] h-[30px] rounded-md border border-[#D0D5DD] text-[#8a9ba8] hover:border-[#b45309] hover:text-[#b45309] hover:bg-[#FFF8E1] transition-colors flex items-center justify-center" title="Hapus TTD">
                            <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 6h18M8 6V4h8v2M19 6l-1 14H6L5 6"/></svg>
                        </button>
                        @endif
                        <button @click="askDelete({{ $p->id }}, '{{ addslashes($p->nama) }}')"
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
    <div x-show="formModal" style="display:none"
         class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 p-4"
         x-transition:enter="transition ease-out duration-150"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition ease-in duration-100"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         @click.self="closeForm()">

        <div x-show="formModal"
             x-transition:enter="transition ease-out duration-150"
             x-transition:enter-start="opacity-0 scale-95"
             x-transition:enter-end="opacity-100 scale-100"
             x-transition:leave="transition ease-in duration-100"
             x-transition:leave-start="opacity-100 scale-100"
             x-transition:leave-end="opacity-0 scale-95"
             class="bg-white rounded-2xl shadow-xl w-full max-w-md p-6">

            <h3 class="text-[15px] font-semibold text-[#1a2a35] mb-4" x-text="form.id ? 'Edit Penandatangan' : 'Tambah Penandatangan'"></h3>

            <div class="space-y-4">
                <div>
                    <label class="block text-[11px] font-semibold text-[#5a6a75] uppercase tracking-[0.7px] mb-1.5">Posisi</label>
                    <div class="flex gap-1.5 p-1 bg-[#F4F6F8] rounded-xl">
                        <button type="button" @click="form.posisi = 'kiri'"
                                class="flex-1 py-2 rounded-lg text-[12px] font-semibold transition-all"
                                :class="form.posisi === 'kiri' ? 'bg-white text-primary shadow-sm' : 'text-[#8a9ba8]'">Kiri (BA)</button>
                        <button type="button" @click="form.posisi = 'kanan'"
                                class="flex-1 py-2 rounded-lg text-[12px] font-semibold transition-all"
                                :class="form.posisi === 'kanan' ? 'bg-white text-primary shadow-sm' : 'text-[#8a9ba8]'">Kanan (BA)</button>
                        <button type="button" @click="form.posisi = 'wadir'"
                                class="flex-1 py-2 rounded-lg text-[12px] font-semibold transition-all"
                                :class="form.posisi === 'wadir' ? 'bg-white text-primary shadow-sm' : 'text-[#8a9ba8]'">Wadir</button>
                    </div>
                    <p x-show="errors.posisi" x-text="errors.posisi?.[0]" class="mt-1 text-[11px] text-[#c62828]" style="display:none"></p>
                </div>

                <div>
                    <label class="block text-[11px] font-semibold text-[#5a6a75] uppercase tracking-[0.7px] mb-1.5">Nama Lengkap</label>
                    <input x-model="form.nama" type="text" placeholder="Nama lengkap"
                           class="w-full h-[42px] px-3.5 text-[13px] text-[#1a2a35] bg-white border border-[#E0E5EA] rounded-xl outline-none focus:border-primary focus:ring-2 focus:ring-primary/10"
                           :class="errors.nama ? 'border-[#c62828]' : ''" />
                    <p x-show="errors.nama" x-text="errors.nama?.[0]" class="mt-1 text-[11px] text-[#c62828]" style="display:none"></p>
                </div>

                <div>
                    <label class="block text-[11px] font-semibold text-[#5a6a75] uppercase tracking-[0.7px] mb-1.5">Jabatan</label>
                    <input x-model="form.jabatan" type="text" placeholder="Jabatan/gelar"
                           class="w-full h-[42px] px-3.5 text-[13px] text-[#1a2a35] bg-white border border-[#E0E5EA] rounded-xl outline-none focus:border-primary focus:ring-2 focus:ring-primary/10"
                           :class="errors.jabatan ? 'border-[#c62828]' : ''" />
                    <p x-show="errors.jabatan" x-text="errors.jabatan?.[0]" class="mt-1 text-[11px] text-[#c62828]" style="display:none"></p>
                </div>

                <div>
                    <label class="block text-[11px] font-semibold text-[#5a6a75] uppercase tracking-[0.7px] mb-1.5">
                        NIP <span class="normal-case font-normal text-[#b0bec5]">(opsional)</span>
                    </label>
                    <input x-model="form.nip" type="text" placeholder="NIP jika ada"
                           class="w-full h-[42px] px-3.5 text-[13px] text-[#1a2a35] bg-white border border-[#E0E5EA] rounded-xl outline-none focus:border-primary focus:ring-2 focus:ring-primary/10"
                           :class="errors.nip ? 'border-[#c62828]' : ''" />
                    <p x-show="errors.nip" x-text="errors.nip?.[0]" class="mt-1 text-[11px] text-[#c62828]" style="display:none"></p>
                </div>

                <div class="flex gap-4">
                    <div class="flex-1">
                        <label class="block text-[11px] font-semibold text-[#5a6a75] uppercase tracking-[0.7px] mb-1.5">Urutan</label>
                        <input x-model="form.urutan" type="number" min="1"
                               class="w-full h-[42px] px-3.5 text-[13px] text-[#1a2a35] bg-white border border-[#E0E5EA] rounded-xl outline-none focus:border-primary focus:ring-2 focus:ring-primary/10"
                               :class="errors.urutan ? 'border-[#c62828]' : ''" />
                        <p x-show="errors.urutan" x-text="errors.urutan?.[0]" class="mt-1 text-[11px] text-[#c62828]" style="display:none"></p>
                    </div>
                    <template x-if="form.id">
                        <div class="flex items-end pb-1">
                            <label class="flex items-center gap-2 cursor-pointer select-none">
                                <input type="checkbox" x-model="form.aktif" class="w-4 h-4 rounded accent-primary" />
                                <span class="text-[13px] text-[#5a6a75]">Aktif</span>
                            </label>
                        </div>
                    </template>
                </div>

                {{-- Upload Tanda Tangan --}}
                <div>
                    <label class="block text-[11px] font-semibold text-[#5a6a75] uppercase tracking-[0.7px] mb-1.5">
                        Tanda Tangan <span class="normal-case font-normal text-[#b0bec5]">(JPG/PNG, maks 2MB, opsional)</span>
                    </label>

                    <template x-if="form.id && form.hasTtd && !form.newTtdPreview">
                        <div class="mb-2 flex items-center gap-2">
                            <div class="border border-[#E5E8EC] rounded-lg p-1.5 bg-[#FAFBFC]">
                                <img :src="form.ttdUrl" alt="TTD" class="h-10 object-contain">
                            </div>
                            <span class="text-[11px] text-[#8a9ba8]">TTD saat ini · upload baru untuk mengganti</span>
                        </div>
                    </template>

                    <label class="flex items-center gap-2 px-3.5 py-2.5 border border-dashed border-[#D0D5DD] rounded-xl cursor-pointer hover:border-primary hover:bg-[#F8FBFC] transition-colors text-[12px] text-[#5a6a75]">
                        <svg class="w-4 h-4 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M21.44 11.05l-9.19 9.19a6 6 0 01-8.49-8.49l9.19-9.19a4 4 0 015.66 5.66l-9.2 9.19a2 2 0 01-2.83-2.83l8.49-8.48"/></svg>
                        <span wire:loading.remove wire:target="ttdFile" x-text="form.newTtdName || 'Pilih gambar tanda tangan'"></span>
                        <span wire:loading wire:target="ttdFile" class="text-[#8a9ba8]">Mengunggah...</span>
                        <input x-ref="ttdFileInput" @change="onFileChange($event)" type="file" wire:model="ttdFile" accept=".jpg,.jpeg,.png" class="hidden">
                    </label>
                    @error('ttdFile') <p class="mt-1 text-[11px] text-[#c62828]">{{ $message }}</p> @enderror

                    <template x-if="form.newTtdPreview">
                        <div class="mt-2 border border-[#E5E8EC] rounded-lg p-2 bg-[#F8FBFC] inline-block">
                            <img :src="form.newTtdPreview" alt="Preview TTD" class="h-12 object-contain">
                        </div>
                    </template>
                </div>
            </div>

            <div class="flex gap-3 mt-6">
                <button @click="closeForm()"
                        class="flex-1 h-[42px] bg-white border border-[#D8DDE2] text-[#1a2a35] text-[13px] font-semibold rounded-xl hover:bg-[#F4F6F8] transition-colors">
                    Batal
                </button>
                <button @click="simpanForm()"
                        wire:loading.attr="disabled"
                        wire:target="save"
                        class="flex-1 h-[42px] bg-primary hover:bg-[#005f78] text-white text-[13px] font-semibold rounded-xl transition-colors disabled:opacity-60">
                    <span wire:loading.remove wire:target="save">Simpan</span>
                    <span wire:loading wire:target="save">Menyimpan...</span>
                </button>
            </div>

        </div>
    </div>

    {{-- Modal Konfirmasi Hapus TTD --}}
    <div x-show="confirmTtd.open" style="display:none"
         class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 p-4"
         x-transition:enter="transition ease-out duration-150"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition ease-in duration-100"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         @click.self="confirmTtd.open = false">

        <div x-show="confirmTtd.open"
             x-transition:enter="transition ease-out duration-150"
             x-transition:enter-start="opacity-0 scale-95"
             x-transition:enter-end="opacity-100 scale-100"
             x-transition:leave="transition ease-in duration-100"
             x-transition:leave-start="opacity-100 scale-100"
             x-transition:leave-end="opacity-0 scale-95"
             class="bg-white rounded-2xl shadow-xl w-full max-w-sm p-6">

            <div class="flex flex-col items-center text-center">
                <div class="w-10 h-10 rounded-full bg-[#FFF8E1] flex items-center justify-center mb-3">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#b45309" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M3 6h18M8 6V4h8v2M19 6l-1 14H6L5 6"/>
                    </svg>
                </div>
                <h4 class="text-[14px] font-semibold text-[#1a2a35] mb-1">Hapus Tanda Tangan?</h4>
                <p class="text-[12px] text-[#8a9ba8] leading-[1.6]">
                    TTD milik <span class="font-semibold text-[#1a2a35]" x-text="'&quot;' + confirmTtd.nama + '&quot;'"></span> akan dihapus dari data ini.
                </p>
            </div>

            <div class="flex gap-3 mt-5">
                <button @click="confirmTtd.open = false"
                        class="flex-1 h-[40px] bg-white border border-[#D8DDE2] text-[#1a2a35] text-[13px] font-semibold rounded-xl hover:bg-[#F4F6F8] transition-colors">
                    Batal
                </button>
                <button @click="$wire.hapusTtd(confirmTtd.id); confirmTtd.open = false"
                        wire:loading.attr="disabled"
                        wire:target="hapusTtd"
                        class="flex-1 h-[40px] bg-[#b45309] hover:bg-[#92400e] text-white text-[13px] font-semibold rounded-xl transition-colors disabled:opacity-60">
                    Hapus TTD
                </button>
            </div>

        </div>
    </div>

    {{-- Modal Konfirmasi Hapus Data --}}
    <div x-show="confirmDelete.open" style="display:none"
         class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 p-4"
         x-transition:enter="transition ease-out duration-150"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition ease-in duration-100"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         @click.self="confirmDelete.open = false">

        <div x-show="confirmDelete.open"
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
                <h4 class="text-[14px] font-semibold text-[#1a2a35] mb-1">Hapus Penandatangan?</h4>
                <p class="text-[12px] text-[#8a9ba8] leading-[1.6]">
                    Data <span class="font-semibold text-[#1a2a35]" x-text="'&quot;' + confirmDelete.nama + '&quot;'"></span> akan dihapus permanen.
                </p>
            </div>

            <div class="flex gap-3 mt-5">
                <button @click="confirmDelete.open = false"
                        class="flex-1 h-[40px] bg-white border border-[#D8DDE2] text-[#1a2a35] text-[13px] font-semibold rounded-xl hover:bg-[#F4F6F8] transition-colors">
                    Batal
                </button>
                <button @click="$wire.delete(confirmDelete.id); confirmDelete.open = false"
                        wire:loading.attr="disabled"
                        wire:target="delete"
                        class="flex-1 h-[40px] bg-[#D2092F] hover:bg-[#b8082a] text-white text-[13px] font-semibold rounded-xl transition-colors disabled:opacity-60">
                    Hapus
                </button>
            </div>

        </div>
    </div>
</div>
