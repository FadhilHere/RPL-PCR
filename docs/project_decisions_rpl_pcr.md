# Project Decisions: Sistem RPL PCR

> File ini mencatat semua keputusan desain, UI/UX, dan preferensi yang WAJIB diikuti selama pengembangan.
> Setiap keputusan baru ditambahkan di bawah dengan tanggal dan alasan.

---

## CARA MENGGUNAKAN FILE INI

- **Sebelum mulai fitur baru** → baca file ini dari atas ke bawah
- **Ada keputusan baru?** → tambahkan entry baru di seksi yang relevan dengan format:
    ```
    > [YYYY-MM-DD] Deskripsi keputusan dan alasannya
    ```
- **Ada perubahan dari keputusan lama?** → coret yang lama (~~teks~~) dan tambahkan keputusan baru di bawahnya
- File ini dibaca **bersama** `database_design_rpl_pcr.md` dan `coding_standards_rpl_pcr.md`

---

## 1. IDENTITAS & BRANDING

### Nama Aplikasi

> [2026-03-20] Nama aplikasi: **Sistem Asesmen RPL PCR** (belum final, menunggu konfirmasi dari pihak PCR)

### Logo & Favicon

> [2026-03-20] Gunakan ikon berbentuk tiga lapisan (stack/layers) berwarna `#004B5F` di atas latar putih dengan `border-radius: 8px`. Merepresentasikan tumpukan pengalaman/kredensial. Implementasi sebagai inline SVG, bukan file gambar.

---

## 2. WARNA (COLOR PALETTE)

### Palet Warna

> [2026-03-20] Warna resmi logo PCR:
>
> - **Primary: `#004B5F`** (biru gelap PCR) → tombol utama, header, sidebar, link aktif
> - **Accent: `#D2092F`** (merah PCR) → digunakan sparingly — badge, notifikasi penting, peringatan, destruktif action
> - **Background dominant: putih (`#FFFFFF`)** → halaman bersih dan profesional

### Panduan penggunaan warna:

- **`#005f78`** → hover state dari elemen primary (satu shade lebih terang dari `#004B5F`)
- **`#003d4f`** → active/pressed state dari elemen primary (satu shade lebih gelap)
- **`#D2092F`** → hanya jika dibutuhkan: badge status ditolak, tombol delete, notifikasi dot, peringatan kritis
- **`#F4F6F8`** → background halaman (bukan putih murni — abu sangat muda agar card putih terlihat menonjol)
- **Putih `#FFFFFF`** → card, form input, topbar, modal
- **`#E5E8EC`** → border default card dan divider
- **`#8a9ba8`** → teks sekunder / label / placeholder / deskripsi
- **`#1a2a35`** → teks utama (judul, nilai penting)

### Status colors (badge & label):

| Status                  | Background | Teks      | Tailwind approx          |
| ----------------------- | ---------- | --------- | ------------------------ |
| Diakui / Sukses         | `#E6F4EA`  | `#1e7e3e` | `green-100 / green-700`  |
| Dievaluasi / Warning    | `#FFF8E1`  | `#b45309` | `yellow-50 / yellow-700` |
| Ditolak / Danger        | `#FCE8E6`  | `#c62828` | `red-50 / red-800`       |
| Draft / Info            | `#E8F0FE`  | `#1557b0` | `blue-50 / blue-800`     |
| Belum Dinilai / Neutral | `#F1F3F4`  | `#5f6368` | `gray-100 / gray-600`    |
| Menunggu Asesor         | `#E8F0FE`  | `#1557b0` | `blue-50 / blue-800`     |

> [2026-03-20] Warna badge TIDAK menggunakan `#D2092F` langsung sebagai background — gunakan varian pastel dari merah (`#FCE8E6`) untuk background, dan merah gelap untuk teks. Aksen merah PCR hanya muncul sebagai solid pada elemen seperti notification dot atau tombol destruktif.

---

## 3. TIPOGRAFI

### Font

> [2026-03-20] **Poppins** (Google Fonts) — untuk semua teks UI

### Skala ukuran teks

> [2026-03-20] Skala yang digunakan (bukan default Tailwind semua):

| Elemen                    | Ukuran    | Weight |
| ------------------------- | --------- | ------ |
| Judul halaman / h1        | `22px`    | `600`  |
| Judul card / section      | `13–15px` | `600`  |
| Teks body / label form    | `13px`    | `400`  |
| Teks sekunder / deskripsi | `12px`    | `400`  |
| Badge / tag / caption     | `10–11px` | `600`  |
| Placeholder input         | `13px`    | `400`  |

