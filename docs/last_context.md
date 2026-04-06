# Last Context: Ringkasan Sesi Pengembangan RPL PCR

> **Tanggal sesi:** 2026-04-01
> **Cakupan:** Bugfix (3 bug kritis) + implementasi dashboard admin & asesor
> File ini merangkum semua perubahan, keputusan teknis, dan cross-reference ke `coding_standards_rpl_pcr.md` dan `project_decisions_rpl_pcr.md`.

---

## RINGKASAN SINGKAT

Sesi ini menyelesaikan **3 bugfix** dan **2 halaman dashboard baru**:

| # | Item | File Terdampak | Status |
|---|------|----------------|--------|
| 1 | Fix broken `@if (false)` tanpa `@endif` di asesor/evaluasi | `asesor/evaluasi/index.blade.php` | ✅ Selesai |
| 2 | Admin tidak bisa buat akun bertipe admin | `admin/akun/index.blade.php`, `TambahAdminAction.php` (baru) | ✅ Selesai |
| 3 | Admin & asesor tidak bisa create MK manual | `admin/materi/prodi.blade.php`, `asesor/materi/prodi.blade.php`, `MataKuliahImport.php` | ✅ Selesai |
| 4 | Dashboard Admin | `admin/dashboard.blade.php` | ✅ Selesai |
| 5 | Dashboard Asesor | `asesor/dashboard.blade.php` | ✅ Selesai |

---

## DETAIL BUG & FIX

### Bug 1 — Broken Template: `@if (false)` tanpa `@endif`

**File:** `resources/views/livewire/asesor/evaluasi/index.blade.php`

**Root cause:** Edit di sesi sebelumnya menambahkan `@include` partials di awal file tetapi **tidak menghapus** blok konten inline lama yang ada di bawahnya. Blok lama dibungkus `@if (false)` sebagai placeholder — tapi `@endif`-nya tidak ada, menyebabkan Blade parse error.

**Fix:** Hapus baris 167–331 (seluruh blok `@if (false)` beserta konten lamanya). Template akhir menggunakan:
```blade
@include('livewire.asesor.evaluasi.partials.verifikasi-bersama')
<x-pengajuan.berkas-pendukung :berkaslist="$permohonan->peserta->dokumenBukti" />
@if (in_array($permohonan->status, [...]))
    <x-pengajuan.sks-rekognisi :permohonan="$permohonan" />
@endif
@include('livewire.asesor.evaluasi.partials.evaluasi-per-mk')
```

**Cross-ref coding standards:** §14 "Gunakan `@include` untuk partial yang menggunakan scope parent" — sudah sesuai.

---

### Bug 2 — Admin Tidak Bisa Buat Akun Admin

**File:** `resources/views/livewire/admin/akun/index.blade.php`
**File baru:** `app/Actions/Admin/TambahAdminAction.php`

**Root cause:** Method `save()` di Volt component hanya memiliki dua branch:
```php
if ($role === 'asesor') { ... }
elseif ($role === 'peserta') { ... }
// tidak ada elseif untuk 'admin' → form reset tanpa action apapun
```

**Fix:**

1. Buat `TambahAdminAction` mengikuti pola `TambahAsesorAction` dan `TambahPesertaAction`:
```php
class TambahAdminAction
{
    public function execute(string $nama, string $email, string $password): User
    {
        $user = User::create([
            'nama'     => $nama,
            'email'    => $email,
            'password' => Hash::make($password),
            'role'     => RoleEnum::Admin,
            'aktif'    => true,
        ]);
        $role = Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
        $user->assignRole($role);
        return $user;
    }
}
```

2. Tambah branch di `save()`:
```php
} elseif ($this->create->roleForm === 'admin') {
    $tambahAdmin->execute(
        $this->create->nama, $this->create->email, $this->create->password,
    );
}
```

**Cross-ref coding standards:** §6 "Action = satu kelas = satu tanggung jawab" — `TambahAdminAction` konsisten dengan pola yang sudah ada.

---

### Bug 3 — Admin & Asesor Tidak Bisa Create Mata Kuliah

Terdapat **dua sub-bug** yang berbeda pada admin dan asesor:

#### 3a. Admin — Validasi SKS `max:6` vs dropdown `range(1,20)`

**File:** `resources/views/livewire/admin/materi/prodi.blade.php`

**Root cause:** Dropdown SKS menampilkan opsi 1–20, tapi validasi server-side punya `max:6`. Jika user pilih SKS > 6:
- Validasi gagal
- Tidak ada elemen `x-show="mkErrors.sks"` untuk menampilkan error SKS ke user
- Form seolah-olah "reset" tanpa feedback — bug tidak tampak.

