<?php

use Livewire\Attributes\Layout;
use Livewire\Volt\Component;
use App\Actions\Admin\ProsesPermohonanAction;
use App\Actions\Admin\UploadDokumenPesertaAction;
use App\Actions\Asesor\SimpanJadwalVerifikasiAction;
use App\Enums\JenisDokumenEnum;
use App\Enums\StatusPermohonanEnum;
use App\Enums\StatusRplMataKuliahEnum;
use App\Enums\StatusVerifikasiEnum;
use App\Models\Asesor;
use App\Models\DokumenBukti;
use App\Models\MataKuliah;
use App\Models\PermohonanRpl;
use App\Models\ProgramStudi;
use App\Models\RplMataKuliah;
use Livewire\WithFileUploads;

new #[Layout('components.layouts.admin')] class extends Component {
    use WithFileUploads;

    public PermohonanRpl $permohonan;

    // ── Upload Berkas Admin ───────────────────────────────────────────────────
    public $adminBerkas       = null;
    public string $adminNama  = '';
    public string $adminJenis = '';
    public string $adminKet   = '';

    public function uploadBerkasAdmin(UploadDokumenPesertaAction $action): void
    {
        $this->validate([
            'adminBerkas' => 'required|file|mimes:pdf,jpg,jpeg,png|max:10240',
            'adminNama'   => 'required|string|max:255',
            'adminJenis'  => 'required|string',
        ], [
            'adminBerkas.required' => 'Pilih file terlebih dahulu.',
            'adminBerkas.mimes'    => 'Format harus PDF, JPG, atau PNG.',
            'adminBerkas.max'      => 'Ukuran file maksimal 10 MB.',
            'adminNama.required'   => 'Nama berkas wajib diisi.',
            'adminJenis.required'  => 'Jenis berkas wajib dipilih.',
        ]);

        $action->execute(
            $this->permohonan->peserta,
            $this->adminBerkas,
            $this->adminNama,
            JenisDokumenEnum::from($this->adminJenis),
            $this->adminKet ?: null,
            auth()->id(),
        );

        $this->reset('adminBerkas', 'adminNama', 'adminJenis', 'adminKet');
        $this->reload();
    }

    public function hapusBerkasAdmin(int $dokumenId): void
    {
        $dokumen = DokumenBukti::findOrFail($dokumenId);
        abort_if($dokumen->peserta_id !== $this->permohonan->peserta_id, 403);

        \Storage::disk('local')->delete($dokumen->berkas);
        $dokumen->delete();
        $this->reload();
    }

    public function mount(PermohonanRpl $permohonan): void
    {
        $this->permohonan = $permohonan->load([
            'peserta.user',
            'peserta.dokumenBukti',
            'programStudi',
            'rplMataKuliah.mataKuliah',
            'verifikasiBersama',
        ]);
    }

    // ── Verifikasi & Proses ───────────────────────────────────────────────────
    // Hanya bisa ketika status = diajukan
    // Mengubah prodi (jika diubah admin), lalu auto-create semua MK prodi, status → diproses

    public function prosesPermohonan(int $prodiId, ProsesPermohonanAction $action): void
    {
        $action->execute($this->permohonan, $prodiId);
        $this->reload();
        $this->dispatch('permohonan-updated');
    }

    // ── Tolak ────────────────────────────────────────────────────────────────

    public function tolakPermohonan(string $catatan = ''): void
    {
        abort_if(in_array($this->permohonan->status, [
            StatusPermohonanEnum::Disetujui,
            StatusPermohonanEnum::Ditolak,
        ]), 403);

        $this->permohonan->update([
            'status'        => StatusPermohonanEnum::Ditolak,
            'catatan_admin' => $catatan ?: null,
        ]);

        $this->reload();
        $this->dispatch('permohonan-updated');
    }

    // ── Kelola MK (setelah diproses) ─────────────────────────────────────────

    public function hapusMk(int $rplMkId): void
    {
        $this->assertCanEditMk();

        RplMataKuliah::findOrFail($rplMkId)->delete();
        $this->reload();
    }

    public function tambahMk(int $mkId): void
    {
        $this->assertCanEditMk();

        RplMataKuliah::firstOrCreate(
            ['permohonan_rpl_id' => $this->permohonan->id, 'mata_kuliah_id' => $mkId],
            ['status' => StatusRplMataKuliahEnum::Menunggu]
        );

        $this->reload();
        $this->dispatch('mk-ditambah');
    }

    // ── Hapus Pengajuan ───────────────────────────────────────────────────────

    public function hapusPermohonan(): void
    {
        $this->permohonan->delete();
        $this->redirect(route('admin.pengajuan.index'), navigate: true);
    }

    // ── Helpers ──────────────────────────────────────────────────────────────

    private function assertCanEditMk(): void
    {
        abort_if(! in_array($this->permohonan->status, [
            StatusPermohonanEnum::Diproses,
            StatusPermohonanEnum::Verifikasi,
        ]), 403);
    }

    private function reload(): void
    {
        $this->permohonan = $this->permohonan->fresh([
            'peserta.user',
            'peserta.dokumenBukti',
            'programStudi',
            'rplMataKuliah.mataKuliah',
            'verifikasiBersama',
        ]);
    }

    // ── Jadwal Verifikasi Bersama ──────────────────────────────────────────

    public function simpanJadwal(string $jadwal, ?string $catatan, array $asesorIds, SimpanJadwalVerifikasiAction $action): void
    {
        $errors = validator(['jadwal' => $jadwal], ['jadwal' => 'required|date'])->errors()->toArray();
        if ($errors) {
            $this->dispatch('jadwal-errors', errors: $errors);
            return;
        }

        $action->execute($this->permohonan, $jadwal, $catatan, asesorIds: array_map('intval', $asesorIds));

        $this->permohonan->load('verifikasiBersama', 'asesor.user');
        $this->dispatch('jadwal-saved');
    }

    public function with(): array
    {
        $prodiId = $this->permohonan->program_studi_id;

        $assignedMkIds = $this->permohonan->rplMataKuliah->pluck('mata_kuliah_id')->toArray();
        $mkTersedia = MataKuliah::where('program_studi_id', $prodiId)
            ->where('bisa_rpl', true)
            ->whereNotIn('id', $assignedMkIds)
            ->orderBy('semester')->orderBy('nama')
            ->get();

        $prodiOptions = ProgramStudi::where('aktif', true)
            ->orderBy('nama')
            ->get()
            ->mapWithKeys(fn($p) => [$p->id => $p->nama . ' (' . $p->kode . ')'])
            ->toArray();

        $status      = $this->permohonan->status;
        $isDiajukan  = $status === StatusPermohonanEnum::Diajukan;
        $isDisproses = $status === StatusPermohonanEnum::Diproses;
        $isVerifikasi = $status === StatusPermohonanEnum::Verifikasi;
        $canEditMk   = $isDisproses || $isVerifikasi;
        $isSelesai   = in_array($status, [StatusPermohonanEnum::Disetujui, StatusPermohonanEnum::Ditolak]);

        $latestVb    = $this->permohonan->verifikasiBersama->sortByDesc('id')->first();

        $asesorOptions = Asesor::with('user')
            ->whereHas('user', fn($q) => $q->where('aktif', true))
            ->get()
            ->mapWithKeys(fn($a) => [$a->id => $a->user->nama . ($a->bidang_keahlian ? ' — ' . $a->bidang_keahlian : '')])
            ->toArray();

        $this->permohonan->loadMissing('asesor.user');

        return compact('mkTersedia', 'prodiOptions', 'status', 'isDiajukan', 'isDisproses', 'isVerifikasi', 'canEditMk', 'isSelesai', 'latestVb', 'asesorOptions');
    }
}; ?>