> Font-size minimum: **10px** — jangan lebih kecil dari ini.

---

## 4. LAYOUT & NAVIGASI

### Struktur Layout per Role

> [2026-03-20] Setiap role memiliki layout terpisah:
>
> - Peserta → sidebar kiri + konten kanan
> - Asesor → sidebar kiri + konten kanan
> - Admin → sidebar kiri + konten kanan

### Sidebar

> [2026-03-20] Keputusan sidebar (dari mockup dashboard peserta):
>
> - **Lebar:** `240px`, fixed ( collapsible untuk MVP)
> - **Background:** `#004B5F` (solid, bukan transparan)
> - **Item menu aktif:** overlay `rgba(255,255,255,0.12)` dengan teks putih penuh — bukan warna aksen merah
> - **Item menu hover:** overlay `rgba(255,255,255,0.07)`
> - **Teks menu default:** `rgba(255,255,255,0.7)`
> - **Ikon:** inline SVG 16×16px, stroke-based, `opacity: 0.8` (aktif: `opacity: 1`)
> - **Section label:** uppercase, `10px`, `letter-spacing: 1px`, `rgba(255,255,255,0.35)`
> - **Footer sidebar:** berisi avatar + nama + role pengguna yang login
> - **Badge notifikasi di menu:** background `#D2092F`, teks putih, `border-radius: 10px`

> [2026-03-20] Keputusan topbar (dari mockup dashboard peserta):
>
> - **Background:** putih, `border-bottom: 1px solid #E5E8EC`
> - **Tinggi:** `56px`
> - **Konten kiri:** judul halaman (`15px/600`) + subjudul tanggal & info (`12px`, `#8a9ba8`)
> - **Konten kanan:** tombol notifikasi (ikon bell + dot merah `#D2092F` jika ada notif baru)
> - Topbar tidak menampilkan nama pengguna (sudah ada di footer sidebar)

### Halaman Login

> [2026-03-20] Layout login: **dua panel horizontal**
>
> - **Panel kiri (420px fixed):** background `#004B5F`, berisi logo, tagline, dan ringkasan alur 4 langkah RPL
> - **Panel kanan (flex: 1):** background `#F4F6F8`, berisi form login di tengah (max-width `400px`)
> - Panel kiri menggunakan dekorasi lingkaran transparan (`rgba(255,255,255,0.04)`) di sudut — subtle, tidak mengganggu
> - **Role switcher:** tampilkan 3 pill (Peserta / Asesor / Admin) di atas form — pill aktif solid `#004B5F`, lainnya border abu
> - **Info box:** tampilkan kotak info kecil (`#F0F7FA` background) di bawah role switcher yang menjelaskan role yang sedang dipilih
> - **Tombol daftar:** outline style (putih + border abu), letakkan di bawah divider "Belum punya akun?"
> - **Help text:** di bawah semua tombol, berisi kontak helpdesk institusi

---

## 5. KOMPONEN UI

### Tombol (Button)

> [2026-03-20] Keputusan komponen tombol:
>
> - **Primary:** background `#004B5F`, teks putih, `border-radius: 7px`, tinggi `44px`, `font-size: 13px`, `font-weight: 600`
>     - Hover: `#005f78` | Active: `#003d4f`
> - **Secondary / Outline:** background putih, border `1.5px solid #D8DDE2`, teks `#004B5F`
>     - Hover: border berubah ke `#004B5F`, background `#F0F7FA`
> - **Destruktif:** background `#D2092F`, teks putih — hanya untuk aksi hapus/tolak
> - **Lebar penuh:** gunakan `width: 100%` pada form login dan form tunggal
> - **Inline/kecil:** padding `8px 16px`, tinggi `34px` — untuk CTA di dalam card

### Tabel Data

> [2026-03-20] Gunakan **pagination** (bukan infinite scroll) — lebih familiar untuk pengguna institusi pendidikan. Detail implementasi Livewire menyusul.

### Form

> _(belum ditentukan — layout 1 kolom atau 2 kolom, label posisi atas atau samping)_

### Dropdown / Select

