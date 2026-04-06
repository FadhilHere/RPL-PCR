# Plan: Implementasi 16 Poin Revisi RPL PCR

## Context

Revisi berdasarkan feedback penguji/reviewer terhadap Sistem Asesmen RPL PCR. Terdapat 16 poin revisi yang mencakup perubahan UI, alur bisnis, skema database, dan fitur baru. Tujuannya agar sistem lebih sesuai dengan kebutuhan real proses RPL di Politeknik Caltex Riau.

---

## Ringkasan Keputusan dari Klarifikasi

| Poin | Keputusan |
|------|-----------|
| 1 | UI saja dibatasi 1 prodi saat buat asesor. Pivot table `asesor_program_studi` tetap. |
| 2 | Flow yang ada (Diajukan → Diproses) sudah cukup sebagai approval admin. Tidak perlu step baru. |
| 5 | Form riwayat hidup lengkap (biodata + 5 section repeatable). Cek password via `Hash::check(nama)`, tidak perlu kolom baru. |
| 7 | Tabel `pertanyaan` tidak berubah strukturnya. Hanya rename label di UI: "Pertanyaan" → "Sub CPMK". |
| 9 | Pakai role admin yang sama. Tambah menu "Jadwal" terpisah di sidebar admin. |
| 11 | Asesor pakai skala 1-5. |
| 14 | Boolean saja. Posisi tombol di samping toggle "Aktif" di halaman manajemen akun. |
| 15 | Tahun ajaran dikelola admin via tabel master `tahun_ajaran` (CRUD). Bukan string manual. |
| 16 | Rata-rata nilai asesor per MK: < 3 = tidak diakui, >= 3 = diakui. |

---

## Phase 1: Database Migrations

### Migration 1: Tambah field biodata ke `peserta`

**File**: `database/migrations/xxxx_add_biodata_fields_to_peserta_table.php`

Field baru di tabel `peserta`:
```
+ agama (string 50, nullable)
+ golongan_pangkat (string 100, nullable)
+ instansi (string 255, nullable)
+ pekerjaan (string 255, nullable)
+ telepon_faks (string 20, nullable)
+ profil_lengkap (boolean, default: false)
```

Note: `nama` sudah di `users`, `nik/telepon/alamat/tempat_lahir/tanggal_lahir/jenis_kelamin` sudah ada di `peserta`.

**Model**: `app/Models/Peserta.php` — tambah field baru ke `$fillable`

### Migration 2: Tabel `riwayat_pendidikan`

**File**: `database/migrations/xxxx_create_riwayat_pendidikan_table.php`

```
riwayat_pendidikan:
  id
  peserta_id (FK → peserta, cascadeOnDelete)
  nama_sekolah (string)
  tahun_lulus (string 4, nullable)
  jurusan (string, nullable)
  timestamps
```

**Model baru**: `app/Models/RiwayatPendidikan.php`

### Migration 3: Tabel `pelatihan_profesional`

**File**: `database/migrations/xxxx_create_pelatihan_profesional_table.php`

```
pelatihan_profesional:
  id
  peserta_id (FK → peserta, cascadeOnDelete)
  tahun (string 4)
  jenis_pelatihan (string) — "Dalam Negeri" / "Luar Negeri"
  penyelenggara (string)
  jangka_waktu (string 100, nullable)
  timestamps
```

**Model baru**: `app/Models/PelatihanProfesional.php`

### Migration 4: Tabel `konferensi_seminar`

**File**: `database/migrations/xxxx_create_konferensi_seminar_table.php`

```
konferensi_seminar:
  id
  peserta_id (FK → peserta, cascadeOnDelete)
  tahun (string 4)
  judul_kegiatan (string)
  penyelenggara (string)
  peran (string 100, nullable) — panitia/pemohon/pembicara
  timestamps
```

**Model baru**: `app/Models/KonferensiSeminar.php`

### Migration 5: Tabel `penghargaan`

**File**: `database/migrations/xxxx_create_penghargaan_table.php`

