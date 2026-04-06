<?php

use Livewire\Attributes\Layout;
use Livewire\Volt\Component;
use Livewire\WithFileUploads;
use Illuminate\Validation\Rule;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\MataKuliahTemplateExport;
use App\Imports\MataKuliahImport;
use App\Models\ProgramStudi;
use App\Models\MataKuliah;
use App\Models\Cpmk;
use App\Models\Pertanyaan;

new #[Layout('components.layouts.admin')] class extends Component {
    use WithFileUploads;

    public ProgramStudi $prodi;
    public string $cpmkDeskripsi  = '';
    public string $pertanyaanBaru = '';
    public $fileImport = null;

    public function mount(ProgramStudi $prodi): void
    {
        $this->prodi = $prodi;
    }

    public function with(): array
    {
        return [
            'mataKuliah' => MataKuliah::with([
                'cpmk'       => fn($q) => $q->orderBy('urutan'),
                'pertanyaan' => fn($q) => $q->orderBy('urutan'),
            ])->withCount('cpmk')
              ->where('program_studi_id', $this->prodi->id)
              ->orderBy('semester')->orderBy('nama')
              ->get(),
        ];
    }

    public function saveMk(?int $editMkId, string $kode, string $nama, int $sks, int $semester, string $deskripsi, bool $bisaRpl): void
    {
        $validator = validator(
            compact('kode', 'nama', 'sks', 'semester'),
            [
                'kode'     => ['required', 'string', 'max:20',
                    Rule::unique('mata_kuliah', 'kode')
                        ->where('program_studi_id', $this->prodi->id)
                        ->ignore($editMkId)
                ],
                'nama'     => 'required|string|max:255',
                'sks'      => 'required|integer|min:1|max:20',
                'semester' => 'required|integer|min:1|max:8',
            ],
            ['kode.unique' => 'Kode ini sudah digunakan oleh mata kuliah lain di prodi ini.']
        );

        if ($validator->fails()) {
            $this->dispatch('mk-validation-errors', errors: $validator->errors()->toArray());
            return;
        }

        $data = [
            'program_studi_id' => $this->prodi->id,
            'kode'             => $kode,
            'nama'             => $nama,
            'sks'              => $sks,
            'semester'         => $semester,
            'deskripsi'        => $deskripsi ?: null,
            'bisa_rpl'         => $bisaRpl,
        ];

        $editMkId ? MataKuliah::findOrFail($editMkId)->update($data) : MataKuliah::create($data);
        $this->dispatch('mk-saved');
    }

    public function deleteMk(int $id): void
    {
        MataKuliah::findOrFail($id)->delete();
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

    public function saveEditCpmk(int $id, string $deskripsi): void
    {
        if (! trim($deskripsi)) return;
        Cpmk::findOrFail($id)->update(['deskripsi' => trim($deskripsi)]);
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

    public function saveEditPertanyaan(int $id, string $teks): void
    {
        if (! trim($teks)) return;
        Pertanyaan::findOrFail($id)->update(['pertanyaan' => trim($teks)]);
    }

    public function downloadTemplate()
    {
        return Excel::download(new MataKuliahTemplateExport(), 'template_mata_kuliah.xlsx');
    }

    public function importExcel(): void
    {
        $this->validate(['fileImport' => 'required|file|mimes:xlsx,xls|max:5120']);

        $import = new MataKuliahImport($this->prodi->id);
        Excel::import($import, $this->fileImport->getRealPath());

        $this->fileImport = null;
        $this->dispatch('import-completed',
            summary: $import->getSummary(),
            errors: $import->getErrors(),
        );
    }
}; ?>

<x-slot:title>{{ $prodi->nama }}</x-slot:title>
<x-slot:subtitle><a href="{{ route('admin.materi.index') }}" class="text-primary hover:underline">Materi Asesmen</a> &rsaquo; {{ $prodi->kode }}</x-slot:subtitle>

<div x-data="{
    showMkModal: false,
    editMkId: null,
    mkForm: { kode: '', nama: '', sks: 2, semester: 1, deskripsi: '', bisaRpl: true },
    mkErrors: {},
    openAddMk() {
        this.editMkId = null;
        this.mkForm = { kode: '', nama: '', sks: 2, semester: 1, deskripsi: '', bisaRpl: true };
        this.mkErrors = {};
        this.showMkModal = true;
    },
    openEditMk(id, mk) {
        this.editMkId = id;
        this.mkForm = { ...mk };
        this.mkErrors = {};
        this.showMkModal = true;
    },
    confirmModal: { show: false, action: '', id: 0, message: '' },
    expandedMkId: null,
    sksOpen: false,
    semesterOpen: false,
    showImportModal: false,
    importSummary: null,
    importErrors: [],
    importDone: false,
    openImport() {
        this.importSummary = null;
        this.importErrors = [];
        this.importDone = false;
        this.showImportModal = true;
    },
    init() {
        this.$wire.on('mk-saved', () => { this.showMkModal = false; this.mkErrors = {}; });
        this.$wire.on('mk-validation-errors', ({ errors }) => { this.mkErrors = errors; });
        this.$wire.on('import-completed', ({ summary, errors }) => {
            this.importSummary = summary;
            this.importErrors = errors;
            this.importDone = true;
        });
    }
}">

    <div class="flex items-center justify-between mb-5">
        <div class="text-[13px] text-[#5a6a75]">
            <span class="font-semibold text-[#1a2a35]">{{ count($mataKuliah) }}</span> mata kuliah · {{ $prodi->jenjang }} · {{ $prodi->total_sks }} SKS
        </div>
        <div class="flex items-center gap-2">
            <button @click="openImport()"
                    class="border border-primary text-primary hover:bg-[#E8F4F8] text-[13px] font-semibold px-4 py-2 rounded-lg transition-colors flex items-center gap-2">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M21 15v4a2 2 0 01-2 2H5a2 2 0 01-2-2v-4"/><polyline points="17 8 12 3 7 8"/><line x1="12" y1="3" x2="12" y2="15"/>
                </svg>
                Import Excel
            </button>
            <button @click="openAddMk()"
                    class="bg-primary hover:bg-[#005f78] text-white text-[13px] font-semibold px-4 py-2 rounded-lg transition-colors flex items-center gap-2">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                    <line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/>
                </svg>
                Tambah Mata Kuliah
            </button>
        </div>
    </div>

    <div class="bg-white rounded-[10px] border border-[#E5E8EC] overflow-hidden">
        @if (count($mataKuliah) === 0)
            <div class="py-14 text-center text-[13px] text-[#8a9ba8]">
                Belum ada mata kuliah. Klik "Tambah Mata Kuliah" untuk mulai.
            </div>
        @else
            @foreach ($mataKuliah as $mk)
            <div class="border-b border-[#F0F2F5] last:border-0" wire:key="mk-row-{{ $mk->id }}">

                <div class="flex items-center gap-3 px-5 py-3.5">
                    <div class="flex-1 min-w-0">
                        <div class="flex items-center gap-2 mb-0.5">
                            <span class="text-[10px] font-semibold text-primary bg-[#E8F4F8] px-[7px] py-[3px] rounded shrink-0">{{ $mk->kode }}</span>
                            <span class="text-[13px] font-medium text-[#1a2a35] truncate">{{ $mk->nama }}</span>
                        </div>
                        <div class="text-[11px] text-[#8a9ba8]">
                            Semester {{ $mk->semester }} · {{ $mk->sks }} SKS ·
                            <span class="{{ $mk->bisa_rpl ? 'text-[#1e7e3e]' : 'text-[#c62828]' }}">
                                {{ $mk->bisa_rpl ? 'Bisa RPL' : 'Tidak Bisa RPL' }}
                            </span>
                        </div>
                    </div>
                    <span class="text-[11px] text-[#8a9ba8] shrink-0">{{ $mk->cpmk_count }} CPMK</span>
                    <button @click="expandedMkId = expandedMkId === {{ $mk->id }} ? null : {{ $mk->id }}"
                            class="text-[12px] text-primary font-medium hover:underline px-2 py-1 shrink-0">
                        <span x-text="expandedMkId === {{ $mk->id }} ? '▲ Sembunyikan' : '▼ CPMK'">▼ CPMK</span>
                    </button>
                    <button @click="openEditMk({{ $mk->id }}, @js(['kode' => $mk->kode, 'nama' => $mk->nama, 'sks' => $mk->sks, 'semester' => $mk->semester, 'deskripsi' => $mk->deskripsi ?? '', 'bisaRpl' => (bool)$mk->bisa_rpl]))"
                            class="text-[#5a6a75] hover:text-primary transition-colors p-1.5 shrink-0">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M11 4H4a2 2 0 00-2 2v14a2 2 0 002 2h14a2 2 0 002-2v-7"/>
                            <path d="M18.5 2.5a2.121 2.121 0 013 3L12 15l-4 1 1-4 9.5-9.5z"/>
                        </svg>
                    </button>
                    <button @click="confirmModal = { show: true, action: 'deleteMk', id: {{ $mk->id }}, message: 'Hapus mata kuliah &quot;{{ addslashes($mk->nama) }}&quot;?' }"
                            class="text-[#c62828] hover:text-[#a02020] transition-colors p-1.5 shrink-0">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <polyline points="3 6 5 6 21 6"/><path d="M19 6l-1 14a2 2 0 01-2 2H8a2 2 0 01-2-2L5 6"/>
                            <path d="M10 11v6"/><path d="M14 11v6"/>
                            <path d="M9 6V4a1 1 0 011-1h4a1 1 0 011 1v2"/>
                        </svg>
                    </button>
                </div>

                {{-- CPMK & Pertanyaan Panel — x-show untuk instant toggle, x-data untuk inline edit state --}}
                <div x-show="expandedMkId === {{ $mk->id }}" style="display:none"
                     x-data="{ editCpmkId: null, editCpmkText: '', editPertanyaanId: null, editPertanyaanText: '' }"
                     class="bg-[#F8FAFB] border-t border-[#F0F2F5] px-5 py-4">

                    {{-- CPMK --}}
                    <div class="text-[11px] font-semibold text-[#5a6a75] uppercase tracking-[0.5px] mb-3">
                        Capaian Pembelajaran Mata Kuliah (CPMK)
                    </div>

                    @forelse ($mk->cpmk as $cpmk)
                    <div class="mb-2" wire:key="cpmk-{{ $cpmk->id }}">
                        {{-- View mode --}}
                        <div x-show="editCpmkId !== {{ $cpmk->id }}" class="flex items-start gap-2.5 group">
                            <span class="w-5 h-5 rounded-full bg-[#E8F4F8] text-primary text-[10px] font-semibold flex items-center justify-center shrink-0 mt-0.5">
                                {{ $cpmk->urutan }}
                            </span>
                            <span class="flex-1 text-[12px] text-[#1a2a35] leading-[1.5]">{{ $cpmk->deskripsi }}</span>
                            <button @click="editCpmkId = {{ $cpmk->id }}; editCpmkText = @js($cpmk->deskripsi)"
                                    class="text-[#5a6a75] opacity-0 group-hover:opacity-100 transition-opacity p-0.5 shrink-0 hover:text-primary">
                                <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M11 4H4a2 2 0 00-2 2v14a2 2 0 002 2h14a2 2 0 002-2v-7"/>
                                    <path d="M18.5 2.5a2.121 2.121 0 013 3L12 15l-4 1 1-4 9.5-9.5z"/>
                                </svg>
                            </button>
                            <button @click="confirmModal = { show: true, action: 'deleteCpmk', id: {{ $cpmk->id }}, message: 'Hapus CPMK ini?' }"
                                    class="text-[#c62828] opacity-0 group-hover:opacity-100 transition-opacity p-0.5 shrink-0">
                                <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/>
                                </svg>
                            </button>
                        </div>
                        {{-- Edit mode --}}
                        <div x-show="editCpmkId === {{ $cpmk->id }}" style="display:none" class="flex items-start gap-2.5">
                            <span class="w-5 h-5 rounded-full bg-[#E8F4F8] text-primary text-[10px] font-semibold flex items-center justify-center shrink-0 mt-2">
                                {{ $cpmk->urutan }}
                            </span>
                            <input x-model="editCpmkText" type="text" placeholder="Deskripsi CPMK..."
                                   class="flex-1 h-9 px-3 text-[12px] text-[#1a2a35] bg-white border border-[#E0E5EA] rounded-lg outline-none focus:border-primary focus:ring-2 focus:ring-primary/10"
                                   @keydown.enter="$wire.saveEditCpmk(editCpmkId, editCpmkText); editCpmkId = null"
                                   @keydown.escape="editCpmkId = null" />
                            <button @click="$wire.saveEditCpmk(editCpmkId, editCpmkText); editCpmkId = null"
                                    class="bg-primary hover:bg-[#005f78] text-white text-[11px] font-semibold px-2 py-1.5 rounded-lg transition-colors shrink-0">
                                Simpan
                            </button>
                            <button @click="editCpmkId = null"
                                    class="bg-white border border-[#D8DDE2] text-[#5a6a75] text-[11px] font-semibold px-2 py-1.5 rounded-lg hover:bg-[#F8FAFB] transition-colors shrink-0">
                                Batal
                            </button>
                        </div>
                    </div>
                    @empty
                    <p class="text-[12px] text-[#8a9ba8] mb-3">Belum ada CPMK.</p>
                    @endforelse

                    <div class="mt-3 flex gap-2">
                        <input wire:model="cpmkDeskripsi" type="text" placeholder="Deskripsi CPMK baru..."
                               class="flex-1 h-9 px-3 text-[12px] text-[#1a2a35] bg-white border border-[#E0E5EA] rounded-lg outline-none focus:border-primary focus:ring-2 focus:ring-primary/10 placeholder:text-[#b0bec5]"
                               wire:keydown.enter="addCpmk({{ $mk->id }})" />
                        <button wire:click="addCpmk({{ $mk->id }})"
                                class="bg-primary hover:bg-[#005f78] text-white text-[12px] font-semibold px-3 py-1.5 rounded-lg transition-colors">
                            Tambah
                        </button>
                    </div>
                    @error('cpmkDeskripsi') <p class="mt-1 text-[11px] text-[#c62828]">{{ $message }}</p> @enderror

                    {{-- Sub CPMK (Pertanyaan Asesmen Mandiri) --}}
                    <div class="mt-5 pt-4 border-t border-[#E8EDEF]">
                        <div class="text-[11px] font-semibold text-[#5a6a75] uppercase tracking-[0.5px] mb-3">
                            Sub CPMK
                        </div>

                        @forelse ($mk->pertanyaan as $pt)
                        <div class="mb-2" wire:key="pt-{{ $pt->id }}">
                            {{-- View mode --}}
                            <div x-show="editPertanyaanId !== {{ $pt->id }}" class="flex items-start gap-2.5 group">
                                <span class="w-5 h-5 rounded-full bg-[#F1F3F4] text-[#5a6a75] text-[10px] font-semibold flex items-center justify-center shrink-0 mt-0.5">
                                    {{ $pt->urutan }}
                                </span>
                                <span class="flex-1 text-[12px] text-[#1a2a35] leading-[1.5]">{{ $pt->pertanyaan }}</span>
                                <button @click="editPertanyaanId = {{ $pt->id }}; editPertanyaanText = @js($pt->pertanyaan)"
                                        class="text-[#5a6a75] opacity-0 group-hover:opacity-100 transition-opacity p-0.5 shrink-0 hover:text-primary">
                                    <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                        <path d="M11 4H4a2 2 0 00-2 2v14a2 2 0 002 2h14a2 2 0 002-2v-7"/>
                                        <path d="M18.5 2.5a2.121 2.121 0 013 3L12 15l-4 1 1-4 9.5-9.5z"/>
                                    </svg>
                                </button>
                                <button @click="confirmModal = { show: true, action: 'deletePertanyaan', id: {{ $pt->id }}, message: 'Hapus pertanyaan ini?' }"
                                        class="text-[#c62828] opacity-0 group-hover:opacity-100 transition-opacity p-0.5 shrink-0">
                                    <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                        <line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/>
                                    </svg>
                                </button>
                            </div>
                            {{-- Edit mode --}}
                            <div x-show="editPertanyaanId === {{ $pt->id }}" style="display:none" class="flex items-start gap-2.5">
                                <span class="w-5 h-5 rounded-full bg-[#F1F3F4] text-[#5a6a75] text-[10px] font-semibold flex items-center justify-center shrink-0 mt-2">
                                    {{ $pt->urutan }}
                                </span>
                                <input x-model="editPertanyaanText" type="text" placeholder="Teks pertanyaan..."
                                       class="flex-1 h-9 px-3 text-[12px] text-[#1a2a35] bg-white border border-[#E0E5EA] rounded-lg outline-none focus:border-primary focus:ring-2 focus:ring-primary/10"
                                       @keydown.enter="$wire.saveEditPertanyaan(editPertanyaanId, editPertanyaanText); editPertanyaanId = null"
                                       @keydown.escape="editPertanyaanId = null" />
                                <button @click="$wire.saveEditPertanyaan(editPertanyaanId, editPertanyaanText); editPertanyaanId = null"
                                        class="bg-primary hover:bg-[#005f78] text-white text-[11px] font-semibold px-2 py-1.5 rounded-lg transition-colors shrink-0">
                                    Simpan
                                </button>
                                <button @click="editPertanyaanId = null"
                                        class="bg-white border border-[#D8DDE2] text-[#5a6a75] text-[11px] font-semibold px-2 py-1.5 rounded-lg hover:bg-[#F8FAFB] transition-colors shrink-0">
                                    Batal
                                </button>
                            </div>
                        </div>
                        @empty
                        <p class="text-[12px] text-[#8a9ba8] mb-3">Belum ada Sub CPMK.</p>
                        @endforelse

                        <div class="mt-3 flex gap-2">
                            <input wire:model="pertanyaanBaru" type="text" placeholder="Tambah Sub CPMK..."
                                   class="flex-1 h-9 px-3 text-[12px] text-[#1a2a35] bg-white border border-[#E0E5EA] rounded-lg outline-none focus:border-primary focus:ring-2 focus:ring-primary/10 placeholder:text-[#b0bec5]"
                                   wire:keydown.enter="addPertanyaan({{ $mk->id }})" />
                            <button wire:click="addPertanyaan({{ $mk->id }})"
                                    class="bg-primary hover:bg-[#005f78] text-white text-[12px] font-semibold px-3 py-1.5 rounded-lg transition-colors shrink-0">
                                Tambah
                            </button>
                        </div>
                        @error('pertanyaanBaru') <p class="mt-1 text-[11px] text-[#c62828]">{{ $message }}</p> @enderror
                    </div>

                </div>

            </div>
            @endforeach
        @endif
    </div>

    {{-- Modal Tambah / Edit Mata Kuliah --}}
    <div x-show="showMkModal" style="display:none"
         class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 p-4"
         @keydown.escape.window="showMkModal = false"
         @click.self="showMkModal = false">
        <div class="bg-white rounded-2xl shadow-xl w-full max-w-md p-6">
            <h3 class="text-[15px] font-semibold text-[#1a2a35] mb-5"
                x-text="editMkId ? 'Edit Mata Kuliah' : 'Tambah Mata Kuliah'"></h3>
            <div class="space-y-4">
                <div class="flex gap-3">
                    <div class="w-28">
                        <label class="block text-[11px] font-semibold text-[#5a6a75] uppercase tracking-[0.7px] mb-1.5">Kode</label>
                        <input x-model="mkForm.kode" type="text" placeholder="TI201"
                               class="w-full h-[42px] px-3 text-[13px] text-[#1a2a35] bg-white border border-[#E0E5EA] rounded-xl outline-none focus:border-primary focus:ring-2 focus:ring-primary/10 placeholder:text-[#b0bec5]"
                               :class="mkErrors.kode ? 'border-[#c62828] focus:border-[#c62828] focus:ring-[#c62828]/10' : ''" />
                        <p x-show="mkErrors.kode" x-text="mkErrors.kode && mkErrors.kode[0]"
                           class="mt-1 text-[10px] text-[#c62828]"></p>
                    </div>
                    <div class="flex-1">
                        <label class="block text-[11px] font-semibold text-[#5a6a75] uppercase tracking-[0.7px] mb-1.5">Nama Mata Kuliah</label>
                        <input x-model="mkForm.nama" type="text" placeholder="Nama lengkap MK"
                               class="w-full h-[42px] px-3 text-[13px] text-[#1a2a35] bg-white border border-[#E0E5EA] rounded-xl outline-none focus:border-primary focus:ring-2 focus:ring-primary/10 placeholder:text-[#b0bec5]"
                               :class="mkErrors.nama ? 'border-[#c62828] focus:border-[#c62828] focus:ring-[#c62828]/10' : ''" />
                        <p x-show="mkErrors.nama" x-text="mkErrors.nama && mkErrors.nama[0]"
                           class="mt-1 text-[10px] text-[#c62828]"></p>
                    </div>
                </div>
                <div class="flex gap-3">
                    {{-- SKS Dropdown --}}
                    <div class="flex-1">
                        <label class="block text-[11px] font-semibold text-[#5a6a75] uppercase tracking-[0.7px] mb-1.5">SKS</label>
                        <div class="relative" @click.outside="sksOpen = false">
                            <button type="button" @click="sksOpen = !sksOpen; semesterOpen = false"
                                    class="w-full h-[42px] px-3 text-[13px] text-left bg-white border rounded-xl outline-none transition-all duration-150 flex items-center justify-between gap-2 cursor-pointer"
                                    :class="sksOpen ? 'border-[#004B5F] ring-2 ring-[#004B5F]/10' : 'border-[#E0E5EA] hover:border-[#C5CDD5]'">
                                <span x-text="mkForm.sks + ' SKS'" class="text-[#1a2a35] truncate"></span>
                                <svg class="w-4 h-4 text-[#8a9ba8] shrink-0 transition-transform duration-200" :class="sksOpen && 'rotate-180'"
                                     viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <polyline points="6 9 12 15 18 9"/>
                                </svg>
                            </button>
                            <div x-show="sksOpen" x-cloak
                                 x-transition:enter="transition ease-out duration-100"
                                 x-transition:enter-start="opacity-0 -translate-y-1"
                                 x-transition:enter-end="opacity-100 translate-y-0"
                                 x-transition:leave="transition ease-in duration-75"
                                 x-transition:leave-start="opacity-100 translate-y-0"
                                 x-transition:leave-end="opacity-0 -translate-y-1"
                                 class="absolute top-[calc(100%+4px)] left-0 w-full z-[200] bg-white border border-[#E0E5EA] rounded-xl shadow-lg overflow-hidden">
                                <div class="py-1 max-h-[220px] overflow-y-auto">
                                    @foreach (range(1, 20) as $n)
                                    <button type="button" @click="mkForm.sks = {{ $n }}; sksOpen = false"
                                            class="w-full text-left px-3 py-2 text-[13px] transition-colors"
                                            :class="mkForm.sks === {{ $n }} ? 'bg-[#E8F4F8] text-[#004B5F] font-semibold' : 'text-[#1a2a35] hover:bg-[#F4F6F8]'">
                                        {{ $n }} SKS
                                    </button>
                                    @endforeach
                                </div>
                            </div>
                        </div>
                        <p x-show="mkErrors.sks" x-text="mkErrors.sks && mkErrors.sks[0]"
                           class="mt-1 text-[10px] text-[#c62828]" style="display:none"></p>
                    </div>
                    {{-- Semester Dropdown --}}
                    <div class="flex-1">
                        <label class="block text-[11px] font-semibold text-[#5a6a75] uppercase tracking-[0.7px] mb-1.5">Semester</label>
                        <div class="relative" @click.outside="semesterOpen = false">
                            <button type="button" @click="semesterOpen = !semesterOpen; sksOpen = false"
                                    class="w-full h-[42px] px-3 text-[13px] text-left bg-white border rounded-xl outline-none transition-all duration-150 flex items-center justify-between gap-2 cursor-pointer"
                                    :class="semesterOpen ? 'border-[#004B5F] ring-2 ring-[#004B5F]/10' : 'border-[#E0E5EA] hover:border-[#C5CDD5]'">
                                <span x-text="'Semester ' + mkForm.semester" class="text-[#1a2a35] truncate"></span>
                                <svg class="w-4 h-4 text-[#8a9ba8] shrink-0 transition-transform duration-200" :class="semesterOpen && 'rotate-180'"
                                     viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <polyline points="6 9 12 15 18 9"/>
                                </svg>
                            </button>
                            <div x-show="semesterOpen" x-cloak
                                 x-transition:enter="transition ease-out duration-100"
                                 x-transition:enter-start="opacity-0 -translate-y-1"
                                 x-transition:enter-end="opacity-100 translate-y-0"
                                 x-transition:leave="transition ease-in duration-75"
                                 x-transition:leave-start="opacity-100 translate-y-0"
                                 x-transition:leave-end="opacity-0 -translate-y-1"
                                 class="absolute top-[calc(100%+4px)] left-0 w-full z-[200] bg-white border border-[#E0E5EA] rounded-xl shadow-lg overflow-hidden">
                                <div class="py-1 max-h-[220px] overflow-y-auto">
                                    @foreach (range(1, 8) as $n)
                                    <button type="button" @click="mkForm.semester = {{ $n }}; semesterOpen = false"
                                            class="w-full text-left px-3 py-2 text-[13px] transition-colors"
                                            :class="mkForm.semester === {{ $n }} ? 'bg-[#E8F4F8] text-[#004B5F] font-semibold' : 'text-[#1a2a35] hover:bg-[#F4F6F8]'">
                                        Semester {{ $n }}
                                    </button>
                                    @endforeach
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div>
                    <label class="block text-[11px] font-semibold text-[#5a6a75] uppercase tracking-[0.7px] mb-1.5">Deskripsi (opsional)</label>
                    <textarea x-model="mkForm.deskripsi" rows="2" placeholder="Deskripsi singkat mata kuliah..."
                              class="w-full px-3 py-2.5 text-[13px] text-[#1a2a35] bg-white border border-[#E0E5EA] rounded-xl outline-none focus:border-primary focus:ring-2 focus:ring-primary/10 placeholder:text-[#b0bec5] resize-none"></textarea>
                </div>
                <div class="flex items-center gap-2.5">
                    <input x-model="mkForm.bisaRpl" type="checkbox" id="bisaRplAdmin"
                           class="w-4 h-4 rounded border-[#D0D5DD] accent-primary cursor-pointer" />
                    <label for="bisaRplAdmin" class="text-[13px] text-[#5a6a75] cursor-pointer select-none">
                        Mata kuliah ini dapat direkognisi (bisa RPL)
                    </label>
                </div>
            </div>
            <div class="flex gap-3 mt-6">
                <button @click="showMkModal = false"
                        class="flex-1 h-[42px] bg-white border border-[#D8DDE2] text-[#1a2a35] text-[13px] font-semibold rounded-xl hover:bg-[#F4F6F8] transition-colors">
                    Batal
                </button>
                <button @click="$wire.saveMk(editMkId, mkForm.kode, mkForm.nama, Number(mkForm.sks), Number(mkForm.semester), mkForm.deskripsi, mkForm.bisaRpl)"
                        wire:loading.attr="disabled" wire:target="saveMk"
                        class="flex-1 h-[42px] bg-primary hover:bg-[#005f78] text-white text-[13px] font-semibold rounded-xl transition-colors disabled:opacity-60">
                    <span wire:loading.remove wire:target="saveMk"
                          x-text="editMkId ? 'Simpan Perubahan' : 'Tambahkan'"></span>
                    <span wire:loading wire:target="saveMk">Menyimpan...</span>
                </button>
            </div>
        </div>
    </div>

    {{-- Modal Import Excel --}}
    <div x-show="showImportModal" x-cloak x-transition.opacity
         class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 p-4"
         @click.self="showImportModal = false">
        <div class="bg-white rounded-2xl shadow-xl w-full max-w-md p-6 mx-4">
            <div class="text-[15px] font-semibold text-[#1a2a35] mb-1">Import Mata Kuliah dari Excel</div>
            <p class="text-[12px] text-[#8a9ba8] mb-5">Upload file Excel (.xlsx) sesuai template yang disediakan.</p>

            {{-- Download template --}}
            <button wire:click="downloadTemplate"
                    class="inline-flex items-center gap-1.5 text-[12px] font-semibold text-primary hover:text-[#005f78] mb-4 transition-colors">
                <svg class="w-3.5 h-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M21 15v4a2 2 0 01-2 2H5a2 2 0 01-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/>
                </svg>
                Download Template
            </button>

            {{-- File input --}}
            <template x-if="!importDone">
                <div>
                    <label class="flex items-center gap-3 border border-dashed border-[#D0D5DD] rounded-xl px-4 py-3.5 cursor-pointer hover:border-primary hover:bg-[#F8FAFB] transition-colors">
                        <svg class="w-5 h-5 text-[#8a9ba8] shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M21 15v4a2 2 0 01-2 2H5a2 2 0 01-2-2v-4"/><polyline points="17 8 12 3 7 8"/><line x1="12" y1="3" x2="12" y2="15"/>
                        </svg>
                        <span wire:loading.remove wire:target="fileImport" class="text-[12px] text-[#8a9ba8]">
                            <template x-if="$wire.fileImport">
                                <span class="text-[#1a2a35] font-medium" x-text="'File siap diimport'"></span>
                            </template>
                            <template x-if="!$wire.fileImport">
                                <span>Pilih file .xlsx atau .xls (maks. 5 MB)</span>
                            </template>
                        </span>
                        <span wire:loading wire:target="fileImport" class="text-[12px] text-[#8a9ba8]">Mengunggah...</span>
                        <input type="file" wire:model="fileImport" accept=".xlsx,.xls" class="hidden">
                    </label>
                    @error('fileImport') <p class="mt-1.5 text-[11px] text-[#c62828]">{{ $message }}</p> @enderror

                    <div class="flex gap-3 mt-5">
                        <button @click="showImportModal = false"
                                class="flex-1 h-[42px] bg-white border border-[#D8DDE2] text-[#1a2a35] text-[13px] font-semibold rounded-xl hover:bg-[#F4F6F8] transition-colors">
                            Batal
                        </button>
                        <button wire:click="importExcel"
                                wire:loading.attr="disabled" wire:target="importExcel"
                                class="flex-1 h-[42px] bg-primary hover:bg-[#005f78] text-white text-[13px] font-semibold rounded-xl transition-colors disabled:opacity-60">
                            <span wire:loading.remove wire:target="importExcel">Import</span>
                            <span wire:loading wire:target="importExcel">Mengimport...</span>
                        </button>
                    </div>
                </div>
            </template>

            {{-- Result --}}
            <template x-if="importDone">
                <div>
                    {{-- Summary --}}
                    <div x-show="importSummary" class="rounded-xl border border-[#d4edda] bg-[#f0faf3] px-4 py-3 mb-3">
                        <div class="text-[12px] text-[#1a5632] font-medium">
                            <span x-text="importSummary.created"></span> ditambahkan,
                            <span x-text="importSummary.updated"></span> diperbarui<template x-if="importSummary.failed > 0"><span class="text-[#c62828]">, <span x-text="importSummary.failed"></span> gagal</span></template>
                        </div>
                    </div>
                    {{-- Errors --}}
                    <template x-if="importErrors.length > 0">
                        <div class="rounded-xl border border-[#f5c6cb] bg-[#fdf0f1] px-4 py-3 mb-3 max-h-40 overflow-y-auto">
                            <div class="text-[11px] font-semibold text-[#c62828] mb-1.5">Detail error:</div>
                            <template x-for="err in importErrors" :key="err.row">
                                <div class="text-[11px] text-[#721c24] mb-0.5">
                                    Baris <span x-text="err.row"></span>:
                                    <span x-text="err.messages.join(', ')"></span>
                                </div>
                            </template>
                        </div>
                    </template>
                    <button @click="showImportModal = false"
                            class="w-full h-[42px] bg-primary hover:bg-[#005f78] text-white text-[13px] font-semibold rounded-xl transition-colors">
                        Tutup
                    </button>
                </div>
            </template>
        </div>
    </div>

    {{-- Modal Konfirmasi Hapus --}}
    <div x-show="confirmModal.show" style="display:none"
         class="fixed inset-0 z-50 flex items-center justify-center bg-black/40"
         @click.self="confirmModal.show = false">
        <div class="bg-white rounded-2xl shadow-xl w-full max-w-sm p-6 mx-4">
            <div class="w-10 h-10 rounded-full bg-[#FCE8E6] flex items-center justify-center mb-4 mx-auto">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#c62828" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <polyline points="3 6 5 6 21 6"/><path d="M19 6l-1 14a2 2 0 01-2 2H8a2 2 0 01-2-2L5 6"/><path d="M10 11v6"/><path d="M14 11v6"/>
                </svg>
            </div>
            <p x-text="confirmModal.message" class="text-[14px] text-[#1a2a35] text-center mb-6"></p>
            <div class="flex gap-3">
                <button @click="confirmModal.show = false"
                        class="flex-1 h-[42px] bg-white border border-[#D8DDE2] text-[#1a2a35] text-[13px] font-semibold rounded-xl hover:bg-[#F4F6F8] transition-colors">
                    Batal
                </button>
                <button @click="$wire[confirmModal.action](confirmModal.id); confirmModal.show = false"
                        class="flex-1 h-[42px] bg-[#c62828] hover:bg-[#a02020] text-white text-[13px] font-semibold rounded-xl transition-colors">
                    Hapus
                </button>
            </div>
        </div>
    </div>

</div>