> [2026-03-22] **Semua dropdown di seluruh aplikasi WAJIB menggunakan desain custom `x-form.select`** — tidak boleh ada `<select>` HTML native yang terekspos ke UI.
>
> **Tampilan yang WAJIB diterapkan:**
> - Trigger button: `h-[42px]`, `border border-[#E0E5EA]`, `rounded-xl`, `text-[13px]`, hover → `border-[#C5CDD5]`
> - Focus/open state: `border-[#004B5F]` + `ring-2 ring-[#004B5F]/10`
> - Chevron SVG `w-4 h-4 text-[#8a9ba8]` yang rotate 180° saat terbuka
> - Panel dropdown: `bg-white border border-[#E0E5EA] rounded-xl shadow-lg`, animasi `opacity + -translate-y-1`
> - Item aktif/terpilih: `bg-[#E8F4F8] text-[#004B5F] font-semibold`
> - Item hover: `hover:bg-[#F4F6F8]`
> - Max-height `220px` dengan `overflow-y-auto` agar scrollable jika item banyak
>
> **Dua cara implementasi yang diizinkan:**
> 1. **`<x-form.select wire:model.live="property" :options="[...]" />`** — untuk dropdown yang terikat ke Livewire property (filter tabel, form dengan `wire:model`)
> 2. **Inline Alpine dropdown** (lihat `admin/materi/prodi.blade.php`) — untuk dropdown di dalam form modal yang state-nya dikelola Alpine (`x-model`). Buat trigger + panel dengan class identik di atas, ganti `$wire.entangle` dengan binding langsung ke Alpine variable.
>
> **Yang TIDAK boleh:**
> - `<select>` HTML native tanpa styling (tampilan default browser)
> - Dropdown dengan desain berbeda dari spesifikasi di atas

### Notifikasi / Alert

> [2026-03-20] Dua jenis:
>
> - **Info box inline** (warna `#F0F7FA`, border `#C5DDE5`) → dipakai di dalam form/halaman untuk panduan kontekstual
> - **Welcome banner** (background `#004B5F`) → dipakai di dashboard untuk pesan status pengajuan yang aktif
> - Toast / snackbar → belum ditentukan, menyusul saat implementasi aksi form

### Timeline / Alur Status

> [2026-03-20] Komponen timeline vertikal untuk menampilkan alur pengajuan peserta:
>
> - Dot selesai: `background: #E6F4EA`, teks `#1e8e3e`, isi tanda ✓
> - Dot aktif: `background: #004B5F`, teks putih, isi nomor langkah
> - Dot pending: `background: #F1F3F4`, teks `#9aa0a6`
> - Garis penghubung: `1px solid #E5E8EC`, di tengah dot secara vertikal
> - Teks tahap aktif dilengkapi tag `[Tahap saat ini]` berwarna `#004B5F`

---

## 6. BAHASA & TEKS

### Bahasa Antarmuka

> [2026-03-20] Bahasa Indonesia untuk semua teks antarmuka (label, pesan error, placeholder, dll)

### Tone of Voice

> [2026-03-20] Formal tapi ramah — ini aplikasi institusi pendidikan resmi (PCR)
> [2026-03-20] Gunakan sapaan "Anda" (bukan "kamu") di semua teks antarmuka — pengguna adalah dosen dan calon mahasiswa dewasa

### Pesan Error Validasi

> [2026-03-20] Tampilkan **langsung di bawah field** yang bermasalah — bukan summary di atas form. Warna teks error: `#c62828` (merah gelap), `font-size: 11px`.

### Teks Placeholder

> [2026-03-20] Placeholder harus deskriptif dan memberi contoh nyata, bukan sekadar mengulang label. Contoh: label "Alamat Email" → placeholder "contoh@email.com"

---

## 7. FITUR & SCOPE

### Yang MASUK scope (MVP)

> [2026-03-20] Berdasarkan `knowledge_base_rpl_pcr.md`:
>
> - Auth 3 role: peserta, asesor, admin
> - Pengajuan RPL Tipe A saja (Transfer Kredit + Perolehan Kredit)
> - Self-assessment per Pertanyaan (berdasarkan CPMK per MK)
> - Upload dokumen bukti
> - Evaluasi VATM oleh asesor (per pertanyaan/asesmen mandiri)
> - Import data MK via Excel (maatwebsite/excel)
> - Penjadwalan konsultasi
> - Output SK Rekognisi (upload PDF oleh admin)

### Yang TIDAK masuk scope (eksklusi MVP)

> [2026-03-20] RPL Tipe B (penyetaraan kualifikasi dosen) — tidak dibangun, inisiatif PCR bukan individu
> [2026-03-20] Integrasi PDDIKTI (verifikasi ijazah) — manual dulu oleh asesor
> [2026-03-20] Generate PDF SK otomatis — admin upload PDF yang sudah dibuat manual

### Batas SKS Mata Kuliah

