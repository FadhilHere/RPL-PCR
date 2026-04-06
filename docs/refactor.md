Lakukan audit dan refactoring menyeluruh pada seluruh codebase project ini sesuai 
coding standards yang berlaku. Jangan terbatas pada satu file — scan semua file 
yang relevan.

## Baca Dulu Sebelum Mulai
Baca file coding standards project di `.claude/coding-standards.md` (atau dokumen 
standar yang tersedia di project). Pahami seluruh isinya sebelum melakukan apapun.

Juga baca struktur project terlebih dahulu:
- Seluruh `app/Actions/`
- Seluruh `app/Livewire/`
- Seluruh `resources/views/livewire/`
- Seluruh `resources/views/components/`

---

## FASE 0 — AUDIT MENYELURUH (jangan skip, jangan mulai coding dulu)

Scan SEMUA file di direktori berikut dan buat audit report lengkap:
- `resources/views/livewire/**/*.blade.php`
- `app/Livewire/**/*.php`
- `app/Actions/**/*.php`
- `app/Models/**/*.php`
- `resources/views/components/**/*.blade.php`

Untuk setiap pelanggaran yang ditemukan, catat dalam format:
[JENIS PELANGGARAN] File: path/to/file.php — Line: XX
Masalah: (jelaskan apa yang salah)
Solusi: (jelaskan apa yang harus dilakukan)
Kategori pelanggaran yang harus dicari:

### A. Business Logic di Volt (seharusnya di Action)
- Method Volt yang isinya lebih dari sekedar validate → call action → redirect/dispatch
- DB query kompleks, multi-step operation, atau domain logic langsung di Volt method

### B. Query Langsung di Blade / `with()`
- `Model::all()` atau query builder langsung di `with()` tanpa scope
- `@php` block di Blade yang berisi query database

### C. Logic & Kalkulasi di Blade
- `@php` block yang berisi kalkulasi (persentase, total, kondisi kompleks)
- `match()` atau kondisi panjang langsung di template
- String literal status/jenis yang seharusnya pakai Enum method (`->label()`, `->badgeClass()`)

### D. Pelanggaran Livewire + Alpine
- Modal show/hide yang masih menggunakan `wire:click="$set('modal.show', true)"`
- Toggle UI yang trigger full Livewire round-trip (seharusnya Alpine)
- `$this->model->load()` di dalam method yang dipanggil per-klik kecil
- Method yang tidak butuh re-render tapi belum pakai `#[Renderless]`
- Form tab/role switcher yang masih pakai `wire:click` (seharusnya Alpine `@click`)

### E. Pelanggaran Model
- `protected $casts = [...]` (seharusnya `protected function casts(): array`)
- Business logic di dalam Model
- Query di dalam Model (bukan scope)

### F. Blade Component yang Bisa Di-extract
- Section blade yang panjang (>40 baris) dan berdiri sendiri secara logis
- Section yang duplikat atau mirip di beberapa file
- Modal yang bisa jadi partial `@include`

### G. Pelanggaran Naming & Struktur
- Enum case yang masih UPPER_CASE (seharusnya PascalCase Indonesia)
- Variabel atau method yang tidak konsisten dengan konvensi
- Action yang belum dikelompokkan per domain (`Admin/`, `Peserta/`, `Asesor/`)

### H. Masalah Keamanan & Konsistensi
- Method Volt yang tidak ada `abort_if` padahal seharusnya ada
- File upload tanpa validasi mime/size
- `{!! !!}` tanpa sanitasi eksplisit

---

Setelah audit selesai, tampilkan:
1. **Summary**: total berapa pelanggaran per kategori, per folder
2. **Priority list**: urutkan dari yang paling kritikal (business logic) ke kosmetik (naming)
3. **Dependency map**: file mana yang harus direfactor duluan karena file lain bergantung padanya

Tunggu konfirmasi sebelum lanjut ke FASE 1.

---

## FASE 1 — EXTRACT SEMUA ACTION CLASS

Berdasarkan temuan FASE 0 kategori A, extract semua business logic ke Action class.

Aturan:
- Satu operasi bisnis = satu Action class
- Kelompokkan per domain: `app/Actions/Admin/`, `app/Actions/Peserta/`, `app/Actions/Asesor/`
- Setiap Action wajib punya method `execute()`
- Operasi multi-tabel wajib dalam `DB::transaction()`
- `abort_if` validasi status tetap di Action (bukan di Volt)
- Validasi yang perlu `dispatch` event ke Alpine tetap di Volt

Setelah setiap Action dibuat, update Volt component-nya:
- Inject Action via method parameter, bukan constructor
- Method Volt harus hanya: validate → call action → reload/redirect/dispatch

