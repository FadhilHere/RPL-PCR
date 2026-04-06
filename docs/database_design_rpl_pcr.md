# Database Design: Sistem Asesmen RPL PCR

> Tech Stack: Laravel + Tailwind + Livewire + Alpine.js | Diperbarui: 2026-03-20

---

## TEMUAN DARI EXCEL `Mata Kuliah RPL PCR_2026.xlsx`

File Excel ini adalah **template import resmi** yang akan digunakan untuk memasukkan data MK ke sistem.

### Struktur Excel (per sheet = per prodi):

| Kolom Excel           | Keterangan                                     | Status          |
| --------------------- | ---------------------------------------------- | --------------- |
| No                    | Nomor urut                                     | Diisi           |
| Semester              | 1–8                                            | Diisi           |
| Mata Kuliah           | Nama MK                                        | Diisi           |
| Kode Mata Kuliah      | Kode unik MK                                   | Diisi           |
| SKS                   | Jumlah SKS                                     | Diisi           |
| Deskripsi Mata Kuliah | Deskripsi singkat MK                           | Sebagian diisi  |
| CPL                   | Capaian Pembelajaran Lulusan                   | **Belum diisi** |
| CPMK                  | Capaian Pembelajaran Mata Kuliah               | **Belum diisi** |
| Sub CPMK              | Sub-capaian (level paling rinci, yang dinilai) | **Belum diisi** |

### Daftar 11 Program Studi PCR:

| Sheet  | Program Studi                            | Prefix Kode | Jumlah MK     |
| ------ | ---------------------------------------- | ----------- | ------------- |
| PSTRM  | PS Teknik Rekayasa Mekatronika           | TM          | 46            |
| PSTRK  | PS Teknik Rekayasa Komputer              | TK          | 59            |
| PSTL   | PS Teknik Listrik                        | TL          | 53            |
| PSTET  | PS Teknik Elektronika Telekomunikasi     | ET          | 45            |
| PSAKTP | PS Akuntansi Perpajakan                  | AK          | 56            |
| PSSI   | PS Sistem Informasi                      | SI          | belum lengkap |
| PSTI   | PS Teknik Informatika                    | TI          | 59            |
| PSTRSE | PS Teknik Rekayasa Sistem Energi         | EL          | 52            |
| PSMS   | PS Teknik Mesin                          | MS          | belum lengkap |
| PSTRJT | PS Tek. Rekayasa Jaringan Telekomunikasi | JT          | 58            |
| MTTK   | PS Magister Terapa Teknik Komputer       | MTTK        | belum lengkap |

> **Catatan:** Kolom CPL, CPMK, Sub CPMK di Excel **masih kosong** — diisi oleh asesor/prodi via import ke sistem.

---

## RINGKASAN ENTITAS

```
users (base auth Laravel)
├── peserta          → profil + riwayat kerja/pendidikan
├── asesor           → profil + keahlian
└── admin            → tidak perlu tabel terpisah (role saja)

program_studi
└── mata_kuliah
    ├── cpmk          (Capaian Pembelajaran Mata Kuliah — learning outcomes per MK)
    └── pertanyaan    (Pertanyaan asesmen per MK — yang dinilai peserta)

permohonan_rpl (pengajuan utama)
└── rpl_mata_kuliah  (MK yang diajukan per permohonan)
    ├── asesmen_mandiri (per Pertanyaan)
    │   └── evaluasi_vatm (oleh asesor)
    └── dokumen_bukti

konsultasi         (jadwal & BA konsultasi)
sk_rekognisi       (output akhir: SK Direktur)
```

---

## SKEMA TABEL LENGKAP

### 1. `users`

> Tabel auth utama Laravel — `users`, `role`, `user_id` tetap bahasa Inggris (konvensi Laravel)