> [2026-04-01] **SKS mata kuliah: min 1, max 20** — berlaku di semua titik validasi dan input:
>
> - Form admin (`admin/materi/prodi.blade.php`): dropdown `range(1, 20)`, validasi `max:20`
> - Form asesor (`asesor/materi/prodi.blade.php`): dropdown `range(1, 20)`, validasi `max:20`
> - Import Excel (`MataKuliahImport.php`): cek `(int) $sks > 20`
>
> **Jangan** set max lebih rendah (misal max:6) di salah satu titik saja tanpa mengubah yang lain — akan menyebabkan bug silent di mana form gagal validasi tapi tidak ada feedback ke user.

---

## 8. KEPUTUSAN TEKNIS

### Framework & Library

> [2026-03-20] Laravel (versi terbaru stable) + Tailwind CSS
> [2026-03-20] `spatie/laravel-permission` untuk manajemen role
> [2026-03-20] `maatwebsite/excel` untuk import/export Excel

### Database

> [2026-03-20] MySQL

### File Storage

> [2026-03-20] Laravel Storage disk `local` (bukan S3) untuk MVP

### Auth

> [2026-03-20] Gunakan **Laravel Breeze** — simple, tidak over-engineered, cocok untuk project ini
> [2026-03-20] Halaman login Breeze di-override penuh dengan desain custom (dua panel) — jangan pakai tampilan default Breeze

### Action Class per Role Create User

> [2026-04-01] Setiap role memiliki **Action class tersendiri** untuk proses create akun user. Wajib dibuat di `app/Actions/Admin/`:
>
> - `TambahAsesorAction` — create user role asesor + profil asesor + assign Spatie role
> - `TambahPesertaAction` — create user role peserta + profil peserta + assign Spatie role
> - `TambahAdminAction` — create user role admin + assign Spatie role `admin`
>
> **Jangan** tambahkan logic create user langsung di method `save()` Volt component — wajib delegasi ke Action. Pola ini memastikan konsistensi: setiap create user selalu melalui `Hash::make()`, `User::create()`, `Role::firstOrCreate()`, dan `assignRole()` tanpa ada yang terlewat.

### Validasi Kode MK: Unique Scoped per Prodi

> [2026-04-01] Field `kode` mata kuliah divalidasi unique **dalam konteks prodi yang sama** — bukan global unik:
>
> ```php
> Rule::unique('mata_kuliah', 'kode')
>     ->where('program_studi_id', $this->prodi->id)
>     ->ignore($this->mk->editId)  // support mode edit
> ```
>
> Berlaku di form asesor dan admin materi. Tanpa ini, kode yang sama di prodi berbeda akan conflict di DB dan melempar `SQLSTATE[23000]` tanpa feedback ke user.

### Dashboard Admin & Asesor: Data-Driven

> [2026-04-01] Dashboard admin dan asesor **wajib data-driven** — bukan halaman statis/placeholder. Mengikuti pola `peserta/dashboard.blade.php`:
>
> - Semua data query dilakukan di `with(): array`, tidak ada public property untuk data
> - Gunakan `$status->label()` dan `$status->badgeClass()` dari Enum — tidak hardcode warna/teks
> - Dashboard asesor: semua query di-scope ke `program_studi_id` milik asesor tersebut — jangan tampilkan data prodi lain
> - Jika asesor belum punya prodi assigned → `$prodiIds = collect()` → semua count 0, tidak boleh error

### Frontend Interactivity

> [2026-03-20] **Livewire v3 + Alpine.js**
>
> - Livewire → untuk komponen yang butuh interaksi server (form multi-step, tabel dengan filter/pagination, real-time status)
> - Alpine.js → untuk interaksi ringan di sisi client saja (toggle, dropdown, modal sederhana)
> - Jangan campur keduanya di satu komponen yang sama tanpa alasan yang jelas

> [2026-03-22] **Aturan pembagian Livewire vs Alpine di Livewire Volt (diperketat):**
>
> Berdasarkan pengalaman membangun halaman akun admin dan refactor materi/prodi, aturan ini dibuat eksplisit:
>
> - **Livewire HANYA untuk:** query DB, validasi, simpan/update/hapus data, dan state yang perlu di-persist/di-share antar request
> - **Alpine WAJIB untuk:** show/hide modal, tab switcher, inline edit toggle, expand/collapse, role selector di form, dan semua state yang hanya hidup selama user berada di halaman tersebut
> - **Alasan:** Setiap property Livewire yang berubah memicu network round-trip (~500ms). State UI yang murni client-side tidak seharusnya membebani server.
> - **Pola form modal:** Alpine kelola seluruh form state (`x-data` di outer div), Livewire menerima data sebagai parameter method saat submit — bukan via `wire:model` per-field
> - **Pola error inline:** Jika form state di Alpine (bukan `wire:model`), gunakan `validator()` helper + `$this->dispatch('validation-errors', errors:)` untuk mengirim error balik ke Alpine; Alpine tampilkan `x-show` + `x-text` di bawah field yang bermasalah
> - Detail implementasi ada di `coding_standards_rpl_pcr.md` §14 — "Pola: Alpine Form State + Livewire Persistence" dan "Pola: Inline Validation Error via Livewire Event → Alpine"

