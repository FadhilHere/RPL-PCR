<?php

use Livewire\Attributes\Layout;
use Livewire\Volt\Component;
use App\Actions\Asesor\SelesaikanVerifikasiAction;
use App\Enums\JenisRplEnum;
use App\Enums\NilaiHurufEnum;
use App\Enums\StatusRplMataKuliahEnum;
use App\Models\PermohonanRpl;
use App\Models\RplMataKuliah;

new #[Layout('components.layouts.asesor')] class extends Component {
    public PermohonanRpl $permohonan;

    // nilaiTransfer[rpl_mk_id] = 'A'|'AB'|'B'|'BC'|'C'|'D'|'E'
    public array $nilaiTransfer = [];
    // mkCatatan[rpl_mk_id] = string
    public array $mkCatatan = [];

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
            $this->mkCatatan[$rplMk->id]     = $rplMk->catatan_asesor ?? '';
        }
    }

    public function simpanNilai(int $rplMkId): void
    {
        $nilai = $this->nilaiTransfer[$rplMkId] ?? '';

        $this->validate([
            "nilaiTransfer.{$rplMkId}" => 'required|in:A,AB,B,BC,C,D,E',
        ], [], ["nilaiTransfer.{$rplMkId}" => 'nilai huruf']);

        $nilaiEnum = NilaiHurufEnum::from($nilai);

        $rplMk = RplMataKuliah::with('mataKuliah')->findOrFail($rplMkId);
        $rplMk->update([
            'nilai_transfer'  => $nilaiEnum->value,
            'catatan_asesor'  => $this->mkCatatan[$rplMkId] ?? null,
            'status'          => $nilaiEnum->diakui()
                ? StatusRplMataKuliahEnum::Diakui
                : StatusRplMataKuliahEnum::TidakDiakui,
            'sks_diakui'      => $nilaiEnum->diakui() ? ($rplMk->mataKuliah->sks ?? 0) : 0,
        ]);

        $this->dispatch('notify-saved');
    }

    public function selesaikanVerifikasi(string $catatanHasil = '', SelesaikanVerifikasiAction $action): void
    {
        $action->execute($this->permohonan, null, $catatanHasil);

        $this->permohonan->load('verifikasiBersama');
        $this->permohonan->refresh();
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

<div x-data="{ saved: false }" @notify-saved.window="saved = true; setTimeout(() => saved = false, 2000)">

    {{-- Notif --}}
    <div x-show="saved" x-transition style="display:none"
         class="fixed bottom-5 right-5 z-50 bg-[#1a2a35] text-white text-[12px] font-medium px-4 py-2.5 rounded-xl shadow-lg">
        Tersimpan
    </div>

    {{-- Info Peserta --}}
    <div class="bg-white rounded-[10px] border border-[#E5E8EC] p-5 mb-5">
        <div class="flex items-center justify-between">
            <div>
                <div class="text-[13px] font-semibold text-[#1a2a35]">{{ $permohonan->peserta->user->nama ?? '—' }}</div>
                <div class="text-[11px] text-[#8a9ba8] mt-0.5">
                    {{ $permohonan->nomor_permohonan }} &middot;
                    {{ $permohonan->programStudi->nama ?? '—' }} &middot;
                    <span class="font-semibold text-[#b45309]">Transfer Kredit (RPL I)</span>
                </div>
            </div>
        </div>
    </div>

    {{-- List MK --}}
    <div class="space-y-4 mb-6">
        @foreach ($permohonan->rplMataKuliah as $rplMk)
        <div class="bg-white rounded-[10px] border border-[#E5E8EC] overflow-hidden" wire:key="mk-{{ $rplMk->id }}">
            {{-- Header MK --}}
            <div class="px-5 py-3.5 border-b border-[#F0F2F5]">
                <div class="text-[13px] font-semibold text-[#1a2a35]">{{ $rplMk->mataKuliah->nama }}</div>
                <div class="text-[11px] text-[#8a9ba8]">{{ $rplMk->mataKuliah->kode }} &middot; {{ $rplMk->mataKuliah->sks }} SKS</div>
            </div>

            <div class="p-5">
                {{-- MK Lampau peserta (jika ada) --}}
                @if ($rplMk->has_mk_sejenis && $rplMk->matkulLampau->isNotEmpty())
                <div class="mb-4">
                    <p class="text-[11px] font-semibold text-[#5a6a75] uppercase tracking-[0.7px] mb-2">MK di PT Asal</p>
                    <div class="bg-[#F4F6F8] rounded-lg overflow-hidden">
                        <table class="w-full text-[12px]">
                            <thead>
                                <tr class="border-b border-[#E5E8EC]">
                                    <th class="text-left font-semibold text-[#8a9ba8] px-3 py-2">Kode</th>
                                    <th class="text-left font-semibold text-[#8a9ba8] px-3 py-2">Nama MK</th>
                                    <th class="text-center font-semibold text-[#8a9ba8] px-3 py-2">SKS</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($rplMk->matkulLampau as $ml)
                                <tr class="border-b border-[#EFF1F3] last:border-0">
                                    <td class="px-3 py-2 text-[#5a6a75]">{{ $ml->kode_mk }}</td>
                                    <td class="px-3 py-2 font-medium text-[#1a2a35]">{{ $ml->nama_mk }}</td>
                                    <td class="px-3 py-2 text-center text-[#5a6a75]">{{ $ml->sks }}</td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
                @elseif (! $rplMk->has_mk_sejenis)
                <div class="mb-4 text-[12px] text-[#8a9ba8] italic">Peserta tidak memiliki MK sejenis di PT asal.</div>
                @endif

                {{-- Input Nilai Huruf --}}
                <div class="flex flex-wrap items-end gap-4">
                    <div>
                        <label class="block text-[11px] font-semibold text-[#5a6a75] uppercase tracking-[0.7px] mb-2">Nilai Huruf</label>
                        <div class="flex gap-1.5">
                            @foreach ($nilaiHurufOptions as $opt)
                            <button type="button"
                                    wire:click="$set('nilaiTransfer.{{ $rplMk->id }}', '{{ $opt->value }}')"
                                    class="w-10 h-10 rounded-lg text-[12px] font-bold border transition-all
                                           {{ ($nilaiTransfer[$rplMk->id] ?? '') === $opt->value
                                               ? 'bg-primary border-primary text-white'
                                               : 'border-[#D0D5DD] text-[#5a6a75] hover:border-primary hover:text-primary' }}">
                                {{ $opt->value }}
                            </button>
                            @endforeach
                        </div>
                        @error("nilaiTransfer.{$rplMk->id}") <p class="mt-1 text-[11px] text-[#c62828]">{{ $message }}</p> @enderror
                    </div>
                    <div class="flex-1 min-w-[200px]">
                        <label class="block text-[11px] font-semibold text-[#5a6a75] uppercase tracking-[0.7px] mb-2">Catatan</label>
                        <input wire:model="mkCatatan.{{ $rplMk->id }}" type="text" placeholder="Catatan asesor (opsional)"
                               class="w-full h-[40px] px-3 text-[12px] text-[#1a2a35] bg-white border border-[#E0E5EA] rounded-lg outline-none focus:border-primary focus:ring-1 focus:ring-primary/10" />
                    </div>
                    <button wire:click="simpanNilai({{ $rplMk->id }})"
                            class="h-[40px] px-4 bg-primary hover:bg-[#005f78] text-white text-[12px] font-semibold rounded-lg transition-colors shrink-0">
                        Simpan
                    </button>

                    {{-- Status badge --}}
                    @if ($rplMk->nilai_transfer)
                    <span class="text-[10px] font-semibold px-2.5 py-1 rounded-full
                          {{ NilaiHurufEnum::from($rplMk->nilai_transfer)->diakui() ? 'bg-[#E6F4EA] text-[#1e7e3e]' : 'bg-[#FCE8E6] text-[#c62828]' }}">
                        {{ NilaiHurufEnum::from($rplMk->nilai_transfer)->diakui() ? 'Diakui' : 'Tidak Diakui' }}
                    </span>
                    @endif
                </div>
            </div>
        </div>
        @endforeach
    </div>

    {{-- Download Word --}}
    <div class="flex justify-end mb-4">
        <a href="{{ route('export.transfer.word', $permohonan) }}"
           class="flex items-center gap-1.5 h-[36px] px-3.5 text-[12px] font-semibold text-[#1557b0] border border-[#AABFDE] rounded-lg hover:bg-[#E8F0FE] transition-colors no-underline">
            <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M21 15v4a2 2 0 01-2 2H5a2 2 0 01-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/>
            </svg>
            Download Word (.docx)
        </a>
    </div>

    {{-- Selesaikan Verifikasi --}}
    @if ($permohonan->verifikasiBersama->isEmpty())
    <div class="bg-white rounded-[10px] border border-[#E5E8EC] p-5">
        <div class="text-[13px] font-semibold text-[#1a2a35] mb-3">Selesaikan Verifikasi</div>
        <div x-data="{ catatan: '' }">
            <label class="block text-[11px] font-semibold text-[#5a6a75] uppercase tracking-[0.7px] mb-1.5">Catatan Hasil (opsional)</label>
            <textarea x-model="catatan" rows="2" placeholder="Catatan hasil evaluasi transfer kredit..."
                      class="w-full px-3 py-2.5 text-[12px] text-[#1a2a35] bg-white border border-[#E0E5EA] rounded-xl outline-none focus:border-primary focus:ring-2 focus:ring-primary/10 resize-none mb-3"></textarea>
            <button @click="$wire.selesaikanVerifikasi(catatan)"
                    class="h-[42px] px-5 bg-[#1e7e3e] hover:bg-[#155a2c] text-white text-[13px] font-semibold rounded-xl transition-colors">
                Selesaikan Verifikasi
            </button>
        </div>
    </div>
    @else
    <div class="bg-[#E6F4EA] border border-[#A8D5B5] rounded-[10px] px-5 py-4 text-[13px] font-semibold text-[#1e7e3e]">
        Verifikasi telah diselesaikan.
    </div>
    @endif

</div>