| Kolom                   | Tipe                             | Keterangan  |
| ----------------------- | -------------------------------- | ----------- |
| id                      | bigint PK                        |             |
| nama                    | varchar(255)                     | ← Indonesia |
| email                   | varchar(255) unique              |             |
| role                    | enum('peserta','asesor','admin') |             |
| aktif                   | boolean default true             | ← Indonesia |
| email_verified_at       | timestamp nullable               |             |
| password                | varchar(255)                     |             |
| remember_token          | varchar(100) nullable            |             |
| created_at / updated_at | timestamp                        |             |

---

### 2. `peserta`

> Profil lengkap peserta (1-to-1 dengan users)

| Kolom                    | Tipe                        | Keterangan                                             |
| ------------------------ | --------------------------- | ------------------------------------------------------ |
| id                       | bigint PK                   |                                                        |
| user_id                  | bigint FK → users           |                                                        |
| nik                      | varchar(20) unique nullable |                                                        |
| telepon                  | varchar(20) nullable        |                                                        |
| alamat                   | text nullable               |                                                        |
| tempat_lahir             | varchar(100) nullable       |                                                        |
| tanggal_lahir            | date nullable               |                                                        |
| jenis_kelamin            | enum('L','P') nullable      |                                                        |
| pendidikan_terakhir      | varchar(100) nullable       | Contoh: D3, S1                                         |
| institusi_asal           | varchar(255) nullable       | PT asal jika ada                                       |
| tahun_lulus              | year nullable               |                                                        |
| is_do_pcr                | boolean default false       | true → blokir mendaftar RPL di PCR                     |
| tanggal_pengunduran_diri | date nullable               | Untuk hitung 1 tahun akademik sebelum bisa daftar lagi |
| created_at / updated_at  | timestamp                   |                                                        |

**Relasi:**

- `users` 1–1 `peserta`
- `peserta` 1–N `pengalaman_kerja`
- `peserta` 1–N `permohonan_rpl`
- `peserta` 1–N `konsultasi`

---

### 3. `pengalaman_kerja`

> Riwayat kerja peserta (untuk Perolehan Kredit)

| Kolom                   | Tipe                  | Keterangan              |
| ----------------------- | --------------------- | ----------------------- |
| id                      | bigint PK             |                         |
| peserta_id              | bigint FK → peserta   |                         |
| nama_perusahaan         | varchar(255)          |                         |
| jabatan                 | varchar(255)          |                         |
| deskripsi_tugas         | text nullable         | Rincian tugas pekerjaan |
| tanggal_mulai           | date                  |                         |
| tanggal_selesai         | date nullable         | null = masih bekerja    |
| masih_bekerja           | boolean default false |                         |
| created_at / updated_at | timestamp             |                         |

---

### 4. `asesor`

> Profil asesor RPL (1-to-1 dengan users)

| Kolom                   | Tipe                  | Keterangan               |
| ----------------------- | --------------------- | ------------------------ |
| id                      | bigint PK             |                          |
| user_id                 | bigint FK → users     |                          |
| nidn                    | varchar(20) nullable  | untuk dosen tetap        |
| bidang_keahlian         | varchar(255)          |                          |
| sertifikat_kompetensi   | text nullable         | deskripsi sertifikat     |
| sudah_pelatihan_rpl     | boolean default false | wajib sesuai pedoman PCR |
| created_at / updated_at | timestamp             |                          |

**Relasi:**

- `users` 1–1 `asesor`
- `asesor` 1–N `rpl_mata_kuliah` (sebagai asesor yang ditugaskan)
- `asesor` 1–N `evaluasi_vatm`
- `asesor` 1–N `konsultasi`

---

### 5. `program_studi`

> Program studi yang tersedia di PCR (11 prodi)

