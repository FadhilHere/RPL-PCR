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

    // matkulLampau[rpl_mk_id] = [['kode_mk'=>'', 'nama_mk'=>'', 'sks'=>''], ...]
    public array $matkulLampau = [];

    public function mount(PermohonanRpl $permohonan): void
    {
        abort_if($permohonan->peserta_id !== auth()->user()->peserta?->id, 403);
        abort_if($permohonan->jenis_rpl !== JenisRplEnum::RplI, 403, 'Hanya untuk pengajuan Transfer Kredit.');

        $this->permohonan = $permohonan->load([
            'programStudi',
            'rplMataKuliah.mataKuliah',
            'rplMataKuliah.matkulLampau',
        ]);

        foreach ($this->permohonan->rplMataKuliah as $rplMk) {
            $this->hasMkSejenis[$rplMk->id] = $rplMk->has_mk_sejenis;

            $lampau = $rplMk->matkulLampau->map(fn($ml) => [
                'id'      => $ml->id,
                'kode_mk' => $ml->kode_mk,
                'nama_mk' => $ml->nama_mk,
                'sks'     => (string) $ml->sks,
            ])->toArray();

            $this->matkulLampau[$rplMk->id] = $lampau ?: [['id' => null, 'kode_mk' => '', 'nama_mk' => '', 'sks' => '']];
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
            $this->matkulLampau[$rplMkId] = [['id' => null, 'kode_mk' => '', 'nama_mk' => '', 'sks' => '']];
        }
    }

    public function addRow(int $rplMkId): void
    {
        $this->matkulLampau[$rplMkId][] = ['id' => null, 'kode_mk' => '', 'nama_mk' => '', 'sks' => ''];
    }

    public function removeRow(int $rplMkId, int $index): void
    {
        $row = $this->matkulLampau[$rplMkId][$index] ?? null;
        if ($row && $row['id']) {
            MatkulLampau::destroy($row['id']);
        }
        array_splice($this->matkulLampau[$rplMkId], $index, 1);

        if (empty($this->matkulLampau[$rplMkId])) {
            $this->matkulLampau[$rplMkId] = [['id' => null, 'kode_mk' => '', 'nama_mk' => '', 'sks' => '']];
        }
    }

    public function saveRow(int $rplMkId, int $index): void
    {
        $row = $this->matkulLampau[$rplMkId][$index] ?? null;
        if (! $row) return;

        $this->validate([
            "matkulLampau.{$rplMkId}.{$index}.kode_mk" => 'required|string|max:20',
            "matkulLampau.{$rplMkId}.{$index}.nama_mk" => 'required|string|max:255',
            "matkulLampau.{$rplMkId}.{$index}.sks"     => 'required|integer|min:1|max:20',
        ], [], [
            "matkulLampau.{$rplMkId}.{$index}.kode_mk" => 'kode MK',
            "matkulLampau.{$rplMkId}.{$index}.nama_mk" => 'nama MK',
            "matkulLampau.{$rplMkId}.{$index}.sks"     => 'SKS',
        ]);

        $ml = MatkulLampau::updateOrCreate(
            ['id' => $row['id'] ?: 0],
            [
                'rpl_mata_kuliah_id' => $rplMkId,
                'kode_mk'            => $row['kode_mk'],
                'nama_mk'            => $row['nama_mk'],
                'sks'                => (int) $row['sks'],
            ]
        );

        $this->matkulLampau[$rplMkId][$index]['id'] = $ml->id;
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

            {{-- MK Lampau Input (hanya jika has_mk_sejenis) --}}
            @if ($hasMkSejenis[$rplMk->id] ?? false)
            <div class="p-5">
                <p class="text-[11px] font-semibold text-[#5a6a75] uppercase tracking-[0.7px] mb-3">Mata Kuliah di PT Asal</p>
                <div class="space-y-3">
                    @foreach ($matkulLampau[$rplMk->id] ?? [] as $i => $row)
                    <div class="flex items-end gap-2" wire:key="row-{{ $rplMk->id }}-{{ $i }}">
                        <div class="w-28">
                            <label class="block text-[10px] font-semibold text-[#8a9ba8] uppercase tracking-[0.5px] mb-1">Kode MK</label>
                            <input wire:model="matkulLampau.{{ $rplMk->id }}.{{ $i }}.kode_mk"
                                   type="text" placeholder="mis. MK001"
                                   class="w-full h-[38px] px-2.5 text-[12px] text-[#1a2a35] bg-white border border-[#E0E5EA] rounded-lg outline-none focus:border-primary focus:ring-1 focus:ring-primary/10" />
                        </div>
                        <div class="flex-1">
                            <label class="block text-[10px] font-semibold text-[#8a9ba8] uppercase tracking-[0.5px] mb-1">Nama MK</label>
                            <input wire:model="matkulLampau.{{ $rplMk->id }}.{{ $i }}.nama_mk"
                                   type="text" placeholder="Nama mata kuliah"
                                   class="w-full h-[38px] px-2.5 text-[12px] text-[#1a2a35] bg-white border border-[#E0E5EA] rounded-lg outline-none focus:border-primary focus:ring-1 focus:ring-primary/10" />
                        </div>
                        <div class="w-16">
                            <label class="block text-[10px] font-semibold text-[#8a9ba8] uppercase tracking-[0.5px] mb-1">SKS</label>
                            <input wire:model="matkulLampau.{{ $rplMk->id }}.{{ $i }}.sks"
                                   type="number" min="1" max="20" placeholder="3"
                                   class="w-full h-[38px] px-2.5 text-[12px] text-[#1a2a35] bg-white border border-[#E0E5EA] rounded-lg outline-none focus:border-primary focus:ring-1 focus:ring-primary/10" />
                        </div>
                        <button wire:click="saveRow({{ $rplMk->id }}, {{ $i }})"
                                class="h-[38px] px-3 bg-primary hover:bg-[#005f78] text-white text-[11px] font-semibold rounded-lg transition-colors shrink-0">
                            Simpan
                        </button>
                        @if (count($matkulLampau[$rplMk->id] ?? []) > 1)
                        <button wire:click="removeRow({{ $rplMk->id }}, {{ $i }})"
                                class="h-[38px] w-[38px] flex items-center justify-center border border-[#D0D5DD] text-[#c62828] hover:bg-[#FCE8E6] rounded-lg transition-colors shrink-0">
                            <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
                        </button>
                        @endif
                    </div>
                    @foreach ($errors->get("matkulLampau.{$rplMk->id}.*.kode_mk") as $err)
                        <p class="text-[11px] text-[#c62828]">{{ $err }}</p>
                    @endforeach
                    @foreach ($errors->get("matkulLampau.{$rplMk->id}.*.nama_mk") as $err)
                        <p class="text-[11px] text-[#c62828]">{{ $err }}</p>
                    @endforeach
                    @foreach ($errors->get("matkulLampau.{$rplMk->id}.*.sks") as $err)
                        <p class="text-[11px] text-[#c62828]">{{ $err }}</p>
                    @endforeach
                    @endforeach
                </div>
                <button wire:click="addRow({{ $rplMk->id }})"
                        class="mt-3 flex items-center gap-1.5 text-[11px] font-semibold text-primary hover:text-[#005f78] transition-colors">
                    <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
                    Tambah MK Asal
                </button>
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
