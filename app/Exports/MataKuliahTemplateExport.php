<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class MataKuliahTemplateExport implements FromArray, WithHeadings, WithStyles, WithColumnWidths
{
    public function headings(): array
    {
        return [
            'Semester',
            'Nama Mata Kuliah',
            'Kode MK',
            'SKS',
            'Deskripsi (opsional)',
            'CPMK (pisahkan dengan ;)',
            'Pertanyaan Asesmen (pisahkan dengan ;)',
        ];
    }

    public function array(): array
    {
        return [
            [1, 'Algoritma dan Pemrograman', 'TI201', 3, 'Mata kuliah ini membahas dasar-dasar algoritma dan pemrograman.', 'Mampu menjelaskan konsep algoritma; Mampu menulis pseudocode', ''],
            [2, 'Basis Data', 'TI202', 3, '', 'Mampu merancang ERD; Mampu menulis query SQL', ''],
        ];
    }

    public function columnWidths(): array
    {
        return [
            'A' => 10,
            'B' => 35,
            'C' => 14,
            'D' => 8,
            'E' => 45,
            'F' => 55,
            'G' => 55,
        ];
    }

    public function styles(Worksheet $sheet): array
    {
        return [
            1 => [
                'font'      => ['bold' => true],
                'fill'      => [
                    'fillType'   => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                    'startColor' => ['rgb' => 'FFF2CC'],
                ],
                'alignment' => ['wrapText' => true, 'vertical' => 'center'],
            ],
        ];
    }
}
