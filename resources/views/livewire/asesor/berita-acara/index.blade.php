<?php

use Livewire\Attributes\Layout;
use Livewire\Volt\Component;
use App\Models\TahunAjaran;

new #[Layout('components.layouts.asesor')] class extends Component {
    public string $filterTahunAjaran = '';
    public string $filterTanggalDari = '';
    public string $filterTanggalSampai = '';

    public function mount(): void
    {
        $this->filterTahunAjaran = (string) (
            TahunAjaran::aktif()->value('id')
            ?? TahunAjaran::query()->orderByDesc('id')->value('id')
            ?? ''
        );
    }

    public function clearTanggalFilter(): void
    {
        $this->filterTanggalDari = '';
        $this->filterTanggalSampai = '';
    }

    public function with(): array
    {
        $tahunAjaranOptions = TahunAjaran::query()
            ->orderByDesc('id')
            ->pluck('nama', 'id')
            ->toArray();

        $query = array_filter([
            'tahun_ajaran_id' => $this->filterTahunAjaran ?: null,
            'tanggal_dari'    => $this->filterTanggalDari ?: null,
            'tanggal_sampai'  => $this->filterTanggalSampai ?: null,
        ], fn($v) => $v !== null && $v !== '');

        $downloadPdfUrl = route('berita-acara.dynamic.download', $query);
        $downloadWordUrl = route('berita-acara.dynamic.download.word', $query);
        $asesorNama = auth()->user()->asesor?->user?->nama ?? '-';

        return compact('tahunAjaranOptions', 'downloadPdfUrl', 'downloadWordUrl', 'asesorNama');
    }
}; ?>

<x-slot:title>Berita Acara</x-slot:title>
<x-slot:subtitle>Unduh berita acara per asesor berdasarkan data jadwal verifikasi</x-slot:subtitle>

<div>
    <div class="bg-white rounded-[10px] border border-[#E5E8EC] p-5 mb-5">
        <div class="text-[13px] font-semibold text-[#1a2a35] mb-4">Download Berita Acara Asesor</div>

        <div class="grid grid-cols-1 lg:grid-cols-4 gap-3">
            <div>
                <label class="block text-[11px] font-semibold text-[#5a6a75] uppercase tracking-[0.7px] mb-1.5">Asesor</label>
                <div class="h-[42px] px-3.5 rounded-xl border border-[#E0E5EA] bg-[#F8FBFC] text-[13px] text-[#1a2a35] flex items-center">
                    {{ $asesorNama }}
                </div>
            </div>

            <div>
                <label class="block text-[11px] font-semibold text-[#5a6a75] uppercase tracking-[0.7px] mb-1.5">Tahun Ajaran</label>
                <x-form.select wire:model.live="filterTahunAjaran"
                               :options="$tahunAjaranOptions"
                               placeholder="Pilih tahun ajaran..."
                               class="w-full" />
            </div>

            <div>
                <label class="block text-[11px] font-semibold text-[#5a6a75] uppercase tracking-[0.7px] mb-1.5">Tanggal Asesi Dari</label>
                <x-form.date-picker x-model="$wire.filterTanggalDari"
                                    placeholder="Pilih tanggal mulai..."
                                    :enable-time="false"
                                    class="w-full" />
            </div>

            <div>
                <label class="block text-[11px] font-semibold text-[#5a6a75] uppercase tracking-[0.7px] mb-1.5">Tanggal Asesi Sampai</label>
                <x-form.date-picker x-model="$wire.filterTanggalSampai"
                                    placeholder="Pilih tanggal akhir..."
                                    :enable-time="false"
                                    class="w-full" />
            </div>

            <div class="flex items-end gap-2">
                @if ($filterTanggalDari || $filterTanggalSampai)
                <button wire:click="clearTanggalFilter"
                        class="h-[42px] px-4 bg-white border border-[#D8DDE2] text-[#1a2a35] text-[13px] font-semibold rounded-xl hover:bg-[#F4F6F8] transition-colors">
                    Reset Tanggal
                </button>
                @endif
                <a href="{{ $downloadPdfUrl }}"
                   class="h-[42px] px-5 bg-primary hover:bg-[#005f78] text-white text-[13px] font-semibold rounded-xl transition-colors no-underline inline-flex items-center">
                    Download PDF
                </a>
                <a href="{{ $downloadWordUrl }}"
                   class="h-[42px] px-5 bg-white border border-[#D8DDE2] text-[#1a2a35] text-[13px] font-semibold rounded-xl hover:bg-[#F4F6F8] transition-colors no-underline inline-flex items-center">
                    Download Word
                </a>
            </div>
        </div>
    </div>

    <div class="bg-white rounded-[10px] border border-[#E5E8EC] px-5 py-4 text-[12px] text-[#5a6a75] leading-[1.65]">
        <div class="font-semibold text-[#1a2a35] mb-1">Format BA yang diunduh</div>
        <div>Tabel berisi: No, Nama Peserta, Total SKS Diperoleh, Tanggal Asesi, Keterangan Hadir.</div>
        <div>Keterangan Hadir dipetakan otomatis: <span class="font-semibold">Selesai = Hadir</span>, <span class="font-semibold">Terjadwal = Belum Asesmen</span>.</div>
    </div>
</div>