**Fix:**
1. Ubah validasi `max:6` → `max:20`
2. Tambahkan error display di bawah dropdown SKS:
```html
<p x-show="mkErrors.sks" x-text="mkErrors.sks && mkErrors.sks[0]"
   class="mt-1 text-[10px] text-[#c62828]" style="display:none"></p>
```

#### 3b. Asesor — Tidak ada validasi unique kode MK

**File:** `resources/views/livewire/asesor/materi/prodi.blade.php`

**Root cause:** Tidak ada `Rule::unique` pada field `kode`. Jika kode MK sudah ada di prodi yang sama → unhandled `SQLSTATE[23000]: Integrity constraint violation`.

**Fix:**
```php
use Illuminate\Validation\Rule;

'mk.kode' => ['required', 'string', 'max:20',
    Rule::unique('mata_kuliah', 'kode')
        ->where('program_studi_id', $this->prodi->id)
        ->ignore($this->mk->editId),
],
```
Tambahkan custom message: `'mk.kode.unique' => 'Kode ini sudah digunakan di prodi ini.'`

#### 3c. Konsistensi SKS max:20 di semua lapisan

**File:** `app/Imports/MataKuliahImport.php`

Ubah `(int) $sks > 6` → `(int) $sks > 20` dan pesan errornya, agar import Excel konsisten dengan form manual.

**Keputusan user (diperketat):**
> "sks nya setting jadi bisa maksimal 20"

Semua titik validasi SKS kini seragam: **min:1, max:20** — form admin, form asesor, dan import Excel.

**Cross-ref coding standards:** §5 "Validasi wajib menggunakan `Rule::unique()->where()->ignore()`" untuk unique scoped — sudah sesuai. §5 "Error tampil langsung di bawah field" — sudah sesuai project_decisions §6.

---

## IMPLEMENTASI DASHBOARD

### Dashboard Admin

**File:** `resources/views/livewire/admin/dashboard.blade.php`

**Data yang di-query di `with(): array`:**

| Variabel | Query | Keterangan |
|---|---|---|
| `$totalPengajuan` | `PermohonanRpl::count()` | Semua status |
| `$menunggu` | `where('status', Diajukan)` | Butuh aksi admin |
| `$aktif` | `whereIn([Diproses, Verifikasi, DalamReview])` | Sedang berjalan |
| `$selesai` | `whereIn([Disetujui, Ditolak])` | Final |
| `$distribusi` | Loop semua StatusPermohonanEnum cases, filter count > 0 | Distribusi status |
| `$pengajuanTerbaru` | with peserta.user + programStudi, limit 7 | Tabel terbaru |
| `$totalAsesor` | `User::where('role', RoleEnum::Asesor)->count()` | Statistik user |
| `$totalPeserta` | `User::where('role', RoleEnum::Peserta)->count()` | Statistik user |

**Seksi UI:**
1. **4 stat cards (row 1):** Total Pengajuan (teal), Menunggu Tindakan (biru), Sedang Berjalan (kuning), Selesai (hijau)
2. **2 stat cards (row 2) + distribusi:** Total Asesor, Total Peserta + badge tiap status berisi count
3. **Tabel pengajuan terbaru:** 7 baris, kolom: No Permohonan (link ke detail), Nama Peserta, Prodi, Status badge, Tanggal
4. **Sidebar quick actions:** Kelola Akun, Materi Asesmen, Semua Pengajuan, Program Studi

---

### Dashboard Asesor

**File:** `resources/views/livewire/asesor/dashboard.blade.php`

**Data yang di-query di `with(): array`:**

| Variabel | Query | Keterangan |
|---|---|---|
| `$prodiIds` | `$asesor->programStudi->pluck('id')` | Filter scope asesor |
| `$pengajuanAktif` | `whereIn(prodiIds)->whereNotIn([Draf, Disetujui, Ditolak])` | Pengajuan aktif di prodi saya |
| `$butuhTindakan` | `whereIn([Diproses, Verifikasi])` | Perlu aksi segera |
| `$dalamReview` | `where(DalamReview)` | Evaluasi VATM berjalan |
| `$disetujui` | `where(Disetujui)` | Final sukses |
| `$pengajuanPerhatian` | with peserta.user + programStudi, whereIn 4 active statuses, limit 5 | Tabel perlu perhatian |
| `$prodiList` | `$asesor->programStudi` | Info prodi assigned |

**Penting:** Jika asesor belum punya prodi assigned (`$asesor = null` atau `programStudi` kosong) → `$prodiIds = collect()` → semua query menghasilkan 0, tidak ada exception.