| Kolom                   | Tipe                      | Keterangan                          |
| ----------------------- | ------------------------- | ----------------------------------- |
| id                      | bigint PK                 |                                     |
| kode_sheet              | varchar(20) unique        | Kode sheet Excel: PSTRM, PSTRK, dst |
| kode                    | varchar(20) unique        | Kode resmi prodi PCR                |
| nama                    | varchar(255)              |                                     |
| jenjang                 | enum('D3','D4','S1','S2') |                                     |
| total_sks               | smallint unsigned         | untuk hitung batas 70% & 50%        |
| aktif                   | boolean default true      |                                     |
| created_at / updated_at | timestamp                 |                                     |

**Aturan bisnis dari pedoman:**

- Maks pengakuan RPL = `total_sks * 0.70`
- Min SKS agar bisa lanjut studi = `total_sks * 0.50`

---

### 6. `mata_kuliah`

> Daftar MK per prodi — diisi via import Excel

| Kolom                   | Tipe                      | Keterangan                              |
| ----------------------- | ------------------------- | --------------------------------------- |
| id                      | bigint PK                 |                                         |
| program_studi_id        | bigint FK → program_studi |                                         |
| kode                    | varchar(20)               | Contoh: TM4101, TK4220                  |
| nama                    | varchar(255)              |                                         |
| sks                     | tinyint unsigned          |                                         |
| semester                | tinyint unsigned          | 1–8                                     |
| deskripsi               | text nullable             | Deskripsi MK dari Excel                 |
| cpl                     | text nullable             | Capaian Pembelajaran Lulusan dari Excel |
| bisa_rpl                | boolean default true      | false untuk TA/proyek akhir             |
| created_at / updated_at | timestamp                 |                                         |

**Index:** unique(`program_studi_id`, `kode`)

---

### 7. `cpmk` (Capaian Pembelajaran Mata Kuliah)

> Learning outcomes / kompetensi yang harus dikuasai peserta dalam satu MK (referensi untuk pertanyaan asesmen)
> Contoh: _"Mampu mencatat dan memposting transaksi ke buku besar sesuai PSAK"_

| Kolom                   | Tipe                    | Keterangan                              |
| ----------------------- | ----------------------- | --------------------------------------- |
| id                      | bigint PK               |                                         |
| mata_kuliah_id          | bigint FK → mata_kuliah |                                         |
| deskripsi               | text                    | Rumusan learning outcome / kompetensi   |
| urutan                  | tinyint unsigned        | Nomor urut CPMK dalam MK (1, 2, 3...)   |
| created_at / updated_at | timestamp               |                                         |

---

### 8. `pertanyaan`

> Pertanyaan spesifik asesmen mandiri per MataKuliah (dinilai peserta dalam skala 1–5)
> Contoh: _"Apakah Anda mampu memposting transaksi ke buku besar?"_

| Kolom                   | Tipe                    | Keterangan                                  |
| ----------------------- | ----------------------- | ------------------------------------------- |
| id                      | bigint PK               |                                             |
| mata_kuliah_id          | bigint FK → mata_kuliah |                                             |
| pertanyaan              | text                    | Rumusan pertanyaan asesmen (1–5 skala)      |
| urutan                  | tinyint unsigned        | Nomor urut pertanyaan dalam MK (1, 2, 3...) |
| created_at / updated_at | timestamp               |                                             |

---

### 9. `permohonan_rpl`

> Formulir Aplikasi RPL Tipe A (F02) — pengajuan utama peserta

| Kolom                   | Tipe                                                                        | Keterangan                  |
| ----------------------- | --------------------------------------------------------------------------- | --------------------------- |
| id                      | bigint PK                                                                   |                             |
| peserta_id              | bigint FK → peserta                                                         |                             |
| program_studi_id        | bigint FK → program_studi                                                   |                             |
| nomor_permohonan        | varchar(50) unique                                                          | Auto-generate: RPL-2026-001 |
| status                  | enum('draf','diajukan','dalam_review','disetujui','ditolak') default 'draf' |                             |
| catatan_admin           | text nullable                                                               |                             |
| tanggal_pengajuan       | timestamp nullable                                                          |                             |
| created_at / updated_at | timestamp                                                                   |                             |

