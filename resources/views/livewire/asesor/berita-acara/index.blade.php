<?php

use Livewire\Attributes\Layout;
use Livewire\Volt\Component;
use Illuminate\Support\Carbon;
use App\Enums\StatusRplMataKuliahEnum;
use App\Enums\StatusVerifikasiEnum;
use App\Models\PermohonanRpl;
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

    private function buildPreviewRows()
    {
        $asesorId = auth()->user()->asesor?->id;
        if (! $asesorId || $this->filterTahunAjaran === '') {
            return collect();
        }

        return PermohonanRpl::query()
            ->with([
                'peserta.user',
                'rplMataKuliah.mataKuliah',
                'verifikasiBersama' => fn($q) => $q
                    ->whereIn('status', [StatusVerifikasiEnum::Terjadwal->value, StatusVerifikasiEnum::Selesai->value])
                    ->when($this->filterTanggalDari, fn($qq) => $qq->whereDate('jadwal', '>=', $this->filterTanggalDari))
                    ->when($this->filterTanggalSampai, fn($qq) => $qq->whereDate('jadwal', '<=', $this->filterTanggalSampai))
                    ->orderByDesc('jadwal')
                    ->orderByDesc('id'),
            ])
            ->where('tahun_ajaran_id', (int) $this->filterTahunAjaran)
            ->whereHas('asesor', fn($q) => $q->where('asesor.id', $asesorId))
            ->whereHas('verifikasiBersama', function ($q) {
                $q->whereIn('status', [StatusVerifikasiEnum::Terjadwal->value, StatusVerifikasiEnum::Selesai->value])
                    ->when($this->filterTanggalDari, fn($qq) => $qq->whereDate('jadwal', '>=', $this->filterTanggalDari))
                    ->when($this->filterTanggalSampai, fn($qq) => $qq->whereDate('jadwal', '<=', $this->filterTanggalSampai));
            })
            ->get()
            ->map(function (PermohonanRpl $permohonan) {
                $verifikasi = $permohonan->verifikasiBersama->first();
                if (! $verifikasi || ! $verifikasi->jadwal) {
                    return null;
                }

                $statusVerifikasi = $verifikasi->status instanceof StatusVerifikasiEnum
                    ? $verifikasi->status
                    : StatusVerifikasiEnum::tryFrom((string) $verifikasi->status);

                $totalSks = $permohonan->rplMataKuliah
                    ->filter(function ($m) {
                        $statusMataKuliah = $m->status instanceof StatusRplMataKuliahEnum
                            ? $m->status
                            : StatusRplMataKuliahEnum::tryFrom((string) $m->status);

                        return $statusMataKuliah === StatusRplMataKuliahEnum::Diakui;
                    })
                    ->sum(fn($m) => $m->mataKuliah->sks ?? 0);

                $keteranganHadir = match ($statusVerifikasi) {
                    StatusVerifikasiEnum::Selesai => 'Hadir',
                    StatusVerifikasiEnum::Terjadwal => 'Belum Asesmen',
                    default => '-',
                };

                return [
                    'nama_peserta'        => $permohonan->peserta?->user?->nama ?? '—',
                    'total_sks_diperoleh' => $totalSks,
                    'tanggal_asesi'       => Carbon::parse($verifikasi->jadwal),
                    'keterangan_hadir'    => $keteranganHadir,
                ];
            })
            ->filter()
            ->sortBy(fn($row) => $row['tanggal_asesi']->timestamp)
            ->values();
    }

    public function with(): array
    {
        $tahunAjaranOptions = TahunAjaran::query()
            ->orderByDesc('id')
            ->pluck('nama', 'id')
            ->toArray();

        $downloadQuery = array_filter([
            'tahun_ajaran_id' => $this->filterTahunAjaran ?: null,
            'tanggal_dari'    => $this->filterTanggalDari ?: null,
            'tanggal_sampai'  => $this->filterTanggalSampai ?: null,
        ], fn($v) => $v !== null && $v !== '');

        $downloadPdfUrl = route('berita-acara.dynamic.download', $downloadQuery);
        $downloadWordUrl = route('berita-acara.dynamic.download.word', $downloadQuery);
        $asesorNama = auth()->user()->asesor?->user?->nama ?? '-';
        $previewRows = $this->buildPreviewRows();
        $totalPeserta = $previewRows->count();

        return compact('tahunAjaranOptions', 'downloadPdfUrl', 'downloadWordUrl', 'asesorNama', 'previewRows', 'totalPeserta');
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
                <x-form.date-picker wire:model.live="filterTanggalDari"
                                    placeholder="Pilih tanggal mulai..."
                                    :enable-time="false"
                                    class="w-full" />
            </div>

            <div>
                <label class="block text-[11px] font-semibold text-[#5a6a75] uppercase tracking-[0.7px] mb-1.5">Tanggal Asesi Sampai</label>
                <x-form.date-picker wire:model.live="filterTanggalSampai"
                                    placeholder="Pilih tanggal akhir..."
                                    :enable-time="false"
                                    class="w-full" />
            </div>
        </div>

        <div class="mt-4 pt-4 border-t border-[#EEF1F4] flex flex-wrap items-center justify-between gap-3">
            <div class="inline-flex items-center gap-2 px-3 py-2 rounded-xl bg-[#F4F7F9] border border-[#E6EBEF] text-[12px] text-[#4b5b67]">
                <span class="font-medium">Peserta Terfilter:</span>
                <span class="font-bold text-[#1a2a35]">{{ $totalPeserta }}</span>
            </div>

            <div class="flex flex-wrap items-center gap-2">
                @if ($filterTanggalDari || $filterTanggalSampai)
                <button type="button"
                        wire:click="clearTanggalFilter"
                        class="h-[42px] px-4 rounded-xl border border-[#E3E7EB] bg-white text-[#33424d] text-[13px] font-semibold hover:bg-[#F6F8FA] transition-colors">
                    Reset Tanggal
                </button>
                @endif
                <a href="{{ $downloadPdfUrl }}"
                   class="h-[42px] px-5 rounded-xl bg-primary text-white text-[13px] font-semibold hover:bg-[#005f78] transition-colors no-underline inline-flex items-center gap-2">
                    Download PDF
                </a>
                <a href="{{ $downloadWordUrl }}"
                   class="h-[42px] px-5 rounded-xl border border-[#D8DDE2] bg-white text-[#1a2a35] text-[13px] font-semibold hover:bg-[#F4F6F8] transition-colors no-underline inline-flex items-center gap-2">
                    Download Word
                </a>
            </div>
        </div>
    </div>

    <div class="bg-white rounded-[10px] border border-[#E5E8EC] overflow-hidden mb-5">
        <div class="px-5 py-3.5 border-b border-[#F0F2F5]">
            <div class="text-[13px] font-semibold text-[#1a2a35]">Peserta Dalam Filter Anda</div>
            <div class="text-[11px] text-[#8a9ba8] mt-0.5">Tabel diperbarui otomatis ketika filter tanggal/tahun diubah.</div>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full">
                <thead>
                    <tr class="border-b border-[#F0F2F5]">
                        <th class="text-left px-5 py-3 text-[11px] font-semibold text-[#8a9ba8] uppercase tracking-[0.5px] w-[60px]">No</th>
                        <th class="text-left px-4 py-3 text-[11px] font-semibold text-[#8a9ba8] uppercase tracking-[0.5px]">Nama Peserta</th>
                        <th class="text-left px-4 py-3 text-[11px] font-semibold text-[#8a9ba8] uppercase tracking-[0.5px] w-[170px]">Total SKS</th>
                        <th class="text-left px-4 py-3 text-[11px] font-semibold text-[#8a9ba8] uppercase tracking-[0.5px] w-[200px]">Tanggal Asesi</th>
                        <th class="text-left px-4 py-3 text-[11px] font-semibold text-[#8a9ba8] uppercase tracking-[0.5px] w-[170px]">Keterangan Hadir</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($previewRows as $idx => $row)
                    <tr class="border-b border-[#F6F8FA] last:border-0 hover:bg-[#FAFBFC] transition-colors">
                        <td class="px-5 py-3.5 text-[12px] text-[#8a9ba8]">{{ $idx + 1 }}</td>
                        <td class="px-4 py-3.5 text-[13px] text-[#1a2a35] font-medium">{{ $row['nama_peserta'] }}</td>
                        <td class="px-4 py-3.5 text-[13px] text-[#1a2a35]">{{ $row['total_sks_diperoleh'] }} SKS</td>
                        <td class="px-4 py-3.5 text-[13px] text-[#1a2a35]">{{ $row['tanggal_asesi']->locale('id')->isoFormat('D MMMM YYYY') }}</td>
                        <td class="px-4 py-3.5">
                            <span class="inline-flex text-[10px] font-semibold px-2 py-0.5 rounded-full {{ $row['keterangan_hadir'] === 'Hadir' ? 'bg-[#E6F4EA] text-[#1e7e3e]' : 'bg-[#FFF8E1] text-[#b45309]' }}">
                                {{ $row['keterangan_hadir'] }}
                            </span>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="5" class="px-5 py-10 text-center text-[13px] text-[#8a9ba8]">
                            Tidak ada peserta pada filter yang dipilih.
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="bg-white rounded-[10px] border border-[#E5E8EC] px-5 py-4 text-[12px] text-[#5a6a75] leading-[1.65]">
        <div class="font-semibold text-[#1a2a35] mb-1">Format BA yang diunduh</div>
        <div>Tabel berisi: No, Nama Peserta, Total SKS Diperoleh, Tanggal Asesi, Keterangan Hadir.</div>
        <div>Keterangan Hadir dipetakan otomatis: <span class="font-semibold">Selesai = Hadir</span>, <span class="font-semibold">Terjadwal = Belum Asesmen</span>.</div>
    </div>
</div>
