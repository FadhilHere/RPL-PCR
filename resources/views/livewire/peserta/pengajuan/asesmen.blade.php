<?php

use Livewire\Attributes\Layout;
use Livewire\Volt\Component;
use App\Models\MatkulLampau;
use App\Models\PermohonanRpl;
use App\Models\RplMataKuliah;
use App\Models\AsesmenMandiri;
use App\Models\DokumenBukti;
use App\Enums\StatusPermohonanEnum;

new #[Layout('components.layouts.peserta')] class extends Component {
    public PermohonanRpl $permohonan;

    // pertanyaanRatings[pertanyaan_id] = nilai (1-5)
    public array $pertanyaanRatings = [];

    // pertanyaan_id yang sudah punya record asesmen (untuk tampilkan tombol Lampirkan Berkas)
    public array $asesmenIds = [];

    // buktiTerpilih[pertanyaan_id] = [nama_dokumen, ...]
    public array $buktiTerpilih = [];

    // hasMkSejenis[rpl_mk_id] = bool
    public array $hasMkSejenis = [];

    // matkulLampau[rpl_mk_id] = ['id'=>'', 'kode_mk'=>'', 'nama_mk'=>'', 'sks'=>'', 'nilai_huruf'=>'']
    public array $matkulLampau = [];

    public function with(): array
    {
        $peserta = auth()->user()->peserta;

        return [
            'semuaBerkas' => $peserta
                ? DokumenBukti::where('peserta_id', $peserta->id)->get()
                : collect(),
        ];
    }

    public function mount(PermohonanRpl $permohonan): void
    {
        abort_if($permohonan->peserta_id !== auth()->user()->peserta?->id, 403);

        $this->permohonan = $permohonan->load([
            'programStudi',
            'rplMataKuliah.mataKuliah.cpmk',
            'rplMataKuliah.mataKuliah.pertanyaan',
            'rplMataKuliah.asesmenMandiri.pertanyaan',
            'rplMataKuliah.matkulLampau',
        ]);

        foreach ($this->permohonan->rplMataKuliah as $rplMk) {
            $this->hasMkSejenis[$rplMk->id] = $rplMk->has_mk_sejenis;

            $lampau = $rplMk->matkulLampau->first();

            $this->matkulLampau[$rplMk->id] = $lampau ? [
                'id'          => $lampau->id,
                'kode_mk'     => $lampau->kode_mk,
                'nama_mk'     => $lampau->nama_mk,
                'sks'         => (string) $lampau->sks,
                'nilai_huruf' => $lampau->nilai_huruf?->value ?? '',
            ] : [
                'id'          => null,
                'kode_mk'     => '',
                'nama_mk'     => '',
                'sks'         => '',
                'nilai_huruf' => '',
            ];

            foreach ($rplMk->asesmenMandiri as $asesmen) {
                $this->pertanyaanRatings[$asesmen->pertanyaan_id] = $asesmen->penilaian_diri;
                $this->buktiTerpilih[$asesmen->pertanyaan_id]     = $asesmen->referensi_berkas ?? [];
                $this->asesmenIds[]                               = $asesmen->pertanyaan_id;
            }
        }
    }

    #[\Livewire\Attributes\Renderless]
    public function saveRating(int $rplMkId, int $pertanyaanId, int $nilai): void
    {
        abort_if($nilai < 1 || $nilai > 5, 422);

        $isNew = !isset($this->pertanyaanRatings[$pertanyaanId]);

        $this->pertanyaanRatings[$pertanyaanId] = $nilai;

        AsesmenMandiri::updateOrCreate(
            ['rpl_mata_kuliah_id' => $rplMkId, 'pertanyaan_id' => $pertanyaanId],
            ['penilaian_diri' => $nilai]
        );

        if ($isNew && ! in_array($pertanyaanId, $this->asesmenIds)) {
            $this->asesmenIds[] = $pertanyaanId;
        }

        $this->dispatch('progress-updated', dinilai: $this->totalDinilai(), total: $this->totalPertanyaan());
        $this->dispatch('rating-recorded', rplMkId: $rplMkId, isNew: $isNew);
    }

    public function toggleMkSejenis(int $rplMkId): void
    {
        $rplMk = RplMataKuliah::findOrFail($rplMkId);
        abort_if($rplMk->permohonan_rpl_id !== $this->permohonan->id, 403);

        $newValue = ! ($this->hasMkSejenis[$rplMkId] ?? false);
        $this->hasMkSejenis[$rplMkId] = $newValue;

        $rplMk->update(['has_mk_sejenis' => $newValue]);

        if (! $newValue) {
            // Hapus semua matkul lampau jika dimatikan
            MatkulLampau::where('rpl_mata_kuliah_id', $rplMkId)->delete();
            $this->matkulLampau[$rplMkId] = [
                'id'          => null,
                'kode_mk'     => '',
                'nama_mk'     => '',
                'sks'         => '',
                'nilai_huruf' => '',
            ];
        }
    }

    public function saveRow(int $rplMkId): void
    {
        $row = $this->matkulLampau[$rplMkId] ?? null;
        if (! $row) return;

        $this->validate([
            "matkulLampau.{$rplMkId}.kode_mk"     => 'required|string|max:20',
            "matkulLampau.{$rplMkId}.nama_mk"     => 'required|string|max:255',
            "matkulLampau.{$rplMkId}.sks"         => 'required|integer|min:1|max:20',
            "matkulLampau.{$rplMkId}.nilai_huruf" => 'nullable|string|in:A,AB,B,BC,C,D,E',
        ], [], [
            "matkulLampau.{$rplMkId}.kode_mk"     => 'kode MK',
            "matkulLampau.{$rplMkId}.nama_mk"     => 'nama MK',
            "matkulLampau.{$rplMkId}.sks"         => 'SKS',
            "matkulLampau.{$rplMkId}.nilai_huruf" => 'nilai huruf',
        ]);

        $nilaiHuruf = $row['nilai_huruf'] !== '' ? $row['nilai_huruf'] : null;

        $ml = MatkulLampau::updateOrCreate(
            ['id' => $row['id'] ?: 0],
            [
                'rpl_mata_kuliah_id' => $rplMkId,
                'kode_mk'            => $row['kode_mk'],
                'nama_mk'            => $row['nama_mk'],
                'sks'                => (int) $row['sks'],
                'nilai_huruf'        => $nilaiHuruf,
            ]
        );

        $this->matkulLampau[$rplMkId]['id'] = $ml->id;
        $this->dispatch('notify-saved');
    }

    public function toggleBerkas(int $dokumenId, int $pertanyaanId, int $rplMkId): void
    {
        $asm = AsesmenMandiri::where('rpl_mata_kuliah_id', $rplMkId)
            ->where('pertanyaan_id', $pertanyaanId)
            ->first();

        if (! $asm) return;

        $dokumen = DokumenBukti::findOrFail($dokumenId);
        $nama      = $dokumen->nama_dokumen;
        $referensi = $asm->referensi_berkas ?? [];

        if (in_array($nama, $referensi)) {
            $referensi = array_values(array_filter($referensi, fn($n) => $n !== $nama));
        } else {
            $referensi[] = $nama;
        }

        $asm->update(['referensi_berkas' => $referensi]);

        $this->buktiTerpilih[$pertanyaanId] = $referensi;
    }

    public function removeBerkas(string $namaBerkas, int $pertanyaanId, int $rplMkId): void
    {
        $asm = AsesmenMandiri::where('rpl_mata_kuliah_id', $rplMkId)
            ->where('pertanyaan_id', $pertanyaanId)
            ->first();

        if (! $asm) return;

        $referensi = array_values(array_filter(
            $asm->referensi_berkas ?? [],
            fn($n) => $n !== $namaBerkas
        ));

        $asm->update(['referensi_berkas' => $referensi]);
        $this->buktiTerpilih[$pertanyaanId] = $referensi;
    }

    #[\Livewire\Attributes\Computed]
    public function totalPertanyaan(): int
    {
        return \App\Models\Pertanyaan::whereIn('mata_kuliah_id', 
            \App\Models\RplMataKuliah::where('permohonan_rpl_id', $this->permohonan->id)
                ->pluck('mata_kuliah_id')
        )->count();
    }

    public function totalDinilai(): int
    {
        return count($this->pertanyaanRatings);
    }

    public function isComplete(): bool
    {
        return $this->totalPertanyaan() > 0 && $this->totalDinilai() >= $this->totalPertanyaan();
    }

    public function ajukan(): void
    {
        abort_if(! $this->isComplete(), 422);
        abort_if($this->permohonan->status !== StatusPermohonanEnum::Diproses, 422);

        $this->permohonan->update(['status' => StatusPermohonanEnum::Verifikasi]);

        $this->redirect(route('peserta.pengajuan.index'), navigate: true);
    }
}; ?>

