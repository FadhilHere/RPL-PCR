<?php

use Livewire\Attributes\Layout;
use Livewire\Volt\Component;
use Livewire\WithFileUploads;
use App\Actions\Asesor\HitungKeputusanMkAction;
use App\Actions\Asesor\SelesaikanVerifikasiAction;
use App\Actions\Asesor\SimpanStatusMkAction;
use App\Enums\StatusPermohonanEnum;
use App\Enums\StatusRplMataKuliahEnum;
use App\Models\Asesor;
use App\Models\EvaluasiVatm;
use App\Models\NilaiAsesor;
use App\Models\PermohonanRpl;

new #[Layout('components.layouts.asesor')] class extends Component {
    use WithFileUploads;

    public PermohonanRpl $permohonan;
    public $berkasBA = null;

    // mkStatus[rpl_mk_id] = status string
    public array $mkStatus = [];
    // mkCatatan[rpl_mk_id] = catatan string
    public array $mkCatatan = [];
    // nilaiAsesor[asesmen_mandiri_id] = nilai int (1-5)
    public array $nilaiAsesor = [];
    // nilaiTransfer[rpl_mk_id] = 'A'|'AB'|... (kalau hybrid lewat matkulLampau)
    public array $nilaiTransfer = [];
    // catatanLampau[matkul_lampau_id] = string
    public array $catatanLampau = [];

    public function mount(PermohonanRpl $permohonan): void
    {
        $asesorId   = auth()->user()->asesor?->id;
        // Validasi: Apakah Asesor ini telah di-assign ke permohonan ini?
        $isAssigned = $asesorId && $permohonan->asesor()->where('asesor_id', $asesorId)->exists();

        if (! $isAssigned) {
            abort(403, 'Anda tidak ditugaskan/diassign ke permohonan ini.');
        }

        $this->permohonan = $permohonan->load([
            'peserta.user',
            'peserta.dokumenBukti',
            'peserta.riwayatPendidikan',
            'peserta.pelatihanProfesional',
            'peserta.konferensiSeminar',
            'peserta.penghargaan',
            'peserta.organisasiProfesi',
            'programStudi',
            'rplMataKuliah.mataKuliah.cpmk',
            'rplMataKuliah.mataKuliah.pertanyaan',
            'rplMataKuliah.asesmenMandiri.pertanyaan',
            'rplMataKuliah.asesmenMandiri.evaluasiVatm',
            'rplMataKuliah.asesmenMandiri.nilaiAsesor',
            'rplMataKuliah.matkulLampau',
            'verifikasiBersama',
        ]);

        foreach ($this->permohonan->rplMataKuliah as $rplMk) {
            $this->mkStatus[$rplMk->id]  = $rplMk->status?->value ?? StatusRplMataKuliahEnum::Menunggu->value;
            $this->mkCatatan[$rplMk->id] = $rplMk->catatan_asesor ?? '';
            $this->nilaiTransfer[$rplMk->id] = $rplMk->nilai_transfer ?? '';

            foreach ($rplMk->asesmenMandiri as $asm) {
                $this->nilaiAsesor[$asm->id] = $asm->nilaiAsesor?->nilai ?? 0;
            }

            foreach ($rplMk->matkulLampau as $ml) {
                $this->catatanLampau[$ml->id] = $ml->catatan_asesor ?? '';
            }
        }
    }


    public function selesaikanVerifikasi(string $catatanHasil = '', SelesaikanVerifikasiAction $action): void
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

    public function simpanCatatanLampau(int $matkulLampauId): void
    {
        $ml = \App\Models\MatkulLampau::findOrFail($matkulLampauId);
        $ml->update([
            'catatan_asesor' => $this->catatanLampau[$matkulLampauId] ?? null,
        ]);
        $this->dispatch('notify-saved');
    }

    public function simpanNilaiTransfer(int $rplMkId): void
    {
        $nilai = $this->nilaiTransfer[$rplMkId] ?? '';

        $this->validate([
            "nilaiTransfer.{$rplMkId}" => 'required|in:A,AB,B,BC,C,D,E',
        ], [], ["nilaiTransfer.{$rplMkId}" => 'grade huruf']);

        $nilaiEnum = \App\Enums\NilaiHurufEnum::from($nilai);
        $rplMk = \App\Models\RplMataKuliah::with('mataKuliah')->findOrFail($rplMkId);

        $status = $nilaiEnum->diakui() ? StatusRplMataKuliahEnum::Diakui : StatusRplMataKuliahEnum::TidakDiakui;

        $rplMk->update([
            'nilai_transfer'  => $nilaiEnum->value,
            'status'          => $status,
            'sks_diakui'      => $nilaiEnum->diakui() ? ($rplMk->mataKuliah->sks ?? 0) : 0,
        ]);

        foreach ($rplMk->matkulLampau as $ml) {
            if (isset($this->catatanLampau[$ml->id])) {
                $ml->update(['catatan_asesor' => $this->catatanLampau[$ml->id]]);
            }
        }

        $this->mkStatus[$rplMkId] = $status->value;
        $this->permohonan->refresh();
        $this->dispatch('notify-saved');
    }

    #[\Livewire\Attributes\Renderless]
    public function saveNilaiAsesor(int $asesmenMandiriId, int $nilai): void
    {
        abort_if($nilai < 1 || $nilai > 5, 422);

        $asesor = auth()->user()->asesor;

        NilaiAsesor::updateOrCreate(
            ['asesmen_mandiri_id' => $asesmenMandiriId],
            [
                'asesor_id'    => $asesor?->id,
                'nilai'        => $nilai,
                'dinilai_pada' => now(),
            ]
        );

        $this->nilaiAsesor[$asesmenMandiriId] = $nilai;

        // Ambil data MK
        $asm   = \App\Models\AsesmenMandiri::find($asesmenMandiriId);
        $rplMk = $asm
            ? \App\Models\RplMataKuliah::with('asesmenMandiri.nilaiAsesor')->find($asm->rpl_mata_kuliah_id)
            : null;

        if ($rplMk) {
            $hitungAction = app(HitungKeputusanMkAction::class);
            $rataRata = $hitungAction->rataRata($rplMk);
            $rekomendasi = $rataRata !== null ? $hitungAction->execute($rplMk) : null;

            $this->dispatch('rata-rata-updated',
                mkId: $rplMk->id,
                rataRata: $rataRata,
                rekomendasiLabel: $rekomendasi?->label(),
                isDiakui: $rekomendasi === \App\Enums\StatusRplMataKuliahEnum::Diakui
            );

            // Auto-set status MK jika semua sub-CPMK sudah dinilai
            if ($rplMk->asesmenMandiri->isNotEmpty() && $rplMk->asesmenMandiri->every(fn($a) => $a->nilaiAsesor !== null)) {
                $status = $rekomendasi;
                $rplMk->update(['status' => $status]);
                $this->mkStatus[$rplMk->id] = $status->value;
                $this->dispatch('mk-status-updated', mkId: $rplMk->id, badge: $status->badgeClass(), label: $status->label());
            }
        }
    }

    #[\Livewire\Attributes\Renderless]
    public function saveVatm(int $asesmenMandiriId, string $field, bool $value): void
    {
        $asesor = auth()->user()->asesor;

        EvaluasiVatm::updateOrCreate(
            ['asesmen_mandiri_id' => $asesmenMandiriId],
            [
                $field            => $value,
                'asesor_id'       => $asesor?->id,
                'dievaluasi_pada' => now(),
            ]
        );

        // Muat ulang relasi evaluasiVatm agar template Livewire mendeteksi perubahan
        $this->permohonan->load('rplMataKuliah.asesmenMandiri.evaluasiVatm');
    }

    public function saveMkStatus(int $rplMkId, SimpanStatusMkAction $action): void
    {
        abort_if(! in_array($this->permohonan->status, [
            StatusPermohonanEnum::DalamReview,
            StatusPermohonanEnum::Disetujui,
        ]), 403);

        $this->validate([
            "mkStatus.{$rplMkId}"  => 'required|in:' . implode(',', array_column(StatusRplMataKuliahEnum::cases(), 'value')),
            "mkCatatan.{$rplMkId}" => 'nullable|string|max:1000',
        ]);

        $status = StatusRplMataKuliahEnum::from($this->mkStatus[$rplMkId]);

        $action->execute($this->permohonan, $rplMkId, $status, $this->mkCatatan[$rplMkId] ?? null);

        $this->permohonan->refresh();
        $this->dispatch('notify-saved');
    }

    public function with(): array
    {
        return [
            'nilaiHurufOptions' => \App\Enums\NilaiHurufEnum::cases(),
        ];
    }
}; ?>