### Design System / Komponen UI

> [2026-03-20] **shadcn/ui sebagai inspirasi design language** — bukan library yang di-install, melainkan dijadikan referensi visual dan pola komponen
>
> - Implementasi manual menggunakan Tailwind CSS + Alpine.js (tidak pakai React/shadcn package)
> - Tujuan: tampilan bersih, modern, dan profesional ala shadcn — tapi **tidak kaku**
> - Boleh kreatif dan customizable di beberapa bagian (warna PCR, ilustrasi, animasi ringan)
> - Prioritas konsistensi komponen: card, table, badge, dialog/modal, form input, sidebar
> - Admin panel → lebih structured (data-heavy, tabel, statistik)
> - Peserta/Asesor panel → boleh lebih friendly dan visual

---

## 9. HALAMAN & ALUR (USER FLOW)

### Alur Peserta

> [2026-03-20] Mengikuti alur resmi PCR:
> Daftar akun → Lengkapi profil → Konsultasi awal → Pengajuan RPL (pilih prodi + MK) → Self-assessment → Upload dokumen → Tunggu hasil

### Alur Asesor

> [2026-03-20] Terima assignment dari admin → Review self-assessment + dokumen → Input evaluasi VATM → Input nilai akhir → Konsultasi lanjutan jika perlu

### Alur Admin

> [2026-03-20] Import data MK → Terima pengajuan masuk → Assign asesor → Monitor progress → Upload SK Direktur

---

## LOG PERUBAHAN

> Catat di sini setiap kali ada keputusan yang **diubah** dari yang sudah ditetapkan sebelumnya.

| Tanggal    | Seksi          | Perubahan                                                                                  |
| ---------- | -------------- | ------------------------------------------------------------------------------------------ |
| 2026-03-20 | §3 Tipografi   | Skala ukuran teks ditentukan secara eksplisit (sebelumnya: "mengikuti default Tailwind")   |
| 2026-03-20 | §4 Sidebar     | Lebar, warna, behavior item aktif/hover ditentukan (sebelumnya: belum ditentukan)          |
| 2026-03-20 | §5 Tombol      | Spesifikasi ukuran, warna, radius, dan state ditentukan (sebelumnya: belum ditentukan)     |
| 2026-03-20 | §5 Form        | Layout 1 kolom, label di atas, spesifikasi input ditentukan (sebelumnya: belum ditentukan) |
| 2026-03-20 | §5 Notifikasi  | Info box inline + welcome banner ditentukan (sebelumnya: belum ditentukan)                 |
| 2026-03-20 | §6 Pesan Error | Tampil langsung di bawah field, bukan summary atas (sebelumnya: belum ditentukan)          |
| 2026-03-20 | §1 Logo        | Ikon layers SVG ditentukan sebagai arah sementara (sebelumnya: belum ditentukan)           |
| 2026-03-20 | §4 Login       | Layout dua panel + role switcher + info box ditentukan                                     |
| 2026-03-20 | §8 Auth        | Ditambahkan: halaman login Breeze wajib di-override dengan desain custom                   |
| 2026-03-22 | §8 Frontend    | Aturan Livewire vs Alpine diperketat: Alpine wajib untuk semua UI state, Livewire hanya DB |
| 2026-03-22 | §5 Dropdown    | Seluruh dropdown WAJIB custom design (x-form.select atau inline Alpine equivalent), native `<select>` dilarang |
| 2026-04-01 | §7 Fitur       | SKS mata kuliah: min 1, max 20 di semua titik (form admin, form asesor, import Excel) |
| 2026-04-01 | §8 Auth        | Action class wajib per role: TambahAsesorAction, TambahPesertaAction, TambahAdminAction |
| 2026-04-01 | §8 Teknis      | Validasi kode MK wajib unique scoped per prodi menggunakan Rule::unique->where->ignore |
| 2026-04-01 | §9 Halaman     | Dashboard admin & asesor: data-driven, scope query per prodi untuk asesor |

---

_File ini hidup sepanjang project berlangsung. Selalu update sebelum mulai sprint baru._