<x-slot:title>Asesmen Mandiri</x-slot:title>
<x-slot:subtitle><a href="{{ route('peserta.pengajuan.index') }}" class="text-primary hover:underline">Pengajuan RPL</a> &rsaquo; {{ $permohonan->nomor_permohonan }}</x-slot:subtitle>

<div x-data="{ modalBukti: { show: false, pertanyaanId: 0, rplMkId: 0 }, modalAjukan: false, showToast: false }"
     @notify-saved.window="showToast = true; setTimeout(() => showToast = false, 3000)">

    {{-- Toast Notifikasi --}}
    <div x-show="showToast" x-cloak
         x-transition:enter="transition ease-out duration-200"
         x-transition:enter-start="opacity-0 translate-y-2"
         x-transition:enter-end="opacity-100 translate-y-0"
         x-transition:leave="transition ease-in duration-150"
         x-transition:leave-start="opacity-100 translate-y-0"
         x-transition:leave-end="opacity-0 translate-y-2"
         class="fixed bottom-6 right-6 z-[9999] flex items-center gap-2.5 bg-primary text-white text-[12px] font-medium px-4 py-3 rounded-xl shadow-lg">
        <svg class="w-4 h-4 text-[#4ade80] shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
            <polyline points="20 6 9 17 4 12"/>
        </svg>
        Berhasil disimpan
    </div>

    @php
        $isDraf      = $permohonan->status === StatusPermohonanEnum::Diproses;
        $total       = $this->totalPertanyaan();
        $dinilai     = $this->totalDinilai();
        $progress    = $total > 0 ? round($dinilai / $total * 100) : 0;
        $ratingLabels = []; // skala 1-5 tanpa label teks (Poin 6)
    @endphp

    {{-- Banner read-only (asesmen sudah disubmit) --}}
    @if (! $isDraf)
    @php
        $bannerColor = $permohonan->status === StatusPermohonanEnum::Diajukan
            ? ['bg' => 'bg-[#FFF8E1] border-[#FFE082]', 'text' => 'text-[#b45309]', 'icon' => '#b45309',
               'msg' => 'Menunggu admin memproses pengajuan — asesmen belum dapat diisi.']
            : ['bg' => 'bg-[#E6F4EA] border-[#A8D5B5]', 'text' => 'text-[#1e7e3e]', 'icon' => '#1e7e3e',
               'msg' => 'Asesmen mandiri telah disubmit — jawaban tidak dapat diubah.'];
    @endphp
    <div class="{{ $bannerColor['bg'] }} border rounded-xl px-4 py-3 mb-5 flex items-center gap-3">
        <svg class="w-4 h-4 shrink-0" viewBox="0 0 24 24" fill="none" stroke="{{ $bannerColor['icon'] }}" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <path d="M22 11.08V12a10 10 0 11-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/>
        </svg>
        <p class="text-[12px] leading-[1.5] font-medium {{ $bannerColor['text'] }}">
            {{ $bannerColor['msg'] }}
        </p>
    </div>
    @endif

    {{-- Progress bar --}}
    <div class="bg-white rounded-xl border border-[#E5E8EC] px-5 py-4 mb-5"
         x-data="{ total: {{ $total }}, dinilai: {{ $dinilai }} }"
         @progress-updated.window="dinilai = $event.detail.dinilai; total = $event.detail.total">
        <div class="flex items-center justify-between mb-2">
            <span class="text-[13px] font-semibold text-[#1a2a35]">Progress Penilaian Diri</span>
            <span class="text-[13px] font-semibold text-primary"><span x-text="dinilai"></span> / <span x-text="total"></span> Pertanyaan</span>
        </div>
        <div class="w-full h-2 bg-[#E5E8EC] rounded-full overflow-hidden">
            <div class="h-full bg-primary rounded-full transition-all duration-300"
                 :style="`width: ${total > 0 ? Math.round((dinilai / total) * 100) : 0}%`"></div>
        </div>
        <div class="text-[11px] text-[#8a9ba8] mt-1.5">
            @if (! $isDraf && $permohonan->status !== StatusPermohonanEnum::Diajukan)
                <span class="text-[#1e7e3e] font-medium">✓ Asesmen disubmit. Menunggu verifikasi bersama asesor.</span>
            @else
                <template x-if="dinilai >= total">
                    <span class="text-[#1e7e3e] font-medium">✓ Semua pertanyaan sudah dinilai. Lampirkan berkas pendukung, lalu ajukan permohonan.</span>
                </template>
                <template x-if="dinilai < total">
                    <span><span x-text="total - dinilai"></span> pertanyaan lagi perlu dinilai.</span>
                </template>
            @endif
        </div>
    </div>

    {{-- Info — belum diproses --}}
    @if ($permohonan->status === StatusPermohonanEnum::Diajukan)
    <div class="bg-[#F0F7FA] border border-[#C5DDE5] rounded-xl px-4 py-3.5 mb-5 flex gap-3">
        <svg class="w-4 h-4 text-primary shrink-0 mt-0.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/>
        </svg>
        <p class="text-[12px] text-[#1a2a35] leading-[1.6]">
            Pengajuan Anda sedang diproses oleh admin. Asesmen mandiri akan tersedia setelah admin memverifikasi dan menetapkan mata kuliah.
        </p>
    </div>
    @endif

    {{-- Tip berkas — hanya tampil saat bisa diisi --}}
    @if ($isDraf && $semuaBerkas->isEmpty())
    <div class="bg-[#FFF8E1] border border-[#FFE082] rounded-xl px-4 py-3 mb-5 flex items-center gap-3">
        <svg class="w-4 h-4 text-[#b45309] shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/>
        </svg>
        <p class="text-[12px] text-[#b45309] leading-[1.5]">
            Upload berkas pendukung Anda terlebih dahulu di menu
            <a href="{{ route('peserta.berkas.index') }}" class="font-semibold underline">Berkas Pendukung</a>,
            lalu lampirkan ke setiap pertanyaan di bawah.
        </p>
    </div>
    @endif

    {{-- Per MK --}}
    @foreach ($permohonan->rplMataKuliah as $rplMk)
    @php $mk = $rplMk->mataKuliah; @endphp
    <div class="bg-white rounded-xl border border-[#E5E8EC] overflow-hidden mb-4" wire:key="rplmk-{{ $rplMk->id }}">

        {{-- Header MK --}}
        @php
            $mkPtIds   = $mk->pertanyaan->pluck('id')->all();
            $mkDinilai = count(array_intersect($mkPtIds, array_keys($this->pertanyaanRatings)));
            $mkTotal   = count($mkPtIds);
        @endphp
        <div class="flex items-center gap-3 px-5 py-4 border-b border-[#F0F2F5]"
             x-data="{ dinilai: {{ $mkDinilai }}, total: {{ $mkTotal }} }"
             @rating-recorded.window="if ($event.detail.rplMkId == {{ $rplMk->id }} && $event.detail.isNew) dinilai++">
            <span class="text-[11px] font-semibold text-primary bg-[#E8F4F8] px-[8px] py-[4px] rounded shrink-0">{{ $mk->nama }}</span>
            <div class="flex-1">
                <div class="text-[11px] text-[#8a9ba8] max-w-lg">Keterampilan ini dapat diperoleh dari pengalaman kerja, pelatihan, sertifikasi, atau pendidikan formal. Dapat dibuktikan dengan transkrip, CV, sertifikat, surat keterangan, dll.</div>
            </div>
            <span class="text-[11px] font-medium shrink-0"
                  :class="dinilai >= total && total > 0 ? 'text-[#1e7e3e]' : 'text-[#8a9ba8]'"
                  x-text="`${dinilai}/${total}`">
            </span>
        </div>



        <div class="px-5 py-4">

            {{-- CPMK referensi — disembunyikan
            @if ($mk->cpmk->isNotEmpty())
            <div class="mb-4">
                <div class="text-[10px] font-semibold text-[#8a9ba8] uppercase tracking-[0.8px] mb-2">Capaian Pembelajaran (CPMK)</div>
                <div class="space-y-1.5">
                    @foreach ($mk->cpmk as $cpmk)
                    <div class="flex items-start gap-2" wire:key="cpmk-{{ $cpmk->id }}">
                        <span class="w-4 h-4 rounded-full bg-[#E8F4F8] text-primary text-[9px] font-semibold flex items-center justify-center shrink-0 mt-0.5">{{ $cpmk->urutan }}</span>
                        <span class="text-[12px] text-[#5a6a75] leading-[1.5]">{{ $cpmk->deskripsi }}</span>
                    </div>
                    @endforeach
                </div>
            </div>
            @endif
            --}}

            {{-- Pertanyaan penilaian diri --}}
            @if ($mk->pertanyaan->isEmpty())
                <div class="text-[12px] text-[#8a9ba8] py-2">Belum ada pertanyaan asesmen untuk mata kuliah ini.</div>
            @else
                {{-- <div class="text-[10px] font-semibold text-[#8a9ba8] uppercase tracking-[0.8px] mb-3">Sub CPMK & Bukti Pendukung</div> --}}
                @foreach ($mk->pertanyaan as $pt)
                @php $asm = $rplMk->asesmenMandiri->firstWhere('pertanyaan_id', $pt->id); @endphp
                <div class="py-3 border-b border-[#F6F8FA] last:border-0" wire:key="pt-{{ $pt->id }}">

                    {{-- Teks pertanyaan --}}
                    <div class="flex items-start gap-2 mb-2.5">
                        <span class="w-5 h-5 rounded-full bg-[#F0F2F5] text-[#5a6a75] text-[10px] font-semibold flex items-center justify-center shrink-0 mt-0.5">{{ $pt->urutan }}</span>
                        <span class="flex-1 text-[12px] text-[#1a2a35] leading-[1.5]">{{ $pt->pertanyaan }}</span>
                    </div>

                    {{-- Rating buttons --}}
                    @if ($isDraf)
                    {{-- Mode edit: Alpine tanpa x-data yang berubah on morph untuk hindari desync + Debounce API Call --}}
                    <div class="ml-7" x-data="{ 
                        sel: $wire.pertanyaanRatings[{{ $pt->id }}],
                        timer: null,
                        updateRating(nilai) {
                            this.sel = nilai;
                            clearTimeout(this.timer);
                            this.timer = setTimeout(() => {
                                $wire.saveRating({{ $rplMk->id }}, {{ $pt->id }}, nilai);
                            }, 500);
                        }
                    }">
                        <p class="text-[11px] text-[#8a9ba8] mb-2">Semakin besar angka yang dipilih, semakin Anda memahami kompetensi ini.</p>
                        <div class="flex flex-wrap gap-2 mb-2">
                            @foreach ([1, 2, 3, 4, 5] as $nilai)
                            <button type="button"
                                @click="updateRating({{ $nilai }})"
                                :class="sel == {{ $nilai }} ? 'bg-primary text-white border-primary' : 'bg-white text-[#5a6a75] border-[#D8DDE2] hover:border-primary hover:text-primary'"
                                class="w-10 h-10 rounded-lg text-[13px] font-semibold border transition-all flex items-center justify-center">
                                {{ $nilai }}
                            </button>
                            @endforeach
                        </div>

                        {{-- Berkas terlampir + tombol lampirkan (dalam scope Alpine agar muncul instan) --}}
                        <div class="flex items-center gap-2 flex-wrap">
                            @foreach ($buktiTerpilih[$pt->id] ?? [] as $namaBerkas)
                            <span class="inline-flex items-center gap-1.5 text-[11px] font-medium bg-[#E8F4F8] text-primary px-2.5 py-1 rounded-full" wire:key="chip-{{ $pt->id }}-{{ $loop->index }}">
                                <svg class="w-3 h-3 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M21.44 11.05l-9.19 9.19a6 6 0 01-8.49-8.49l9.19-9.19a4 4 0 015.66 5.66l-9.2 9.19a2 2 0 01-2.83-2.83l8.49-8.48"/>
                                </svg>
                                {{ Str::limit($namaBerkas, 24) }}
                                <button wire:click="removeBerkas('{{ $namaBerkas }}', {{ $pt->id }}, {{ $rplMk->id }})"
                                        class="ml-0.5 text-primary/50 hover:text-[#c62828] transition-colors leading-none"
                                        title="Hapus lampiran">
                                    <svg class="w-2.5 h-2.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round">
                                        <line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/>
                                    </svg>
                                </button>
                            </span>
                            @endforeach

                            {{-- Muncul setelah peserta mengisi rating --}}
                            <button type="button"
                                    x-show="sel" x-cloak
                                    x-transition:enter="transition ease-out duration-150"
                                    x-transition:enter-start="opacity-0 scale-90"
                                    x-transition:enter-end="opacity-100 scale-100"
                                    @click="modalBukti = { show: true, pertanyaanId: {{ $pt->id }}, rplMkId: {{ $rplMk->id }} }"
                                    class="inline-flex items-center gap-1 text-[11px] text-[#8a9ba8] hover:text-primary border border-dashed border-[#D8DDE2] hover:border-primary px-2.5 py-1 rounded-full transition-colors">
                                <svg class="w-3 h-3" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                                    <line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/>
                                </svg>
                                Lampirkan Berkas
                            </button>
                        </div>
                    </div>
                    @else
                    {{-- Mode baca: tampil static, tidak bisa diklik --}}
                    <div class="ml-7">
                        <div class="flex flex-wrap gap-2 mb-2">
                            @foreach ([1, 2, 3, 4, 5] as $nilai)
                            <span class="w-10 h-10 rounded-lg text-[13px] font-semibold border flex items-center justify-center
                                         {{ ($pertanyaanRatings[$pt->id] ?? null) === $nilai
                                             ? 'bg-primary text-white border-primary'
                                             : 'bg-[#F8FAFB] text-[#c5cdd5] border-[#EAEDF0]' }}">
                                {{ $nilai }}
                            </span>
                            @endforeach
                        </div>
                        @if (! empty($buktiTerpilih[$pt->id]))
                        <div class="flex items-center gap-2 flex-wrap">
                            @foreach ($buktiTerpilih[$pt->id] as $namaBerkas)
                            <span class="inline-flex items-center gap-1.5 text-[11px] font-medium bg-[#E8F4F8] text-primary px-2.5 py-1 rounded-full">
                                <svg class="w-3 h-3 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M21.44 11.05l-9.19 9.19a6 6 0 01-8.49-8.49l9.19-9.19a4 4 0 015.66 5.66l-9.2 9.19a2 2 0 01-2.83-2.83l8.49-8.48"/>
                                </svg>
                                {{ Str::limit($namaBerkas, 24) }}
                            </span>
                            @endforeach
                        </div>
                        @endif
                    </div>
                    @endif

                </div>
                @endforeach
            @endif

        </div>

        {{-- Toggle has_mk_sejenis dan MK Lampau Input di bawah pertanyaan --}}
        <div class="px-5 py-4 bg-[#FAFBFC] border-t border-[#F0F2F5]">
            <div class="flex items-center gap-2 mb-4 cursor-pointer select-none">
                <label class="inline-flex items-center gap-2 cursor-pointer">
                    <input type="checkbox"
                           wire:click="toggleMkSejenis({{ $rplMk->id }})"
                           @checked($hasMkSejenis[$rplMk->id] ?? false)
                           class="w-4 h-4 rounded accent-primary cursor-pointer">
                    <span class="text-[13px] text-[#1a2a35] font-semibold select-none">Saya pernah mengambil Mata Kuliah sejenis di PT Asal</span>
                </label>
                
                {{-- Info icon tooltip --}}
                <div class="group relative flex items-center cursor-help">
                    <svg class="w-4 h-4 text-[#8a9ba8]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <circle cx="12" cy="12" r="10"/><line x1="12" y1="16" x2="12" y2="12"/><line x1="12" y1="8" x2="12.01" y2="8"/>
                    </svg>
                    <div class="absolute bottom-full left-1/2 -translate-x-1/2 mb-2 w-48 p-2 bg-[#1a2a35] text-white text-[11px] leading-[1.4] rounded-lg opacity-0 invisible group-hover:opacity-100 group-hover:visible transition-all pointer-events-none z-10 text-center shadow-lg">
                        Diisi informasi mata kuliah sesuai dengan yang tertera pada transkrip nilai Anda di PT Asal.
                    </div>
                </div>
            </div>

            @if ($hasMkSejenis[$rplMk->id] ?? false)
            <div class="mt-4 space-y-3">
                <div class="flex items-end gap-2">
                    <div class="w-28">
                        <label class="block text-[10px] font-semibold text-[#8a9ba8] uppercase tracking-[0.5px] mb-1">Kode MK</label>
                        <input wire:model="matkulLampau.{{ $rplMk->id }}.kode_mk"
                               type="text" placeholder="mis. MK001"
                               class="w-full h-[42px] px-3 text-[13px] text-[#1a2a35] bg-white border border-[#E0E5EA] rounded-xl outline-none focus:border-primary focus:ring-2 focus:ring-primary/10 transition-all" />
                    </div>
                    <div class="flex-1">
                        <label class="block text-[10px] font-semibold text-[#8a9ba8] uppercase tracking-[0.5px] mb-1">Nama MK</label>
                        <input wire:model="matkulLampau.{{ $rplMk->id }}.nama_mk"
                               type="text" placeholder="Nama mata kuliah"
                               class="w-full h-[42px] px-3 text-[13px] text-[#1a2a35] bg-white border border-[#E0E5EA] rounded-xl outline-none focus:border-primary focus:ring-2 focus:ring-primary/10 transition-all" />
                    </div>
                    <div class="w-20">
                        <label class="block text-[10px] font-semibold text-[#8a9ba8] uppercase tracking-[0.5px] mb-1">SKS</label>
                        <input wire:model="matkulLampau.{{ $rplMk->id }}.sks"
                               type="number" min="1" max="20" placeholder="3"
                               class="w-full h-[42px] px-3 text-[13px] text-[#1a2a35] bg-white border border-[#E0E5EA] rounded-xl outline-none focus:border-primary focus:ring-2 focus:ring-primary/10 transition-all" />
                    </div>
                    {{-- Nilai Huruf dari Transkrip --}}
                    <div class="w-24">
                        <label class="block text-[10px] font-semibold text-[#8a9ba8] uppercase tracking-[0.5px] mb-1">Nilai</label>
                        <x-form.select
                            wire:model="matkulLampau.{{ $rplMk->id }}.nilai_huruf"
                            :options="array_combine(
                                array_column(App\Enums\NilaiHurufEnum::cases(), 'value'),
                                array_column(App\Enums\NilaiHurufEnum::cases(), 'value')
                            )"
                            placeholder="—"
                        />
                    </div>
                    <button wire:click="saveRow({{ $rplMk->id }})"
                            class="h-[42px] px-4 bg-primary hover:bg-[#005f78] text-white text-[12px] font-semibold rounded-xl transition-colors shrink-0">
                        Simpan
                    </button>
                </div>
                @foreach ($errors->get("matkulLampau.{$rplMk->id}.kode_mk") as $err)
                    <p class="text-[11px] text-[#c62828] mt-1">{{ $err }}</p>
                @endforeach
                @foreach ($errors->get("matkulLampau.{$rplMk->id}.nama_mk") as $err)
                    <p class="text-[11px] text-[#c62828] mt-1">{{ $err }}</p>
                @endforeach
                @foreach ($errors->get("matkulLampau.{$rplMk->id}.sks") as $err)
                    <p class="text-[11px] text-[#c62828] mt-1">{{ $err }}</p>
                @endforeach
                @foreach ($errors->get("matkulLampau.{$rplMk->id}.nilai_huruf") as $err)
                    <p class="text-[11px] text-[#c62828] mt-1">{{ $err }}</p>
                @endforeach
            </div>
            @endif
        </div>

    </div>
    @endforeach

    {{-- Navigasi bawah --}}
    <div class="flex items-center justify-between mt-2"
         x-data="{ total: {{ $total }}, dinilai: {{ $dinilai }} }"
         @progress-updated.window="dinilai = $event.detail.dinilai; total = $event.detail.total">
        <a href="{{ route('peserta.pengajuan.index') }}"
           class="text-[13px] text-[#5a6a75] hover:text-primary transition-colors no-underline">
            ← Kembali ke Daftar
        </a>
        @if ($isDraf)
            <button x-show="dinilai >= total" x-cloak
                    @click="modalAjukan = true"
                    class="bg-primary hover:bg-[#005f78] text-white text-[13px] font-semibold px-5 py-2.5 rounded-lg transition-colors">
                Submit Asesmen
            </button>
            <button x-show="dinilai < total" disabled
                    :title="(total - dinilai) + ' pertanyaan belum dinilai'"
                    class="bg-[#E5E8EC] text-[#8a9ba8] text-[13px] font-semibold px-5 py-2.5 rounded-lg cursor-not-allowed">
                Submit Asesmen
            </button>
        @elseif ($permohonan->status === StatusPermohonanEnum::Diajukan)
        <span class="text-[12px] font-medium px-3 py-1.5 rounded-lg bg-[#FFF8E1] text-[#b45309]">
            Menunggu admin memproses
        </span>
        @else
        <span class="text-[12px] font-medium px-3 py-1.5 rounded-lg {{ $permohonan->status->badgeClass() }}">
            {{ $permohonan->status->label() }}
        </span>
        @endif
    </div>

    {{-- Modal Konfirmasi Ajukan --}}
    <div x-show="modalAjukan" x-cloak class="fixed inset-0 z-50 flex items-center justify-center bg-black/40">
        <div class="bg-white rounded-2xl shadow-xl w-full max-w-sm mx-4 p-6">

            {{-- Icon --}}
            <div class="w-12 h-12 rounded-full bg-[#FFF8E1] flex items-center justify-center mb-4 mx-auto">
                <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="#b45309" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/>
                    <line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/>
                </svg>
            </div>

            {{-- Judul & pesan --}}
            <h3 class="text-[15px] font-semibold text-[#1a2a35] text-center mb-2">Submit Asesmen Mandiri?</h3>
            <p class="text-[12px] text-[#5a6a75] text-center leading-[1.6] mb-4">
                Apakah Anda yakin seluruh penilaian diri sudah benar?<br>
                Asesmen yang sudah disubmit <strong>tidak dapat diubah</strong>.
            </p>

            {{-- Ringkasan --}}
            <div class="bg-[#F8FAFB] rounded-xl px-4 py-3 mb-5 space-y-1.5">
                <div class="flex items-center justify-between text-[12px]">
                    <span class="text-[#8a9ba8]">Pertanyaan dinilai</span>
                    <span class="font-semibold text-[#1a2a35]">{{ $dinilai }} / {{ $total }}</span>
                </div>
                <div class="flex items-center justify-between text-[12px]">
                    <span class="text-[#8a9ba8]">Berkas diunggah</span>
                    <span class="font-semibold text-[#1a2a35]">{{ $semuaBerkas->count() }} berkas</span>
                </div>
            </div>

            {{-- Tombol --}}
            <div class="flex gap-3">
                <button @click="modalAjukan = false"
                        class="flex-1 h-[42px] bg-white border border-[#D8DDE2] text-[13px] font-semibold text-[#5a6a75] rounded-xl hover:bg-[#F8FAFB] transition-colors">
                    Periksa Lagi
                </button>
                <button wire:click="ajukan"
                        wire:loading.attr="disabled"
                        class="flex-1 h-[42px] bg-primary hover:bg-[#005f78] text-white text-[13px] font-semibold rounded-xl transition-colors disabled:opacity-60">
                    <span wire:loading.remove wire:target="ajukan">Ya, Submit</span>
                    <span wire:loading wire:target="ajukan">Menyimpan...</span>
                </button>
            </div>

        </div>
    </div>

    {{-- Modal Pilih Berkas --}}
    <div x-show="modalBukti.show" x-cloak class="fixed inset-0 z-50 flex items-center justify-center bg-black/40">
        <div class="bg-white rounded-2xl shadow-xl w-full max-w-md mx-4">

            {{-- Header --}}
            <div class="flex items-center justify-between px-5 py-4 border-b border-[#F0F2F5]">
                <h3 class="text-[14px] font-semibold text-[#1a2a35]">Pilih Berkas Pendukung</h3>
                <button @click="modalBukti.show = false"
                        class="text-[#8a9ba8] hover:text-[#1a2a35] transition-colors p-1">
                    <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/>
                    </svg>
                </button>
            </div>

            {{-- Body --}}
            <div class="px-5 py-4 max-h-[360px] overflow-y-auto">
                @if ($semuaBerkas->isEmpty())
                    <div class="py-8 text-center">
                        <p class="text-[12px] text-[#8a9ba8] mb-3">Belum ada berkas yang diunggah.</p>
                        <a href="{{ route('peserta.berkas.index') }}"
                           class="text-[12px] font-semibold text-primary hover:underline">
                            Upload berkas di menu "Berkas Pendukung" →
                        </a>
                    </div>
                @else
                    <p class="text-[11px] text-[#8a9ba8] mb-3">Pilih satu atau lebih berkas sebagai bukti pendukung jawaban ini.</p>
                    @foreach ($semuaBerkas as $dok)
                    <button @click="$wire.toggleBerkas({{ $dok->id }}, modalBukti.pertanyaanId, modalBukti.rplMkId)"
                            :class="($wire.buktiTerpilih[modalBukti.pertanyaanId] ?? []).includes({{ json_encode($dok->nama_dokumen) }})
                                ? 'border-primary bg-[#E8F4F8]'
                                : 'border-[#E5E8EC] hover:border-primary hover:bg-[#FAFBFC]'"
                            class="w-full flex items-center gap-3 p-3 rounded-xl mb-2 border transition-all text-left"
                            wire:key="modal-dok-{{ $dok->id }}">
                        <div :class="($wire.buktiTerpilih[modalBukti.pertanyaanId] ?? []).includes({{ json_encode($dok->nama_dokumen) }}) ? 'bg-primary' : 'bg-[#F0F2F5]'"
                             class="w-8 h-8 rounded-lg flex items-center justify-center shrink-0 transition-colors">
                            <svg :class="($wire.buktiTerpilih[modalBukti.pertanyaanId] ?? []).includes({{ json_encode($dok->nama_dokumen) }}) ? 'text-white' : 'text-[#5a6a75]'"
                                 class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V8z"/>
                                <polyline points="14 2 14 8 20 8"/>
                            </svg>
                        </div>
                        <div class="flex-1 min-w-0">
                            <div class="text-[12px] font-medium text-[#1a2a35] truncate">{{ $dok->nama_dokumen }}</div>
                            <div class="text-[11px] text-[#8a9ba8]">{{ $dok->jenis_dokumen->label() }}</div>
                        </div>
                        <svg x-show="($wire.buktiTerpilih[modalBukti.pertanyaanId] ?? []).includes({{ json_encode($dok->nama_dokumen) }})"
                             class="w-4 h-4 text-primary shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                            <polyline points="20 6 9 17 4 12"/>
                        </svg>
                    </button>
                    @endforeach
                @endif
            </div>

            {{-- Footer --}}
            <div class="px-5 py-4 border-t border-[#F0F2F5]">
                <button @click="modalBukti.show = false"
                        class="w-full h-[40px] bg-primary hover:bg-[#005f78] text-white text-[12px] font-semibold rounded-xl transition-colors">
                    Selesai
                </button>
            </div>

        </div>
    </div>

</div>