```
penghargaan:
  id
  peserta_id (FK → peserta, cascadeOnDelete)
  tahun (string 4)
  bentuk_penghargaan (string)
  pemberi (string)
  timestamps
```

**Model baru**: `app/Models/Penghargaan.php`

### Migration 6: Tabel `organisasi_profesi`

**File**: `database/migrations/xxxx_create_organisasi_profesi_table.php`

```
organisasi_profesi:
  id
  peserta_id (FK → peserta, cascadeOnDelete)
  tahun (string 4)
  nama_organisasi (string)
  jabatan (string, nullable)
  timestamps
```

**Model baru**: `app/Models/OrganisasiProfesi.php`

### Migration 7: Tabel `nilai_asesor` (Poin 11)

**File**: `database/migrations/xxxx_create_nilai_asesor_table.php`

```
nilai_asesor:
  id
  asesmen_mandiri_id (FK → asesmen_mandiri, cascadeOnDelete)
  asesor_id (FK → asesor)
  nilai (unsignedTinyInteger) — 1-5
  catatan (text, nullable)
  dinilai_pada (timestamp)
  timestamps
  unique: [asesmen_mandiri_id, asesor_id]
```

**Model baru**: `app/Models/NilaiAsesor.php`

### Migration 8a: Tabel `tahun_ajaran` (Poin 15)

**File**: `database/migrations/xxxx_create_tahun_ajaran_table.php`

Tabel master yang dikelola admin dari tahun ke tahun.

```
tahun_ajaran:
  id
  nama (string 20, unique) — contoh: "2025/2026"
  aktif (boolean, default: false) — hanya 1 yang aktif pada satu waktu
  timestamps
```

**Model baru**: `app/Models/TahunAjaran.php`

Admin bisa mengelola (CRUD) tahun ajaran. Semester tidak disimpan di sini karena pasti hanya ganjil/genap — dipilih terpisah saat peserta buat permohonan.

### Migration 8b: Tambah field pembayaran & tahun_ajaran_id ke `permohonan_rpl` (Poin 14 & 15)

**File**: `database/migrations/xxxx_add_pembayaran_and_tahun_ajaran_to_permohonan_rpl.php`

```
permohonan_rpl:
  + pembayaran_terverifikasi (boolean, default: false)
  + tanggal_verifikasi_pembayaran (timestamp, nullable)
  + admin_verifikator_id (FK → users, nullable)
  + tahun_ajaran_id (FK → tahun_ajaran, nullable)
  + semester (enum: 'ganjil','genap', nullable)
```

### ~~Migration 9~~ — TIDAK DIPERLUKAN (Poin 5)

Password default peserta = nama peserta. Pengecekan cukup via `Hash::check($user->nama, $user->password)` di middleware. Jika masih match → belum ganti password → redirect ke halaman ganti password. Tidak perlu kolom baru di `users`.

### Migration 10: Tambah `uploaded_by_user_id` ke `dokumen_bukti` (Poin 3)

**File**: `database/migrations/xxxx_add_uploaded_by_to_dokumen_bukti.php`

```
dokumen_bukti:
  + uploaded_by_user_id (FK → users, nullable)
```

---

## Phase 2: Enum & Model Updates

### Enum baru: `SemesterEnum`
**File**: `app/Enums/SemesterEnum.php`
- Cases: `Ganjil = 'ganjil'`, `Genap = 'genap'`
- Methods: `label()`, `options()`

### Enum update: `JenisDokumenEnum`
**File**: `app/Enums/JenisDokumenEnum.php`
Tambah cases baru sesuai Poin 4:
```
+ DokumenAsesmenMandiri = 'dokumen_asesmen_mandiri'
+ KeanggotaanProfesi = 'keanggotaan_profesi'
+ DukunganAsosiasi = 'dukungan_asosiasi'
+ BuktiPengalamanKerja = 'bukti_pengalaman_kerja'
+ BuktiKeahlian = 'bukti_keahlian'
+ PernyataanSejawat = 'pernyataan_sejawat'
+ Pelatihan = 'pelatihan'
+ WorkshopSeminar = 'workshop_seminar'
+ KaryaPenghargaan = 'karya_penghargaan'
```