Kerjakan per file, laporkan progress setiap selesai satu file.

---

## FASE 2 — BERSIHKAN SEMUA VOLT COMPONENT

Berdasarkan temuan FASE 0 kategori D, untuk setiap Volt file:

1. Tambahkan `#[Renderless]` pada method yang hanya sync state ke DB tanpa perlu re-render HTML
2. Ganti `$this->model->load()` per-klik dengan update property lokal saja
3. Pastikan semua kondisi yang sama dipakai 2+ method dijadikan private method helper
4. Pastikan semua `use` import lengkap setelah Action diekstrak
5. Verifikasi `with()` hanya berisi data yang memang perlu fresh setiap render

---

## FASE 3 — PERBAIKI SEMUA MODEL

Berdasarkan temuan FASE 0 kategori E:

1. Ganti semua `protected $casts = [...]` menjadi `protected function casts(): array`
2. Pastikan semua kolom status/jenis di-cast ke Enum yang sesuai
3. Hapus business logic dari Model, pindah ke Action
4. Ubah query di Model menjadi scope jika memang filter yang sering dipakai

---

## FASE 4 — EXTRACT BLADE COMPONENTS & PARTIAL

Berdasarkan temuan FASE 0 kategori C dan F:

### Untuk setiap `@php` block kalkulasi di Blade:
- Pindahkan ke dalam Blade component sebagai logic di atas `@props`
- Atau pindahkan ke `with()` di Volt jika data memang dari server

### Untuk setiap section panjang yang bisa berdiri sendiri:
Buat Blade component di `resources/views/components/{role}/`:
- Definisikan `@props` dengan benar
- Pindahkan `@php` logic ke dalam component
- Pastikan tidak ada query di dalam component

### Untuk modal:
- Pindahkan ke partial `@include` di subfolder `partials/` dekat file Volt-nya
- Pastikan semua variable yang dibutuhkan modal di-pass via `compact()` atau array

### Aturan penamaan component:
resources/views/components/
├── admin/
│   └── pengajuan/
│       ├── info-peserta.blade.php
│       └── ...
├── asesor/
│   └── ...
└── peserta/
└── ...
---

## FASE 5 — PERBAIKI ENUM & NAMING

Berdasarkan temuan FASE 0 kategori G dan H:

1. Ganti semua Enum case UPPER_CASE ke PascalCase Indonesia
2. Setelah rename Enum case, cari dan update SEMUA referensi di seluruh codebase
   (Volt, Blade, Model, Action, Seeder) — jangan sampai ada yang miss
3. Tambahkan method `label()` dan `badgeClass()` pada Enum yang belum punya
4. Ganti semua string literal status di Blade dengan `$model->status->label()` 
   dan `$model->status->badgeClass()`

---

## FASE 6 — VERIFIKASI MENYELURUH

Setelah semua fase selesai, jalankan verifikasi ini TANPA SKIP:

### Verifikasi PHP/Laravel
```bash
php artisan route:list
php artisan view:cache
php artisan config:cache
php artisan optimize
```
Pastikan tidak ada error di semua perintah di atas.

### Verifikasi Manual per File
Untuk setiap file yang diubah, cek:
- [ ] Semua `use` import ada dan benar
- [ ] Semua `@props` di Blade component terdefinisi
- [ ] Semua variable di `@include` partial sudah di-pass
- [ ] `wire:key` masih ada di semua list item
- [ ] Event listener Alpine masih terhubung ke method/dispatch yang benar
- [ ] `$wire.methodName()` di Blade component masih memanggil nama method yang ada di Volt
- [ ] Tidak ada `@php` block yang berisi query database
- [ ] Tidak ada string literal status yang seharusnya pakai Enum

### Verifikasi Struktur Akhir
- [ ] Setiap Volt class akhir < 80 baris PHP
- [ ] Setiap Blade utama akhir < 40 baris (di luar modal partial)
- [ ] Semua Action sudah dikelompokkan per domain folder
- [ ] Tidak ada duplikasi logic antara Volt dan Action

---

## ATURAN GLOBAL SELAMA REFACTORING

- **Jangan ubah fungsionalitas** — refactor saja, bukan rewrite logic
- **Kerjakan per fase**, laporkan hasil tiap fase, tunggu konfirmasi sebelum lanjut
- **Jika menemukan bug** di luar scope refactor, catat di "Temuan Tambahan" — 
  jangan langsung fix tanpa konfirmasi
- **Jika ada ambiguitas** (tidak yakin apakah ini pelanggaran atau bukan), 
  tanyakan dulu sebelum mengubah
- **Prioritaskan tidak breaking** daripada sempurna — jika ekstraksi suatu 
  component berisiko, skip dulu dan catat
