<?php

use Livewire\Attributes\Layout;
use Livewire\Volt\Component;
use Livewire\WithFileUploads;
use App\Actions\Asesor\SelesaikanVerifikasiAction;
use App\Enums\JenisRplEnum;
use App\Enums\NilaiHurufEnum;
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
            'programStudi',
            'rplMataKuliah.mataKuliah',
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

    public function setStatusMk(int $rplMkId, string $status): void
    {
        $statusEnum = StatusRplMataKuliahEnum::from($status);
        $rplMk = RplMataKuliah::with('mataKuliah')->findOrFail($rplMkId);
        $rplMk->update([
            'status'     => $statusEnum,
            'sks_diakui' => $statusEnum === StatusRplMataKuliahEnum::Diakui ? ($rplMk->mataKuliah->sks ?? 0) : 0,
        ]);
        $this->permohonan->load(['rplMataKuliah.mataKuliah', 'rplMataKuliah.matkulLampau', 'verifikasiBersama']);
        $this->dispatch('notify-saved');
    }

    public function simpanNilai(int $rplMkId): void
    {
        $nilai = $this->nilaiTransfer[$rplMkId] ?? '';

        $this->validate([
            "nilaiTransfer.{$rplMkId}" => 'required|in:A,AB,B,BC,C,D,E',
        ], [], ["nilaiTransfer.{$rplMkId}" => 'nilai huruf']);

        $nilaiEnum = NilaiHurufEnum::from($nilai);

        $rplMk = RplMataKuliah::with(['mataKuliah', 'matkulLampau'])->findOrFail($rplMkId);
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
                    'catatan_asesor' => $this->catatanLampau[$ml->id]
                ]);
            }
        }

        $this->permohonan->refresh();
        $this->dispatch('notify-saved');
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
    <div class="bg-white rounded-[10px] border border-[#E5E8EC] p-5 mb-5">
        <div class="flex items-center justify-between gap-4">
            <div class="flex-1">
                <div class="text-[13px] font-semibold text-[#1a2a35]">{{ $permohonan->peserta->user->nama ?? '—' }}</div>
                <div class="text-[11px] text-[#8a9ba8] mt-0.5">
                    {{ $permohonan->nomor_permohonan }} &middot;
                    {{ $permohonan->programStudi->nama ?? '—' }} &middot;
                    <span class="font-semibold text-[#b45309]">Transfer Kredit (RPL I)</span>
                </div>
            </div>
            <a href="{{ route('export.hasil.word', $permohonan) }}"
               class="flex items-center gap-1.5 h-[32px] px-3 text-[11px] font-semibold text-primary border border-[#BDE0EB] rounded-lg hover:bg-[#E8F4F8] transition-colors no-underline shrink-0">
                <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M21 15v4a2 2 0 01-2 2H5a2 2 0 01-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/>
                </svg>
                Download Hasil (Word)
            </a>
        </div>
    </div>

    {{-- Rekognisi SKS --}}
    <x-pengajuan.sks-rekognisi :permohonan="$permohonan" />

    {{-- Verifikasi Bersama --}}
    @include('livewire.asesor.evaluasi.partials.verifikasi-bersama')

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
                        <div class="mt-6 flex items-center justify-between">
                            <div class="flex gap-2">
                                <button wire:click="setStatusMk({{ $rplMk->id }}, 'diakui')"
                                        class="h-[38px] px-5 text-[12px] font-semibold rounded-xl border-2 transition-all
                                               {{ $rplMk->status === StatusRplMataKuliahEnum::Diakui
                                                   ? 'bg-[#E6F4EA] border-[#1e7e3e] text-[#1e7e3e]'
                                                   : 'bg-white border-[#D0D5DD] text-[#5a6a75] hover:border-[#1e7e3e] hover:text-[#1e7e3e]' }}">
                                    ✓ Diakui
                                </button>
                                <button wire:click="setStatusMk({{ $rplMk->id }}, 'tidak_diakui')"
                                        class="h-[38px] px-5 text-[12px] font-semibold rounded-xl border-2 transition-all
                                               {{ $rplMk->status === StatusRplMataKuliahEnum::TidakDiakui
                                                   ? 'bg-[#FCE8E6] border-[#c62828] text-[#c62828]'
                                                   : 'bg-white border-[#D0D5DD] text-[#5a6a75] hover:border-[#c62828] hover:text-[#c62828]' }}">
                                    ✗ Tidak Diakui
                                </button>
                            </div>
                            <button wire:click="simpanNilai({{ $rplMk->id }})"
                                    class="h-[38px] px-5 bg-primary hover:bg-[#005f78] text-white text-[12px] font-semibold rounded-xl transition-all flex items-center gap-1.5">
                                Simpan Nilai
                            </button>
                        </div>
                    </div>
                </div>
                @elseif (! $rplMk->has_mk_sejenis)
                <div class="mt-5 pt-5 border-t border-[#F0F2F5]">
                    <p class="text-[12px] text-[#8a9ba8] italic mb-4 text-center">Peserta tidak memiliki MK sejenis di PT asal.</p>
                    <div class="flex justify-center gap-2">
                        <button wire:click="setStatusMk({{ $rplMk->id }}, 'diakui')"
                                class="h-[38px] px-5 text-[12px] font-semibold rounded-xl border-2 transition-all
                                       {{ $rplMk->status === StatusRplMataKuliahEnum::Diakui
                                           ? 'bg-[#E6F4EA] border-[#1e7e3e] text-[#1e7e3e]'
                                           : 'bg-white border-[#D0D5DD] text-[#5a6a75] hover:border-[#1e7e3e] hover:text-[#1e7e3e]' }}">
                            ✓ Diakui
                        </button>
                        <button wire:click="setStatusMk({{ $rplMk->id }}, 'tidak_diakui')"
                                class="h-[38px] px-5 text-[12px] font-semibold rounded-xl border-2 transition-all
                                       {{ $rplMk->status === StatusRplMataKuliahEnum::TidakDiakui
                                           ? 'bg-[#FCE8E6] border-[#c62828] text-[#c62828]'
                                           : 'bg-white border-[#D0D5DD] text-[#5a6a75] hover:border-[#c62828] hover:text-[#c62828]' }}">
                            ✗ Tidak Diakui
                        </button>
                    </div>
                </div>
                @endif
            </div>
        </div>
        @endforeach
    </div>

</div>
