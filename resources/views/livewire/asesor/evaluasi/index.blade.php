<?php

use Livewire\Attributes\Layout;
use Livewire\Volt\Component;
use Livewire\WithFileUploads;
use App\Actions\Asesor\FinalisasiPermohonanAction;
use App\Actions\Asesor\HitungKeputusanMkAction;
use App\Actions\Asesor\SanitizeCatatanAsesorAction;
use App\Actions\Asesor\SelesaikanVerifikasiAction;
use App\Actions\Asesor\SimpanStatusMkAction;
use App\Enums\NilaiHurufEnum;
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

    public function simpanCatatanLampau(SanitizeCatatanAsesorAction $sanitizer, int $matkulLampauId): void
    {
        $ml = \App\Models\MatkulLampau::query()
            ->whereKey($matkulLampauId)
            ->whereIn('rpl_mata_kuliah_id', $this->permohonan->rplMataKuliah()->select('id'))
            ->firstOrFail();

        $ml->update([
            'catatan_asesor' => $sanitizer->execute($this->catatanLampau[$matkulLampauId] ?? null),
        ]);
        $this->dispatch('notify-saved');
    }

    protected function simpanPenilaianTransferMk(\App\Models\RplMataKuliah $rplMk, NilaiHurufEnum $nilaiEnum, SanitizeCatatanAsesorAction $sanitizer): void
    {
        $status = $nilaiEnum->diakui() ? StatusRplMataKuliahEnum::Diakui : StatusRplMataKuliahEnum::TidakDiakui;

        $rplMk->update([
            'nilai_transfer'  => $nilaiEnum->value,
            'status'          => $status,
            'sks_diakui'      => $nilaiEnum->diakui() ? ($rplMk->mataKuliah->sks ?? 0) : 0,
        ]);

        foreach ($rplMk->matkulLampau as $ml) {
            if (isset($this->catatanLampau[$ml->id])) {
                $ml->update([
                    'catatan_asesor' => $sanitizer->execute($this->catatanLampau[$ml->id]),
                ]);
            }
        }

        $this->mkStatus[$rplMk->id] = $status->value;
        $this->dispatch('mk-status-updated', mkId: $rplMk->id, badge: $status->badgeClass(), label: $status->label());
    }

    public function simpanNilaiTransfer(SanitizeCatatanAsesorAction $sanitizer, int $rplMkId): void
    {
        $nilai = $this->nilaiTransfer[$rplMkId] ?? '';

        $this->validate([
            "nilaiTransfer.{$rplMkId}" => 'required|in:' . implode(',', array_column(NilaiHurufEnum::cases(), 'value')),
        ], [], ["nilaiTransfer.{$rplMkId}" => 'grade huruf']);

        $nilaiEnum = NilaiHurufEnum::from($nilai);
        $rplMk = \App\Models\RplMataKuliah::query()
            ->with(['mataKuliah', 'matkulLampau'])
            ->where('permohonan_rpl_id', $this->permohonan->id)
            ->findOrFail($rplMkId);

        $this->simpanPenilaianTransferMk($rplMk, $nilaiEnum, $sanitizer);
        $this->permohonan->refresh();
        $this->dispatch('notify-saved');
    }

    public function saveNilaiAsesor(int $asesmenMandiriId, int $nilai): void
    {
        abort_if($nilai < 1 || $nilai > 5, 422);

        $asesor = auth()->user()->asesor;

        $asm = \App\Models\AsesmenMandiri::query()
            ->whereKey($asesmenMandiriId)
            ->whereIn('rpl_mata_kuliah_id', $this->permohonan->rplMataKuliah()->select('id'))
            ->firstOrFail();

        NilaiAsesor::updateOrCreate(
            ['asesmen_mandiri_id' => $asm->id],
            [
                'asesor_id'    => $asesor?->id,
                'nilai'        => $nilai,
                'dinilai_pada' => now(),
            ]
        );

        $this->nilaiAsesor[$asm->id] = $nilai;

        $rplMk = \App\Models\RplMataKuliah::query()
            ->with('asesmenMandiri.nilaiAsesor')
            ->where('permohonan_rpl_id', $this->permohonan->id)
            ->find($asm->rpl_mata_kuliah_id);

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

            $this->permohonan->refresh();
        }
    }

    #[\Livewire\Attributes\Renderless]
    public function saveVatm(int $asesmenMandiriId, string $field, bool $value): void
    {
        $asesor = auth()->user()->asesor;

        $asm = \App\Models\AsesmenMandiri::query()
            ->whereKey($asesmenMandiriId)
            ->whereIn('rpl_mata_kuliah_id', $this->permohonan->rplMataKuliah()->select('id'))
            ->firstOrFail();

        EvaluasiVatm::updateOrCreate(
            ['asesmen_mandiri_id' => $asm->id],
            [
                $field            => $value,
                'asesor_id'       => $asesor?->id,
                'dievaluasi_pada' => now(),
            ]
        );

        // Muat ulang relasi evaluasiVatm agar template Livewire mendeteksi perubahan
        $this->permohonan->load('rplMataKuliah.asesmenMandiri.evaluasiVatm');
    }

    public function finalisasi(FinalisasiPermohonanAction $action, SanitizeCatatanAsesorAction $sanitizer): void
    {
        $rplMataKuliah = $this->permohonan->rplMataKuliah()
            ->with(['mataKuliah', 'matkulLampau'])
            ->get();

        $belumDinilai = [];

        foreach ($rplMataKuliah as $rplMk) {
            if (! ($rplMk->has_mk_sejenis && $rplMk->matkulLampau->isNotEmpty())) {
                continue;
            }

            $nilaiTerpilih = (string) ($this->nilaiTransfer[$rplMk->id] ?? $rplMk->nilai_transfer ?? '');
            $nilaiEnum     = $nilaiTerpilih !== '' ? NilaiHurufEnum::tryFrom($nilaiTerpilih) : null;

            if (! $nilaiEnum) {
                $belumDinilai[] = $rplMk->mataKuliah->kode ?? ('MK #' . $rplMk->id);
                continue;
            }

            $this->nilaiTransfer[$rplMk->id] = $nilaiEnum->value;
            $this->simpanPenilaianTransferMk($rplMk, $nilaiEnum, $sanitizer);
        }

        if ($belumDinilai !== []) {
            $this->dispatch(
                'notify-error',
                message: 'Masih ada mata kuliah transfer yang belum dinilai: ' . implode(', ', $belumDinilai) . '.'
            );
            return;
        }

        $this->permohonan->refresh();

        try {
            $action->execute($this->permohonan);
        } catch (\DomainException $e) {
            $this->dispatch('notify-error', message: $e->getMessage());
            return;
        }

        $this->redirect(route('asesor.pengajuan.index'), navigate: true);
    }

    public function saveMkStatus(int $rplMkId, SimpanStatusMkAction $action): void
    {
        abort_if(! in_array($this->permohonan->status, [
            StatusPermohonanEnum::Asesmen,
            StatusPermohonanEnum::Disetujui,
        ]), 403);

        $this->validate([
            "mkStatus.{$rplMkId}"  => 'required|in:' . implode(',', array_column(StatusRplMataKuliahEnum::cases(), 'value')),
            "mkCatatan.{$rplMkId}" => 'nullable|string|max:1000',
        ]);

        $status = StatusRplMataKuliahEnum::from($this->mkStatus[$rplMkId]);

        $action->execute($this->permohonan, $rplMkId, $status, $this->mkCatatan[$rplMkId] ?? null);

        $this->dispatch('mk-status-updated', mkId: $rplMkId, badge: $status->badgeClass(), label: $status->label());
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

    {{-- SKS Rekognisi Card (saat asesor sedang/selesai menilai) --}}
    @if (in_array($permohonan->status, [StatusPermohonanEnum::Asesmen, StatusPermohonanEnum::Disetujui, StatusPermohonanEnum::Ditolak]))
    <x-pengajuan.sks-rekognisi :permohonan="$permohonan" />
    @endif

    {{-- Per MK --}}
    @include('livewire.asesor.evaluasi.partials.evaluasi-per-mk')

    {{-- Tombol Selesai (Finalisasi Permohonan) --}}
    @if ($permohonan->status === StatusPermohonanEnum::Asesmen)
        @php
            $nilaiTransferState = $this->nilaiTransfer ?? [];
            $mkStatusState      = $this->mkStatus ?? [];

            $statusPrediksiMk = $permohonan->rplMataKuliah->mapWithKeys(function ($mk) use ($nilaiTransferState, $mkStatusState) {
                if ($mk->has_mk_sejenis && $mk->matkulLampau->isNotEmpty()) {
                    $nilai     = (string) ($nilaiTransferState[$mk->id] ?? $mk->nilai_transfer ?? '');
                    $nilaiEnum = $nilai !== '' ? NilaiHurufEnum::tryFrom($nilai) : null;

                    if (! $nilaiEnum) {
                        return [$mk->id => StatusRplMataKuliahEnum::Menunggu];
                    }

                    return [$mk->id => $nilaiEnum->diakui()
                        ? StatusRplMataKuliahEnum::Diakui
                        : StatusRplMataKuliahEnum::TidakDiakui];
                }

                $statusValue = $mkStatusState[$mk->id] ?? $mk->status?->value ?? StatusRplMataKuliahEnum::Menunggu->value;
                return [$mk->id => StatusRplMataKuliahEnum::from($statusValue)];
            });

            $sksDiakuiPreview = $permohonan->rplMataKuliah->sum(fn ($mk) =>
                ($statusPrediksiMk[$mk->id] ?? StatusRplMataKuliahEnum::Menunggu) === StatusRplMataKuliahEnum::Diakui
                    ? ($mk->mataKuliah->sks ?? 0)
                    : 0
            );
            $totalSksProdi   = $permohonan->programStudi->total_sks ?? 0;
            $persenSks       = $totalSksProdi > 0 ? round($sksDiakuiPreview / $totalSksProdi * 100) : 0;
            $akanDisetujui   = $totalSksProdi > 0 && $sksDiakuiPreview >= ($totalSksProdi * 0.5);
            $masihMenunggu   = $statusPrediksiMk->contains(fn ($st) => $st === StatusRplMataKuliahEnum::Menunggu);
        @endphp

        <div x-data="{ openFinal: false }" class="mt-6 mb-4">
            <div class="bg-white rounded-xl border border-[#E5E8EC] p-5 flex items-center justify-between gap-4">
                <div>
                    <div class="text-[14px] font-semibold text-[#1a2a35] mb-1">Selesaikan Penilaian</div>
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
                 x-transition:enter="transition ease-out duration-150" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
                 x-transition:leave="transition ease-in duration-100" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0">
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
