<?php

namespace App\Enums;

enum JenisDokumenEnum: string
{
    case Cv                    = 'cv';
    case Ijazah                = 'ijazah';
    case Transkrip             = 'transkrip';
    case KeteranganMataKuliah  = 'keterangan_mata_kuliah';
    case Sertifikat            = 'sertifikat';
    case SuratKeterangan       = 'surat_keterangan';
    case Logbook               = 'logbook';
    case KaryaMonumental       = 'karya_monumental';
    case KeanggotaanProfesi    = 'keanggotaan_profesi';
    case DukunganAsosiasi      = 'dukungan_asosiasi';
    case BuktiPengalamanKerja  = 'bukti_pengalaman_kerja';
    case BuktiKeahlian         = 'bukti_keahlian';
    case PernyataanSejawat     = 'pernyataan_sejawat';
    case Pelatihan             = 'pelatihan';
    case WorkshopSeminar       = 'workshop_seminar';
    case KaryaPenghargaan      = 'karya_penghargaan';
    case Lainnya               = 'lainnya';

    /** @return array<string, string> Format: ['value' => 'label'] — untuk x-form.select */
    public static function options(): array
    {
        return array_column(
            array_map(fn($case) => [$case->value, $case->label()], self::cases()),
            1, 0
        );
    }

    public function label(): string
    {
        return match($this) {
            self::Cv                   => 'CV / Daftar Riwayat Hidup',
            self::Ijazah               => 'Ijazah',
            self::Transkrip            => 'Transkrip Nilai',
            self::KeteranganMataKuliah => 'Dokumen Keterangan Mata Kuliah',
            self::Sertifikat           => 'Sertifikat Kompetensi',
            self::SuratKeterangan      => 'Surat Keterangan Kerja',
            self::Logbook              => 'Logbook',
            self::KaryaMonumental      => 'Karya Monumental',
            self::KeanggotaanProfesi   => 'Keanggotaan Asosiasi Profesi',
            self::DukunganAsosiasi     => 'Surat Dukungan Asosiasi',
            self::BuktiPengalamanKerja => 'Bukti Pengalaman Kerja',
            self::BuktiKeahlian        => 'Bukti Keahlian / Pengetahuan',
            self::PernyataanSejawat    => 'Pernyataan Keahlian dari Sejawat',
            self::Pelatihan            => 'Sertifikat Pelatihan',
            self::WorkshopSeminar      => 'Workshop / Seminar / Simposium',
            self::KaryaPenghargaan     => 'Karya / Penghargaan',
            self::Lainnya              => 'Lainnya',
        };
    }

    public function wajib(): bool
    {
        return match($this) {
            self::Cv => true,
            default  => false,
        };
    }
}
