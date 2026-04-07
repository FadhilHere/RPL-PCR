<?php

use Livewire\Attributes\Layout;
use Livewire\Volt\Component;
use App\Models\MatkulLampau;
use App\Models\PermohonanRpl;
use App\Models\RplMataKuliah;
use App\Enums\JenisRplEnum;

new #[Layout('components.layouts.peserta')] class extends Component {
    public PermohonanRpl $permohonan;

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
        }
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

<div>
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
                {{-- Toggle has_mk_sejenis --}}
                <label class="flex items-center gap-2 cursor-pointer select-none">
                    <input type="checkbox"
                           wire:click="toggleMkSejenis({{ $rplMk->id }})"
                           @checked($hasMkSejenis[$rplMk->id] ?? false)
                           class="w-4 h-4 rounded accent-primary">
                    <span class="text-[12px] text-[#5a6a75]">Pernah ambil MK sejenis</span>
                </label>
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

            {{-- MK Lampau Input (hanya jika has_mk_sejenis) --}}
            @if ($hasMkSejenis[$rplMk->id] ?? false)
            <div class="p-5">
                <p class="text-[11px] font-semibold text-[#5a6a75] uppercase tracking-[0.7px] mb-3">Mata Kuliah di PT Asal</p>
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
                        <p class="text-[11px] text-[#c62828]">{{ $err }}</p>
                    @endforeach
                    @foreach ($errors->get("matkulLampau.{$rplMk->id}.nama_mk") as $err)
                        <p class="text-[11px] text-[#c62828]">{{ $err }}</p>
                    @endforeach
                    @foreach ($errors->get("matkulLampau.{$rplMk->id}.sks") as $err)
                        <p class="text-[11px] text-[#c62828]">{{ $err }}</p>
                    @endforeach
                    @foreach ($errors->get("matkulLampau.{$rplMk->id}.nilai_huruf") as $err)
                        <p class="text-[11px] text-[#c62828]">{{ $err }}</p>
                    @endforeach
                </div>
            </div>
            @else
            <div class="px-5 py-3 text-[12px] text-[#8a9ba8] italic">
                Tidak ada MK sejenis di PT asal — asesor akan menilai langsung berdasarkan berkas.
            </div>
            @endif
        </div>
        @endforeach
    </div>

    <div class="mt-6">
        <a href="{{ route('peserta.pengajuan.index') }}"
           class="inline-flex items-center h-[42px] px-5 bg-primary hover:bg-[#005f78] text-white text-[13px] font-semibold rounded-xl transition-colors no-underline">
            Selesai
        </a>
    </div>
</div>
