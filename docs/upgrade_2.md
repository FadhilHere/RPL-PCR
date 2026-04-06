# Plan: Multi-Area Improvements (Admin, Register, Peserta, Asesor)

## Context

User telah review seluruh fitur dan menemukan banyak improvement dan bug fix yang diperlukan di 4 area: Admin, Halaman Register, Peserta, dan Asesor. Ini bukan fitur baru — ini polish dan fix dari implementasi sebelumnya.

---

## A. ADMIN (9 item)

### A1. Pembayaran jadi toggle switch (mirip Aktif)

**File:** `resources/views/livewire/admin/akun/index.blade.php` line 295-316

- **Saat ini:** Pembayaran badge — UI-nya bukan toggle switch
- **Ubah:** Ganti badge jadi toggle switch identik dengan toggle Aktif (line 286-292)
- Toggle tetap panggil `togglePembayaran()`, logic tidak berubah

### A2. Admin upload berkas dari halaman akun (halaman khusus per peserta)

**File baru:** `resources/views/livewire/admin/akun/berkas.blade.php`

- Halaman khusus admin untuk upload berkas per peserta
- Tombol "Berkas" di kolom aksi tabel akun → navigasi ke halaman ini
- Berisi: info peserta atas, upload form (reuse pattern detail.blade.php), list berkas existing + view/download/delete
- Upload di `admin/pengajuan/detail.blade.php` tetap ada (tambahan)
- **Route:** `admin/akun/{peserta}/berkas` → `admin.akun.berkas`

### A3. Data akun descending by created_at

**File:** `resources/views/livewire/admin/akun/index.blade.php` line 183-184

- Ganti `orderByRaw("FIELD(role, ...)")->orderBy('nama')` → `orderByDesc('created_at')`

### A4. Jadwal: custom date picker component

**File:** `resources/views/livewire/admin/jadwal/index.blade.php` line 249

- Ganti `<input type="datetime-local">` → `<x-form.date-picker :enable-time="true">`
- Modal form jadwal perlu jadi Alpine-driven (x-data), submit params ke Livewire method (terkait A9)

### A5. Bug fix: Edit jadwal → klik Tambah = tetap mode Edit

**File:** `resources/views/livewire/admin/jadwal/index.blade.php` line 128

- Tombol Tambah toolbar pakai `$set` inline, tidak reset `jadwalTanggal` dan `jadwalCatatan`
- **Fix:** Jadikan modal Alpine-driven (A9), reset semua state di Alpine saat open tambah

### A6. Hapus jadwal: custom modal bukan browser dialog

**File:** `resources/views/livewire/admin/jadwal/index.blade.php` line 198-199

- Ganti `wire:confirm="Hapus jadwal ini?"` → custom Alpine confirmation modal
- Pattern sama seperti `modal-konfirmasi-hapus.blade.php` di admin akun

### A7. Hapus status "Minta Revisi"

**File:** `app/Enums/StatusVerifikasiEnum.php` line 8

- Hapus `case MintaRevisi = 'minta_revisi'` + label + badgeClass entries
- Cek references: jadwal filter, verifikasi-bersama partial

### A8. Pembayaran belum verifikasi → modal, bukan 403

**Files:** `app/Actions/Admin/ProsesPermohonanAction.php` line 17, `resources/views/livewire/admin/pengajuan/detail.blade.php`

- Di Action: hapus `abort_if` pembayaran, throw custom exception atau return false
- Di detail.blade.php: cek `pembayaran_terverifikasi` di UI sebelum panggil prosesPermohonan, tampilkan modal peringatan Alpine jika false

### A9. Modal jadwal pakai Alpine (bukan Livewire property)

**File:** `resources/views/livewire/admin/jadwal/index.blade.php` line 229-279

- Ganti `@if ($showJadwalForm)` (server round trip) → Alpine `x-show="showForm"`
- Form state di Alpine x-data, submit via `$wire.simpanJadwal(tanggal, catatan)`
- Menyelesaikan A4, A5, A6 sekaligus dalam satu refactor modal

---

## B. HALAMAN REGISTER (4 item)

### B1. Tanggal lahir: custom date picker