Juga update migration enum column `dokumen_bukti.jenis_dokumen` untuk menambah values baru.

### Model updates:
- `Peserta.php`: tambah field baru ke `$fillable`, tambah relations (riwayatPendidikan, pelatihanProfesional, konferensiSeminar, penghargaan, organisasiProfesi)
- `PermohonanRpl.php`: tambah field `pembayaran_terverifikasi`, `tahun_ajaran_id` ke `$fillable`, relation `tahunAjaran()` BelongsTo
- `AsesmenMandiri.php`: tambah relation `nilaiAsesor()` HasOne
- `DokumenBukti.php`: tambah `uploaded_by_user_id` ke `$fillable`, relation `uploadedBy()`

---

## Phase 3: Poin-poin UI Sederhana (8, 6, 13)

### Poin 8: Rename "Pengajuan Masuk" → "Pengajuan RPL"
**File**: `resources/views/components/layouts/asesor.blade.php` line 71
- Change `'label' => 'Pengajuan Masuk'` → `'label' => 'Pengajuan RPL'`

**File**: `resources/views/livewire/asesor/evaluasi/index.blade.php`
- Update breadcrumb/back link text juga

### Poin 6: Likert scale 1-5 tanpa label teks
**File**: `resources/views/livewire/peserta/pengajuan/asesmen.blade.php`

Perubahan:
1. Hapus `$ratingLabels` array (line 151)
2. Ubah loop `[1, 2, 3, 4]` → `[1, 2, 3, 4, 5]` (line 279 & 327)
3. Hapus teks label dari button: `{{ $nilai }} — {{ $ratingLabels[$nilai] }}` → `{{ $nilai }}`
4. Tambah deskripsi di atas rating: "Semakin besar angka yang dipilih, semakin Anda memahami kompetensi ini"
5. Fix `saveRating()` validation: `$nilai > 4` → `$nilai > 5` (line 55)

### Poin 13: Hide nama mata kuliah dari peserta
**File**: `resources/views/livewire/peserta/pengajuan/asesmen.blade.php`

Perubahan di header MK (line 226-240):
1. Ganti `{{ $mk->kode }}` → `Kompetensi {{ $loop->iteration }}`
2. Ganti `{{ $mk->nama }}` → deskripsi panduan
3. Tambah teks instruksi: "Keterampilan ini dapat diperoleh dari pengalaman kerja, pelatihan, sertifikasi, atau pendidikan formal. Dapat dibuktikan dengan transkrip, CV, sertifikat, surat keterangan, dll."
4. Tetap tampilkan SKS & semester

### Poin 7: Rename label "Pertanyaan" → "Sub CPMK" di UI
**Files yang terdampak**:
- `resources/views/livewire/peserta/pengajuan/asesmen.blade.php` — label section
- `resources/views/livewire/asesor/evaluasi/partials/evaluasi-per-mk.blade.php` — label
- `resources/views/livewire/admin/materi/prodi.blade.php` — management label
- `resources/views/livewire/asesor/materi/prodi.blade.php` — management label

Perubahan: Semua teks "Pertanyaan" yang merujuk ke assessment questions → "Sub CPMK"

---

## Phase 4: Fitur Onboarding (Poin 5)

### Middleware: `EnsureProfilLengkap`
**File baru**: `app/Http/Middleware/EnsureProfilLengkap.php`

Logic:
1. Skip jika bukan role peserta
2. Cek `Hash::check($user->nama, $user->password)` → jika true, password belum diganti → redirect ke halaman ganti password
3. Cek `auth()->user()->peserta?->profil_lengkap` → redirect ke halaman lengkapi profil

Register di `routes/web.php` pada group peserta.

### Halaman ganti password (modifikasi existing)
**File**: Update password form yang sudah ada
- Tidak perlu update kolom tambahan, cukup ganti password biasa (setelah diganti, `Hash::check(nama)` akan return false)

