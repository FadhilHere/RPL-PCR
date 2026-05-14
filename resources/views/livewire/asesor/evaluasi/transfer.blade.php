<?php

use Livewire\Attributes\Layout;
use Livewire\Volt\Component;
use Livewire\WithFileUploads;
use App\Actions\Asesor\FinalisasiPermohonanAction;
use App\Actions\Asesor\ManageMatkulLampauAction;
use App\Actions\Asesor\SanitizeCatatanAsesorAction;
use App\Actions\Asesor\SelesaikanVerifikasiAction;
use App\Enums\JenisRplEnum;
use App\Enums\NilaiHurufEnum;
use App\Enums\NilaiTranskripEnum;
use App\Enums\StatusPermohonanEnum;
use App\Enums\StatusRplMataKuliahEnum;
use App\Models\MatkulLampau;
use App\Models\PermohonanRpl;
use App\Models\RplMataKuliah;

new #[Layout('components.layouts.asesor')] class extends Component {
    use WithFileUploads;

    public PermohonanRpl $permohonan;
    public $berkasBA = null;

    // nilaiTransfer[rpl_mk_id] = 'A'|'AB'|'B'|'BC'|'C'|'D'|'E'
    public array $nilaiTransfer = [];
    // catatanLampau[matkul_lampau_id] = string
    public array $catatanLampau = [];
    // editForm[matkul_lampau_id] = [kode_mk_asesor, nama_mk_asesor, sks_asesor, nilai_huruf_asesor]
    public array $editForm = [];
    // form tambah MK Lampau manual (shared, satu sekaligus)
    public array $tambahForm = ['kode_mk_asesor' => '', 'nama_mk_asesor' => '', 'sks_asesor' => '', 'nilai_huruf_asesor' => ''];

    public function mount(PermohonanRpl $permohonan): void
    {
        $asesorId   = auth()->user()->asesor?->id;
        $isAssigned = $asesorId && $permohonan->asesor()->where('asesor_id', $asesorId)->exists();

        if (! $isAssigned) {
            abort(403, 'Anda tidak ditugaskan ke pengajuan ini.');
        }

        abort_if($permohonan->jenis_rpl !== JenisRplEnum::RplI, 403, 'Halaman ini hanya untuk Transfer Kredit.');

        $this->permohonan = $permohonan->load([
            'peserta.user',
            'peserta.dokumenBukti',
            'peserta.riwayatPendidikan',
            'peserta.pelatihanProfesional',
            'peserta.konferensiSeminar',
            'peserta.penghargaan',
            'peserta.organisasiProfesi',
            'programStudi',
            'rplMataKuliah.mataKuliah',
            'rplMataKuliah.asesmenMandiri.pertanyaan',
            'rplMataKuliah.matkulLampau',
            'verifikasiBersama',
        ]);

        foreach ($this->permohonan->rplMataKuliah as $rplMk) {
            $this->nilaiTransfer[$rplMk->id] = $rplMk->nilai_transfer ?? '';

            foreach ($rplMk->matkulLampau as $ml) {
                $this->catatanLampau[$ml->id] = $ml->catatan_asesor ?? '';
                $this->editForm[$ml->id] = [
                    'kode_mk_asesor'     => $ml->kode_mk_final ?? '',
                    'nama_mk_asesor'     => $ml->nama_mk_final ?? '',
                    'sks_asesor'         => $ml->sks_final ?? '',
                    'nilai_huruf_asesor' => $ml->nilai_huruf_final?->value ?? '',
                ];
            }
        }
    }

    public function simpanNilai(SanitizeCatatanAsesorAction $sanitizer, int $rplMkId): void
    {
        $nilai = $this->nilaiTransfer[$rplMkId] ?? '';

        $this->validate([
            "nilaiTransfer.{$rplMkId}" => 'required|in:' . implode(',', array_column(NilaiHurufEnum::cases(), 'value')),
        ], [], ["nilaiTransfer.{$rplMkId}" => 'nilai huruf']);

        $nilaiEnum = NilaiHurufEnum::from($nilai);

        $rplMk = RplMataKuliah::query()
            ->with(['mataKuliah', 'matkulLampau'])
            ->where('permohonan_rpl_id', $this->permohonan->id)
            ->findOrFail($rplMkId);

        $rplMk->update([
            'nilai_transfer'  => $nilaiEnum->value,
            'catatan_asesor'  => null,
            'status'          => $nilaiEnum->diakui()
                ? StatusRplMataKuliahEnum::Diakui
                : StatusRplMataKuliahEnum::TidakDiakui,
            'sks_diakui'      => $nilaiEnum->diakui() ? ($rplMk->mataKuliah->sks ?? 0) : 0,
        ]);

        foreach ($rplMk->matkulLampau as $ml) {
            if (isset($this->catatanLampau[$ml->id])) {
                $ml->update([
                    'catatan_asesor' => $sanitizer->execute($this->catatanLampau[$ml->id]),
                ]);
            }
        }

        $this->permohonan->refresh();
        $this->dispatch('notify-saved');
    }

    public function finalisasi(FinalisasiPermohonanAction $action): void
    {
        try {
            $action->execute($this->permohonan);
        } catch (\DomainException $e) {
            $this->dispatch('notify-error', message: $e->getMessage());
            return;
        }

        $this->redirect(route('asesor.pengajuan.index'), navigate: true);
    }

    public function selesaikanVerifikasi(SelesaikanVerifikasiAction $action, string $catatanHasil = ''): void
    {
        if ($this->berkasBA) {
            $this->validate(['berkasBA' => 'file|mimes:pdf,jpg,jpeg,png|max:10240']);
        }

        $action->execute($this->permohonan, $this->berkasBA, $catatanHasil);

        $this->permohonan->load('verifikasiBersama');
        $this->permohonan->refresh();
        $this->berkasBA = null;
        $this->dispatch('notify-saved');
    }

    public function simpanEditMatkulLampau(int $mlId, ManageMatkulLampauAction $action): void
    {
        $this->validate([
            "editForm.{$mlId}.kode_mk_asesor"     => 'required|string|max:20',
            "editForm.{$mlId}.nama_mk_asesor"      => 'required|string|max:255',
            "editForm.{$mlId}.sks_asesor"          => 'required|integer|min:1|max:20',
            "editForm.{$mlId}.nilai_huruf_asesor"  => 'nullable|string|in:' . implode(',', array_column(NilaiTranskripEnum::cases(), 'value')),
        ]);

        $ml = $this->findMl($mlId);
        $action->updateAsesor($ml, $this->editForm[$mlId]);

        $this->permohonan->load(['rplMataKuliah.matkulLampau']);
        $this->dispatch('ml-saved', mlId: $mlId);
        $this->dispatch('notify-saved');
    }

    public function tambahMatkulLampau(int $rplMkId, ManageMatkulLampauAction $action): void
    {
        $this->validate([
            'tambahForm.kode_mk_asesor'     => 'required|string|max:20',
            'tambahForm.nama_mk_asesor'     => 'required|string|max:255',
            'tambahForm.sks_asesor'         => 'required|integer|min:1|max:20',
            'tambahForm.nilai_huruf_asesor' => 'nullable|string|in:' . implode(',', array_column(NilaiTranskripEnum::cases(), 'value')),
        ]);

        $rplMk = RplMataKuliah::query()
            ->whereIn('id', $this->permohonan->rplMataKuliah->pluck('id'))
            ->findOrFail($rplMkId);

        $ml = $action->create($rplMk, $this->tambahForm);

        $this->catatanLampau[$ml->id] = '';
        $this->editForm[$ml->id] = [
            'kode_mk_asesor'     => $this->tambahForm['kode_mk_asesor'],
            'nama_mk_asesor'     => $this->tambahForm['nama_mk_asesor'],
            'sks_asesor'         => $this->tambahForm['sks_asesor'],
            'nilai_huruf_asesor' => $this->tambahForm['nilai_huruf_asesor'],
        ];
        $this->tambahForm = ['kode_mk_asesor' => '', 'nama_mk_asesor' => '', 'sks_asesor' => '', 'nilai_huruf_asesor' => ''];
        $this->permohonan->load(['rplMataKuliah.matkulLampau']);
        $this->dispatch('notify-saved');
    }

    public function hapusMatkulLampau(int $mlId, ManageMatkulLampauAction $action): void
    {
        $ml = $this->findMl($mlId);
        $action->delete($ml);

        unset($this->catatanLampau[$mlId]);
        $this->permohonan->load(['rplMataKuliah.matkulLampau']);
        $this->dispatch('notify-saved');
    }

    private function findMl(int $mlId): MatkulLampau
    {
        $validIds = $this->permohonan->rplMataKuliah
            ->flatMap(fn($rplMk) => $rplMk->matkulLampau->pluck('id'))
            ->all();

        abort_if(! in_array($mlId, $validIds), 403);

        return MatkulLampau::findOrFail($mlId);
    }

    public function with(): array
    {
        return [
            'nilaiHurufOptions'    => NilaiHurufEnum::cases(),
            'nilaiTranskripOptions' => NilaiTranskripEnum::cases(),
        ];
    }
}; ?>