**Relasi:**

- `permohonan_rpl` 1–N `rpl_mata_kuliah`
- `permohonan_rpl` 1–N `konsultasi`
- `permohonan_rpl` 1–1 `sk_rekognisi`
- `peserta` 1–N `dokumen_bukti` (berkas global, bukan per permohonan)

---

### 10. `rpl_mata_kuliah`

> MK spesifik yang diajukan dalam satu permohonan RPL

| Kolom                   | Tipe                                                                                         | Keterangan                      |
| ----------------------- | -------------------------------------------------------------------------------------------- | ------------------------------- |
| id                      | bigint PK                                                                                    |                                 |
| permohonan_rpl_id       | bigint FK → permohonan_rpl                                                                   |                                 |
| mata_kuliah_id          | bigint FK → mata_kuliah                                                                      |                                 |
| jenis_rpl               | enum('transfer_kredit','perolehan_kredit')                                                   |                                 |
| asesor_id               | bigint FK → asesor nullable                                                                  | Ditugaskan oleh admin           |
| status                  | enum('menunggu','dalam_review','diakui','tidak_diakui','diakui_sebagian') default 'menunggu' |                                 |
| nilai_akhir             | varchar(5) nullable                                                                          | Contoh: A, B+, B                |
| sks_diakui              | tinyint unsigned nullable                                                                    | Bisa sebagian dari total SKS MK |
| catatan_asesor          | text nullable                                                                                |                                 |
| created_at / updated_at | timestamp                                                                                    |                                 |

**Index:** unique(`permohonan_rpl_id`, `mata_kuliah_id`)

---

### 11. `asesmen_mandiri`

> Asesmen mandiri peserta per Pertanyaan per MK (nilai self-assessment 1–5, inti dari formulir)

| Kolom                   | Tipe                        | Keterangan                                                |
| ----------------------- | --------------------------- | --------------------------------------------------------- |
| id                      | bigint PK                   |                                                           |
| rpl_mata_kuliah_id      | bigint FK → rpl_mata_kuliah |                                                           |
| pertanyaan_id           | bigint FK → pertanyaan      |                                                           |
| penilaian_diri          | tinyint unsigned            | 1=Kurang Sekali, 2=Kurang, 3=Cukup, 4=Baik, 5=Sangat Baik |
| referensi_berkas        | json nullable               | Array nama dokumen pendukung yang dipilih peserta per pertanyaan (mis. `["CV Saya","Sertifikat AWS"]`) |
| created_at / updated_at | timestamp                   |                                                           |

**Index:** unique(`rpl_mata_kuliah_id`, `pertanyaan_id`)

---

### 12. `dokumen_bukti`

> Berkas pendukung yang diupload peserta — terikat ke peserta (global, bukan per permohonan)

| Kolom                   | Tipe                                                                                                                                     | Keterangan                                      |
| ----------------------- | ---------------------------------------------------------------------------------------------------------------------------------------- | ----------------------------------------------- |
| id                      | bigint PK                                                                                                                                |                                                 |
| peserta_id              | bigint FK → peserta                                                                                                                      | Berkas milik peserta, reusable lintas permohonan |
| jenis_dokumen           | enum('ijazah','transkrip','rps_silabus','sertifikat','logbook','surat_keterangan','cv','penilaian_kinerja','karya_monumental','lainnya') |                                                 |
| nama_dokumen            | varchar(255)                                                                                                                             |                                                 |
| berkas                  | varchar(500)                                                                                                                             | Path file di `local` disk (storage/app/private) |
| keterangan              | text nullable                                                                                                                            |                                                 |
| created_at / updated_at | timestamp                                                                                                                                |                                                 |

**Catatan arsitektur:** Relasi per-pertanyaan menggunakan `asesmen_mandiri.referensi_berkas` (JSON nama berkas), bukan FK. Asesor/admin melihat semua berkas peserta di satu panel sentral.

