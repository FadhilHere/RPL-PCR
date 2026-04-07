<?php

namespace App\Exports;

use App\Enums\JenisRplEnum;
use App\Enums\StatusRplMataKuliahEnum;
use App\Models\PermohonanRpl;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class ResumeAsesmenExport implements FromCollection, WithHeadings, WithTitle, WithStyles
{
    public function __construct(
        private readonly ?int $prodiId = null,
        private readonly ?int $asesorId = null,
    ) {}

    public function collection(): Collection
    {
        $query = PermohonanRpl::with([
            'peserta.user',
            'programStudi',
            'tahunAjaran',
            'rplMataKuliah.mataKuliah',
        ])->whereNotIn('status', ['draf', 'diajukan']);

        if ($this->prodiId) {
            $query->where('program_studi_id', $this->prodiId);
        }

        if ($this->asesorId) {
            $query->whereHas('asesor', fn($q) => $q->where('asesor_id', $this->asesorId));
        }

        $rows = collect();
        $no   = 1;

        foreach ($query->get() as $p) {
            $sksDiakui = $p->rplMataKuliah
                ->where('status', StatusRplMataKuliahEnum::Diakui)
                ->sum(fn($m) => $m->mataKuliah->sks ?? 0);

            $sksTidakDiakui = $p->rplMataKuliah
                ->where('status', StatusRplMataKuliahEnum::TidakDiakui)
                ->sum(fn($m) => $m->mataKuliah->sks ?? 0);

            $rows->push([
                'No'              => $no++,
                'Nomor Permohonan'=> $p->nomor_permohonan,
                'Nama Peserta'    => $p->peserta?->user?->nama ?? '—',
                'Program Studi'   => $p->programStudi?->nama ?? '—',
                'Tahun Ajaran'    => $p->tahunAjaran?->nama ?? '—',
                'Jenis RPL'       => $p->jenis_rpl?->label() ?? '—',
                'Status'          => $p->status->label(),
                'SKS Diakui'      => $sksDiakui,
                'SKS Tidak Diakui'=> $sksTidakDiakui,
                'Total MK'        => $p->rplMataKuliah->count(),
                'MK Diakui'       => $p->rplMataKuliah->where('status', StatusRplMataKuliahEnum::Diakui)->count(),
                'Tanggal Pengajuan'=> $p->tanggal_pengajuan?->format('d/m/Y') ?? '—',
            ]);
        }

        return $rows;
    }

    public function headings(): array
    {
        return [
            'No',
            'Nomor Permohonan',
            'Nama Peserta',
            'Program Studi',
            'Tahun Ajaran',
            'Jenis RPL',
            'Status',
            'SKS Diakui',
            'SKS Tidak Diakui',
            'Total MK',
            'MK Diakui',
            'Tanggal Pengajuan',
        ];
    }

    public function title(): string
    {
        return 'Resume Asesmen';
    }

    public function styles(Worksheet $sheet): array
    {
        return [
            1 => [
                'font' => ['bold' => true],
                'fill' => [
                    'fillType'   => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                    'startColor' => ['rgb' => '004B5F'],
                ],
                'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
            ],
        ];
    }
}