**File:** `resources/views/livewire/pages/auth/register.blade.php` line 232

- Ganti `<input type="date">` → `<x-form.date-picker :enable-time="false">`
- Bridge ke Livewire via Alpine entangle

### B2. Upload berkas wajib saat registrasi

**Files:** `register.blade.php`, `RegisterForm.php`, `RegisterPesertaAction.php`

- Tambah upload wajib: **CV**, **Transkrip Akademik** (skip jika alumni PCR), **Dokumen Keterangan MK** (skip jika alumni PCR)
- Section baru setelah "Pas Foto", layout 2-kolom, manfaatkan space
- Checkbox "Saya alumni PCR" (pindah dari berkas peserta ke sini) → toggle wajib/tidak
- File disimpan setelah register berhasil → `DokumenBukti` records dibuat
- **Enum baru:** `KeteranganMataKuliah = 'keterangan_mata_kuliah'` di `JenisDokumenEnum`

### B3. Checkbox pernyataan (terms & agreement)

**File:** `register.blade.php`, `RegisterForm.php`

- Checkbox wajib sebelum tombol submit
- Teks panjang dalam scrollable box (max-h, overflow-y-auto)
- Validasi: `accepted`

### B4. Periode pengajuan (tahun ajaran + semester) di registrasi

**Files:** `register.blade.php`, `RegisterForm.php`, `RegisterPesertaAction.php`

- Tahun Ajaran: read-only (aktif), Semester: dropdown Ganjil/Genap
- **Migration baru:** tambah `tahun_ajaran_id` (FK nullable) + `semester` (enum nullable) ke tabel `peserta`
- `BuatPermohonanAction`: otomatis ambil dari `peserta.tahun_ajaran_id` dan `peserta.semester`
- Update model Peserta: tambah `tahun_ajaran_id`, `semester` ke `$fillable` + cast

---

## C. PESERTA (2 item)

### C1. Hapus 3 jenis dokumen dari enum + berkas pendukung

**File:** `app/Enums/JenisDokumenEnum.php`

- Hapus: `DokumenAsesmenMandiri`, `PenilaianKinerja`, `RpsSilabus`
- Update `label()`, `wajib()`, `options()` match statements
- Checklist di `berkas/index.blade.php` otomatis terupdate (pakai `::cases()`)

### C2. Hapus alumni checkbox dari berkas pendukung

**File:** `resources/views/livewire/peserta/berkas/index.blade.php` line 14, 79-82, 89

- Hapus property `$alumniPcr` dan checkbox UI
- Alumni status sudah tersimpan di `peserta.is_do_pcr` dari registrasi (B4)
- Checklist wajib berkas: baca dari `$this->permohonan->peserta->is_do_pcr` instead

---

## D. ASESOR (3 item)

### D1. Referensi berkas per sub-CPMK jadi clickable

**File:** `resources/views/livewire/asesor/evaluasi/partials/evaluasi-per-mk.blade.php` line 54-64

- Badge referensi berkas saat ini hanya teks → jadikan clickable
- Match `nama_dokumen` di badge dengan `peserta.dokumenBukti` → buat link ke `berkas.view` route atau inline expand (Alpine toggle)

### D2. Status MK auto-trigger setelah semua sub-CPMK dinilai

**File:** `resources/views/livewire/asesor/evaluasi/index.blade.php` method `saveNilaiAsesor()` line 91-107

- Setelah save nilai: cek apakah semua asesmenMandiri di MK sudah ada nilaiAsesor
- Jika semua terisi: hitung rata-rata → auto-set `rpl_mata_kuliah.status` (≥3 Diakui, <3 TidakDiakui)
- Dropdown override tetap tersedia di bawah

### D3. Dashboard asesor lebih interaktif

**File:** `resources/views/livewire/asesor/dashboard.blade.php`

- Stat cards: tambah ikon SVG + hover effect per card
- Tabel: tambah kolom action button (link ke evaluasi)
- Tambah stat "Ditolak"
- Bisa tambah progress visualization (ring atau bar)

---

## File Summary