<x-slot:title>Evaluasi VATM</x-slot:title>
<x-slot:subtitle>
    <a href="{{ route('asesor.pengajuan.index') }}" class="text-primary hover:underline">Pengajuan RPL</a>
    &rsaquo; {{ $permohonan->nomor_permohonan }}
</x-slot:subtitle>

<div>

    {{-- Toast notifikasi simpan --}}
    <div x-data="{ show: false }"
         @notify-saved.window="show = true; setTimeout(() => show = false, 3000)">
        <div x-show="show"
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
    </div>

    {{-- Info permohonan --}}
    <div x-data="{ profilOpen: false }" class="mb-5">
    <div class="bg-white rounded-xl border border-[#E5E8EC] px-5 py-4 flex items-center gap-5">
        <div class="flex-1">
            <div class="text-[12px] text-[#8a9ba8] mb-0.5">Peserta</div>
            <div class="text-[14px] font-semibold text-[#1a2a35]">{{ $permohonan->peserta->user->nama ?? '-' }}</div>
        </div>
        <div class="flex-1">
            <div class="text-[12px] text-[#8a9ba8] mb-0.5">Program Studi</div>
            <div class="text-[13px] font-medium text-[#1a2a35]">{{ $permohonan->programStudi->nama ?? '-' }}</div>
        </div>
        <div class="flex-1">
            <div class="text-[12px] text-[#8a9ba8] mb-0.5">No. Permohonan</div>
            <div class="text-[13px] font-medium text-[#1a2a35]">{{ $permohonan->nomor_permohonan }}</div>
        </div>
        <div class="flex items-center gap-3">
            <span class="text-[11px] font-semibold px-2.5 py-1 rounded-full {{ $permohonan->status->badgeClass() }}">{{ $permohonan->status->label() }}</span>
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

            {{-- Header --}}
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

                {{-- Tab bar --}}
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

                {{-- Biodata --}}
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

                {{-- Riwayat Pendidikan --}}
                <div x-show="tab === 'pendidikan'" x-cloak>
                    @if ($permohonan->peserta->riwayatPendidikan->isEmpty())
                    <div class="text-center text-[12px] text-[#8a9ba8] py-6">Belum ada data riwayat pendidikan.</div>
                    @else
                    <table class="w-full text-[12px]">
                        <thead><tr class="border-b border-[#F0F2F5] bg-[#FAFBFC]">
                            <th class="text-left font-semibold text-[#8a9ba8] px-3 py-2">Nama Sekolah / Institusi</th>
                            <th class="text-left font-semibold text-[#8a9ba8] px-3 py-2">Jurusan</th>
                            <th class="text-left font-semibold text-[#8a9ba8] px-3 py-2">Tahun Lulus</th>
                        </tr></thead>
                        <tbody>
                            @foreach ($permohonan->peserta->riwayatPendidikan as $row)
                            <tr class="border-b border-[#F6F8FA] last:border-0">
                                <td class="px-3 py-2.5 font-medium text-[#1a2a35]">{{ $row->nama_sekolah }}</td>
                                <td class="px-3 py-2.5 text-[#5a6a75]">{{ $row->jurusan ?? '—' }}</td>
                                <td class="px-3 py-2.5 text-[#5a6a75]">{{ $row->tahun_lulus ?? '—' }}</td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                    @endif
                </div>

                {{-- Pelatihan --}}
                <div x-show="tab === 'pelatihan'" x-cloak>
                    @if ($permohonan->peserta->pelatihanProfesional->isEmpty())
                    <div class="text-center text-[12px] text-[#8a9ba8] py-6">Belum ada data pelatihan.</div>
                    @else
                    <table class="w-full text-[12px]">
                        <thead><tr class="border-b border-[#F0F2F5] bg-[#FAFBFC]">
                            <th class="text-left font-semibold text-[#8a9ba8] px-3 py-2">Jenis Pelatihan</th>
                            <th class="text-left font-semibold text-[#8a9ba8] px-3 py-2">Penyelenggara</th>
                            <th class="text-left font-semibold text-[#8a9ba8] px-3 py-2">Jangka Waktu</th>
                            <th class="text-left font-semibold text-[#8a9ba8] px-3 py-2">Tahun</th>
                        </tr></thead>
                        <tbody>
                            @foreach ($permohonan->peserta->pelatihanProfesional as $row)
                            <tr class="border-b border-[#F6F8FA] last:border-0">
                                <td class="px-3 py-2.5 font-medium text-[#1a2a35]">{{ $row->jenis_pelatihan }}</td>
                                <td class="px-3 py-2.5 text-[#5a6a75]">{{ $row->penyelenggara }}</td>
                                <td class="px-3 py-2.5 text-[#5a6a75]">{{ $row->jangka_waktu ?? '—' }}</td>
                                <td class="px-3 py-2.5 text-[#5a6a75]">{{ $row->tahun }}</td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                    @endif
                </div>

                {{-- Konferensi --}}
                <div x-show="tab === 'konferensi'" x-cloak>
                    @if ($permohonan->peserta->konferensiSeminar->isEmpty())
                    <div class="text-center text-[12px] text-[#8a9ba8] py-6">Belum ada data konferensi / seminar.</div>
                    @else
                    <table class="w-full text-[12px]">
                        <thead><tr class="border-b border-[#F0F2F5] bg-[#FAFBFC]">
                            <th class="text-left font-semibold text-[#8a9ba8] px-3 py-2">Judul Kegiatan</th>
                            <th class="text-left font-semibold text-[#8a9ba8] px-3 py-2">Penyelenggara</th>
                            <th class="text-left font-semibold text-[#8a9ba8] px-3 py-2">Peran</th>
                            <th class="text-left font-semibold text-[#8a9ba8] px-3 py-2">Tahun</th>
                        </tr></thead>
                        <tbody>
                            @foreach ($permohonan->peserta->konferensiSeminar as $row)
                            <tr class="border-b border-[#F6F8FA] last:border-0">
                                <td class="px-3 py-2.5 font-medium text-[#1a2a35]">{{ $row->judul_kegiatan }}</td>
                                <td class="px-3 py-2.5 text-[#5a6a75]">{{ $row->penyelenggara }}</td>
                                <td class="px-3 py-2.5 text-[#5a6a75]">{{ $row->peran ?? '—' }}</td>
                                <td class="px-3 py-2.5 text-[#5a6a75]">{{ $row->tahun }}</td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                    @endif
                </div>

                {{-- Penghargaan --}}
                <div x-show="tab === 'penghargaan'" x-cloak>
                    @if ($permohonan->peserta->penghargaan->isEmpty())
                    <div class="text-center text-[12px] text-[#8a9ba8] py-6">Belum ada data penghargaan.</div>
                    @else
                    <table class="w-full text-[12px]">
                        <thead><tr class="border-b border-[#F0F2F5] bg-[#FAFBFC]">
                            <th class="text-left font-semibold text-[#8a9ba8] px-3 py-2">Bentuk Penghargaan</th>
                            <th class="text-left font-semibold text-[#8a9ba8] px-3 py-2">Pemberi</th>
                            <th class="text-left font-semibold text-[#8a9ba8] px-3 py-2">Tahun</th>
                        </tr></thead>
                        <tbody>
                            @foreach ($permohonan->peserta->penghargaan as $row)
                            <tr class="border-b border-[#F6F8FA] last:border-0">
                                <td class="px-3 py-2.5 font-medium text-[#1a2a35]">{{ $row->bentuk_penghargaan }}</td>
                                <td class="px-3 py-2.5 text-[#5a6a75]">{{ $row->pemberi }}</td>
                                <td class="px-3 py-2.5 text-[#5a6a75]">{{ $row->tahun }}</td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                    @endif
                </div>

                {{-- Organisasi Profesi --}}
                <div x-show="tab === 'organisasi'" x-cloak>
                    @if ($permohonan->peserta->organisasiProfesi->isEmpty())
                    <div class="text-center text-[12px] text-[#8a9ba8] py-6">Belum ada data organisasi profesi.</div>
                    @else
                    <table class="w-full text-[12px]">
                        <thead><tr class="border-b border-[#F0F2F5] bg-[#FAFBFC]">
                            <th class="text-left font-semibold text-[#8a9ba8] px-3 py-2">Nama Organisasi</th>
                            <th class="text-left font-semibold text-[#8a9ba8] px-3 py-2">Jabatan</th>
                            <th class="text-left font-semibold text-[#8a9ba8] px-3 py-2">Tahun</th>
                        </tr></thead>
                        <tbody>
                            @foreach ($permohonan->peserta->organisasiProfesi as $row)
                            <tr class="border-b border-[#F6F8FA] last:border-0">
                                <td class="px-3 py-2.5 font-medium text-[#1a2a35]">{{ $row->nama_organisasi }}</td>
                                <td class="px-3 py-2.5 text-[#5a6a75]">{{ $row->jabatan ?? '—' }}</td>
                                <td class="px-3 py-2.5 text-[#5a6a75]">{{ $row->tahun }}</td>
                            </tr>
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

    {{-- Berkas Pendukung Peserta --}}
    <x-pengajuan.berkas-pendukung :berkaslist="$permohonan->peserta->dokumenBukti" />

    {{-- SKS Rekognisi Card (dalam_review + disetujui) --}}
    @if (in_array($permohonan->status, [StatusPermohonanEnum::DalamReview, StatusPermohonanEnum::Disetujui]))
    <x-pengajuan.sks-rekognisi :permohonan="$permohonan" />
    @endif

    {{-- Per MK --}}
    @include('livewire.asesor.evaluasi.partials.evaluasi-per-mk')

    {{-- Back + Resume --}}
    <div class="mt-2 flex items-center justify-between">
        <a href="{{ route('asesor.pengajuan.index') }}"
           class="text-[13px] text-[#5a6a75] hover:text-primary transition-colors no-underline">
            ← Kembali ke Pengajuan RPL
        </a>
        <a href="{{ route('asesor.evaluasi.resume', $permohonan) }}"
           class="inline-flex items-center gap-2 h-[38px] px-4 bg-white border border-[#D0D5DD] text-[13px] font-semibold text-[#1a2a35] rounded-xl hover:bg-[#F4F6F8] transition-colors no-underline">
            <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V8z"/>
                <polyline points="14 2 14 8 20 8"/>
                <line x1="16" y1="13" x2="8" y2="13"/>
                <line x1="16" y1="17" x2="8" y2="17"/>
            </svg>
            Ringkasan
        </a>
    </div>

</div>