### Halaman lengkapi profil (Poin 5)
**File baru**: `resources/views/livewire/peserta/lengkapi-profil.blade.php`

Form multi-section dengan tabs (Alpine):
1. **Tab Biodata**: nama (readonly dari users), NIK, tempat lahir, tanggal lahir, jenis kelamin, agama, golongan/pangkat, instansi, pekerjaan, alamat, telepon, telepon faks
2. **Tab Riwayat Pendidikan**: repeatable rows (nama sekolah, tahun lulus, jurusan) — minimal 1 entry wajib
3. **Tab Pelatihan Profesional**: repeatable rows (tahun, jenis, penyelenggara, jangka waktu) — opsional
4. **Tab Konferensi/Seminar**: repeatable rows (tahun, judul, penyelenggara, peran) — opsional
5. **Tab Penghargaan**: repeatable rows (tahun, bentuk, pemberi) — opsional
6. **Tab Organisasi Profesi**: repeatable rows (tahun, nama organisasi, jabatan) — opsional

Pattern: Alpine manages form state + tabs, submit all ke Livewire sekali.

**Action baru**: `app/Actions/Peserta/LengkapiProfilAction.php`
- Terima semua data dari form
- `DB::transaction()`: update peserta biodata + insert repeatable rows + set `profil_lengkap = true`

**Route baru**: 
```php
Volt::route('lengkapi-profil', 'peserta.lengkapi-profil')->name('peserta.lengkapi-profil');
```
Route ini TIDAK dibungkus middleware `EnsureProfilLengkap` (agar bisa diakses).

---

## Phase 5: Fitur Dokumen & Upload (Poin 3, 4)

### Poin 4: Checklist dokumen persyaratan saat pendaftaran
**File**: `resources/views/livewire/peserta/berkas/index.blade.php`
- Tambah section checklist yang menampilkan semua jenis dokumen yang diperlukan
- Tandai mana yang wajib vs opsional
- Show status: sudah upload / belum upload per jenis
- Tambah pertanyaan "Apakah Anda alumni PCR?" (Alpine toggle)
  - Jika alumni: Transkrip jadi wajib
  - Jika bukan: Transkrip opsional

**File**: `resources/views/livewire/peserta/pengajuan/dokumen.blade.php`
- Tampilkan panduan serupa dengan checklist requirement

### Poin 3: Admin kelola berkas peserta
**File**: `resources/views/livewire/admin/pengajuan/detail.blade.php`
- Tambah section "Berkas Peserta" yang menampilkan dokumen peserta
- Admin bisa upload berkas baru atas nama peserta
- Admin bisa hapus/replace berkas yang salah
- Set `uploaded_by_user_id` ke admin yang upload

**Action baru**: `app/Actions/Admin/UploadDokumenPesertaAction.php`

---

## Phase 6: Fitur Pembayaran & Periode (Poin 14, 15)

### Poin 14: Verifikasi pembayaran
**Lokasi**: Di halaman manajemen akun admin (`admin/akun/index.blade.php`)
- Tambah toggle/button "Pembayaran" di samping toggle "Aktif" per peserta
- Saat diklik: update `permohonan_rpl.pembayaran_terverifikasi = true` + catat waktu + admin_id

**Guard**: Di `ProsesPermohonanAction.php`:
- Tambah check: `abort_if(!$permohonan->pembayaran_terverifikasi, 403, 'Pembayaran belum diverifikasi')`

Peserta yang belum terverifikasi pembayarannya tidak bisa masuk tahap asesmen.

### Poin 15: Tahun ajaran & semester

**Halaman admin CRUD tahun ajaran**:
**File baru**: `resources/views/livewire/admin/tahun-ajaran/index.blade.php`
- Admin bisa CRUD tahun ajaran (nama + semester + toggle aktif)
- Hanya 1 tahun ajaran aktif pada satu waktu

**Route baru**:
```php
Volt::route('tahun-ajaran', 'admin.tahun-ajaran.index')->name('admin.tahun-ajaran.index');
```

