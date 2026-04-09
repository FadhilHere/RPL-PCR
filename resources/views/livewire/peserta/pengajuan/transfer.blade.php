<?php

use Livewire\Attributes\Layout;
use Livewire\Volt\Component;
use App\Models\MatkulLampau;
use App\Models\PermohonanRpl;
use App\Models\RplMataKuliah;
use App\Models\AsesmenMandiri;
use App\Enums\JenisRplEnum;
use App\Enums\StatusPermohonanEnum;

new #[Layout('components.layouts.peserta')] class extends Component {
    public PermohonanRpl $permohonan;

    // pertanyaanRatings[pertanyaan_id] = nilai (1-5)
    public array $pertanyaanRatings = [];

    // hasMkSejenis[rpl_mk_id] = bool
    public array $hasMkSejenis = [];

    // matkulLampau[rpl_mk_id] = ['id'=>'', 'kode_mk'=>'', 'nama_mk'=>'', 'sks'=>'', 'nilai_huruf'=>'']
    public array $matkulLampau = [];

    public function mount(PermohonanRpl $permohonan): void
    {
        abort_if($permohonan->peserta_id !== auth()->user()->peserta?->id, 403);
        abort_if($permohonan->jenis_rpl !== JenisRplEnum::RplI, 403, 'Hanya untuk pengajuan Transfer Kredit.');

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
            }
        }
    }

    private function canEdit(): bool
    {
        return in_array($this->permohonan->status, [
                StatusPermohonanEnum::Diproses,
                StatusPermohonanEnum::Asesmen,
                StatusPermohonanEnum::Verifikasi,
            ]) && $this->permohonan->asesmen_submitted_at === null;
    }

    #[\Livewire\Attributes\Renderless]
    public function saveRating(int $rplMkId, int $pertanyaanId, int $nilai): void
    {
        abort_if(! $this->canEdit(), 422);
        abort_if($nilai < 1 || $nilai > 5, 422);

        $this->pertanyaanRatings[$pertanyaanId] = $nilai;

        AsesmenMandiri::updateOrCreate(
            ['rpl_mata_kuliah_id' => $rplMkId, 'pertanyaan_id' => $pertanyaanId],
            ['penilaian_diri' => $nilai]
        );
        $this->dispatch('progress-updated', dinilai: $this->totalDinilai(), total: $this->totalPertanyaan());
    }

    public function toggleMkSejenis(int $rplMkId): void
    {
        abort_if(! $this->canEdit(), 403);
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
        abort_if(! $this->canEdit(), 403);
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
        abort_if(! $this->canEdit(), 422);

        $this->permohonan->update(['asesmen_submitted_at' => now()]);

        $this->redirect(route('peserta.pengajuan.index'), navigate: true);
    }

    public function with(): array
    {
        return [];
    }
}; ?>

<x-slot:title>Input MK Lampau — Transfer Kredit</x-slot:title>
<x-slot:subtitle>
    <a href="{{ route('peserta.pengajuan.index') }}" class="text-primary hover:underline">Pengajuan RPL</a>
    &rsaquo; {{ $permohonan->nomor_permohonan }} &rsaquo; MK Lampau
</x-slot:subtitle>