---

### 13. `evaluasi_vatm`

> Evaluasi VATM oleh asesor per asesmen_mandiri (per Pertanyaan)

| Kolom                   | Tipe                               | Keterangan                           |
| ----------------------- | ---------------------------------- | ------------------------------------ |
| id                      | bigint PK                          |                                      |
| asesmen_mandiri_id      | bigint FK → asesmen_mandiri unique | 1-to-1                               |
| asesor_id               | bigint FK → asesor                 |                                      |
| valid                   | boolean nullable                   | V — bukti relevan dan sesuai standar |
| autentik                | boolean nullable                   | A — bukti benar milik pemohon        |
| terkini                 | boolean nullable                   | T — bukti masih up-to-date           |
| memadai                 | boolean nullable                   | M — bukti cukup untuk dinilai        |
| catatan                 | text nullable                      |                                      |
| dievaluasi_pada         | timestamp nullable                 |                                      |
| created_at / updated_at | timestamp                          |                                      |

---

### 14. `konsultasi`

> Jadwal & Berita Acara Konsultasi (Form 1 & Form 9)

| Kolom                   | Tipe                                                         | Keterangan                |
| ----------------------- | ------------------------------------------------------------ | ------------------------- |
| id                      | bigint PK                                                    |                           |
| peserta_id              | bigint FK → peserta                                          |                           |
| asesor_id               | bigint FK → asesor nullable                                  | Bisa belum ditentukan     |
| permohonan_rpl_id       | bigint FK → permohonan_rpl nullable                          | Null jika konsultasi awal |
| jenis                   | enum('awal','lanjutan') default 'awal'                       |                           |
| jadwal                  | timestamp                                                    |                           |
| status                  | enum('terjadwal','selesai','dibatalkan') default 'terjadwal' |                           |
| catatan_konsultasi      | text nullable                                                | Isi Berita Acara          |
| created_at / updated_at | timestamp                                                    |                           |

---

### 15. `sk_rekognisi`

> Surat Keputusan Direktur PCR (output akhir)

| Kolom                   | Tipe                              | Keterangan             |
| ----------------------- | --------------------------------- | ---------------------- |
| id                      | bigint PK                         |                        |
| permohonan_rpl_id       | bigint FK → permohonan_rpl unique |                        |
| nomor_sk                | varchar(100) unique               |                        |
| tanggal_sk              | date                              |                        |
| berkas                  | varchar(500) nullable             | PDF SK yang diupload   |
| diterbitkan_oleh        | bigint FK → users                 | Admin yang menerbitkan |
| created_at / updated_at | timestamp                         |                        |

---

## DIAGRAM RELASI (ERD Ringkas)

```
users ──────────────── peserta ──────────── pengalaman_kerja
  │                      │
  └──── asesor            ├──── permohonan_rpl ──── konsultasi
  │                      │         │
  └──── (admin)           │         ├──── rpl_mata_kuliah ──── asesor
                          │         │         │
program_studi ────────────┘         │         ├──── asesmen_mandiri ──── pertanyaan
  │                                 │         │         │                  │
  └──── mata_kuliah                 │         │         └── evaluasi_vatm  mata_kuliah
        ├── cpmk                    │         │
        └── pertanyaan              │         └──── dokumen_bukti
                                    │
                                    └──── sk_rekognisi
```

---

## ATURAN BISNIS (Business Rules)

### Validasi SKS RPL

```
total_sks_diakui = SUM(sks_diakui) dari rpl_mata_kuliah WHERE status = 'diakui'
batas_maksimal   = program_studi.total_sks * 0.70
batas_minimum    = program_studi.total_sks * 0.50

→ total_sks_diakui TIDAK BOLEH > batas_maksimal
→ total_sks_diakui HARUS >= batas_minimum agar peserta bisa lanjut studi
```