**Sidebar admin**: tambah menu "Tahun Ajaran"

**File**: `resources/views/livewire/peserta/pengajuan/buat.blade.php`
- Tampilkan tahun ajaran aktif (atau dropdown dari yang tersedia)
- Tambah dropdown semester (Ganjil/Genap) via `SemesterEnum`
- Simpan `tahun_ajaran_id` + `semester` ke permohonan

**Action update**: `BuatPermohonanAction` — terima & simpan `tahun_ajaran_id` + `semester`

---

## Phase 7: Asesor Scoring & Reporting (Poin 11, 12, 16)

### Poin 11: Nilai asesor per sub-CPMK
**File**: `resources/views/livewire/asesor/evaluasi/partials/evaluasi-per-mk.blade.php`
- Tambah input nilai 1-5 per pertanyaan (di samping VATM toggles)
- Alpine untuk instant feedback, Livewire `#[Renderless]` untuk save
- Tampilkan side-by-side: nilai peserta (self-assessment) vs nilai asesor

**File**: `resources/views/livewire/asesor/evaluasi/index.blade.php`
- Tambah method `saveNilaiAsesor(int $asesmenMandiriId, int $nilai)`
- Load `nilaiAsesor` relation di mount/with

### Poin 16: Auto-hitung keputusan MK
**Action baru**: `app/Actions/Asesor/HitungKeputusanMkAction.php`
```
execute(RplMataKuliah $rplMk): StatusRplMataKuliahEnum
  - Hitung rata-rata nilai_asesor per pertanyaan dalam MK
  - < 3 → TidakDiakui
  - >= 3 → Diakui
```

Integrasikan di UI evaluasi: tampilkan rata-rata + rekomendasi otomatis. Asesor bisa override jika perlu.

### Poin 12: Resume/report untuk pleno
**File baru**: `resources/views/livewire/asesor/evaluasi/resume.blade.php`

Isi:
- Header: Info peserta + prodi
- Tabel perbandingan per MK: rata-rata self-assessment vs rata-rata nilai asesor
- Keputusan per MK (diakui/tidak)
- Summary: "LULUS Prodi X; n SKS: 1. MK A (3 SKS), 2. MK B (2 SKS)"
- Total SKS diakui vs total SKS program
- Layout printable (`@media print`)

**Route baru**:
```php
Volt::route('pengajuan/{permohonan}/evaluasi/resume', 'asesor.evaluasi.resume')
    ->name('asesor.evaluasi.resume');
```

---

## Phase 8: Jadwal Terpisah & Inline Viewer (Poin 9, 10)

### Poin 9: Menu jadwal terpisah
**File baru**: `resources/views/livewire/admin/jadwal/index.blade.php`
- List semua jadwal verifikasi_bersama + konsultasi
- Admin bisa create/edit/delete jadwal
- Filter by prodi, status, tanggal

**Route baru**:
```php
Volt::route('jadwal', 'admin.jadwal.index')->name('admin.jadwal.index');
```

**Sidebar update**: `resources/views/components/layouts/admin.blade.php`
- Tambah menu "Jadwal Verifikasi" setelah "Semua Pengajuan"

### Poin 10: Inline document viewer
**File**: `resources/views/components/pengajuan/berkas-pendukung.blade.php` (atau di evaluasi partials)
- Per dokumen badge: tambah toggle "Lihat" (Alpine x-show)
- Klik → expand panel di bawah badge
- PDF: `<iframe src="...">` inline
- Image: `<img>` inline
- Collapse saat klik lagi

Implementasi di semua view yang menampilkan berkas (admin detail, asesor evaluasi).

---

## Poin 1: UI Dropdown Prodi (Poin 1)

**File**: `resources/views/livewire/admin/akun/index.blade.php` (atau form terkait)
- Ubah multi-select prodi menjadi single `<x-form.select>` dropdown saat tambah asesor
- Kirim sebagai array 1 elemen ke `TambahAsesorAction`

---

## Urutan Implementasi (Recommended)