| #   | File                                                                          | Aksi   | Keterangan                                         |
| --- | ----------------------------------------------------------------------------- | ------ | -------------------------------------------------- |
| 1   | `app/Enums/StatusVerifikasiEnum.php`                                          | MODIFY | Hapus MintaRevisi                                  |
| 2   | `app/Enums/JenisDokumenEnum.php`                                              | MODIFY | Hapus 3 cases, tambah KeteranganMataKuliah         |
| 3   | `database/migrations/xxxx_add_periode_to_peserta.php`                         | NEW    | tahun_ajaran_id + semester                         |
| 4   | `resources/views/livewire/admin/akun/index.blade.php`                         | MODIFY | Toggle pembayaran, ordering                        |
| 5   | `resources/views/livewire/admin/akun/berkas.blade.php`                        | NEW    | Upload berkas per peserta                          |
| 6   | `resources/views/livewire/admin/jadwal/index.blade.php`                       | MODIFY | Date picker, bug fix, Alpine modal, custom hapus   |
| 7   | `resources/views/livewire/admin/pengajuan/detail.blade.php`                   | MODIFY | Modal pembayaran warning                           |
| 8   | `app/Actions/Admin/ProsesPermohonanAction.php`                                | MODIFY | Hapus abort pembayaran                             |
| 9   | `resources/views/livewire/pages/auth/register.blade.php`                      | MODIFY | Date picker, upload berkas, terms, alumni, periode |
| 10  | `app/Livewire/Forms/RegisterForm.php`                                         | MODIFY | Fields baru                                        |
| 11  | `app/Actions/Auth/RegisterPesertaAction.php`                                  | MODIFY | Params baru                                        |
| 12  | `app/Models/Peserta.php`                                                      | MODIFY | Fillable + cast baru                               |
| 13  | `resources/views/livewire/peserta/berkas/index.blade.php`                     | MODIFY | Hapus alumni checkbox, hapus 3 doc types           |
| 14  | `resources/views/livewire/asesor/evaluasi/index.blade.php`                    | MODIFY | Auto-trigger status                                |
| 15  | `resources/views/livewire/asesor/evaluasi/partials/evaluasi-per-mk.blade.php` | MODIFY | Clickable referensi berkas                         |
| 16  | `resources/views/livewire/asesor/dashboard.blade.php`                         | MODIFY | UI improvements                                    |
| 17  | `routes/web.php`                                                              | MODIFY | Route admin berkas                                 |

---

## Urutan Implementasi

1. **Enum fixes** — StatusVerifikasiEnum (A7), JenisDokumenEnum (C1, B2 enum)
2. **Migration** — add periode ke peserta (B4)
3. **Admin akun** — toggle pembayaran (A1), ordering (A3), tombol berkas (A2)
4. **Admin akun berkas** — halaman baru (A2)
5. **Admin jadwal** — full refactor Alpine modal + date picker + bug fix + custom hapus (A4, A5, A6, A9)
6. **Admin pengajuan** — modal pembayaran (A8)
7. **Register** — date picker (B1), upload berkas (B2), terms (B3), alumni (B4), periode (B4)
8. **Peserta berkas** — cleanup (C2)
9. **Asesor evaluasi** — clickable berkas (D1), auto-status (D2)
10. **Asesor dashboard** — UI improvements (D3)

---

## Verification

1. Admin: Toggle pembayaran = toggle switch seperti Aktif
2. Admin: Data descending by created_at
3. Admin: Halaman berkas per peserta accessible dari tabel akun
4. Admin: Jadwal → date picker custom, modal Alpine instant, no edit→tambah bug, custom hapus modal
5. Admin: Proses pengajuan tanpa bayar → modal warning, bukan 403
6. Register: Date picker custom tanggal lahir
7. Register: Upload CV (wajib), Transkrip (skip alumni), Keterangan MK (skip alumni)
8. Register: Checkbox pernyataan wajib centang
9. Register: Pilih periode (tahun ajaran aktif + semester)
10. Peserta: Berkas tidak tampilkan DokumenAsesmenMandiri, PenilaianKinerja, RpsSilabus
11. Asesor: Klik referensi berkas di sub-CPMK → lihat dokumen
12. Asesor: Status MK auto-set saat semua sub-CPMK dinilai
13. Asesor: Dashboard lebih interaktif
