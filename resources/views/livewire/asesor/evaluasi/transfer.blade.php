<?php

use Livewire\Attributes\Layout;
use Livewire\Volt\Component;
use Livewire\WithFileUploads;
use App\Actions\Asesor\FinalisasiPermohonanAction;
use App\Actions\Asesor\SanitizeCatatanAsesorAction;
use App\Actions\Asesor\SelesaikanVerifikasiAction;
use App\Enums\JenisRplEnum;
use App\Enums\NilaiHurufEnum;
use App\Enums\StatusPermohonanEnum;
use App\Enums\StatusRplMataKuliahEnum;
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

    public function with(): array
    {
        return [
            'nilaiHurufOptions' => NilaiHurufEnum::cases(),
        ];
    }
}; ?>

<x-slot:title>Evaluasi Transfer Kredit</x-slot:title>
<x-slot:subtitle>
    <a href="{{ route('asesor.pengajuan.index') }}" class="text-primary hover:underline">Pengajuan</a>
    &rsaquo; {{ $permohonan->nomor_permohonan }} &rsaquo; Evaluasi Transfer
</x-slot:subtitle>

<div x-data="{ saved: false }" @notify-saved.window="saved = true; setTimeout(() => saved = false, 3000)">

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
        <div class="bg-white rounded-[10px] border border-[#E5E8EC] overflow-hidden" wire:key="mk-{{ $rplMk->id }}">
            {{-- Header MK --}}
            <div class="px-5 py-3.5 border-b border-[#F0F2F5] flex items-center justify-between">
                <div>
                    <div class="text-[13px] font-semibold text-[#1a2a35]">{{ $rplMk->mataKuliah->nama }}</div>
                    <div class="text-[11px] text-[#8a9ba8]">{{ $rplMk->mataKuliah->kode }} &middot; {{ $rplMk->mataKuliah->sks }} SKS</div>
                </div>
                @if ($rplMk->status !== StatusRplMataKuliahEnum::Menunggu)
                <span class="text-[11px] font-semibold px-2.5 py-1 rounded-full {{ $rplMk->status->badgeClass() }}">
                    {{ $rplMk->status->label() }}
                </span>
                @endif
            </div>

            <div class="p-5">
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

                {{-- MK Lampau peserta (jika ada) --}}
                @if ($rplMk->has_mk_sejenis && $rplMk->matkulLampau->isNotEmpty())
                <div class="mb-5 border-t border-[#F0F2F5] pt-5">
                    <div class="text-[10px] font-semibold text-[#8a9ba8] uppercase tracking-[0.8px] mb-2">MK di PT Asal yang Diajukan Peserta</div>
                    <div class="bg-[#F4F6F8] rounded-xl overflow-hidden mb-4 border border-[#E5E8EC]">
                        <table class="w-full text-[12px]">
                            <thead>
                                <tr class="border-b border-[#E5E8EC]">
                                    <th class="text-left font-semibold text-[#8a9ba8] px-4 py-2.5">Kode MK</th>
                                    <th class="text-left font-semibold text-[#8a9ba8] px-4 py-2.5">Nama MK PT Asal</th>
                                    <th class="text-center font-semibold text-[#8a9ba8] px-4 py-2.5 w-20">SKS</th>
                                    <th class="text-center font-semibold text-[#8a9ba8] px-4 py-2.5 w-28">Nilai Peserta</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($rplMk->matkulLampau as $ml)
                                <tr class="border-b border-[#EFF1F3] last:border-0 bg-white">
                                    <td class="px-4 py-3 text-[#5a6a75] font-medium">{{ $ml->kode_mk }}</td>
                                    <td class="px-4 py-3 text-[#1a2a35] font-semibold">{{ $ml->nama_mk }}</td>
                                    <td class="px-4 py-3 text-center text-[#5a6a75]">{{ $ml->sks }}</td>
                                    <td class="px-4 py-3 text-center">
                                        <span class="inline-flex items-center justify-center w-7 h-7 rounded-lg bg-[#E8F4F8] text-primary text-[12px] font-bold">{{ $ml->nilai_huruf?->value ?? '-' }}</span>
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

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
                            <div class="flex-1 space-y-4">
                                @foreach ($rplMk->matkulLampau as $ml)
                                <div wire:key="cat-lampau-ui-{{ $ml->id }}">
                                    <label class="block text-[12px] font-semibold text-[#1a2a35] mb-2">
                                        Catatan Asesor untuk <span class="text-primary">{{ $ml->kode_mk }} — {{ $ml->nama_mk }}</span>
                                    </label>
                                    <div wire:ignore
                                         x-data="{ content: @entangle('catatanLampau.'.$ml->id), quill: null }"
                                         x-init="
                                            quill = new Quill($refs.quillLampau{{ $ml->id }}, {
                                                theme: 'snow',
                                                placeholder: 'Tulis catatan asesor terkait matkul PT Asal ini...',
                                                modules: {
                                                    toolbar: [
                                                        ['bold', 'italic', 'underline'],
                                                        [{ 'list': 'ordered'}, { 'list': 'bullet' }]
                                                    ]
                                                }
                                            });
                                            if (content) quill.root.innerHTML = content;
                                            quill.on('text-change', () => { content = quill.root.innerHTML === '<p><br></p>' ? '' : quill.root.innerHTML; });
                                         ">
                                        <div x-ref="quillLampau{{ $ml->id }}"></div>
                                    </div>
                                </div>
                                @endforeach
                            </div>
                        </div>

                        {{-- Simpan Semua Info Asesor --}}
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
                @elseif (! $rplMk->has_mk_sejenis)
                <div class="mt-5 pt-5 border-t border-[#F0F2F5]">
                    <p class="text-[12px] text-[#8a9ba8] italic mb-4 text-center">Peserta tidak mengisi MK lampau. Asesor tetap bisa memberi nilai transfer untuk MK ini.</p>

                    <div class="bg-white rounded-xl border border-[#E5E8EC] px-5 py-5 shadow-sm">
                        <div class="mb-3">
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
                @endif
            </div>
        </div>
        @endforeach
    </div>

    {{-- Tombol Selesai (Finalisasi Permohonan) --}}
    @if ($permohonan->status === StatusPermohonanEnum::Verifikasi)
        @php
            $sksDiakuiPreview = $permohonan->rplMataKuliah
                ->where('status', StatusRplMataKuliahEnum::Diakui)
                ->sum(fn ($mk) => $mk->mataKuliah->sks ?? 0);
            $totalSksProdi   = $permohonan->programStudi->total_sks ?? 0;
            $persenSks       = $totalSksProdi > 0 ? round($sksDiakuiPreview / $totalSksProdi * 100) : 0;
            $akanDisetujui   = $totalSksProdi > 0 && $sksDiakuiPreview >= ($totalSksProdi * 0.5);
            $masihMenunggu   = $permohonan->rplMataKuliah->contains(fn ($mk) => $mk->status === StatusRplMataKuliahEnum::Menunggu);
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