1. **Phase 1** — Semua migrations (fondasi data)
2. **Phase 2** — Enum & model updates
3. **Phase 3** — UI sederhana (rename, likert, hide MK) — quick wins
4. **Phase 4** — Onboarding flow (Poin 5) — impactful
5. **Phase 5** — Dokumen management (Poin 3, 4)
6. **Phase 6** — Pembayaran & periode (Poin 14, 15)
7. **Phase 7** — Asesor scoring & reporting (Poin 11, 12, 16) — complex
8. **Phase 8** — Jadwal & inline viewer (Poin 9, 10)
9. **Poin 1** — Dropdown prodi (simple, anytime)

---

## File Inventory

### Migrations baru (10 files)
1. `add_biodata_fields_to_peserta_table`
2. `create_riwayat_pendidikan_table`
3. `create_pelatihan_profesional_table`
4. `create_konferensi_seminar_table`
5. `create_penghargaan_table`
6. `create_organisasi_profesi_table`
7. `create_nilai_asesor_table`
8. `create_tahun_ajaran_table`
9. `add_pembayaran_and_tahun_ajaran_to_permohonan_rpl`
10. `add_uploaded_by_to_dokumen_bukti`

### Models baru (8 files)
1. `app/Models/RiwayatPendidikan.php`
2. `app/Models/PelatihanProfesional.php`
3. `app/Models/KonferensiSeminar.php`
4. `app/Models/Penghargaan.php`
5. `app/Models/OrganisasiProfesi.php`
6. `app/Models/NilaiAsesor.php`
7. `app/Models/TahunAjaran.php`

### Enum baru (1 file)
1. `app/Enums/SemesterEnum.php`

### Middleware baru (1 file)
1. `app/Http/Middleware/EnsureProfilLengkap.php`

### Action baru (3 files)
1. `app/Actions/Peserta/LengkapiProfilAction.php`
2. `app/Actions/Admin/UploadDokumenPesertaAction.php`
3. `app/Actions/Asesor/HitungKeputusanMkAction.php`

### Volt pages baru (4 files)
1. `resources/views/livewire/peserta/lengkapi-profil.blade.php`
2. `resources/views/livewire/asesor/evaluasi/resume.blade.php`
3. `resources/views/livewire/admin/jadwal/index.blade.php`
4. `resources/views/livewire/admin/tahun-ajaran/index.blade.php`

### Files yang dimodifikasi (~15 files)
1. `app/Models/Peserta.php` — field + relations
2. `app/Models/PermohonanRpl.php` — field pembayaran/periode
3. `app/Models/AsesmenMandiri.php` — relation nilaiAsesor
5. `app/Models/DokumenBukti.php` — uploadedBy relation
6. `app/Enums/JenisDokumenEnum.php` — cases baru
7. `routes/web.php` — routes + middleware baru
8. `resources/views/livewire/peserta/pengajuan/asesmen.blade.php` — likert, hide MK, rename
9. `resources/views/livewire/peserta/berkas/index.blade.php` — checklist dokumen
10. `resources/views/livewire/peserta/pengajuan/dokumen.blade.php` — checklist
11. `resources/views/livewire/admin/akun/index.blade.php` — pembayaran toggle + single prodi
12. `resources/views/livewire/admin/pengajuan/detail.blade.php` — kelola berkas
13. `resources/views/livewire/asesor/evaluasi/index.blade.php` — nilai asesor
14. `resources/views/livewire/asesor/evaluasi/partials/evaluasi-per-mk.blade.php` — scoring UI
15. `resources/views/components/layouts/asesor.blade.php` — rename menu
16. `resources/views/components/layouts/admin.blade.php` — menu jadwal

---

## Verification

Setelah implementasi setiap phase:
1. `php artisan migrate` — pastikan migration berjalan
2. `php artisan route:list` — pastikan route baru terdaftar
3. Test manual per role (admin/asesor/peserta)
4. Cek N+1 queries via Laravel Debugbar
5. Verifikasi semua form validation bekerja
6. Test responsive (mobile sidebar)
