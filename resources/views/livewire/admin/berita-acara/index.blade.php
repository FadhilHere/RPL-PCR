<?php

use Livewire\Attributes\Layout;
use Livewire\Volt\Component;
use Illuminate\Support\Carbon;
use App\Enums\JenisRplEnum;
use App\Enums\StatusRplMataKuliahEnum;
use App\Enums\StatusVerifikasiEnum;
use App\Models\Asesor;
use App\Models\PermohonanRpl;
use App\Models\TahunAjaran;

new #[Layout('components.layouts.admin')] class extends Component {
    public string $filterAsesor = '';
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
        if ($this->filterAsesor === '' || $this->filterTahunAjaran === '') {
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
            ->whereHas('asesor', fn($q) => $q->where('asesor.id', (int) $this->filterAsesor))
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

                $jenisRpl = $permohonan->jenis_rpl;
                if (! $jenisRpl instanceof JenisRplEnum) {
                    $jenisRpl = is_string($jenisRpl) ? JenisRplEnum::tryFrom($jenisRpl) : null;
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
                    'jenis_rpl'           => $jenisRpl?->label() ?? '-',
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
        $asesorOptions = Asesor::query()
            ->with('user')
            ->whereHas('user', fn($q) => $q->where('aktif', true))
            ->get()
            ->sortBy(fn($a) => $a->user?->nama ?? '')
            ->mapWithKeys(fn($a) => [
                $a->id => ($a->user?->nama ?? '-') . ($a->nidn ? ' (NIDN ' . $a->nidn . ')' : ''),
            ])
            ->toArray();

        $tahunAjaranOptions = TahunAjaran::query()
            ->orderByDesc('id')
            ->pluck('nama', 'id')
            ->toArray();

        $downloadPdfUrl = null;
        $downloadWordUrl = null;
        if ($this->filterAsesor !== '') {
            $params = array_filter([
                'asesor_id'       => $this->filterAsesor,
                'tahun_ajaran_id' => $this->filterTahunAjaran ?: null,
                'tanggal_dari'    => $this->filterTanggalDari ?: null,
                'tanggal_sampai'  => $this->filterTanggalSampai ?: null,
            ], fn($v) => $v !== null && $v !== '');

            $downloadPdfUrl = route('berita-acara.dynamic.download', $params);
            $downloadWordUrl = route('berita-acara.dynamic.download.word', $params);
        }

        $previewRows = $this->buildPreviewRows();
        $totalPeserta = $previewRows->count();

        return compact('asesorOptions', 'tahunAjaranOptions', 'downloadPdfUrl', 'downloadWordUrl', 'previewRows', 'totalPeserta');
    }
}; ?>

<x-slot:title>Berita Acara</x-slot:title>
<x-slot:subtitle>Unduh berita acara asesmen per asesor berdasarkan tahun ajaran dan rentang tanggal asesi</x-slot:subtitle>

<div>
    <div class="bg-white rounded-[10px] border border-[#E5E8EC] p-5 mb-4">
        <div class="text-[13px] font-semibold text-[#1a2a35] mb-4">Download Berita Acara Per Asesor</div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-3 mb-3">
            <div>
                <label class="block text-[11px] font-semibold text-[#5a6a75] uppercase tracking-[0.7px] mb-1.5">Asesor</label>
                <x-form.select wire:model.live="filterAsesor"
                               :options="$asesorOptions"
                               placeholder="Pilih asesor..."
                               :searchable="true"
                               search-placeholder="Cari asesor..."
                               class="w-full" />
            </div>

            <div>
                <label class="block text-[11px] font-semibold text-[#5a6a75] uppercase tracking-[0.7px] mb-1.5">Tahun Ajaran</label>
                <x-form.select wire:model.live="filterTahunAjaran"
                               :options="$tahunAjaranOptions"
                               placeholder="Pilih tahun ajaran..."
                               class="w-full" />
            </div>

            <div class="flex items-end gap-2">
                @if ($downloadPdfUrl && $downloadWordUrl)
                <a href="{{ $downloadPdfUrl }}"
                   class="flex-1 h-[42px] bg-primary hover:bg-[#005f78] text-white text-[13px] font-semibold rounded-xl transition-colors no-underline inline-flex items-center justify-center">
                    Download PDF
                </a>
                <a href="{{ $downloadWordUrl }}"
                   class="flex-1 h-[42px] bg-white border border-[#D8DDE2] text-[#1a2a35] text-[13px] font-semibold rounded-xl hover:bg-[#F4F6F8] transition-colors no-underline inline-flex items-center justify-center">
                    Download Word
                </a>
                @else
                <button disabled
                        class="w-full h-[42px] bg-[#CFD8DC] text-white text-[13px] font-semibold rounded-xl cursor-not-allowed">
                    Pilih Asesor
                </button>
                @endif
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-[1fr_1fr_auto] gap-3">
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
            <div class="flex items-end">
                @if ($filterTanggalDari || $filterTanggalSampai)
                <button wire:click="clearTanggalFilter"
                        class="h-[42px] px-4 bg-white border border-[#D8DDE2] text-[#1a2a35] text-[13px] font-semibold rounded-xl hover:bg-[#F4F6F8] transition-colors">
                    Reset Tanggal
                </button>
                @endif
            </div>
        </div>

        <div class="mt-4 text-[12px] text-[#5a6a75]">
            Preview peserta sesuai filter: <span class="font-semibold text-[#1a2a35]">{{ $totalPeserta }}</span>
        </div>
    </div>

    <div class="bg-white rounded-[10px] border border-[#E5E8EC] overflow-hidden">
        <div class="px-5 py-3.5 border-b border-[#F0F2F5]">
            <div class="text-[13px] font-semibold text-[#1a2a35]">Preview Peserta</div>
            <div class="text-[11px] text-[#8a9ba8] mt-0.5">No, Nama Peserta, Jenis RPL, Total SKS Diperoleh, Tanggal Asesi, Keterangan Hadir</div>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full">
                <thead>
                    <tr class="border-b border-[#F0F2F5]">
                        <th class="text-left px-5 py-3 text-[11px] font-semibold text-[#8a9ba8] uppercase tracking-[0.5px] w-[60px]">No</th>
                        <th class="text-left px-4 py-3 text-[11px] font-semibold text-[#8a9ba8] uppercase tracking-[0.5px]">Nama Peserta</th>
                        <th class="text-left px-4 py-3 text-[11px] font-semibold text-[#8a9ba8] uppercase tracking-[0.5px] w-[200px]">Jenis RPL</th>
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
                        <td class="px-4 py-3.5 text-[13px] text-[#1a2a35]">{{ $row['jenis_rpl'] }}</td>
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
                        <td colspan="6" class="px-5 py-10 text-center text-[13px] text-[#8a9ba8]">
                            {{ $filterAsesor === '' ? 'Pilih asesor untuk menampilkan preview peserta.' : 'Tidak ada data peserta pada filter yang dipilih.' }}
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
