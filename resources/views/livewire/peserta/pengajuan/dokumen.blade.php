<?php

use Livewire\Attributes\Layout;
use Livewire\Volt\Component;
use App\Enums\JenisDokumenEnum;
use App\Enums\StatusPermohonanEnum;
use App\Models\PermohonanRpl;
use App\Models\DokumenBukti;

new #[Layout('components.layouts.peserta')] class extends Component {
    public PermohonanRpl $permohonan;

    public function mount(PermohonanRpl $permohonan): void
    {
        abort_if($permohonan->peserta_id !== auth()->user()->peserta?->id, 403);
        $this->permohonan = $permohonan;
    }

    public function with(): array
    {
        $peserta = auth()->user()->peserta;

        return [
            'dokumenList'  => $peserta
                ? DokumenBukti::where('peserta_id', $peserta->id)->latest()->get()
                : collect(),
            'jenisOptions' => JenisDokumenEnum::options(),
        ];
    }

    public function ajukan(): void
    {
        abort_if($this->permohonan->status !== StatusPermohonanEnum::Draf, 422);

        $this->permohonan->update([
            'status'            => StatusPermohonanEnum::Diajukan,
            'tanggal_pengajuan' => now(),
        ]);

        $this->redirect(route('peserta.pengajuan.index'), navigate: true);
    }

}; ?>

<x-slot:title>Ajukan Permohonan</x-slot:title>
<x-slot:subtitle><a href="{{ route('peserta.pengajuan.index') }}" class="text-primary hover:underline">Pengajuan RPL</a> &rsaquo; {{ $permohonan->nomor_permohonan }}</x-slot:subtitle>

<div x-data="{ showConfirm: false }">


    {{-- Ringkasan berkas --}}
    <div class="bg-white rounded-xl border border-[#E5E8EC] overflow-hidden mb-5">
        <div class="flex items-center justify-between px-5 py-3.5 border-b border-[#F0F2F5]">
            <div class="text-[13px] font-semibold text-[#1a2a35]">Berkas Pendukung</div>
            <span class="text-[11px] text-[#8a9ba8]">{{ count($dokumenList) }} berkas</span>
        </div>

        @forelse ($dokumenList as $dok)
        <div class="flex items-center gap-3.5 px-5 py-3.5 border-b border-[#F6F8FA] last:border-0" wire:key="dok-{{ $dok->id }}">
            <div class="w-9 h-9 rounded-lg bg-[#E8F4F8] flex items-center justify-center shrink-0">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#004B5F" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V8z"/>
                    <polyline points="14 2 14 8 20 8"/>
                </svg>
            </div>
            <div class="flex-1 min-w-0">
                <div class="text-[12px] font-medium text-[#1a2a35] truncate">{{ $dok->nama_dokumen }}</div>
                <div class="text-[11px] text-[#8a9ba8]">{{ $jenisOptions[$dok->jenis_dokumen] ?? $dok->jenis_dokumen }}</div>
            </div>
        </div>
        @empty
        <div class="py-8 text-center">
            <p class="text-[13px] text-[#8a9ba8] mb-3">Belum ada berkas yang diunggah.</p>
            <a href="{{ route('peserta.berkas.index') }}"
               class="text-[13px] font-semibold text-primary hover:underline no-underline">
                Upload berkas di menu "Berkas Pendukung" →
            </a>
        </div>
        @endforelse
    </div>

    {{-- Tombol aksi --}}
    <div class="flex items-center justify-between">
        <a href="{{ route('peserta.pengajuan.asesmen', $permohonan->id) }}"
           class="text-[13px] text-[#5a6a75] hover:text-primary transition-colors no-underline">
            ← Kembali ke Asesmen
        </a>

        @if ($permohonan->status === StatusPermohonanEnum::Draf)
        <button @click="showConfirm = true"
                class="bg-primary hover:bg-[#005f78] text-white text-[13px] font-semibold px-5 py-2.5 rounded-lg transition-colors
                       {{ count($dokumenList) === 0 ? 'opacity-50 cursor-not-allowed' : '' }}"
                @if (count($dokumenList) === 0) disabled @endif>
            Ajukan Permohonan →
        </button>
        @else
        <span class="text-[12px] font-medium px-3 py-1.5 rounded-lg bg-[#E8F0FE] text-[#1557b0]">
            Permohonan sudah diajukan
        </span>
        @endif
    </div>

    {{-- Modal Konfirmasi Ajukan (Alpine) --}}
    <div x-show="showConfirm"
         x-cloak
         x-transition.opacity
         class="fixed inset-0 z-50 flex items-center justify-center bg-black/40"
         @click.self="showConfirm = false">
        <div class="bg-white rounded-2xl shadow-xl w-full max-w-sm p-6 mx-4">
            <div class="w-10 h-10 rounded-full bg-[#E8F4F8] flex items-center justify-center mb-4 mx-auto">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#004B5F" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M21 15v4a2 2 0 01-2 2H5a2 2 0 01-2-2v-4"/>
                    <polyline points="7 10 12 15 17 10"/>
                    <line x1="12" y1="15" x2="12" y2="3"/>
                </svg>
            </div>
            <p class="text-[14px] text-[#1a2a35] text-center mb-6 leading-relaxed">
                Ajukan permohonan? Setelah diajukan, Anda tidak dapat mengubah data asesmen dan berkas.
            </p>
            <div class="flex gap-3">
                <button @click="showConfirm = false"
                    class="flex-1 h-[42px] bg-white border border-[#D8DDE2] text-[13px] font-semibold text-[#5a6a75] rounded-xl hover:bg-[#F8FAFB] transition-colors">
                    Batal
                </button>
                <button @click="showConfirm = false" wire:click="ajukan"
                        wire:loading.attr="disabled"
                        class="flex-1 h-[42px] bg-primary hover:bg-[#005f78] text-white text-[13px] font-semibold rounded-xl transition-colors disabled:opacity-60">
                    <span wire:loading.remove wire:target="ajukan">Ya, Ajukan</span>
                    <span wire:loading wire:target="ajukan">Mengajukan...</span>
                </button>
            </div>
        </div>
    </div>

</div>
