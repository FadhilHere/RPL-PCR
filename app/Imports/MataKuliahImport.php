<?php

namespace App\Imports;

use App\Models\Cpmk;
use App\Models\MataKuliah;
use App\Models\Pertanyaan;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\ToCollection;

class MataKuliahImport implements ToCollection
{
    private int $prodiId;
    private int $created = 0;
    private int $updated = 0;
    private int $failed  = 0;
    private array $errors = [];

    public function __construct(int $prodiId)
    {
        $this->prodiId = $prodiId;
    }

    /**
     * Column index:
     * 0 = Semester, 1 = Nama MK, 2 = Kode MK, 3 = SKS,
     * 4 = Deskripsi, 5 = CPMK (;-separated), 6 = Pertanyaan (;-separated)
     */
    public function collection(Collection $rows): void
    {
        foreach ($rows as $index => $row) {
            // Skip header row
            if ($index === 0) {
                continue;
            }

            $rowNumber = $index + 1;

            // Skip empty rows
            if ($row->filter()->isEmpty()) {
                continue;
            }

            $semester  = trim($row[0] ?? '');
            $nama      = trim($row[1] ?? '');
            $kode      = trim($row[2] ?? '');
            $sks       = trim($row[3] ?? '');
            $deskripsi = trim($row[4] ?? '');
            $cpmkRaw   = trim($row[5] ?? '');
            $pertRaw   = trim($row[6] ?? '');

            // Validate
            $rowErrors = [];
            if ($kode === '') {
                $rowErrors[] = 'Kode MK wajib diisi';
            } elseif (strlen($kode) > 20) {
                $rowErrors[] = 'Kode MK maksimal 20 karakter';
            }
            if ($nama === '') {
                $rowErrors[] = 'Nama Mata Kuliah wajib diisi';
            } elseif (strlen($nama) > 255) {
                $rowErrors[] = 'Nama Mata Kuliah maksimal 255 karakter';
            }
            if ($sks === '' || ! is_numeric($sks) || (int) $sks < 1 || (int) $sks > 20) {
                $rowErrors[] = 'SKS harus antara 1-20';
            }
            if ($semester === '' || ! is_numeric($semester) || (int) $semester < 1 || (int) $semester > 8) {
                $rowErrors[] = 'Semester harus antara 1-8';
            }

            if ($rowErrors) {
                $this->errors[] = ['row' => $rowNumber, 'messages' => $rowErrors];
                $this->failed++;
                continue;
            }

            try {
                DB::transaction(function () use ($kode, $nama, $sks, $semester, $deskripsi, $cpmkRaw, $pertRaw) {
                    $existing = MataKuliah::where('program_studi_id', $this->prodiId)
                        ->where('kode', $kode)
                        ->first();

                    if ($existing) {
                        $existing->update([
                            'nama'      => $nama,
                            'sks'       => (int) $sks,
                            'semester'  => (int) $semester,
                            'deskripsi' => $deskripsi ?: null,
                        ]);
                        $mk = $existing;
                        $this->updated++;
                    } else {
                        $mk = MataKuliah::create([
                            'program_studi_id' => $this->prodiId,
                            'kode'             => $kode,
                            'nama'             => $nama,
                            'sks'              => (int) $sks,
                            'semester'         => (int) $semester,
                            'deskripsi'        => $deskripsi ?: null,
                            'bisa_rpl'         => true,
                        ]);
                        $this->created++;
                    }

                    // CPMK: replace all
                    if ($cpmkRaw !== '') {
                        $mk->cpmk()->delete();
                        $cpmkItems = array_values(array_filter(array_map('trim', explode(';', $cpmkRaw))));
                        foreach ($cpmkItems as $i => $deskripsi) {
                            Cpmk::create([
                                'mata_kuliah_id' => $mk->id,
                                'deskripsi'      => $deskripsi,
                                'urutan'         => $i + 1,
                            ]);
                        }
                    }

                    // Pertanyaan: only replace if column non-empty
                    if ($pertRaw !== '') {
                        // Check if existing pertanyaan have asesmen_mandiri children
                        $hasAsesmen = $mk->pertanyaan()
                            ->whereHas('asesmenMandiri')
                            ->exists();

                        if (! $hasAsesmen) {
                            $mk->pertanyaan()->delete();
                            $pertItems = array_values(array_filter(array_map('trim', explode(';', $pertRaw))));
                            foreach ($pertItems as $i => $teks) {
                                Pertanyaan::create([
                                    'mata_kuliah_id' => $mk->id,
                                    'pertanyaan'     => $teks,
                                    'urutan'         => $i + 1,
                                ]);
                            }
                        }
                        // If hasAsesmen, silently skip pertanyaan replacement
                    }
                });
            } catch (\Throwable $e) {
                $this->errors[] = ['row' => $rowNumber, 'messages' => [$e->getMessage()]];
                $this->failed++;
            }
        }
    }

    public function getSummary(): array
    {
        return [
            'created' => $this->created,
            'updated' => $this->updated,
            'failed'  => $this->failed,
        ];
    }

    public function getErrors(): array
    {
        return $this->errors;
    }
}
