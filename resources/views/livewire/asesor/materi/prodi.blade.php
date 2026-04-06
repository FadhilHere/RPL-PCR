<?php

use Livewire\Attributes\Layout;
use Livewire\Volt\Component;
use App\Models\Asesor;
use App\Models\ProgramStudi;
use App\Models\MataKuliah;
use App\Models\Cpmk;
use App\Models\Pertanyaan;
use App\Livewire\Forms\MataKuliahForm;
use Illuminate\Validation\Rule;

new #[Layout('components.layouts.asesor')] class extends Component {
    public ProgramStudi $prodi;

    // Modal tambah/edit MK
    public bool $showMkModal = false;
    public MataKuliahForm $mk;

    // Accordion CPK: expanded MK ID
    public ?int $expandedMkId = null;

    // Form tambah CPMK
    public string $cpmkDeskripsi = '';

    // Edit CPMK
    public ?int $editCpmkId = null;
    public string $editCpmkDeskripsi = '';

    // Pertanyaan
    public string $pertanyaanBaru = '';
    public ?int $editPertanyaanId = null;
    public string $editPertanyaanTeks = '';

    // Modal konfirmasi hapus
    public array $modal = ['show' => false, 'action' => '', 'id' => 0, 'message' => ''];

    public function mount(ProgramStudi $prodi): void
    {
        $asesorId   = auth()->user()->asesor?->id;
        $isAssigned = $asesorId && Asesor::find($asesorId)
            ->programStudi()->where('program_studi_id', $prodi->id)->exists();

        if (! $isAssigned) {
            abort(403, 'Anda tidak ditugaskan ke prodi ini.');
        }

        $this->prodi = $prodi;
    }

    public function with(): array
    {
        return [
            'mataKuliah' => MataKuliah::withCount('cpmk')
                ->with([
                    'cpmk'       => fn($q) => $q->orderBy('urutan'),
                    'pertanyaan' => fn($q) => $q->orderBy('urutan'),
                ])
                ->where('program_studi_id', $this->prodi->id)
                ->orderBy('semester')->orderBy('nama')
                ->get(),
        ];
    }

    public function openAddMk(): void
    {
        $this->mk->reset();
        $this->showMkModal = true;
    }

    public function openEditMk(int $id): void
    {
        $mataKuliah = MataKuliah::findOrFail($id);
        $this->mk->editId    = $id;
        $this->mk->kode      = $mataKuliah->kode;
        $this->mk->nama      = $mataKuliah->nama;
        $this->mk->sks       = $mataKuliah->sks;
        $this->mk->semester  = $mataKuliah->semester;
        $this->mk->deskripsi = $mataKuliah->deskripsi ?? '';
        $this->mk->bisaRpl   = $mataKuliah->bisa_rpl;
        $this->showMkModal   = true;
    }

    public function saveMk(): void
    {
        $this->validate([
            'mk.kode' => ['required', 'string', 'max:20',
                Rule::unique('mata_kuliah', 'kode')
                    ->where('program_studi_id', $this->prodi->id)
                    ->ignore($this->mk->editId),
            ],
            'mk.nama'     => 'required|string|max:255',
            'mk.sks'      => 'required|integer|min:1|max:20',
            'mk.semester' => 'required|integer|min:1|max:8',
        ], [
            'mk.kode.unique' => 'Kode ini sudah digunakan oleh mata kuliah lain di prodi ini.',
        ]);

        $data = [
            'program_studi_id' => $this->prodi->id,
            'kode'             => $this->mk->kode,
            'nama'             => $this->mk->nama,
            'sks'              => $this->mk->sks,
            'semester'         => $this->mk->semester,
            'deskripsi'        => $this->mk->deskripsi ?: null,
            'bisa_rpl'         => $this->mk->bisaRpl,
        ];

        if ($this->mk->editId) {
            MataKuliah::findOrFail($this->mk->editId)->update($data);
        } else {
            MataKuliah::create($data);
        }

        $this->showMkModal = false;
        $this->mk->reset();
    }

    public function deleteMk(int $id): void
    {
        MataKuliah::findOrFail($id)->delete();
    }

    public function toggleCpmk(int $mkId): void
    {
        $this->expandedMkId     = ($this->expandedMkId === $mkId) ? null : $mkId;
        $this->cpmkDeskripsi    = '';
        $this->pertanyaanBaru   = '';
        $this->editCpmkId       = null;
        $this->editPertanyaanId = null;
    }

    public function addCpmk(int $mkId): void
    {
        $this->validate(['cpmkDeskripsi' => 'required|string']);

        $urutan = Cpmk::where('mata_kuliah_id', $mkId)->max('urutan') + 1;
        Cpmk::create([
            'mata_kuliah_id' => $mkId,
            'deskripsi'      => $this->cpmkDeskripsi,
            'urutan'         => $urutan,
        ]);
        $this->cpmkDeskripsi = '';
    }

    public function deleteCpmk(int $id): void
    {
        Cpmk::findOrFail($id)->delete();
    }

    public function openEditCpmk(int $id): void
    {
        $cpmk = Cpmk::findOrFail($id);
        $this->editCpmkId = $id;
        $this->editCpmkDeskripsi = $cpmk->deskripsi;
    }

    public function saveEditCpmk(): void
    {
        $this->validate(['editCpmkDeskripsi' => 'required|string']);
        Cpmk::findOrFail($this->editCpmkId)->update(['deskripsi' => $this->editCpmkDeskripsi]);
        $this->editCpmkId = null;
        $this->editCpmkDeskripsi = '';
    }

    public function openConfirm(string $action, int $id, string $message): void
    {
        $this->modal = ['show' => true, 'action' => $action, 'id' => $id, 'message' => $message];
    }

    public function addPertanyaan(int $mkId): void
    {
        $this->validate(['pertanyaanBaru' => 'required|string']);

        $urutan = Pertanyaan::where('mata_kuliah_id', $mkId)->max('urutan') + 1;
        Pertanyaan::create([
            'mata_kuliah_id' => $mkId,
            'pertanyaan'     => $this->pertanyaanBaru,
            'urutan'         => $urutan,
        ]);
        $this->pertanyaanBaru = '';
    }

    public function deletePertanyaan(int $id): void
    {
        Pertanyaan::findOrFail($id)->delete();
    }

    public function openEditPertanyaan(int $id): void
    {
        $p = Pertanyaan::findOrFail($id);
        $this->editPertanyaanId   = $id;
        $this->editPertanyaanTeks = $p->pertanyaan;
    }

    public function saveEditPertanyaan(): void
    {
        $this->validate(['editPertanyaanTeks' => 'required|string']);
        Pertanyaan::findOrFail($this->editPertanyaanId)->update(['pertanyaan' => $this->editPertanyaanTeks]);
        $this->editPertanyaanId   = null;
        $this->editPertanyaanTeks = '';
    }

    public function confirmed(): void
    {
        match ($this->modal['action']) {
            'deleteMk'          => $this->deleteMk($this->modal['id']),
            'deleteCpmk'        => $this->deleteCpmk($this->modal['id']),
            'deletePertanyaan'  => $this->deletePertanyaan($this->modal['id']),
        };
        $this->modal = ['show' => false, 'action' => '', 'id' => 0, 'message' => ''];
    }
}; ?>