<x-slot:title>Evaluasi Transfer Kredit</x-slot:title>
<x-slot:subtitle>
    <a href="{{ route('asesor.pengajuan.index') }}" class="text-primary hover:underline">Pengajuan</a>
    &rsaquo; {{ $permohonan->nomor_permohonan }} &rsaquo; Evaluasi Transfer
</x-slot:subtitle>

<div x-data="{ saved: false, confirmModal: { show: false, id: 0 } }" @notify-saved.window="saved = true; setTimeout(() => saved = false, 3000)">

    {{-- Toast Notif --}}
    <div x-show="saved"
         x-cloak
         x-transition:enter="transition ease-out duration-200"
         x-transition:enter-start="opacity-0 translate-y-2"
         x-transition:enter-end="opacity-100 translate-y-0"
         x-transition:leave="transition ease-in duration-150"
         x-transition:leave-start="opacity-100 translate-y-0"
         x-transition:leave-end="opacity-0 translate-y-2"
         class="fixed bottom-6 right-6 z-[9999] flex items-center gap-2.5 bg-[#1a2a35] text-white text-[12px] font-medium px-4 py-3 rounded-xl shadow-lg">
        <svg class="w-4 h-4 text-[#4ade80] shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
            <polyline points="20 6 9 17 4 12"/>
        </svg>
        Status berhasil disimpan
    </div>

    {{-- Info Peserta --}}
    <div x-data="{ profilOpen: false }" class="mb-5">
        <div class="bg-white rounded-[10px] border border-[#E5E8EC] px-5 py-4 flex items-center gap-5">
            <div class="flex-1">
                <div class="text-[12px] text-[#8a9ba8] mb-0.5">Peserta</div>
                <div class="text-[14px] font-semibold text-[#1a2a35]">{{ $permohonan->peserta->user->nama ?? '—' }}</div>
            </div>
            <div class="flex-1">
                <div class="text-[12px] text-[#8a9ba8] mb-0.5">Program Studi</div>
                <div class="text-[13px] font-medium text-[#1a2a35]">{{ $permohonan->programStudi->nama ?? '—' }}</div>
            </div>
            <div class="flex-1">
                <div class="text-[12px] text-[#8a9ba8] mb-0.5">No. Permohonan</div>
                <div class="text-[13px] font-medium text-[#1a2a35]">{{ $permohonan->nomor_permohonan }}</div>
            </div>
            <div class="flex items-center gap-2">
                <span class="text-[11px] font-semibold px-2.5 py-1 rounded-full {{ $permohonan->status->badgeClass() }}">{{ $permohonan->status->label() }}</span>
                <a href="{{ route('export.hasil.word', $permohonan) }}"
                   class="flex items-center gap-1.5 h-[34px] px-3.5 text-[12px] font-semibold text-primary border border-[#BDE0EB] rounded-lg hover:bg-[#E8F4F8] transition-colors no-underline">
                    <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M21 15v4a2 2 0 01-2 2H5a2 2 0 01-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/>
                    </svg>
                    Download Hasil (Word)
                </a>
                <button @click="profilOpen = true"
                        class="flex items-center gap-1.5 h-[34px] px-3.5 border border-[#D0D5DD] text-[#5a6a75] hover:border-primary hover:text-primary hover:bg-[#E8F4F8] rounded-lg text-[12px] font-medium transition-colors">
                    <svg class="w-3.5 h-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M20 21v-2a4 4 0 00-4-4H8a4 4 0 00-4 4v2"/><circle cx="12" cy="7" r="4"/>
                    </svg>
                    Profil Peserta
                </button>
            </div>
        </div>

        {{-- Modal Profil Peserta (read-only) --}}
        <div x-show="profilOpen" x-cloak
             class="fixed inset-0 z-50 flex items-start justify-center bg-black/50 p-4 overflow-y-auto"
             x-transition:enter="transition ease-out duration-150" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
             x-transition:leave="transition ease-in duration-100" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0">
            <div @click.outside="profilOpen = false" @keydown.escape.window="profilOpen = false"
                 class="bg-white rounded-2xl shadow-xl w-full max-w-3xl my-6"
                 x-transition:enter="transition ease-out duration-150" x-transition:enter-start="opacity-0 scale-95" x-transition:enter-end="opacity-100 scale-100"
                 x-transition:leave="transition ease-in duration-100" x-transition:leave-start="opacity-100 scale-100" x-transition:leave-end="opacity-0 scale-95">

                <div class="flex items-center justify-between px-6 py-4 border-b border-[#F0F2F5]">
                    <div>
                        <div class="text-[15px] font-semibold text-[#1a2a35]">Profil Peserta</div>
                        <div class="text-[12px] text-[#8a9ba8]">{{ $permohonan->peserta->user->nama ?? '' }}</div>
                    </div>
                    <button @click="profilOpen = false" class="w-8 h-8 flex items-center justify-center rounded-lg hover:bg-[#F4F6F8] text-[#8a9ba8] hover:text-[#1a2a35] transition-colors">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
                    </button>
                </div>

                <div class="p-6 space-y-6" x-data="{ tab: 'biodata' }">
                    <div class="flex overflow-x-auto border-b border-[#F0F2F5] -mt-2">
                        @foreach ([
                            'biodata'     => 'Biodata',
                            'pendidikan'  => 'Pendidikan',
                            'pelatihan'   => 'Pelatihan',
                            'konferensi'  => 'Konferensi',
                            'penghargaan' => 'Penghargaan',
                            'organisasi'  => 'Organisasi',
                        ] as $key => $label)
                        <button @click="tab = '{{ $key }}'"
                            :class="tab === '{{ $key }}' ? 'border-b-2 border-primary text-primary font-semibold' : 'text-[#8a9ba8] hover:text-[#1a2a35]'"
                            class="px-4 py-2.5 text-[12px] whitespace-nowrap transition-colors shrink-0">{{ $label }}</button>
                        @endforeach
                    </div>

                    <div x-show="tab === 'biodata'" x-cloak>
                        @php $p = $permohonan->peserta; @endphp
                        <div class="grid grid-cols-2 gap-x-8 gap-y-3 text-[12px]">
                            @foreach ([
                                'Nama'             => $p->user->nama ?? '—',
                                'Email'            => $p->user->email ?? '—',
                                'NIP / NIK'        => $p->nik ?? '—',
                                'No. HP / WA'      => $p->telepon ?? '—',
                                'Telepon / Faks'   => $p->telepon_faks ?? '—',
                                'Jenis Kelamin'    => $p->jenis_kelamin === 'L' ? 'Laki-laki' : ($p->jenis_kelamin === 'P' ? 'Perempuan' : '—'),
                                'Tempat Lahir'     => $p->tempat_lahir ?? '—',
                                'Tanggal Lahir'    => $p->tanggal_lahir?->format('d M Y') ?? '—',
                                'Agama'            => $p->agama ?? '—',
                                'Golongan/Pangkat' => $p->golongan_pangkat ?? '—',
                                'Instansi'         => $p->instansi ?? '—',
                                'Pekerjaan'        => $p->pekerjaan ?? '—',
                            ] as $lbl => $val)
                            <div class="flex flex-col gap-0.5">
                                <div class="text-[10px] font-semibold text-[#8a9ba8] uppercase tracking-[0.5px]">{{ $lbl }}</div>
                                <div class="text-[#1a2a35]">{{ $val }}</div>
                            </div>
                            @endforeach
                            @if ($p->alamat)
                            <div class="col-span-2 flex flex-col gap-0.5">
                                <div class="text-[10px] font-semibold text-[#8a9ba8] uppercase tracking-[0.5px]">Alamat</div>
                                <div class="text-[#1a2a35]">{{ $p->alamat }}{{ $p->kota ? ', ' . $p->kota : '' }}{{ $p->provinsi ? ', ' . $p->provinsi : '' }}{{ $p->kode_pos ? ' ' . $p->kode_pos : '' }}</div>
                            </div>
                            @endif
                        </div>
                    </div>

                    <div x-show="tab === 'pendidikan'" x-cloak>
                        @if ($permohonan->peserta->riwayatPendidikan->isEmpty())
                        <div class="text-center text-[12px] text-[#8a9ba8] py-6">Belum ada data riwayat pendidikan.</div>
                        @else
                        <table class="w-full text-[12px]">
                            <thead><tr class="border-b border-[#F0F2F5] bg-[#FAFBFC]"><th class="text-left font-semibold text-[#8a9ba8] px-3 py-2">Nama Sekolah / Institusi</th><th class="text-left font-semibold text-[#8a9ba8] px-3 py-2">Jurusan</th><th class="text-left font-semibold text-[#8a9ba8] px-3 py-2">Tahun Lulus</th></tr></thead>
                            <tbody>
                                @foreach ($permohonan->peserta->riwayatPendidikan as $row)
                                <tr class="border-b border-[#F6F8FA] last:border-0"><td class="px-3 py-2.5 font-medium text-[#1a2a35]">{{ $row->nama_sekolah }}</td><td class="px-3 py-2.5 text-[#5a6a75]">{{ $row->jurusan ?? '—' }}</td><td class="px-3 py-2.5 text-[#5a6a75]">{{ $row->tahun_lulus ?? '—' }}</td></tr>
                                @endforeach
                            </tbody>
                        </table>
                        @endif
                    </div>

                    <div x-show="tab === 'pelatihan'" x-cloak>
                        @if ($permohonan->peserta->pelatihanProfesional->isEmpty())
                        <div class="text-center text-[12px] text-[#8a9ba8] py-6">Belum ada data pelatihan.</div>
                        @else
                        <table class="w-full text-[12px]">
                            <thead><tr class="border-b border-[#F0F2F5] bg-[#FAFBFC]"><th class="text-left font-semibold text-[#8a9ba8] px-3 py-2">Jenis Pelatihan</th><th class="text-left font-semibold text-[#8a9ba8] px-3 py-2">Penyelenggara</th><th class="text-left font-semibold text-[#8a9ba8] px-3 py-2">Jangka Waktu</th><th class="text-left font-semibold text-[#8a9ba8] px-3 py-2">Tahun</th></tr></thead>
                            <tbody>
                                @foreach ($permohonan->peserta->pelatihanProfesional as $row)
                                <tr class="border-b border-[#F6F8FA] last:border-0"><td class="px-3 py-2.5 font-medium text-[#1a2a35]">{{ $row->jenis_pelatihan }}</td><td class="px-3 py-2.5 text-[#5a6a75]">{{ $row->penyelenggara }}</td><td class="px-3 py-2.5 text-[#5a6a75]">{{ $row->jangka_waktu ?? '—' }}</td><td class="px-3 py-2.5 text-[#5a6a75]">{{ $row->tahun }}</td></tr>
                                @endforeach
                            </tbody>
                        </table>
                        @endif
                    </div>

                    <div x-show="tab === 'konferensi'" x-cloak>
                        @if ($permohonan->peserta->konferensiSeminar->isEmpty())
                        <div class="text-center text-[12px] text-[#8a9ba8] py-6">Belum ada data konferensi / seminar.</div>
                        @else
                        <table class="w-full text-[12px]">
                            <thead><tr class="border-b border-[#F0F2F5] bg-[#FAFBFC]"><th class="text-left font-semibold text-[#8a9ba8] px-3 py-2">Judul Kegiatan</th><th class="text-left font-semibold text-[#8a9ba8] px-3 py-2">Penyelenggara</th><th class="text-left font-semibold text-[#8a9ba8] px-3 py-2">Peran</th><th class="text-left font-semibold text-[#8a9ba8] px-3 py-2">Tahun</th></tr></thead>
                            <tbody>
                                @foreach ($permohonan->peserta->konferensiSeminar as $row)
                                <tr class="border-b border-[#F6F8FA] last:border-0"><td class="px-3 py-2.5 font-medium text-[#1a2a35]">{{ $row->judul_kegiatan }}</td><td class="px-3 py-2.5 text-[#5a6a75]">{{ $row->penyelenggara }}</td><td class="px-3 py-2.5 text-[#5a6a75]">{{ $row->peran ?? '—' }}</td><td class="px-3 py-2.5 text-[#5a6a75]">{{ $row->tahun }}</td></tr>
                                @endforeach
                            </tbody>
                        </table>
                        @endif
                    </div>

                    <div x-show="tab === 'penghargaan'" x-cloak>
                        @if ($permohonan->peserta->penghargaan->isEmpty())
                        <div class="text-center text-[12px] text-[#8a9ba8] py-6">Belum ada data penghargaan.</div>
                        @else
                        <table class="w-full text-[12px]">
                            <thead><tr class="border-b border-[#F0F2F5] bg-[#FAFBFC]"><th class="text-left font-semibold text-[#8a9ba8] px-3 py-2">Bentuk Penghargaan</th><th class="text-left font-semibold text-[#8a9ba8] px-3 py-2">Pemberi</th><th class="text-left font-semibold text-[#8a9ba8] px-3 py-2">Tahun</th></tr></thead>
                            <tbody>
                                @foreach ($permohonan->peserta->penghargaan as $row)
                                <tr class="border-b border-[#F6F8FA] last:border-0"><td class="px-3 py-2.5 font-medium text-[#1a2a35]">{{ $row->bentuk_penghargaan }}</td><td class="px-3 py-2.5 text-[#5a6a75]">{{ $row->pemberi }}</td><td class="px-3 py-2.5 text-[#5a6a75]">{{ $row->tahun }}</td></tr>
                                @endforeach
                            </tbody>
                        </table>
                        @endif
                    </div>

                    <div x-show="tab === 'organisasi'" x-cloak>
                        @if ($permohonan->peserta->organisasiProfesi->isEmpty())
                        <div class="text-center text-[12px] text-[#8a9ba8] py-6">Belum ada data organisasi profesi.</div>
                        @else
                        <table class="w-full text-[12px]">
                            <thead><tr class="border-b border-[#F0F2F5] bg-[#FAFBFC]"><th class="text-left font-semibold text-[#8a9ba8] px-3 py-2">Nama Organisasi</th><th class="text-left font-semibold text-[#8a9ba8] px-3 py-2">Jabatan</th><th class="text-left font-semibold text-[#8a9ba8] px-3 py-2">Tahun</th></tr></thead>
                            <tbody>
                                @foreach ($permohonan->peserta->organisasiProfesi as $row)
                                <tr class="border-b border-[#F6F8FA] last:border-0"><td class="px-3 py-2.5 font-medium text-[#1a2a35]">{{ $row->nama_organisasi }}</td><td class="px-3 py-2.5 text-[#5a6a75]">{{ $row->jabatan ?? '—' }}</td><td class="px-3 py-2.5 text-[#5a6a75]">{{ $row->tahun }}</td></tr>
                                @endforeach
                            </tbody>
                        </table>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Verifikasi Bersama --}}
    @include('livewire.asesor.evaluasi.partials.verifikasi-bersama')

    {{-- Dokumen Utama Peserta --}}
    @php
        $dokumenUtama = $permohonan->peserta->dokumenBukti
            ->filter(fn($dok) => in_array($dok->jenis_dokumen, [
                \App\Enums\JenisDokumenEnum::Transkrip,
                \App\Enums\JenisDokumenEnum::Cv,
                \App\Enums\JenisDokumenEnum::KeteranganMataKuliah,
            ], true))
            ->values();
    @endphp
    <x-pengajuan.berkas-pendukung :berkaslist="$dokumenUtama" />

    {{-- Rekognisi SKS --}}
    <x-pengajuan.sks-rekognisi :permohonan="$permohonan" />

    {{-- List MK --}}
    <div class="space-y-4 mb-6">
        @foreach ($permohonan->rplMataKuliah as $rplMk)
        <div class="bg-white rounded-[10px] border border-[#E5E8EC] overflow-hidden" wire:key="mk-{{ $rplMk->id }}" x-data="{ open: false }">
            {{-- Header MK --}}
            <div class="px-5 py-3.5 border-b border-[#F0F2F5] flex items-center justify-between cursor-pointer select-none"
                 @click="open = !open">
                <div>
                    <div class="text-[13px] font-semibold text-[#1a2a35]">{{ $rplMk->mataKuliah->nama }}</div>
                    <div class="text-[11px] text-[#8a9ba8]">{{ $rplMk->mataKuliah->kode }} &middot; {{ $rplMk->mataKuliah->sks }} SKS</div>
                </div>
                <div class="flex items-center gap-2">
                    @if ($rplMk->matkulLampau->isNotEmpty())
                    <span class="text-[10px] font-semibold px-2 py-0.5 rounded-full bg-[#E8F4F8] text-primary">Ada MK Lampau</span>
                    @endif
                    @if ($rplMk->status !== StatusRplMataKuliahEnum::Menunggu)
                    <span class="text-[11px] font-semibold px-2.5 py-1 rounded-full {{ $rplMk->status->badgeClass() }}">
                        {{ $rplMk->status->label() }}
                    </span>
                    @endif
                    <svg :class="open ? 'rotate-180' : ''" class="w-4 h-4 text-[#8a9ba8] shrink-0 transition-transform duration-200" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <polyline points="6 9 12 15 18 9"/>
                    </svg>
                </div>
            </div>

            <div class="p-5"
                 x-show="open"
                 x-transition:enter="transition ease-out duration-200"
                 x-transition:enter-start="opacity-0"
                 x-transition:enter-end="opacity-100"
                 x-transition:leave="transition ease-in duration-150"
                 x-transition:leave-start="opacity-100"
                 x-transition:leave-end="opacity-0">
                {{-- CPMK & Asesmen Mandiri Peserta (Hanya views, tanpa form input/VATM) --}}
                @if ($rplMk->mataKuliah->cpmk->isNotEmpty())
                <div class="mb-5">
                    <div class="text-[10px] font-semibold text-[#8a9ba8] uppercase tracking-[0.8px] mb-2">Capaian Pembelajaran (CPMK)</div>
                    <div class="space-y-1.5">
                        @foreach ($rplMk->mataKuliah->cpmk as $cpmk)
                        <div class="flex items-start gap-2" wire:key="cpmk-{{ $cpmk->id }}">
                            <span class="w-4 h-4 rounded-full bg-[#E8F4F8] text-primary text-[9px] font-semibold flex items-center justify-center shrink-0 mt-0.5">{{ $cpmk->urutan }}</span>
                            <span class="text-[12px] text-[#5a6a75] leading-[1.5]">{{ $cpmk->deskripsi }}</span>
                        </div>
                        @endforeach
                    </div>
                </div>
                @endif

                @if ($rplMk->asesmenMandiri->isNotEmpty())
                <div class="mb-5 bg-[#FAFBFC] border border-[#F0F2F5] rounded-xl p-4">
                    <div class="text-[10px] font-semibold text-[#8a9ba8] uppercase tracking-[0.8px] mb-3">Sub CPMK — Penilaian Diri Peserta</div>
                    @foreach ($rplMk->asesmenMandiri as $asm)
                    @php $pt = $asm->pertanyaan; @endphp
                    <div class="py-3 border-b border-[#F0F2F5] last:border-0" wire:key="asm-{{ $asm->id }}">
                        <div class="flex items-start gap-2 mb-2">
                            <span class="w-5 h-5 rounded-full bg-[#E5E8EC] text-[#5a6a75] text-[10px] font-semibold flex items-center justify-center shrink-0 mt-0.5">{{ $pt?->urutan ?? '-' }}</span>
                            <span class="flex-1 text-[12px] text-[#1a2a35] leading-[1.5]">{{ $pt?->pertanyaan ?? '—' }}</span>
                        </div>
                        <div class="ml-7 flex items-center gap-4">
                            <span class="text-[11px] font-medium text-[#5a6a75]">
                                Nilai Pemahaman Peserta: <span class="inline-flex items-center justify-center w-6 h-6 rounded bg-[#E8F4F8] text-primary text-[12px] font-bold">{{ $asm->penilaian_diri ?? '-' }}</span>
                                <span class="text-[#8a9ba8] text-[10px]">/ 5</span>
                            </span>
                        </div>
                    </div>
                    @endforeach
                </div>
                @endif

                {{-- MK Lampau (selalu tampil, editable) --}}
                <div class="mb-5 border-t border-[#F0F2F5] pt-5" x-data="{ showTambah: false }" @notify-saved.window="showTambah = false">
                    <div class="flex items-center justify-between mb-2">
                        <div class="text-[10px] font-semibold text-[#8a9ba8] uppercase tracking-[0.8px]">MK di PT Asal yang Diajukan Peserta</div>
                    </div>
                    <div class="bg-[#F4F6F8] rounded-xl overflow-hidden mb-3 border border-[#E5E8EC]">
                        <table class="w-full text-[12px]">
                            <thead>
                                <tr class="border-b border-[#E5E8EC]">
                                    <th class="text-left font-semibold text-[#8a9ba8] px-4 py-2.5 w-[110px]">Kode MK</th>
                                    <th class="text-left font-semibold text-[#8a9ba8] px-4 py-2.5">Nama MK PT Asal</th>
                                    <th class="text-center font-semibold text-[#8a9ba8] px-4 py-2.5 w-[90px]">SKS</th>
                                    <th class="text-center font-semibold text-[#8a9ba8] px-4 py-2.5 w-[160px]">Nilai</th>
                                    <th class="text-center font-semibold text-[#8a9ba8] px-4 py-2.5 w-[150px]">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($rplMk->matkulLampau as $ml)
                                <tr x-data="{ editing: false }"
                                    @ml-saved.window="if ($event.detail.mlId === {{ $ml->id }}) editing = false"
                                    wire:key="ml-{{ $ml->id }}"
                                    class="border-b border-[#EFF1F3] last:border-0 bg-white">
                                    {{-- Display mode --}}
                                    <template x-if="!editing">
                                        <td class="px-4 py-3 text-[#5a6a75] font-medium">
                                            @if ($ml->isOverridden('kode_mk'))
                                            <span class="inline-block w-1 h-4 bg-amber-400 mr-1.5 rounded-sm align-middle" title="Diedit asesor"></span>
                                            @endif
                                            {{ $ml->kode_mk_final ?? '—' }}
                                        </td>
                                    </template>
                                    <template x-if="editing">
                                        <td class="px-3 py-2">
                                            <input type="text" wire:model.defer="editForm.{{ $ml->id }}.kode_mk_asesor" placeholder="Kode MK"
                                                   class="w-full h-[38px] rounded-xl border border-[#E0E5EA] px-3 text-[12px] text-[#1a2a35] focus:outline-none focus:border-primary focus:ring-2 focus:ring-primary/10">
                                            @error("editForm.{$ml->id}.kode_mk_asesor") <p class="text-[10px] text-red-500 mt-0.5">{{ $message }}</p> @enderror
                                        </td>
                                    </template>

                                    <template x-if="!editing">
                                        <td class="px-4 py-3 text-[#1a2a35] font-semibold">
                                            @if ($ml->isOverridden('nama_mk'))
                                            <span class="inline-block w-1 h-4 bg-amber-400 mr-1.5 rounded-sm align-middle" title="Diedit asesor"></span>
                                            @endif
                                            {{ $ml->nama_mk_final ?? '—' }}
                                        </td>
                                    </template>
                                    <template x-if="editing">
                                        <td class="px-3 py-2">
                                            <input type="text" wire:model.defer="editForm.{{ $ml->id }}.nama_mk_asesor" placeholder="Nama MK"
                                                   class="w-full h-[38px] rounded-xl border border-[#E0E5EA] px-3 text-[12px] text-[#1a2a35] focus:outline-none focus:border-primary focus:ring-2 focus:ring-primary/10">
                                            @error("editForm.{$ml->id}.nama_mk_asesor") <p class="text-[10px] text-red-500 mt-0.5">{{ $message }}</p> @enderror
                                        </td>
                                    </template>

                                    <template x-if="!editing">
                                        <td class="px-4 py-3 text-center text-[#5a6a75]">
                                            @if ($ml->isOverridden('sks'))
                                            <span class="inline-block w-1 h-4 bg-amber-400 mr-1.5 rounded-sm align-middle" title="Diedit asesor"></span>
                                            @endif
                                            {{ $ml->sks_final ?? '—' }}
                                        </td>
                                    </template>
                                    <template x-if="editing">
                                        <td class="px-3 py-2">
                                            <input type="number" wire:model.defer="editForm.{{ $ml->id }}.sks_asesor" placeholder="SKS" min="1" max="20"
                                                   class="w-full h-[38px] rounded-xl border border-[#E0E5EA] px-3 text-[12px] text-[#1a2a35] text-center focus:outline-none focus:border-primary focus:ring-2 focus:ring-primary/10">
                                            @error("editForm.{$ml->id}.sks_asesor") <p class="text-[10px] text-red-500 mt-0.5">{{ $message }}</p> @enderror
                                        </td>
                                    </template>

                                    <template x-if="!editing">
                                        <td class="px-4 py-3 text-center">
                                            @if ($ml->isOverridden('nilai_huruf'))
                                            <span class="inline-block w-1 h-4 bg-amber-400 mr-1.5 rounded-sm align-middle" title="Diedit asesor"></span>
                                            @endif
                                            <span class="inline-flex items-center justify-center w-7 h-7 rounded-lg bg-[#E8F4F8] text-primary text-[12px] font-bold">{{ $ml->nilai_huruf_final?->value ?? '-' }}</span>
                                        </td>
                                    </template>
                                    <template x-if="editing">
                                        <td class="px-3 py-2">
                                            <input type="text" wire:model.defer="editForm.{{ $ml->id }}.nilai_huruf_asesor" placeholder="mis. A, AB, B+"
                                                   class="w-full h-[38px] rounded-xl border border-[#E0E5EA] px-3 text-[12px] text-[#1a2a35] focus:outline-none focus:border-primary focus:ring-2 focus:ring-primary/10">
                                            @error("editForm.{$ml->id}.nilai_huruf_asesor") <p class="text-[10px] text-red-500 mt-0.5">{{ $message }}</p> @enderror
                                        </td>
                                    </template>

                                    <template x-if="!editing">
                                        <td class="px-4 py-3 text-center">
                                            <div class="flex items-center justify-center gap-1.5">
                                                <button type="button"
                                                        @click="editing = true"
                                                        class="text-[#5a6a75] hover:text-primary transition-colors p-1.5"
                                                        title="Edit"
                                                        aria-label="Edit">
                                                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                                        <path d="M11 4H4a2 2 0 00-2 2v14a2 2 0 002 2h14a2 2 0 002-2v-7"/>
                                                        <path d="M18.5 2.5a2.121 2.121 0 013 3L12 15l-4 1 1-4 9.5-9.5z"/>
                                                    </svg>
                                                </button>
                                                <button type="button"
                                                        @click="confirmModal = { show: true, id: {{ $ml->id }} }"
                                                        class="text-[#c62828] hover:text-[#a02020] transition-colors p-1.5"
                                                        title="Hapus"
                                                        aria-label="Hapus">
                                                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                                        <polyline points="3 6 5 6 21 6"/><path d="M19 6l-1 14a2 2 0 01-2 2H8a2 2 0 01-2-2L5 6"/>
                                                        <path d="M10 11v6"/><path d="M14 11v6"/>
                                                        <path d="M9 6V4a1 1 0 011-1h4a1 1 0 011 1v2"/>
                                                    </svg>
                                                </button>
                                            </div>
                                        </td>
                                    </template>
                                    <template x-if="editing">
                                        <td class="px-3 py-2 text-center">
                                            <div class="flex items-center justify-center gap-1.5">
                                                <button type="button"
                                                        @click="editing = false; $wire.simpanEditMatkulLampau({{ $ml->id }})"
                                                        class="h-[34px] px-4 rounded-xl bg-primary text-white text-[12px] font-semibold hover:bg-[#005f78] transition-colors">
                                                    Simpan
                                                </button>
                                                <button type="button"
                                                        @click="editing = false"
                                                        class="h-[34px] px-4 rounded-xl border border-[#D0D5DD] text-[#5a6a75] text-[12px] font-medium hover:border-[#8a9ba8] hover:text-[#1a2a35] transition-colors">
                                                    Batal
                                                </button>
                                            </div>
                                        </td>
                                    </template>
                                </tr>
                                @empty
                                <tr class="bg-white">
                                    <td colspan="5" class="px-4 py-5 text-center text-[12px] text-[#8a9ba8] italic">Belum ada MK Lampau yang diinput.</td>
                                </tr>
                                @endforelse

                                {{-- Form Tambah MK Lampau --}}
                                <tr x-show="showTambah" wire:key="tambah-form-{{ $rplMk->id }}" class="bg-white border-t-2 border-[#E0E5EA]">
                                    <td class="px-3 py-3">
                                        <input type="text" wire:model.defer="tambahForm.kode_mk_asesor" placeholder="MK001"
                                               class="w-full h-[42px] rounded-xl border border-[#E0E5EA] px-3 text-[12px] text-[#1a2a35] focus:outline-none focus:border-primary focus:ring-2 focus:ring-primary/10">
                                        @error('tambahForm.kode_mk_asesor') <p class="text-[10px] text-red-500 mt-0.5">{{ $message }}</p> @enderror
                                    </td>
                                    <td class="px-3 py-3">
                                        <input type="text" wire:model.defer="tambahForm.nama_mk_asesor" placeholder="Nama mata kuliah di PT Asal"
                                               class="w-full h-[42px] rounded-xl border border-[#E0E5EA] px-3 text-[12px] text-[#1a2a35] focus:outline-none focus:border-primary focus:ring-2 focus:ring-primary/10">
                                        @error('tambahForm.nama_mk_asesor') <p class="text-[10px] text-red-500 mt-0.5">{{ $message }}</p> @enderror
                                    </td>
                                    <td class="px-3 py-3">
                                        <input type="number" wire:model.defer="tambahForm.sks_asesor" placeholder="SKS" min="1" max="20"
                                               class="w-full h-[42px] rounded-xl border border-[#E0E5EA] px-3 text-[12px] text-[#1a2a35] text-center focus:outline-none focus:border-primary focus:ring-2 focus:ring-primary/10">
                                        @error('tambahForm.sks_asesor') <p class="text-[10px] text-red-500 mt-0.5">{{ $message }}</p> @enderror
                                    </td>
                                    <td class="px-3 py-3">
                                        <input type="text" wire:model.defer="tambahForm.nilai_huruf_asesor" placeholder="mis. A, AB, B+"
                                               class="w-full h-[42px] rounded-xl border border-[#E0E5EA] px-3 text-[12px] text-[#1a2a35] focus:outline-none focus:border-primary focus:ring-2 focus:ring-primary/10">
                                        @error('tambahForm.nilai_huruf_asesor') <p class="text-[10px] text-red-500 mt-0.5">{{ $message }}</p> @enderror
                                    </td>
                                    <td class="px-3 py-3">
                                        <div class="flex items-center justify-center gap-2">
                                            <button type="button"
                                                    wire:click="tambahMatkulLampau({{ $rplMk->id }})"
                                                    class="h-[42px] px-5 rounded-xl bg-primary text-white text-[12px] font-semibold hover:bg-[#005f78] transition-colors whitespace-nowrap">
                                                Tambah
                                            </button>
                                            <button type="button"
                                                    @click="showTambah = false"
                                                    class="h-[42px] px-4 rounded-xl border border-[#D0D5DD] text-[#5a6a75] text-[12px] font-medium hover:border-[#8a9ba8] hover:text-[#1a2a35] transition-colors whitespace-nowrap">
                                                Batal
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    <button type="button"
                            @click="showTambah = !showTambah"
                            x-show="!showTambah"
                            class="flex items-center gap-1.5 text-[12px] font-medium text-primary hover:underline mb-4">
                        <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/>
                        </svg>
                        Tambah MK Lampau Manual
                    </button>

                    {{-- MK Tujuan (Header) --}}
                    <div class="rounded-xl border-2 border-[#BDE0EB] overflow-hidden mb-4">
                        <div class="bg-[#E8F4F8] px-5 py-4 flex items-center gap-4">
                            <div class="w-11 h-11 rounded-full bg-white flex items-center justify-center shrink-0 shadow-sm">
                                <svg class="w-5 h-5 text-primary" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M4 19.5v-15A2.5 2.5 0 016.5 2H20v20H6.5a2.5 2.5 0 01-2.5-2.5z"/><path d="M6.5 2H20v20H6.5A2.5 2.5 0 014 19.5v-15A2.5 2.5 0 016.5 2z"/>
                                </svg>
                            </div>
                            <div class="flex-1">
                                <div class="text-[10px] font-semibold text-primary uppercase tracking-[0.8px] mb-1">Mata Kuliah Tujuan (PCR)</div>
                                <div class="text-[15px] font-bold text-[#1a2a35]">{{ $rplMk->mataKuliah->kode }} — {{ $rplMk->mataKuliah->nama }}</div>
                            </div>
                            <div class="flex gap-2 shrink-0">
                                <span class="px-3 py-1.5 rounded-lg bg-white text-primary text-[12px] font-bold border border-[#BDE0EB]">Semester {{ $rplMk->mataKuliah->semester }}</span>
                                <span class="px-3 py-1.5 rounded-lg bg-white text-primary text-[12px] font-bold border border-[#BDE0EB]">{{ $rplMk->mataKuliah->sks }} SKS</span>
                            </div>
                        </div>
                    </div>

                    {{-- Konversi Nilai + Catatan Lampau --}}
                    <div class="bg-white rounded-xl border border-[#E5E8EC] px-5 py-5 shadow-sm">
                        <div class="flex flex-col lg:flex-row gap-8 mb-2">
                            {{-- Kiri: Konversi Nilai --}}
                            <div class="shrink-0 w-[420px]">
                                <label class="block text-[12px] font-semibold text-[#1a2a35] mb-3">Konversi Nilai Asesor</label>
                                <div class="flex gap-2 flex-wrap mb-1">
                                    @foreach ($nilaiHurufOptions as $opt)
                                    <button type="button"
                                            wire:click="$set('nilaiTransfer.{{ $rplMk->id }}', '{{ $opt->value }}')"
                                            class="w-12 h-12 rounded-xl text-[14px] font-bold border-2 transition-all
                                                   {{ ($nilaiTransfer[$rplMk->id] ?? '') === $opt->value
                                                       ? 'bg-primary border-primary text-white shadow-md shadow-primary/20'
                                                       : 'bg-white border-[#D0D5DD] text-[#5a6a75] hover:border-primary hover:text-primary' }}">
                                        {{ $opt->value }}
                                    </button>
                                    @endforeach
                                </div>
                                @error("nilaiTransfer.{$rplMk->id}") <p class="mt-1.5 text-[12px] text-[#c62828]">{{ $message }}</p> @enderror
                            </div>

                            {{-- Kanan: Catatan Lampau --}}
                            @if ($rplMk->matkulLampau->isNotEmpty())
                            <div class="flex-1 space-y-4">
                                @foreach ($rplMk->matkulLampau as $ml)
                                <div wire:key="cat-lampau-ui-{{ $ml->id }}">
                                    <label class="block text-[12px] font-semibold text-[#1a2a35] mb-2">
                                        Catatan Asesor untuk <span class="text-primary">{{ $ml->kode_mk_final ?? '—' }} — {{ $ml->nama_mk_final ?? '—' }}</span>
                                    </label>
                                    <div wire:ignore
                                         x-data="{
                                            initialized: false,
                                            content: @entangle('catatanLampau.'.$ml->id),
                                            quill: null,
                                            initQuill() {
                                                if (this.initialized) return;
                                                this.initialized = true;
                                                this.$nextTick(() => {
                                                    this.quill = new Quill(this.$refs.quillLampau{{ $ml->id }}, {
                                                        theme: 'snow',
                                                        placeholder: 'Tulis catatan asesor terkait matkul PT Asal ini...',
                                                        modules: { toolbar: [['bold', 'italic', 'underline'], [{ 'list': 'ordered'}, { 'list': 'bullet' }]] }
                                                    });
                                                    if (this.content) this.quill.root.innerHTML = this.content;
                                                    this.quill.on('text-change', () => {
                                                        this.content = this.quill.root.innerHTML === '<p><br></p>' ? '' : this.quill.root.innerHTML;
                                                    });
                                                    this.quill.focus();
                                                });
                                            }
                                         }">
                                        <div x-show="!initialized"
                                             @click="initQuill()"
                                             class="border border-[#D8DDE2] rounded-lg p-3 min-h-[80px] cursor-text hover:border-primary transition-colors text-[12px] text-[#1a2a35] prose prose-sm max-w-none"
                                             x-html="content || '<span class=\'text-[#8a9ba8]\'>Klik untuk menambahkan catatan...</span>'">
                                        </div>
                                        <div x-show="initialized">
                                            <div x-ref="quillLampau{{ $ml->id }}"></div>
                                        </div>
                                    </div>
                                </div>
                                @endforeach
                            </div>
                            @endif
                        </div>

                        {{-- Simpan Nilai --}}
                        <div class="mt-6 flex items-center justify-between gap-3">
                            <p class="text-[11px] text-[#8a9ba8]">
                                Status ditentukan otomatis dari nilai huruf. Nilai di bawah C akan tidak diakui.
                            </p>
                            <button wire:click="simpanNilai({{ $rplMk->id }})"
                                    class="h-[38px] px-5 bg-primary hover:bg-[#005f78] text-white text-[12px] font-semibold rounded-xl transition-all flex items-center gap-1.5">
                                Simpan Nilai
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        @endforeach
    </div>

    <div x-show="confirmModal.show"
         x-cloak
         class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 p-4"
         x-transition:enter="transition ease-out duration-150" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
         x-transition:leave="transition ease-in duration-100" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0">
        <div @click.outside="confirmModal.show = false" @keydown.escape.window="confirmModal.show = false"
             class="bg-white rounded-2xl shadow-xl w-full max-w-sm p-6"
             x-transition:enter="transition ease-out duration-150" x-transition:enter-start="opacity-0 scale-95" x-transition:enter-end="opacity-100 scale-100">
            <div class="flex items-center gap-3 mb-4">
                <div class="w-10 h-10 rounded-full bg-[#FCE8E6] flex items-center justify-center shrink-0">
                    <svg class="w-5 h-5 text-[#c62828]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <polyline points="3 6 5 6 21 6"/><path d="M19 6l-1 14a2 2 0 01-2 2H8a2 2 0 01-2-2L5 6"/>
                    </svg>
                </div>
                <div>
                    <div class="text-[14px] font-semibold text-[#1a2a35]">Hapus MK Lampau?</div>
                    <div class="text-[12px] text-[#8a9ba8]">Data akan dihapus permanen.</div>
                </div>
            </div>
            <div class="flex gap-3">
                <button @click="confirmModal.show = false"
                        class="flex-1 h-[40px] border border-[#D8DDE2] text-[#1a2a35] text-[13px] font-semibold rounded-xl hover:bg-[#F4F6F8] transition-colors">
                    Batal
                </button>
                <button @click="$wire.hapusMatkulLampau(confirmModal.id); confirmModal.show = false"
                        class="flex-1 h-[40px] bg-[#c62828] hover:bg-[#b71c1c] text-white text-[13px] font-semibold rounded-xl transition-colors">
                    Ya, Hapus
                </button>
            </div>
        </div>
    </div>

    {{-- Tombol Selesai (Finalisasi Permohonan) --}}
    @if ($permohonan->status === StatusPermohonanEnum::Verifikasi)
        @php
            $sksDiakuiPreview = $permohonan->rplMataKuliah
                ->where('status', StatusRplMataKuliahEnum::Diakui)
                ->sum(fn ($mk) => $mk->mataKuliah->sks ?? 0);
            $totalSksProdi   = $permohonan->programStudi->total_sks ?? 0;
            $persenSks       = $totalSksProdi > 0 ? round($sksDiakuiPreview / $totalSksProdi * 100) : 0;
            $akanDisetujui     = $totalSksProdi > 0 && $sksDiakuiPreview >= ($totalSksProdi * 0.5);
            $akanMelebihiBatas = $totalSksProdi > 0 && $sksDiakuiPreview > ($totalSksProdi * 0.7);
            $masihMenunggu     = $permohonan->rplMataKuliah->contains(fn ($mk) => $mk->status === StatusRplMataKuliahEnum::Menunggu);
        @endphp

        <div x-data="{ openFinal: false }" class="mt-2 mb-4">
            <div class="bg-white rounded-xl border border-[#E5E8EC] p-5 flex items-center justify-between gap-4">
                <div>
                    <div class="text-[14px] font-semibold text-[#1a2a35] mb-1">Selesaikan Verifikasi Transfer</div>
                    <div class="text-[12px] text-[#8a9ba8] leading-relaxed">
                        Tekan tombol Selesai jika seluruh mata kuliah sudah dinilai.
                        Permohonan akan dikunci dan diteruskan ke tahap berikutnya.
                    </div>
                </div>
                <button type="button"
                        @click="openFinal = true"
                        @if($masihMenunggu) disabled @endif
                        class="shrink-0 inline-flex items-center gap-2 h-[42px] px-5 bg-primary hover:bg-[#005f78] text-white text-[13px] font-semibold rounded-xl transition-colors disabled:opacity-50 disabled:cursor-not-allowed">
                    <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                        <polyline points="20 6 9 17 4 12"/>
                    </svg>
                    Selesai
                </button>
            </div>

            {{-- Modal Konfirmasi Finalisasi --}}
            <div x-show="openFinal" x-cloak
                 class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4"
                 x-transition:enter="transition ease-out duration-150" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100">
                <div @click.outside="openFinal = false" @keydown.escape.window="openFinal = false"
                     class="bg-white rounded-2xl shadow-xl w-full max-w-md"
                     x-transition:enter="transition ease-out duration-150" x-transition:enter-start="opacity-0 scale-95" x-transition:enter-end="opacity-100 scale-100">
                    <div class="px-6 py-5 border-b border-[#F0F2F5]">
                        <div class="text-[15px] font-semibold text-[#1a2a35]">Finalisasi Permohonan</div>
                        <div class="text-[12px] text-[#8a9ba8] mt-1">Tindakan ini tidak dapat dibatalkan.</div>
                    </div>
                    <div class="px-6 py-5 space-y-3">
                        <div class="bg-[#F4F6F8] rounded-lg p-4">
                            <div class="text-[11px] font-semibold text-[#8a9ba8] uppercase tracking-[0.5px] mb-1">SKS Diakui</div>
                            <div class="text-[18px] font-semibold text-[#1a2a35]">
                                {{ $sksDiakuiPreview }} / {{ $totalSksProdi }} SKS
                                <span class="text-[12px] font-medium text-[#8a9ba8]">({{ $persenSks }}%)</span>
                            </div>
                        </div>
                        <div class="text-[12px] leading-relaxed">
                            @if ($masihMenunggu)
                                <span class="text-[#c62828] font-semibold">Masih ada mata kuliah yang belum dinilai.</span>
                                Lengkapi semua penilaian terlebih dahulu sebelum memfinalisasi.
                            @elseif ($akanDisetujui)
                                Berdasarkan rule 50% SKS, permohonan akan
                                <span class="text-[#1e7e3e] font-semibold">DISETUJUI</span>.
                            @else
                                Berdasarkan rule 50% SKS, permohonan akan
                                <span class="text-[#c62828] font-semibold">DITOLAK</span>
                                karena SKS yang diakui di bawah 50%.
                            @endif
                        </div>
                        @if (!$masihMenunggu && $akanMelebihiBatas)
                        <div class="flex items-start gap-2 bg-amber-50 border border-amber-200 rounded-lg px-3 py-2">
                            <svg class="w-4 h-4 text-amber-500 shrink-0 mt-0.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/>
                            </svg>
                            <span class="text-[11px] text-amber-700">SKS yang diakui melebihi <strong>70%</strong> dari total SKS prodi. Pastikan sudah sesuai sebelum memfinalisasi.</span>
                        </div>
                        @endif
                    </div>
                    <div class="px-6 py-4 border-t border-[#F0F2F5] flex items-center justify-end gap-2">
                        <button type="button" @click="openFinal = false"
                                class="h-[38px] px-4 bg-white border border-[#D0D5DD] text-[#5a6a75] text-[13px] font-semibold rounded-lg hover:bg-[#F4F6F8] transition-colors">
                            Batal
                        </button>
                        <button type="button"
                                @click="$wire.finalisasi(); openFinal = false"
                                @if($masihMenunggu) disabled @endif
                                class="h-[38px] px-4 bg-primary hover:bg-[#005f78] text-white text-[13px] font-semibold rounded-lg transition-colors disabled:opacity-50 disabled:cursor-not-allowed">
                            Ya, Selesai
                        </button>
                    </div>
                </div>
            </div>
        </div>
    @endif

    {{-- Toast notifikasi error finalisasi --}}
    <div x-data="{ show: false, msg: '' }"
         @notify-error.window="msg = $event.detail.message; show = true; setTimeout(() => show = false, 4000)">
        <div x-show="show" x-cloak
             x-transition:enter="transition ease-out duration-200"
             x-transition:enter-start="opacity-0 translate-y-2"
             x-transition:enter-end="opacity-100 translate-y-0"
             class="fixed bottom-6 right-6 z-[9999] flex items-center gap-2.5 bg-[#c62828] text-white text-[12px] font-medium px-4 py-3 rounded-xl shadow-lg max-w-sm">
            <svg class="w-4 h-4 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                <circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/>
            </svg>
            <span x-text="msg"></span>
        </div>
    </div>

</div>