**Seksi UI:**
1. **4 stat cards:** Pengajuan Aktif (teal), Butuh Tindakan (kuning), Dalam Review (kuning), Disetujui (hijau)
2. **Tabel pengajuan perlu perhatian:** 5 baris, kolom: No Permohonan (link ke evaluasi), Nama Peserta, Prodi, Status badge, Tanggal
3. **Sidebar:** Daftar Program Studi yang ditugaskan + CTA button ke Kelola Materi Asesmen

---

## PERATURAN BARU / KEPUTUSAN TEKNIS SESI INI

### Keputusan #1 — SKS Maksimal 20 (diperketat)

> **Sebelumnya:** Validasi `max:6`, dropdown `range(1,20)` → inkonsisten
> **Sekarang:** Semua titik: form admin, form asesor, import Excel → **min:1, max:20**

Berlaku di:
- `admin/materi/prodi.blade.php` — validasi `max:20`, dropdown `range(1,20)`
- `asesor/materi/prodi.blade.php` — validasi `max:20`, dropdown `range(1,20)`
- `MataKuliahImport.php` — cek `> 20`

**Perlu ditambahkan ke `project_decisions_rpl_pcr.md` §7 Fitur & Scope atau §8 Teknis.**

---

### Keputusan #2 — Action Class Wajib untuk Setiap Role Create User

> Pola `TambahXxxAction` wajib ada untuk setiap role:
> - `TambahAsesorAction` ✅ (sudah ada sebelumnya)
> - `TambahPesertaAction` ✅ (sudah ada sebelumnya)
> - `TambahAdminAction` ✅ (ditambahkan sesi ini)

Lokasi: `app/Actions/Admin/`

**Sesuai coding_standards §6:** "Action = satu kelas = satu tanggung jawab, satu aksi."

---

### Keputusan #3 — Error Display Wajib untuk Setiap Field di Form Alpine

> Setiap field yang ada di form Alpine (`x-data`) **wajib memiliki error display** di bawahnya, tidak boleh ada field yang validasinya bisa gagal tapi tidak ada `x-show="errors.field"`.

Pola yang wajib diikuti:
```html
<!-- Field input/select -->
<div>...</div>
<!-- Error display langsung di bawah -->
<p x-show="mkErrors.fieldName"
   x-text="mkErrors.fieldName && mkErrors.fieldName[0]"
   class="mt-1 text-[10px] text-[#c62828]"
   style="display:none"></p>
```

`style="display:none"` diperlukan agar Blade tidak menampilkan elemen sebelum Alpine hydrate.

**Sesuai project_decisions §6:** "Tampilkan error langsung di bawah field."
**Sesuai coding_standards §8 / §14:** Pola "Inline Validation Error via Livewire Event → Alpine."

---

### Keputusan #4 — Validasi Unique MK Scoped per Prodi

> Field `kode` mata kuliah harus divalidasi unique **dalam konteks prodi yang sama**, bukan global.

```php
Rule::unique('mata_kuliah', 'kode')
    ->where('program_studi_id', $this->prodi->id)
    ->ignore($this->mk->editId),  // support mode edit
```

Berlaku di:
- `asesor/materi/prodi.blade.php` ✅
- `admin/materi/prodi.blade.php` — perlu diverifikasi apakah sudah ada

---

### Keputusan #5 — Dashboard Bukan Placeholder

> Dashboard admin dan asesor kini data-driven, bukan halaman statis. Pattern mengikuti `peserta/dashboard.blade.php`:
> - Semua data query di `with(): array`
> - Tidak ada public property untuk menyimpan data query
> - Gunakan `$status->label()` dan `$status->badgeClass()` dari Enum — tidak hardcode teks/warna

**Sesuai coding_standards §4 Volt:** "Gunakan `with(): array` untuk passing data ke template."
**Sesuai project_decisions §2:** Warna dari enum `badgeClass()` konsisten dengan palet resmi.

---

## CROSS-REFERENCE: APAKAH SESUAI STANDAR?

### `coding_standards_rpl_pcr.md`

| Standar | Status | Catatan |
|---------|--------|---------|
| §1 Struktur folder: Action di `app/Actions/{Role}/` | ✅ | `TambahAdminAction` di `app/Actions/Admin/` |
| §3 Model: relasi `peserta.user` via eager loading | ✅ | `with(['peserta.user', 'programStudi'])` di dashboard |
| §4 Volt: `new #[Layout(...)] class extends Component` | ✅ | Kedua dashboard menggunakan pola ini |
| §4 Volt: `with(): array` untuk data | ✅ | Semua data query di `with()`, tidak ada public property |
| §5 Validasi: `Rule::unique()->where()->ignore()` | ✅ | Diterapkan di asesor/materi |
| §5 Validasi: error tampil di bawah field | ✅ | Error `<p x-show>` ditambahkan untuk field SKS |
| §5 Validasi: custom message dalam Bahasa Indonesia | ✅ | `'mk.kode.unique' => 'Kode ini sudah digunakan...'` |
| §6 Action: satu kelas satu tanggung jawab | ✅ | `TambahAdminAction::execute()` |
| §14 Pola Alpine form: state di Alpine, submit ke Livewire | ✅ | Form MK menggunakan pola `x-data` + `$wire.call()` |
| §14 Error via `$this->dispatch('validation-errors', errors:)` | ✅ | Admin/asesor materi menggunakan pola dispatch ke Alpine |