### Aturan Pendaftaran

- `peserta.is_do_pcr = true` → blokir mendaftar RPL di PCR
- `peserta.tanggal_pengunduran_diri` → cek apakah sudah lewat 1 tahun akademik sebelum bisa daftar lagi
- Tugas Akhir/Proyek Akhir → `mata_kuliah.bisa_rpl = false`
- Minimal 5 asesor dengan `sudah_pelatihan_rpl = true` per prodi

### Nilai Enum Status Permohonan

| Nilai DB       | Tampilan     |
| -------------- | ------------ |
| `draf`         | Draf         |
| `diajukan`     | Diajukan     |
| `dalam_review` | Dalam Review |
| `disetujui`    | Disetujui    |
| `ditolak`      | Ditolak      |

---

## MEKANISME IMPORT EXCEL

### Alur Import oleh Admin:

```
1. Admin download template Excel (format: Mata Kuliah RPL PCR_2026.xlsx)
2. Prodi/Asesor mengisi kolom CPMK di Excel
3. Admin upload Excel per prodi ke sistem
4. Sistem membaca sheet name → mapping ke program_studi.kode
5. Sistem import per baris → insert/update mata_kuliah, cpmk
```

### Mapping Sheet → Program Studi:

```
PSTRM  → PS Teknik Rekayasa Mekatronika
PSTRK  → PS Teknik Rekayasa Komputer
PSTL   → PS Teknik Listrik
PSTET  → PS Teknik Elektronika Telekomunikasi
PSAKTP → PS Akuntansi Perpajakan
PSSI   → PS Sistem Informasi
PSTI   → PS Teknik Informatika
PSTRSE → PS Teknik Rekayasa Sistem Energi
PSMS   → PS Teknik Mesin
PSTRJT → PS Teknik Rekayasa Jaringan Telekomunikasi
MTTK   → PS Magister Terapan Teknik Komputer
```

**Package:** `maatwebsite/excel` (Laravel Excel)

---

## CATATAN KONVENSI PENAMAAN

| Term                                     | Keputusan     | Alasan                                           |
| ---------------------------------------- | ------------- | ------------------------------------------------ |
| `users`                                  | Tetap Inggris | Konvensi Laravel auth, dipakai Spatie permission |
| `role`                                   | Tetap Inggris | Konvensi Laravel + Spatie                        |
| `user_id`                                | Tetap Inggris | Konvensi FK Laravel                              |
| `email`, `password`                      | Tetap Inggris | Term universal/teknis                            |
| `nama`, `telepon`, `alamat`              | Indonesia     | Kolom domain bisnis                              |
| `permohonan_rpl`, `asesmen_mandiri`, dll | Indonesia     | Nama tabel domain bisnis                         |

---

## URUTAN MIGRASI LARAVEL

```
1.  create_users_table              → built-in Laravel
2.  create_permission_tables        → Spatie (otomatis)
3.  add_role_to_users_table         → tambah kolom role & aktif ke users
4.  create_peserta_table
5.  create_pengalaman_kerja_table
6.  create_asesor_table
7.  create_program_studi_table
8.  create_mata_kuliah_table
9.  create_cpmk_table               → tabel cpmk (learning outcomes per MK)
10. create_pertanyaan_table         → tabel pertanyaan (assessment questions per MK)
11. create_rpl_applications_table   → tabel permohonan_rpl
12. create_rpl_mata_kuliah_table
13. create_self_assessments_table   → tabel asesmen_mandiri (per pertanyaan)
14. create_dokumen_bukti_table
15. create_vatm_evaluations_table   → tabel evaluasi_vatm
16. create_consultations_table      → tabel konsultasi
17. create_sk_rekognisi_table
```

---

_Berdasarkan: `knowledge_base_rpl_pcr.md` + analisis `Mata Kuliah RPL PCR_2026.xlsx` | PCR 2026_