<x-slot:title>{{ $prodi->nama }}</x-slot:title>
<x-slot:subtitle><a href="{{ route('asesor.materi.index') }}" class="text-primary hover:underline">Materi Asesmen</a> &rsaquo; {{ $prodi->kode }}</x-slot:subtitle>

<div>

    {{-- Header aksi --}}
    <div class="flex items-center justify-between mb-5">
        <div class="text-[13px] text-[#5a6a75]">
            <span class="font-semibold text-[#1a2a35]">{{ count($mataKuliah) }}</span> mata kuliah · {{ $prodi->jenjang }} · {{ $prodi->total_sks }} SKS
        </div>
        <button wire:click="openAddMk"
                class="bg-primary hover:bg-[#005f78] text-white text-[13px] font-semibold px-4 py-2 rounded-lg transition-colors flex items-center gap-2">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                <line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/>
            </svg>
            Tambah Mata Kuliah
        </button>
    </div>

    {{-- Tabel MK --}}
    <div class="bg-white rounded-[10px] border border-[#E5E8EC] overflow-hidden">
        @if (count($mataKuliah) === 0)
            <div class="py-14 text-center text-[13px] text-[#8a9ba8]">
                Belum ada mata kuliah. Klik "Tambah Mata Kuliah" untuk mulai.
            </div>
        @else
            @foreach ($mataKuliah as $mataKuliahItem)
            <div class="border-b border-[#F0F2F5] last:border-0">

                {{-- Row MK --}}
                <div class="flex items-center gap-3 px-5 py-3.5">
                    <div class="flex-1 min-w-0">
                        <div class="flex items-center gap-2 mb-0.5">
                            <span class="text-[10px] font-semibold text-primary bg-[#E8F4F8] px-[7px] py-[3px] rounded shrink-0">{{ $mataKuliahItem->kode }}</span>
                            <span class="text-[13px] font-medium text-[#1a2a35] truncate">{{ $mataKuliahItem->nama }}</span>
                        </div>
                        <div class="text-[11px] text-[#8a9ba8]">
                            Semester {{ $mataKuliahItem->semester }} · {{ $mataKuliahItem->sks }} SKS ·
                            <span class="{{ $mataKuliahItem->bisa_rpl ? 'text-[#1e7e3e]' : 'text-[#c62828]' }}">
                                {{ $mataKuliahItem->bisa_rpl ? 'Bisa RPL' : 'Tidak Bisa RPL' }}
                            </span>
                        </div>
                    </div>
                    <span class="text-[11px] text-[#8a9ba8] shrink-0">{{ $mataKuliahItem->cpmk_count }} CPMK</span>

                    <button wire:click="toggleCpmk({{ $mataKuliahItem->id }})"
                            class="text-[12px] text-primary font-medium hover:underline px-2 py-1 shrink-0">
                        {{ $expandedMkId === $mataKuliahItem->id ? '▲ Sembunyikan' : '▼ CPMK' }}
                    </button>
                    <button wire:click="openEditMk({{ $mataKuliahItem->id }})"
                            class="text-[#5a6a75] hover:text-primary transition-colors p-1.5 shrink-0">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M11 4H4a2 2 0 00-2 2v14a2 2 0 002 2h14a2 2 0 002-2v-7"/>
                            <path d="M18.5 2.5a2.121 2.121 0 013 3L12 15l-4 1 1-4 9.5-9.5z"/>
                        </svg>
                    </button>
                    <button wire:click="openConfirm('deleteMk', {{ $mataKuliahItem->id }}, 'Hapus mata kuliah &quot;{{ $mataKuliahItem->nama }}&quot;? Semua CPMK terkait juga akan dihapus.')"
                            class="text-[#c62828] hover:text-[#a02020] transition-colors p-1.5 shrink-0">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <polyline points="3 6 5 6 21 6"/><path d="M19 6l-1 14a2 2 0 01-2 2H8a2 2 0 01-2-2L5 6"/>
                            <path d="M10 11v6"/><path d="M14 11v6"/>
                            <path d="M9 6V4a1 1 0 011-1h4a1 1 0 011 1v2"/>
                        </svg>
                    </button>
                </div>

                {{-- Accordion CPK --}}
                @if ($expandedMkId === $mataKuliahItem->id)
                <div class="bg-[#F8FAFB] border-t border-[#F0F2F5] px-5 py-4">
                    <div class="text-[11px] font-semibold text-[#5a6a75] uppercase tracking-[0.5px] mb-3">
                        Capaian Pembelajaran Mata Kuliah (CPMK)
                    </div>

                    @php $cpmkList = $mataKuliahItem->cpmk; @endphp

                    @forelse ($cpmkList as $cpmk)
                    <div class="mb-2">
                        @if ($editCpmkId === $cpmk->id)
                        <div class="flex items-start gap-2.5">
                            <span class="w-5 h-5 rounded-full bg-[#E8F4F8] text-primary text-[10px] font-semibold flex items-center justify-center shrink-0 mt-2">
                                {{ $cpmk->urutan }}
                            </span>
                            <input wire:model="editCpmkDeskripsi" type="text" placeholder="Deskripsi CPMK..."
                                   class="flex-1 h-9 px-3 text-[12px] text-[#1a2a35] bg-white border border-[#E0E5EA] rounded-lg outline-none focus:border-primary focus:ring-2 focus:ring-primary/10"
                                   wire:keydown.enter="saveEditCpmk" />
                            <button wire:click="saveEditCpmk"
                                    class="bg-primary hover:bg-[#005f78] text-white text-[11px] font-semibold px-2 py-1.5 rounded-lg transition-colors shrink-0">
                                Simpan
                            </button>
                            <button wire:click="$set('editCpmkId', null)"
                                    class="bg-white border border-[#D8DDE2] text-[#5a6a75] text-[11px] font-semibold px-2 py-1.5 rounded-lg hover:bg-[#F8FAFB] transition-colors shrink-0">
                                Batal
                            </button>
                        </div>
                        @else
                        <div class="flex items-start gap-2.5 group">
                            <span class="w-5 h-5 rounded-full bg-[#E8F4F8] text-primary text-[10px] font-semibold flex items-center justify-center shrink-0 mt-0.5">
                                {{ $cpmk->urutan }}
                            </span>
                            <span class="flex-1 text-[12px] text-[#1a2a35] leading-[1.5]">{{ $cpmk->deskripsi }}</span>
                            <button wire:click="openEditCpmk({{ $cpmk->id }})"
                                    class="text-[#5a6a75] opacity-0 group-hover:opacity-100 transition-opacity p-0.5 shrink-0 hover:text-primary">
                                <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M11 4H4a2 2 0 00-2 2v14a2 2 0 002 2h14a2 2 0 002-2v-7"/>
                                    <path d="M18.5 2.5a2.121 2.121 0 013 3L12 15l-4 1 1-4 9.5-9.5z"/>
                                </svg>
                            </button>
                            <button wire:click="openConfirm('deleteCpmk', {{ $cpmk->id }}, 'Hapus CPMK ini?')"
                                    class="text-[#c62828] opacity-0 group-hover:opacity-100 transition-opacity p-0.5 shrink-0">
                                <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/>
                                </svg>
                            </button>
                        </div>
                        @endif
                    </div>
                    @empty
                    <p class="text-[12px] text-[#8a9ba8] mb-3">Belum ada CPMK. Tambahkan di bawah.</p>
                    @endforelse

                    {{-- Form tambah CPMK --}}
                    <div class="mt-3 flex gap-2">
                        <input wire:model="cpmkDeskripsi"
                               type="text"
                               placeholder="Deskripsi CPMK baru..."
                               class="flex-1 h-9 px-3 text-[12px] text-[#1a2a35] bg-white border border-[#E0E5EA] rounded-lg outline-none focus:border-primary focus:ring-2 focus:ring-primary/10 placeholder:text-[#b0bec5]"
                               wire:keydown.enter="addCpmk({{ $mataKuliahItem->id }})" />
                        <button wire:click="addCpmk({{ $mataKuliahItem->id }})"
                                class="bg-primary hover:bg-[#005f78] text-white text-[12px] font-semibold px-3 py-1.5 rounded-lg transition-colors shrink-0">
                            Tambah
                        </button>
                    </div>
                    @error('cpmkDeskripsi')
                        <p class="mt-1 text-[11px] text-[#c62828]">{{ $message }}</p>
                    @enderror

                    {{-- Sub CPMK (Pertanyaan Asesmen Mandiri) --}}
                    <div class="mt-5 pt-4 border-t border-[#E8EDEF]">
                        <div class="text-[11px] font-semibold text-[#5a6a75] uppercase tracking-[0.5px] mb-3">
                            Sub CPMK
                        </div>
                        @php $pertanyaanList = $mataKuliahItem->pertanyaan; @endphp
                        @forelse ($pertanyaanList as $p)
                        <div class="mb-2">
                            @if ($editPertanyaanId === $p->id)
                            <div class="flex items-start gap-2.5">
                                <span class="w-5 h-5 rounded-full bg-[#F1F3F4] text-[#5a6a75] text-[10px] font-semibold flex items-center justify-center shrink-0 mt-2">
                                    {{ $p->urutan }}
                                </span>
                                <input wire:model="editPertanyaanTeks" type="text" placeholder="Teks pertanyaan..."
                                       class="flex-1 h-9 px-3 text-[12px] text-[#1a2a35] bg-white border border-[#E0E5EA] rounded-lg outline-none focus:border-primary focus:ring-2 focus:ring-primary/10"
                                       wire:keydown.enter="saveEditPertanyaan" />
                                <button wire:click="saveEditPertanyaan"
                                        class="bg-primary hover:bg-[#005f78] text-white text-[11px] font-semibold px-2 py-1.5 rounded-lg transition-colors shrink-0">
                                    Simpan
                                </button>
                                <button wire:click="$set('editPertanyaanId', null)"
                                        class="bg-white border border-[#D8DDE2] text-[#5a6a75] text-[11px] font-semibold px-2 py-1.5 rounded-lg hover:bg-[#F8FAFB] transition-colors shrink-0">
                                    Batal
                                </button>
            </div>
                            @else
                            <div class="flex items-start gap-2.5 group">
                                <span class="w-5 h-5 rounded-full bg-[#F1F3F4] text-[#5a6a75] text-[10px] font-semibold flex items-center justify-center shrink-0 mt-0.5">
                                    {{ $p->urutan }}
                                </span>
                                <span class="flex-1 text-[12px] text-[#1a2a35] leading-[1.5]">{{ $p->pertanyaan }}</span>
                                <button wire:click="openEditPertanyaan({{ $p->id }})"
                                        class="text-[#5a6a75] opacity-0 group-hover:opacity-100 transition-opacity p-0.5 shrink-0 hover:text-primary">
                                    <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                        <path d="M11 4H4a2 2 0 00-2 2v14a2 2 0 002 2h14a2 2 0 002-2v-7"/>
                                        <path d="M18.5 2.5a2.121 2.121 0 013 3L12 15l-4 1 1-4 9.5-9.5z"/>
                                    </svg>
                                </button>
                                <button wire:click="openConfirm('deletePertanyaan', {{ $p->id }}, 'Hapus pertanyaan ini?')"
                                        class="text-[#c62828] opacity-0 group-hover:opacity-100 transition-opacity p-0.5 shrink-0">
                                    <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                        <line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/>
                                    </svg>
                                </button>
                            </div>
                            @endif
                        </div>
                        @empty
                        <p class="text-[12px] text-[#8a9ba8] mb-3">Belum ada Sub CPMK.</p>
                        @endforelse
                        <div class="mt-3 flex gap-2">
                            <input wire:model="pertanyaanBaru" type="text" placeholder="Tambah Sub CPMK..."
                                   class="flex-1 h-9 px-3 text-[12px] text-[#1a2a35] bg-white border border-[#E0E5EA] rounded-lg outline-none focus:border-primary focus:ring-2 focus:ring-primary/10 placeholder:text-[#b0bec5]"
                                   wire:keydown.enter="addPertanyaan({{ $mataKuliahItem->id }})" />
                            <button wire:click="addPertanyaan({{ $mataKuliahItem->id }})"
                                    class="bg-primary hover:bg-[#005f78] text-white text-[12px] font-semibold px-3 py-1.5 rounded-lg transition-colors shrink-0">
                                Tambah
                            </button>
                        </div>
                        @error('pertanyaanBaru') <p class="mt-1 text-[11px] text-[#c62828]">{{ $message }}</p> @enderror
                    </div>
                </div>
                @endif

            </div>
            @endforeach
        @endif
    </div>

    {{-- Modal Tambah/Edit MK --}}
    @if ($showMkModal)
    <div class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 p-4">
        <div class="bg-white rounded-2xl shadow-xl w-full max-w-md p-6" wire:click.stop>
            <h3 class="text-[15px] font-semibold text-[#1a2a35] mb-5">
                {{ $mk->editId ? 'Edit Mata Kuliah' : 'Tambah Mata Kuliah' }}
            </h3>

            <div class="space-y-4">

                <div class="flex gap-3">
                    <div class="w-28">
                        <label class="block text-[11px] font-semibold text-[#5a6a75] uppercase tracking-[0.7px] mb-1.5">Kode</label>
                        <input wire:model="mk.kode" type="text" placeholder="TI201"
                               class="w-full h-[42px] px-3 text-[13px] text-[#1a2a35] bg-white border border-[#E0E5EA] rounded-xl outline-none focus:border-primary focus:ring-2 focus:ring-primary/10 placeholder:text-[#b0bec5]" />
                        @error('mk.kode') <p class="mt-1 text-[10px] text-[#c62828]">{{ $message }}</p> @enderror
                    </div>
                    <div class="flex-1">
                        <label class="block text-[11px] font-semibold text-[#5a6a75] uppercase tracking-[0.7px] mb-1.5">Nama Mata Kuliah</label>
                        <input wire:model="mk.nama" type="text" placeholder="Nama lengkap MK"
                               class="w-full h-[42px] px-3 text-[13px] text-[#1a2a35] bg-white border border-[#E0E5EA] rounded-xl outline-none focus:border-primary focus:ring-2 focus:ring-primary/10 placeholder:text-[#b0bec5]" />
                        @error('mk.nama') <p class="mt-1 text-[10px] text-[#c62828]">{{ $message }}</p> @enderror
                    </div>
                </div>

                <div class="flex gap-3">
                    <div class="flex-1">
                        <label class="block text-[11px] font-semibold text-[#5a6a75] uppercase tracking-[0.7px] mb-1.5">SKS</label>
                        <x-form.select wire:model="mk.sks"
                            :options="collect(range(1,20))->mapWithKeys(fn($n) => [$n => $n.' SKS'])->all()" />
                    </div>
                    <div class="flex-1">
                        <label class="block text-[11px] font-semibold text-[#5a6a75] uppercase tracking-[0.7px] mb-1.5">Semester</label>
                        <x-form.select wire:model="mk.semester"
                            :options="collect(range(1,8))->mapWithKeys(fn($n) => [$n => 'Semester '.$n])->all()" />
                    </div>
                </div>

                <div>
                    <label class="block text-[11px] font-semibold text-[#5a6a75] uppercase tracking-[0.7px] mb-1.5">Deskripsi (opsional)</label>
                    <textarea wire:model="mk.deskripsi" rows="2" placeholder="Deskripsi singkat mata kuliah..."
                              class="w-full px-3 py-2.5 text-[13px] text-[#1a2a35] bg-white border border-[#E0E5EA] rounded-xl outline-none focus:border-primary focus:ring-2 focus:ring-primary/10 placeholder:text-[#b0bec5] resize-none"></textarea>
                </div>

                <div class="flex items-center gap-2.5">
                    <input wire:model="mk.bisaRpl" type="checkbox" id="bisaRpl"
                           class="w-4 h-4 rounded border-[#D0D5DD] accent-primary cursor-pointer" />
                    <label for="bisaRpl" class="text-[13px] text-[#5a6a75] cursor-pointer select-none">
                        Mata kuliah ini dapat direkognisi (bisa RPL)
                    </label>
                </div>

            </div>

            <div class="flex gap-3 mt-6">
                <button wire:click="$set('showMkModal', false)"
                        class="flex-1 h-[42px] bg-white border border-[#D8DDE2] text-[#1a2a35] text-[13px] font-semibold rounded-xl hover:bg-[#F4F6F8] transition-colors">
                    Batal
                </button>
                <button wire:click="saveMk"
                        class="flex-1 h-[42px] bg-primary hover:bg-[#005f78] text-white text-[13px] font-semibold rounded-xl transition-colors">
                    {{ $mk->editId ? 'Simpan Perubahan' : 'Tambahkan' }}
                </button>
            </div>
        </div>
    </div>
    @endif

    {{-- Modal Konfirmasi Hapus --}}
    @if ($modal['show'])
    <div class="fixed inset-0 z-50 flex items-center justify-center bg-black/40">
        <div class="bg-white rounded-2xl shadow-xl w-full max-w-sm p-6 mx-4">
            <div class="w-10 h-10 rounded-full bg-[#FCE8E6] flex items-center justify-center mb-4 mx-auto">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#c62828" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <polyline points="3 6 5 6 21 6"/><path d="M19 6l-1 14a2 2 0 01-2 2H8a2 2 0 01-2-2L5 6"/><path d="M10 11v6"/><path d="M14 11v6"/>
                </svg>
            </div>
            <p class="text-[14px] text-[#1a2a35] text-center mb-6">{{ $modal['message'] }}</p>
            <div class="flex gap-3">
                <button wire:click="$set('modal.show', false)"
                        class="flex-1 h-[42px] bg-white border border-[#D8DDE2] text-[#1a2a35] text-[13px] font-semibold rounded-xl hover:bg-[#F4F6F8] transition-colors">
                    Batal
                </button>
                <button wire:click="confirmed"
                        class="flex-1 h-[42px] bg-[#c62828] hover:bg-[#a02020] text-white text-[13px] font-semibold rounded-xl transition-colors">
                    Hapus
                </button>
            </div>
        </div>
    </div>
    @endif

</div>