### `project_decisions_rpl_pcr.md`

| Keputusan | Status | Catatan |
|-----------|--------|---------|
| §2 Warna teal `#004B5F` untuk elemen primer | ✅ | Stat card Total Pengajuan & Pengajuan Aktif |
| §2 Badge biru `#E8F0FE/#1557b0` untuk status info | ✅ | Menunggu Tindakan (Diajukan) |
| §2 Badge kuning `#FFF8E1/#b45309` untuk warning | ✅ | Sedang Berjalan, Butuh Tindakan, Dalam Review |
| §2 Badge hijau `#E6F4EA/#1e7e3e` untuk sukses | ✅ | Selesai, Disetujui |
| §3 Tipografi: teks body 13px, caption 10–11px | ✅ | Stat card label `text-[11px]`, nilai `text-[28px]` |
| §5 Dropdown custom `x-form.select` atau Alpine equiv | ✅ | Tidak ada native `<select>` di form MK |
| §6 Error tampil di bawah field, warna `#c62828` | ✅ | `class="text-[10px] text-[#c62828]"` |
| §6 Bahasa Indonesia formal, sapaan "Anda" | ✅ | Semua label dan pesan error dalam Bahasa Indonesia |
| §8 Spatie Permission: `Role::firstOrCreate` + `assignRole` | ✅ | Digunakan di `TambahAdminAction` |

### Yang Perlu Diupdate di Docs Lain

| File | Seksi | Yang perlu ditambahkan |
|------|-------|------------------------|
| `project_decisions_rpl_pcr.md` | §7 atau §8 | Keputusan SKS max 20 |
| `project_decisions_rpl_pcr.md` | §8 Teknis | Keputusan: setiap role create user wajib punya Action class sendiri |
| `coding_standards_rpl_pcr.md` | §5 atau §14 | Keputusan: field SKS selalu range 1–20 di form |

---

## DAFTAR FILE YANG DIUBAH SESI INI

```
app/
└── Actions/
    └── Admin/
        └── TambahAdminAction.php          [BARU] — action create akun admin

app/
└── Imports/
    └── MataKuliahImport.php               [EDIT] — ubah cek sks > 6 → > 20

resources/views/livewire/
├── admin/
│   ├── akun/
│   │   └── index.blade.php                [EDIT] — tambah elseif branch untuk role admin
│   ├── dashboard.blade.php                [EDIT PENUH] — dari placeholder ke dashboard data-driven
│   └── materi/
│       └── prodi.blade.php                [EDIT] — ubah max:6 → max:20, tambah error display SKS
├── asesor/
│   ├── dashboard.blade.php                [EDIT PENUH] — dari placeholder ke dashboard data-driven
│   ├── evaluasi/
│   │   └── index.blade.php                [EDIT] — hapus blok @if(false) yang rusak
│   └── materi/
│       └── prodi.blade.php                [EDIT] — tambah Rule::unique kode, max:20 sks
```

---

## CATATAN UNTUK SESI BERIKUTNYA

1. **Verifikasi `admin/materi/prodi.blade.php`** — apakah validasi `kode` MK sudah punya `Rule::unique` scoped per prodi atau belum (asesor sudah ditambahkan, admin perlu dicek).

2. **Dashboard admin — kolom `nomor_permohonan`** — jika ada pengajuan yang masih dalam status `Draf` dan belum punya nomor, kolom ini akan menampilkan `—`. Pertimbangkan apakah perlu filter exclude Draf dari tabel terbaru.

3. **Dashboard asesor — link ke evaluasi** — menggunakan `route('asesor.evaluasi.index', $p)`. Pastikan route tersebut accessible untuk semua status yang ditampilkan (Diajukan, Diproses, Verifikasi, DalamReview).

4. **SKS max:20** — perlu didokumentasikan di `project_decisions_rpl_pcr.md` agar keputusan ini tidak di-revert oleh pengembang lain.

5. **Pengajuan admin** — route `admin.pengajuan.index` dan `admin.pengajuan.detail` sudah ada di routes, tapi halaman detailnya (`admin/pengajuan/detail.blade.php`) perlu dicek apakah sudah diimplementasikan.

---

_Dibuat otomatis pada 2026-04-01. Untuk sesi selanjutnya baca file ini terlebih dahulu sebelum mulai._