<x-slot:title>Detail Pengajuan</x-slot:title>
<x-slot:subtitle>
    <a href="{{ route('admin.pengajuan.index') }}" class="text-primary hover:underline">Semua Pengajuan</a>
    &rsaquo; {{ $permohonan->nomor_permohonan }}
</x-slot:subtitle>

<div
    x-data="{
        prodiId: {{ $permohonan->program_studi_id }},
        tolakOpen: false,
        catatanTolak: '',
        tambahMkOpen: false,
        hapusOpen: false,
    }"
    @permohonan-updated.window="tolakOpen = false"
    @mk-ditambah.window="tambahMkOpen = false"
>

    @include('livewire.admin.pengajuan.partials.info-peserta-aksi')

    {{-- ===== BERKAS PENDUKUNG (admin dapat upload/hapus) ===== --}}
    <div class="bg-white rounded-xl border border-[#E5E8EC] overflow-hidden mb-5"
         x-data="{ showUpload: false, showViewer: false, viewUrl: '', viewType: '', viewName: '' }">
        <div class="flex items-center justify-between px-5 py-3.5 border-b border-[#F0F2F5]">
            <span class="text-[13px] font-semibold text-[#1a2a35]">Berkas Pendukung Peserta</span>
            <div class="flex items-center gap-3">
                <span class="text-[11px] text-[#8a9ba8]">{{ $permohonan->peserta->dokumenBukti->count() }} berkas</span>
                <button @click="showUpload = !showUpload"
                        class="flex items-center gap-1.5 text-[11px] font-semibold text-primary hover:text-[#005f78] transition-colors">
                    <svg class="w-3.5 h-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                        <line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/>
                    </svg>
                    Upload Berkas
                </button>
            </div>
        </div>

        {{-- Form Upload Admin --}}
        <div x-show="showUpload" x-cloak x-transition
             class="px-5 py-4 border-b border-[#F0F2F5] bg-[#FAFBFC]">
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3">
                <div>
                    <label class="block text-[10px] font-semibold text-[#5a6a75] uppercase tracking-[0.7px] mb-1">Nama Berkas</label>
                    <input wire:model="adminNama" type="text" placeholder="Nama berkas..."
                           class="w-full h-[38px] px-3 text-[12px] text-[#1a2a35] bg-white border border-[#E0E5EA] rounded-lg outline-none focus:border-primary focus:ring-2 focus:ring-primary/10 placeholder:text-[#b0bec5]" />
                    @error('adminNama') <p class="mt-0.5 text-[10px] text-[#c62828]">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="block text-[10px] font-semibold text-[#5a6a75] uppercase tracking-[0.7px] mb-1">Jenis</label>
                    <x-form.select wire:model="adminJenis"
                                   placeholder="— Pilih jenis —"
                                   :options="\App\Enums\JenisDokumenEnum::options()" />
                    @error('adminJenis') <p class="mt-0.5 text-[10px] text-[#c62828]">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="block text-[10px] font-semibold text-[#5a6a75] uppercase tracking-[0.7px] mb-1">File (PDF/Gambar)</label>
                    <input wire:model="adminBerkas" type="file" accept=".pdf,.jpg,.jpeg,.png"
                           class="w-full text-[11px] text-[#5a6a75] file:mr-2 file:py-1 file:px-2 file:rounded file:border-0 file:text-[11px] file:bg-[#E8F4F8] file:text-primary file:font-semibold" />
                    @error('adminBerkas') <p class="mt-0.5 text-[10px] text-[#c62828]">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="block text-[10px] font-semibold text-[#5a6a75] uppercase tracking-[0.7px] mb-1">Keterangan <span class="normal-case font-normal">(opsional)</span></label>
                    <div class="flex gap-2">
                        <input wire:model="adminKet" type="text" placeholder="Keterangan..."
                               class="flex-1 h-[38px] px-3 text-[12px] text-[#1a2a35] bg-white border border-[#E0E5EA] rounded-lg outline-none focus:border-primary placeholder:text-[#b0bec5]" />
                        <button wire:click="uploadBerkasAdmin"
                                wire:loading.attr="disabled"
                                wire:target="uploadBerkasAdmin"
                                class="h-[38px] px-3 bg-primary hover:bg-[#005f78] text-white text-[12px] font-semibold rounded-lg transition-colors shrink-0 disabled:opacity-60">
                            Upload
                        </button>
                    </div>
                </div>
            </div>
        </div>

        {{-- Daftar Berkas --}}
        @if ($permohonan->peserta->dokumenBukti->isEmpty())
        <div class="py-6 text-center text-[12px] text-[#8a9ba8]">Peserta belum mengunggah berkas pendukung.</div>
        @else
        <div class="divide-y divide-[#F6F8FA]">
            @foreach ($permohonan->peserta->dokumenBukti as $berkas)
            @php $vt = in_array(strtolower(pathinfo($berkas->berkas, PATHINFO_EXTENSION)), ['jpg','jpeg','png']) ? 'image' : 'pdf'; @endphp
            <div class="flex items-center gap-3.5 px-5 py-3" wire:key="berkas-admin-{{ $berkas->id }}">
                <div class="w-8 h-8 rounded-lg bg-[#E8F4F8] flex items-center justify-center shrink-0">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="#004B5F" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V8z"/>
                        <polyline points="14 2 14 8 20 8"/>
                    </svg>
                </div>
                <div class="flex-1 min-w-0">
                    <div class="text-[12px] font-medium text-[#1a2a35] truncate">{{ $berkas->nama_dokumen }}</div>
                    <div class="flex items-center gap-2">
                        <span class="text-[11px] text-[#8a9ba8]">{{ $berkas->jenis_dokumen->label() }}</span>
                        @if ($berkas->uploaded_by_user_id)
                        <span class="text-[10px] text-[#b45309] bg-[#FFF3E0] px-1.5 py-0.5 rounded">Admin</span>
                        @endif
                    </div>
                </div>
                <button @click="viewUrl = '{{ route('berkas.view', $berkas->id) }}'; viewType = '{{ $vt }}'; viewName = '{{ addslashes($berkas->nama_dokumen) }}'; showViewer = true"
                   class="w-[28px] h-[28px] rounded-md border border-[#D0D5DD] text-[#5a6a75] hover:border-primary hover:text-primary hover:bg-[#E8F4F8] transition-colors flex items-center justify-center">
                    <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
                </button>
                <a href="{{ route('berkas.download', $berkas->id) }}"
                   class="w-[28px] h-[28px] rounded-md border border-[#D0D5DD] text-[#5a6a75] hover:border-primary hover:text-primary hover:bg-[#E8F4F8] transition-colors flex items-center justify-center no-underline">
                    <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15v4a2 2 0 01-2 2H5a2 2 0 01-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
                </a>
                <button wire:click="hapusBerkasAdmin({{ $berkas->id }})"
                        wire:confirm="Hapus berkas ini?"
                        class="text-[#c62828] hover:text-[#a02020] transition-colors p-1 shrink-0">
                    <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <polyline points="3 6 5 6 21 6"/><path d="M19 6l-1 14a2 2 0 01-2 2H8a2 2 0 01-2-2L5 6"/>
                        <path d="M10 11v6"/><path d="M14 11v6"/><path d="M9 6V4a1 1 0 011-1h4a1 1 0 011 1v2"/>
                    </svg>
                </button>
            </div>
            @endforeach
        </div>
        {{-- Viewer modal --}}
        <div x-show="showViewer" x-cloak x-transition.opacity
             class="fixed inset-0 z-[60] flex items-center justify-center bg-black/60"
             @click.self="showViewer = false">
            <div class="bg-white rounded-2xl shadow-2xl w-full max-w-3xl mx-4 flex flex-col overflow-hidden" style="max-height: 90vh">
                <div class="flex items-center justify-between px-5 py-3.5 border-b border-[#F0F2F5] shrink-0">
                    <span x-text="viewName" class="text-[13px] font-semibold text-[#1a2a35] truncate max-w-[60%]"></span>
                    <button @click="showViewer = false" class="text-[#8a9ba8] hover:text-[#1a2a35] p-1">
                        <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/>
                        </svg>
                    </button>
                </div>
                <div class="flex-1 min-h-0 bg-[#F0F2F5]" style="height: 75vh">
                    <template x-if="viewType === 'pdf'"><iframe :src="viewUrl" class="w-full border-0" style="height: 75vh"></iframe></template>
                    <template x-if="viewType === 'image'"><div class="flex items-center justify-center p-6 h-full"><img :src="viewUrl" class="max-w-full max-h-full object-contain rounded-lg shadow" /></div></template>
                </div>
            </div>
        </div>
        @endif
    </div>

    {{-- ===== JADWAL VERIFIKASI BERSAMA ===== --}}
    @if (! $isDiajukan)
    @include('livewire.admin.pengajuan.partials.jadwal-verifikasi')
    @endif

    {{-- ===== DAFTAR MK (hanya setelah diproses) ===== --}}
    @if (! $isDiajukan)
    @include('livewire.admin.pengajuan.partials.daftar-mk')
    @endif

    {{-- SKS Rekognisi Card (disetujui) --}}
    @if ($status === StatusPermohonanEnum::Disetujui)
    <x-pengajuan.sks-rekognisi :permohonan="$permohonan" />
    @endif

    {{-- Back + Hapus --}}
    <div class="flex items-center justify-between">
        <a href="{{ route('admin.pengajuan.index') }}"
           class="text-[13px] text-[#5a6a75] hover:text-primary transition-colors no-underline">
            ← Kembali ke Daftar Pengajuan
        </a>
        <button @click="hapusOpen = true"
                class="flex items-center gap-1.5 text-[12px] font-semibold text-[#c62828] hover:text-[#a01e1e] transition-colors">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <polyline points="3 6 5 6 21 6"/><path d="M19 6l-1 14a2 2 0 01-2 2H8a2 2 0 01-2-2L5 6"/>
                <path d="M10 11v6"/><path d="M14 11v6"/><path d="M9 6V4a1 1 0 011-1h4a1 1 0 011 1v2"/>
            </svg>
            Hapus Pengajuan
        </button>
    </div>

    @include('livewire.admin.pengajuan.partials.modal-tolak')
    @include('livewire.admin.pengajuan.partials.modal-tambah-mk')
    @include('livewire.admin.pengajuan.partials.modal-hapus')

</div>