<div x-data="{ modalAjukan: false, showToast: false }"
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
        $isDraf      = $this->canEdit();
        $total       = $this->totalPertanyaan();
        $dinilai     = $this->totalDinilai();
        $progress    = $total > 0 ? round($dinilai / $total * 100) : 0;
    @endphp

    {{-- Banner read-only (asesmen sudah disubmit / belum bisa diisi) --}}
    @if (! $isDraf)
    @php
        $bannerColor = match(true) {
            $permohonan->status === StatusPermohonanEnum::Diajukan
                => ['bg' => 'bg-[#FFF8E1] border-[#FFE082]', 'text' => 'text-[#b45309]', 'icon' => '#b45309',
                   'msg' => 'Menunggu admin memproses pengajuan — asesmen belum dapat diisi.'],
            $permohonan->asesmen_submitted_at !== null
                => ['bg' => 'bg-[#E6F4EA] border-[#A8D5B5]', 'text' => 'text-[#1e7e3e]', 'icon' => '#1e7e3e',
                   'msg' => 'Asesmen mandiri telah disubmit — jawaban tidak dapat diubah.'],
            default
                => ['bg' => 'bg-[#F0F7FA] border-[#C5DDE5]', 'text' => 'text-primary', 'icon' => '#004B5F',
                   'msg' => 'Permohonan telah selesai diproses.'],
        };
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
            @if (! $isDraf && $permohonan->asesmen_submitted_at !== null)
                <span class="text-[#1e7e3e] font-medium">✓ Asesmen disubmit. Menunggu verifikasi bersama asesor.</span>
            @else
                <template x-if="dinilai >= total">
                    <span class="text-[#1e7e3e] font-medium">✓ Semua pertanyaan sudah dinilai. Anda dapat mensubmit asesmen.</span>
                </template>
                <template x-if="dinilai < total">
                    <span><span x-text="total - dinilai"></span> pertanyaan lagi perlu dinilai.</span>
                </template>
            @endif
        </div>
    </div>

    {{-- Info --}}
    <div class="flex gap-3 bg-[#FFF8E1] border border-[#FFD54F] rounded-xl px-4 py-3.5 mb-6">
        <svg class="w-4 h-4 text-[#b45309] shrink-0 mt-0.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <path d="M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/>
        </svg>
        <div>
            <p class="text-[12px] font-semibold text-[#1a2a35]">Pengajuan Transfer Kredit (RPL I)</p>
            <p class="text-[12px] text-[#5a6a75] mt-0.5 leading-[1.5]">
                Untuk setiap mata kuliah, centang jika Anda pernah mengambil mata kuliah sejenis di perguruan tinggi asal, lalu isi datanya. Asesor akan menilai dengan nilai huruf A–C.
            </p>
        </div>
    </div>

    {{-- List MK --}}
    <div class="space-y-4">
        @foreach ($permohonan->rplMataKuliah as $rplMk)
        <div class="bg-white rounded-[10px] border border-[#E5E8EC] overflow-hidden" wire:key="mk-{{ $rplMk->id }}">
            {{-- MK Header --}}
            <div class="flex items-center justify-between px-5 py-3.5 border-b border-[#F0F2F5]">
                <div>
                    <div class="text-[13px] font-semibold text-[#1a2a35]">{{ $rplMk->mataKuliah->nama }}</div>
                    <div class="text-[11px] text-[#8a9ba8]">{{ $rplMk->mataKuliah->kode }} &middot; {{ $rplMk->mataKuliah->sks }} SKS</div>
                </div>
            </div>

            {{-- CPMK --}}
            @if ($rplMk->mataKuliah->cpmk->isNotEmpty())
            <div class="px-5 pt-4 pb-3 bg-[#FAFBFC] border-b border-[#F0F2F5]">
                <div class="text-[10px] font-semibold text-[#8a9ba8] uppercase tracking-[0.8px] mb-2">Capaian Pembelajaran (CPMK)</div>
                <div class="space-y-2">
                    @foreach ($rplMk->mataKuliah->cpmk as $cpmk)
                    <div class="flex items-start gap-2" wire:key="cpmk-{{ $cpmk->id }}">
                        <span class="w-4 h-4 rounded-full bg-[#E8F4F8] text-primary text-[9px] font-semibold flex items-center justify-center shrink-0 mt-0.5">{{ $cpmk->urutan }}</span>
                        <span class="text-[12px] text-[#5a6a75] leading-[1.5]">{{ $cpmk->deskripsi }}</span>
                    </div>
                    @endforeach
                </div>
            </div>
            @endif

            {{-- Pertanyaan penilaian diri --}}
            @if ($rplMk->mataKuliah->pertanyaan->isEmpty())
                <div class="px-5 py-3 text-[12px] text-[#8a9ba8]">Belum ada pertanyaan asesmen untuk mata kuliah ini.</div>
            @else
                <div class="px-5 py-3">
                @foreach ($rplMk->mataKuliah->pertanyaan as $pt)
                <div class="py-3 border-b border-[#F6F8FA] last:border-0" wire:key="pt-{{ $pt->id }}">
                    <div class="flex items-start gap-2 mb-2.5">
                        <span class="w-5 h-5 rounded-full bg-[#F0F2F5] text-[#5a6a75] text-[10px] font-semibold flex items-center justify-center shrink-0 mt-0.5">{{ $pt->urutan }}</span>
                        <span class="flex-1 text-[12px] text-[#1a2a35] leading-[1.5]">{{ $pt->pertanyaan }}</span>
                    </div>
                    @if ($isDraf)
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
                    </div>
                    @else
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
                    </div>
                    @endif
                </div>
                @endforeach
                </div>
            @endif

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
                <div class="space-y-3">
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

                {{-- Catatan Asesor (read-only, hanya muncul jika ada) --}}
                @if ($rplMk->catatan_asesor)
                <div class="mt-4 bg-[#F0F7FA] border border-[#C5DDE5] rounded-xl px-4 py-3">
                    <div class="text-[10px] font-semibold text-primary uppercase tracking-[0.7px] mb-1">Catatan Asesor — MK Tujuan</div>
                    <div class="text-[12px] text-[#1a2a35] leading-[1.6] [&_ol]:list-decimal [&_ol]:pl-5 [&_ul]:list-disc [&_ul]:pl-5 [&_li]:mb-0.5 [&_b]:font-bold [&_i]:italic [&_u]:underline">{!! $rplMk->catatan_asesor !!}</div>
                </div>
                @endif

                @foreach ($rplMk->matkulLampau as $ml)
                    @if ($ml->catatan_asesor)
                    <div class="mt-3 bg-[#FFF8E1] border border-[#FFE082] rounded-xl px-4 py-3">
                        <div class="text-[10px] font-semibold text-[#b45309] uppercase tracking-[0.7px] mb-1">Catatan Asesor — MK Lampau ({{ $ml->kode_mk }})</div>
                        <div class="text-[12px] text-[#1a2a35] leading-[1.6] [&_ol]:list-decimal [&_ol]:pl-5 [&_ul]:list-disc [&_ul]:pl-5 [&_li]:mb-0.5 [&_b]:font-bold [&_i]:italic [&_u]:underline">{!! $ml->catatan_asesor !!}</div>
                    </div>
                    @endif
                @endforeach
            </div>
        </div>
        @endforeach
    </div>

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
        @elseif ($permohonan->asesmen_submitted_at !== null)
        <span class="text-[12px] font-medium px-3 py-1.5 rounded-lg bg-[#E6F4EA] text-[#1e7e3e]">
            ✓ Asesmen Disubmit
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
            <div class="w-12 h-12 rounded-full bg-[#FFF8E1] flex items-center justify-center mb-4 mx-auto">
                <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="#b45309" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/>
                    <line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/>
                </svg>
            </div>
            <h3 class="text-[15px] font-semibold text-[#1a2a35] text-center mb-2">Submit Asesmen Mandiri?</h3>
            <p class="text-[12px] text-[#5a6a75] text-center leading-[1.6] mb-4">
                Apakah Anda yakin seluruh penilaian diri sudah benar?<br>
                Asesmen yang sudah disubmit <strong>tidak dapat diubah</strong>.
            </p>
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
</div>
