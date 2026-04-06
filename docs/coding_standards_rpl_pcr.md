# Coding Standards: Sistem RPL PCR
> Laravel 11 + Livewire Volt + Tailwind | Berlaku untuk semua kontributor

---

## 1. STRUKTUR FOLDER

Ikuti konvensi Laravel standar dengan tambahan organisasi berikut:

```
app/
├── Actions/              ← Business logic satu aksi = satu kelas
│   ├── Peserta/
│   ├── Asesor/
│   └── Admin/
├── Http/
│   ├── Controllers/      ← HANYA untuk file serving / webhook (bukan CRUD form)
│   └── Middleware/
├── Livewire/             ← Livewire lifecycle Actions & Form classes
│   ├── Actions/          ← Livewire lifecycle (Logout, dll)
│   └── Forms/            ← Livewire Form classes untuk form kompleks
├── Models/
├── Services/             ← Jika logic terlalu kompleks untuk Action
│   └── ExcelImportService.php
├── Imports/              ← Kelas maatwebsite/excel
├── Exports/
├── Enums/                ← PHP 8.1 Enum untuk semua konstanta
│   ├── RoleEnum.php
│   ├── StatusPermohonanEnum.php
│   ├── JenisRplEnum.php
│   └── JenisDokumenEnum.php
└── Policies/             ← Authorization per model

resources/views/
├── components/           ← Blade components reusable
│   ├── layouts/          ← Layout per role (admin, asesor, peserta)
│   └── form/             ← Komponen form reusable
└── livewire/             ← Volt page components (PHP+Blade satu file)
    ├── peserta/
    ├── asesor/
    └── admin/
```

> **Catatan penting:** Project ini menggunakan **Livewire Volt**. Semua halaman interaktif adalah Volt component di `resources/views/livewire/`, bukan Controller. Lihat §4 dan §14 untuk penjelasan lengkap.

---

## 2. PENAMAAN (NAMING CONVENTIONS)

### File & Kelas
| Tipe | Format | Contoh |
|---|---|---|
| Model | PascalCase, singular | `PermohonanRpl`, `MataKuliah` |
| Controller | PascalCase + Controller | `BerkasController` (hanya untuk file serving) |
| Livewire Form | PascalCase + Form | `TambahAsesorForm`, `UploadDokumenForm` |
| Action | PascalCase + deskripsi aksi | `SubmitPermohonanAction` |
| Enum | PascalCase + Enum | `StatusPermohonanEnum` |
| Migration | snake_case + timestamp | `2026_03_20_create_permohonan_rpl_table` |
| Seeder | PascalCase + Seeder | `ProgramStudiSeeder` |

### Database
| Tipe | Format | Contoh |
|---|---|---|
| Tabel | snake_case | `permohonan_rpl`, `mata_kuliah` |
| Kolom | snake_case | `program_studi_id`, `tanggal_pengajuan` |
| FK | `{tabel_singular}_id` | `peserta_id`, `asesor_id` |
| Boolean | awalan `is_` atau `has_` | `is_do_pcr`, `aktif` |
| Timestamp custom | suffix `_at` | `tanggal_pengajuan`, `evaluated_at` |

### Variabel & Method
| Tipe | Format | Contoh |
|---|---|---|
| Variabel | camelCase | `$permohonanRpl`, `$mataKuliah` |
| Method | camelCase, verb+noun | `submitPermohonan()`, `hapusDokumen()` |
| Route name | kebab-case, dot notation | `peserta.pengajuan.index`, `admin.akun.index` |
| Volt component | dot notation sesuai path | `peserta.berkas.index` → `resources/views/livewire/peserta/berkas/index.blade.php` |

### Penamaan Enum Case
Gunakan **PascalCase** (bukan UPPER_CASE) dan **Bahasa Indonesia**:
```php
// BENAR ✓
case Draf      = 'draf';
case Diajukan  = 'diajukan';
case Diproses  = 'diproses';

// SALAH ✗
case DRAFT     = 'draft';
case SUBMITTED = 'submitted';
```

---

## 3. MODEL

### Wajib ada di setiap Model:
```php
class PermohonanRpl extends Model
{
    protected $table = 'permohonan_rpl';

    // 1. Selalu definisikan $fillable (jangan pakai $guarded = [])
    protected $fillable = [
        'peserta_id',
        'program_studi_id',
        'nomor_permohonan',
        'status',
        'catatan_admin',
        'tanggal_pengajuan',
    ];

    // 2. Cast tipe data secara eksplisit — gunakan method casts() (Laravel 11)
    protected function casts(): array
    {
        return [
            'tanggal_pengajuan' => 'datetime',
            'status'            => StatusPermohonanEnum::class,  // gunakan Enum
        ];
    }

    // 3. Semua relasi di bagian paling bawah, dikelompokkan

    // --- Belongs To ---
    public function peserta(): BelongsTo
    {
        return $this->belongsTo(Peserta::class);
    }

    // --- Has Many ---
    public function rplMataKuliah(): HasMany
    {
        return $this->hasMany(RplMataKuliah::class);
    }
}
```

### Aturan Model:
- **Jangan tulis query di dalam Model.** Gunakan scope untuk filter yang sering dipakai.
- **Scope** untuk filter umum: `scopeAktif()`, `scopeByProdi()`, dll.
- **Accessor/Mutator** hanya untuk transformasi data sederhana.
- **Jangan** taruh business logic di Model.
- Gunakan `protected function casts(): array` (bukan `protected $casts = [...]`).

---

## 4. CONTROLLER

### Prinsip: Controller HANYA untuk endpoint HTTP non-Livewire

Project ini menggunakan **Livewire Volt** untuk semua halaman dan form. Controller **tidak dipakai** untuk CRUD biasa.

**Controller HANYA untuk:**
- File serving (`response()->file()`, `->download()`)
- OAuth / external webhook callback
- API endpoint (jika ada kebutuhan di masa depan)

**Jika ragu: gunakan Livewire Volt, bukan Controller.**

```php
// BENAR ✓ — Controller untuk file serving
class BerkasController extends Controller
{
    public function viewDokumen(DokumenBukti $dokumen)
    {
        $this->authorizeDokumen($dokumen);

        return response()->file(
            Storage::disk('local')->path($dokumen->berkas),
            ['Content-Type' => Storage::disk('local')->mimeType($dokumen->berkas)]
        );
    }
}

// SALAH ✗ — jangan buat Controller untuk form CRUD
class PermohonanController extends Controller
{
    public function store(Request $request) { /* gunakan Livewire Volt */ }
}
```

### Aturan Controller:
- Tidak ada subfolder `Peserta/`, `Asesor/`, `Admin/` di `Controllers/` — tidak diperlukan.
- Tidak ada resource controller untuk halaman aplikasi.
- Jika Controller hanya punya satu method, gunakan invokable (`__invoke`).

---

## 5. VALIDASI

Project menggunakan **Livewire Volt** sehingga **Laravel FormRequest tidak berlaku** untuk form halaman. Gunakan tiga level berikut sesuai kompleksitas form:

### Level 1 — Inline `$this->validate()` (form sederhana, ≤ 3 field)

Untuk toggle status, single upload, atau aksi cepat:

```php
// Di dalam Volt component method
public function hapusDokumen(int $id): void
{
    // Tidak perlu validasi — gunakan abort_if untuk authorization
    $dokumen = DokumenBukti::findOrFail($id);
    abort_if($dokumen->peserta_id !== auth()->user()->peserta?->id, 403);
    $dokumen->delete();
}

public function uploadDokumen(): void
{
    $this->validate([
        'berkas'       => 'required|file|mimes:pdf,jpg,jpeg,png|max:5120',
        'jenisDokumen' => 'required|string',
    ], [
        'berkas.max'   => 'Ukuran file maksimal 5 MB.',
        'berkas.mimes' => 'Format file harus PDF, JPG, atau PNG.',
    ]);

    // ... proses upload
}
```

### Level 2 — Livewire Form Class (form kompleks, ≥ 4 field)

Untuk form dengan banyak field atau yang dipakai di lebih dari satu component. Ini adalah **ekuivalen FormRequest** di arsitektur Livewire.

Form class harus **self-contained**: properti + validasi + **method `store()`**. Jangan taruh logic simpan-ke-DB di Volt component kalau sudah pakai Form class — itu sama saja tidak clean.

```php
// app/Livewire/Forms/TambahAsesorForm.php
class TambahAsesorForm extends Form
{
    // 1. Properti + validasi
    #[Validate('required|string|max:255')]
    public string $nama = '';

    #[Validate('required|email|unique:users,email')]
    public string $email = '';

    #[Validate('required|string|min:8')]
    public string $password = '';

    #[Validate('nullable|string|max:20')]
    public string $nidn = '';

    #[Validate('required|string|max:255')]
    public string $bidangKeahlian = '';

    public bool $sudahPelatihan = false;

    // 2. Business logic simpan ke DB ada di Form class, bukan di Volt
    public function store(): void
    {
        $user = User::create([
            'nama'     => $this->nama,
            'email'    => $this->email,
            'password' => Hash::make($this->password),
            'role'     => 'asesor',
            'aktif'    => true,
        ]);

        $user->assignRole('asesor');

        Asesor::create([
            'user_id'             => $user->id,
            'nidn'                => $this->nidn ?: null,
            'bidang_keahlian'     => $this->bidangKeahlian,
            'sudah_pelatihan_rpl' => $this->sudahPelatihan,
        ]);
    }
}
```

```php
// Volt component — hanya orchestrate: validate → store → redirect
new #[Layout('components.layouts.admin')] class extends Component {
    public TambahAsesorForm $form;

    public function save(): void
    {
        $this->form->validate();
        $this->form->store();
        $this->redirect(route('admin.akun.index'), navigate: true);
    }
};
```

**Kapan membuat Livewire Form class:**
- Form dengan ≥ 4 field
- Form yang sama dipakai di 2+ component
- Form yang butuh validasi kondisional kompleks

**Aturan Form class:**
- Properti + validasi + `store()` ada dalam satu kelas — **jangan pisahkan**
- Redirect tetap di Volt component (lifecycle concern, bukan form concern)
- Untuk reset setelah simpan: `$this->form->reset()` di Volt component
- Wire binding di view: `wire:model="form.nama"`, error: `@error('form.nama')`

### Level 3 — Laravel FormRequest (HANYA jika ada Controller dengan input form)

```php
// Saat ini tidak ada use case di project ini.
// BerkasController hanya GET/serve, tidak menerima input form.
// Jika suatu saat ada Controller baru dengan form input, WAJIB pakai FormRequest.
class SomeFormRequest extends FormRequest
{
    public function authorize(): bool { return true; }
    public function rules(): array { return [...]; }
}
```

### Ringkasan Kapan Pakai Mana
| Situasi | Gunakan |
|---|---|
| Aksi cepat, 1-3 field di Volt | `$this->validate([...])` inline |
| Form lengkap ≥ 4 field di Volt | Livewire Form class (`extends Form`) |
| Controller menerima POST/PUT | Laravel `FormRequest` |
| Validasi dari Alpine form state | `validator()` helper + `dispatch('event', errors:)` |

---

## 6. ACTION

Gunakan Action untuk setiap operasi bisnis yang nyata. Satu Action = satu tanggung jawab.

```php
// app/Actions/Peserta/SubmitPermohonanAction.php
class SubmitPermohonanAction
{
    public function execute(array $data, Peserta $peserta): PermohonanRpl
    {
        // 1. Validasi aturan bisnis
        $this->ensurePesertaCanApply($peserta);

        // 2. Jalankan dalam transaksi database
        return DB::transaction(function () use ($data, $peserta) {
            $permohonan = PermohonanRpl::create([
                'peserta_id'       => $peserta->id,
                'program_studi_id' => $data['program_studi_id'],
                'nomor_permohonan' => $this->generateNomor(),
                'status'           => StatusPermohonanEnum::Diajukan,
                'tanggal_pengajuan' => now(),
            ]);

            // attach mata kuliah yang dipilih...

            return $permohonan;
        });
    }

    private function ensurePesertaCanApply(Peserta $peserta): void
    {
        if ($peserta->is_do_pcr) {
            throw new \DomainException('Peserta DO dari PCR tidak dapat mendaftar RPL.');
        }
    }

    private function generateNomor(): string
    {
        $year  = now()->year;
        $count = PermohonanRpl::whereYear('created_at', $year)->count() + 1;
        return sprintf('RPL-%d-%03d', $year, $count);
    }
}
```

Action dipanggil dari Volt component:
```php
// Di dalam method Volt component
public function submit(SubmitPermohonanAction $action): void
{
    $this->validate([...]);
    $action->execute($this->data, auth()->user()->peserta);
    $this->redirect(route('peserta.pengajuan.index'), navigate: true);
}
```

---

## 7. ENUM

Gunakan PHP 8.1 Enum untuk semua konstanta yang dibatasi nilainya.

```php
// app/Enums/StatusPermohonanEnum.php
enum StatusPermohonanEnum: string
{
    case Draf        = 'draf';
    case Diajukan    = 'diajukan';
    case Diproses    = 'diproses';
    case Verifikasi  = 'verifikasi';
    case DalamReview = 'dalam_review';
    case Disetujui   = 'disetujui';
    case Ditolak     = 'ditolak';

    public function label(): string
    {
        return match($this) {
            self::Draf        => 'Draf',
            self::Diajukan    => 'Diajukan',
            self::Diproses    => 'Diproses',
            self::Verifikasi  => 'Verifikasi',
            self::DalamReview => 'Dalam Review',
            self::Disetujui   => 'Disetujui',
            self::Ditolak     => 'Ditolak',
        ];
    }

    public function badgeClass(): string
    {
        return match($this) {
            self::Draf        => 'bg-[#F1F3F4] text-[#5f6368]',
            self::Diajukan    => 'bg-[#E8F0FE] text-[#1557b0]',
            self::Diproses    => 'bg-[#E8F0FE] text-[#1557b0]',
            self::Verifikasi  => 'bg-[#FFF8E1] text-[#b45309]',
            self::DalamReview => 'bg-[#FFF8E1] text-[#b45309]',
            self::Disetujui   => 'bg-[#E6F4EA] text-[#1e7e3e]',
            self::Ditolak     => 'bg-[#FCE8E6] text-[#c62828]',
        ];
    }
}
```

**Daftar Enum yang harus dibuat:**
- `RoleEnum` — peserta, asesor, admin
- `StatusPermohonanEnum` — draf, diajukan, diproses, verifikasi, dalam_review, disetujui, ditolak
- `JenisRplEnum` — transfer_kredit, perolehan_kredit
- `JenisDokumenEnum` — cv, ijazah, transkrip, sertifikat, logbook, dll
- `JenisKelaminEnum` — L, P

---

## 8. ROUTING

```php
// routes/web.php — kelompokkan per role dengan prefix & middleware
// Gunakan Volt::route() untuk semua halaman Livewire Volt

use Livewire\Volt\Volt;

// Peserta
Route::prefix('peserta')->middleware(['auth', 'verified', 'role:peserta'])->group(function () {
    Volt::route('dashboard', 'peserta.dashboard')->name('peserta.dashboard');
    Volt::route('pengajuan', 'peserta.pengajuan.index')->name('peserta.pengajuan.index');
    Volt::route('pengajuan/buat', 'peserta.pengajuan.buat')->name('peserta.pengajuan.buat');
});

// Asesor
Route::prefix('asesor')->middleware(['auth', 'verified', 'role:asesor'])->group(function () {
    Volt::route('dashboard', 'asesor.dashboard')->name('asesor.dashboard');
});

// Admin
Route::prefix('admin')->middleware(['auth', 'verified', 'role:admin'])->group(function () {
    Volt::route('dashboard', 'admin.dashboard')->name('admin.dashboard');
});

// Endpoint Controller (file serving, bukan Livewire)
Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('berkas/{dokumen}/view', [BerkasController::class, 'viewDokumen'])->name('berkas.view');
    Route::get('berkas/{dokumen}/download', [BerkasController::class, 'downloadDokumen'])->name('berkas.download');
});
```

**Nama route:** `{role}.{resource}.{action}` → `peserta.pengajuan.index`, `admin.akun.index`

---

## 9. QUERY & DATABASE

```php
// BENAR ✓ — eager loading, hindari N+1
$permohonanList = PermohonanRpl::with([
    'peserta.user',
    'programStudi',
    'rplMataKuliah.mataKuliah',
])->where('status', StatusPermohonanEnum::Diproses)->get();

// SALAH ✗ — N+1 query
$permohonanList = PermohonanRpl::all();
foreach ($permohonanList as $p) {
    echo $p->peserta->user->nama; // query baru setiap iterasi!
}
```

**Aturan Query:**
- Selalu gunakan **eager loading** (`with()`) saat mengambil relasi.
- Gunakan **`select()`** untuk membatasi kolom yang diambil jika data besar.
- Operasi bulk (insert/update banyak baris) → gunakan `insert([])` atau `upsert()`, jangan loop.
- Semua operasi multi-tabel **wajib** dalam `DB::transaction()`.
- Jangan gunakan query mentah `DB::statement()` kecuali benar-benar tidak ada cara lain.

---

## 10. BLADE & TAILWIND

### Komponen Reusable
Buat Blade Component untuk elemen UI yang dipakai >1 kali:

```
resources/views/components/
├── layouts/
│   ├── admin.blade.php
│   ├── asesor.blade.php
│   └── peserta.blade.php
├── form/
│   ├── select.blade.php
│   └── date-picker.blade.php
└── badge-status.blade.php    → <x-badge-status :status="$permohonan->status" />
```

### Aturan Blade:
- **Jangan** tulis logika PHP kompleks di Blade. Pindahkan ke method component atau `with()`.
- **Jangan** query langsung di Blade (`Model::all()` di view = dilarang keras).
- Gunakan `@can` / `@role` untuk conditional berdasarkan permission.
- Pisahkan layout per role (`components/layouts/admin.blade.php`, dll).

---

## 11. MIGRATION

```php
// Selalu ikuti urutan ini dalam setiap migration:
Schema::create('rpl_mata_kuliah', function (Blueprint $table) {
    $table->id();

    // FK dulu
    $table->foreignId('permohonan_rpl_id')->constrained('permohonan_rpl')->cascadeOnDelete();
    $table->foreignId('mata_kuliah_id')->constrained()->cascadeOnDelete();
    $table->foreignId('asesor_id')->nullable()->constrained('asesor')->nullOnDelete();

    // Kolom data
    $table->string('jenis_rpl')->nullable();    // simpan value Enum sebagai string
    $table->string('status')->default('pending');
    $table->string('nilai_akhir', 5)->nullable();
    $table->tinyInteger('sks_diakui')->nullable();
    $table->text('catatan_asesor')->nullable();

    // Index unik di akhir
    $table->unique(['permohonan_rpl_id', 'mata_kuliah_id']);

    $table->timestamps();
});
```

**Aturan Migration:**
- Satu migration = satu tabel atau satu perubahan spesifik.
- Jangan edit migration yang sudah di-commit — buat migration baru untuk alter.
- Selalu tambahkan `->comment('...')` pada kolom yang tidak self-explanatory.

---

## 12. SEEDER & TESTING DATA

```php
// DatabaseSeeder.php — panggil seeder dalam urutan yang benar
public function run(): void
{
    $this->call([
        ProgramStudiSeeder::class,      // harus duluan
        MataKuliahSeeder::class,        // butuh program_studi
        UserSeeder::class,              // admin, asesor, peserta dummy
        // Jangan seed production data di sini — pisahkan
    ]);
}
```

**Gunakan Factory untuk data dummy test:**
```php
// Jangan hardcode data dummy di Seeder untuk test
PermohonanRpl::factory()->count(10)->create();
```

---

## 13. KEAMANAN

- **Validasi selalu ada** — `$this->validate()` di Volt atau Form class (lihat §5).
- **Policy** untuk setiap aksi sensitif: update, delete, approve.
- File upload: validasi `mimes`, `max`, dan simpan dengan nama random (`Storage::putFile()` atau `storeAs(uniqid())`).
- Jangan expose ID sequential di URL — gunakan **route model binding**.
- Semua input user dianggap tidak aman — tidak ada `{!! !!}` di Blade kecuali sudah disanitasi eksplisit.
- Gunakan `abort_if()` untuk authorization cepat di Volt method.

---

## 14. LIVEWIRE VOLT & ALPINE.JS

### Livewire Volt — Struktur File

Project ini menggunakan **Livewire Volt** (single-file component). Setiap halaman interaktif adalah satu file `.blade.php` yang berisi kelas PHP anonim + template Blade:

```php
<?php
// resources/views/livewire/peserta/berkas/index.blade.php

use Livewire\Attributes\Layout;
use Livewire\Volt\Component;

new #[Layout('components.layouts.peserta')] class extends Component {

    // Public properties = state yang di-bind ke view
    public string $namaDokumen = '';

    // Data untuk view — dipanggil setiap render
    public function with(): array
    {
        return [
            'dokumenList' => DokumenBukti::where('peserta_id', auth()->user()->peserta?->id)->get(),
        ];
    }

    // Method = aksi yang dipanggil dari view
    public function simpan(): void
    {
        $this->validate([...]);
        // ... proses
    }
}; ?>

<x-slot:title>Judul Halaman</x-slot:title>

<div>
    {{-- Template Blade --}}
</div>
```

**Lokasi:** `resources/views/livewire/{role}/{fitur}/{nama}.blade.php`

**Pola `with()` vs `mount()`:**
- `with()` → data yang perlu fresh setiap render (list yang bisa berubah)
- `mount()` → inisialisasi sekali saat component pertama kali dimuat (parameter dari route, initial state)

---

### Kapan pakai Livewire vs Alpine.js

| Kebutuhan | Gunakan |
|---|---|
| Form multi-step (wizard pengajuan RPL) | Livewire |
| Tabel dengan filter, sort, pagination | Livewire |
| Upload file dengan progress | Livewire |
| Real-time status update | Livewire |
| Toggle show/hide elemen | Alpine.js |
| Dropdown, modal sederhana | Alpine.js |
| Animasi transisi | Alpine.js |

**Aturan inti:** Livewire hanya untuk operasi yang membutuhkan server. Alpine untuk semua yang murni UI.

---

#### ❌ JANGAN — Livewire untuk toggle UI murni
```blade
{{-- Modal show/hide via Livewire = 2 round-trip yang tidak perlu --}}
<button wire:click="openConfirm(...)">Hapus</button>
<button wire:click="$set('modal.show', false)">Batal</button>
@if ($modal['show'])
    <div class="fixed inset-0 ...">...</div>
@endif
```

#### ✓ BENAR — Alpine untuk toggle, Livewire hanya untuk aksi nyata
```blade
{{-- Modal: Alpine show/hide, Livewire hanya untuk aksi hapus --}}
<div x-data="{ showConfirm: false, itemId: 0 }">
    <button @click="showConfirm = true; itemId = {{ $item->id }}">Hapus</button>

    <div x-show="showConfirm" x-cloak x-transition.opacity
         class="fixed inset-0 z-50 flex items-center justify-center bg-black/40"
         @click.self="showConfirm = false">
        <div class="...">
            <button @click="showConfirm = false">Batal</button>
            <button @click="$wire.hapusItem(itemId); showConfirm = false">Hapus</button>
        </div>
    </div>
</div>
```

---

#### ❌ JANGAN — Livewire re-render untuk toggle checkbox list
```blade
{{-- Setiap centang MK = full Livewire re-render --}}
<label wire:click="toggleMk({{ $mk->id }})">...</label>
```

#### ✓ BENAR — Alpine untuk visual instan + `#[Renderless]` untuk sync state
```blade
{{-- Alpine instan, Livewire ringan (renderless = tanpa HTML response) --}}
<div x-data="{ selIds: @js($selectedMkIds) }">
    <label @click.prevent="
               const i = selIds.indexOf({{ $mk->id }});
               i === -1 ? selIds.push({{ $mk->id }}) : selIds.splice(i, 1);
               $wire.toggleMk({{ $mk->id }})
           "
           :class="selIds.includes({{ $mk->id }}) ? 'border-primary bg-[#E8F4F8]' : 'border-[#E0E5EA]'"
           class="...">
    </label>
</div>
```
```php
// Livewire method: renderless = sync state tanpa re-render HTML
#[\Livewire\Attributes\Renderless]
public function toggleMk(int $mkId): void { ... }
```

---

#### ❌ JANGAN — Livewire re-render untuk setiap toggle tombol (misal: rating 1-5, checkbox VATM)
```blade
<button wire:click="saveRating({{ $rplMk->id }}, {{ $pt->id }}, {{ $nilai }})">
    {{-- Seluruh komponen re-render setiap klik --}}
</button>
```

#### ✓ BENAR — Alpine update visual seketika, Livewire simpan di background
```blade
{{-- x-data inisialisasi dengan nilai PHP saat ini --}}
<div x-data="{ sel: {{ $pertanyaanRatings[$pt->id] ?? 'null' }} }">
    @foreach ([1,2,3,4,5] as $nilai)
    <button
        @click="sel = {{ $nilai }}; $wire.saveRating({{ $rplMk->id }}, {{ $pt->id }}, {{ $nilai }})"
        :class="sel === {{ $nilai }} ? 'bg-primary text-white border-primary' : 'bg-white ...'"
        class="px-3 py-1.5 rounded-lg text-[11px] font-semibold border transition-all">
        {{ $nilai }}
    </button>
    @endforeach
</div>
```
```php
// Livewire method: tidak perlu re-load relasi — update property lokal
public function saveRating(int $rplMkId, int $pertanyaanId, int $nilai): void
{
    $this->pertanyaanRatings[$pertanyaanId] = $nilai;
    AsesmenMandiri::updateOrCreate([...], ['penilaian_diri' => $nilai]);

    // ✗ JANGAN: $this->permohonan->load('rplMataKuliah.asesmenMandiri');
    // ✓ Update state lokal saja:
    if (! in_array($pertanyaanId, $this->asesmenIds)) {
        $this->asesmenIds[] = $pertanyaanId;
    }
}
```

---

#### ❌ JANGAN — `$this->model->load()` setelah setiap update kecil
```php
public function saveVatm(...): void
{
    EvaluasiVatm::updateOrCreate([...], [...]);
    $this->permohonan->load(['rplMataKuliah.asesmenMandiri.evaluasiVatm']); // ✗ mahal!
}
```

#### ✓ BENAR — Percayakan state ke Alpine, load hanya saat benar-benar perlu
```php
public function saveVatm(...): void
{
    EvaluasiVatm::updateOrCreate([...], [...]);
    // Alpine sudah update UI — tidak perlu reload
}
```

---

#### Ringkasan Pola Performa Livewire + Alpine

| Situasi | Solusi |
|---|---|
| Toggle modal show/hide | Alpine `x-show` + `x-data` — zero round-trip |
| Checkbox / toggle list (tanpa DB) | Alpine `@click` + `:class` — zero round-trip |
| Checkbox / toggle list (perlu sync DB) | Alpine instan + `#[Renderless]` Livewire |
| Rating / select tombol (perlu simpan) | Alpine `x-data` instan + `$wire.method()` background |
| Reload data setelah update kecil | Update property lokal saja, hindari `->load()` |
| Data yang harus segar setiap render | Gunakan `with()` method, bukan `mount()` |
| Form modal (multi-field, multi-tab) | Alpine kelola seluruh form state, Livewire hanya save |

---

#### Pola: Alpine Form State + Livewire Persistence

Untuk form di dalam modal atau panel yang memiliki banyak field dan/atau beberapa tab/role selector, kelola **seluruh state form di Alpine** dan kirim ke Livewire **hanya saat submit**.

❌ **JANGAN** — setiap pergantian tab/role memanggil Livewire (`~512ms per klik`):
```blade
{{-- Tab switcher yang trigger full Livewire round-trip --}}
<button wire:click="$set('roleForm', 'asesor')">Asesor</button>
<button wire:click="$set('roleForm', 'peserta')">Peserta</button>
```

✓ **BENAR** — tab switcher murni Alpine (`0ms`), Livewire hanya saat save:
```blade
<div x-data="{ role: @js($roleForm), form: { nama: '', email: '', password: '' } }"
     @saved.window="$wire.set('showForm', false)">

    {{-- Tab switcher: Alpine only, zero round-trip --}}
    <button @click="role = 'asesor'" :class="role === 'asesor' ? 'bg-white shadow' : ''">Asesor</button>
    <button @click="role = 'peserta'" :class="role === 'peserta' ? 'bg-white shadow' : ''">Peserta</button>

    {{-- Field tambahan per role: Alpine x-show, instant --}}
    <div x-show="role === 'asesor'" style="display:none">
        <input wire:model="bidangKeahlian" ... />
    </div>

    {{-- Submit: kirim role dari Alpine ke Livewire sekali saja --}}
    <button @click="$wire.save(role)" wire:loading.attr="disabled" wire:target="save">
        <span wire:loading.remove wire:target="save"
              x-text="{ asesor: 'Buat Akun Asesor', peserta: 'Buat Akun Peserta' }[role]">
        </span>
        <span wire:loading wire:target="save">Menyimpan...</span>
    </button>
</div>
```

```php
// Livewire: terima role sebagai parameter method, bukan property yang di-set berkali-kali
public function save(string $role): void
{
    // Set ke property SEBELUM validate, agar jika validasi gagal dan re-render terjadi,
    // Alpine reinit ke role yang tepat via @js($roleForm)
    $this->roleForm = in_array($role, ['asesor', 'peserta', 'admin']) ? $role : 'asesor';

    $rules = [/* common rules */];
    if ($this->roleForm === 'asesor') {
        $rules['bidangKeahlian'] = 'required|string|max:255';
    }
    $this->validate($rules);

    // ... simpan ke DB, lalu:
    $this->dispatch('saved');
}
```

---

#### Pola: Inline Validation Error via Livewire Event → Alpine

Gunakan pola ini saat data form ada di **parameter method** (bukan `wire:model` property), sehingga `$this->validate()` biasa tidak bisa dipakai.

```php
use Illuminate\Validation\Rule;

public function saveMk(?int $editMkId, string $kode, string $nama, int $sks): void
{
    // Gunakan validator() helper — bukan $this->validate() — karena data dari params, bukan property
    $validator = validator(
        compact('kode', 'nama', 'sks'),
        [
            'kode' => ['required', 'string', 'max:20',
                Rule::unique('mata_kuliah', 'kode')
                    ->where('program_studi_id', $this->prodi->id)
                    ->ignore($editMkId)  // ignore current record saat edit
            ],
            'nama' => 'required|string|max:255',
            'sks'  => 'required|integer|min:1|max:6',
        ],
        ['kode.unique' => 'Kode ini sudah dipakai oleh mata kuliah lain di prodi ini.']
    );

    if ($validator->fails()) {
        // Kirim error ke Alpine via event — modal tetap terbuka, error tampil inline
        $this->dispatch('mk-validation-errors', errors: $validator->errors()->toArray());
        return;
    }

    // ... simpan ke DB, lalu:
    $this->dispatch('mk-saved');
}
```

```blade
{{-- Alpine: terima event dari Livewire, tampilkan error inline --}}
<div x-data="{
    mkErrors: {},
    init() {
        this.$wire.on('mk-validation-errors', ({ errors }) => { this.mkErrors = errors; });
        this.$wire.on('mk-saved', () => { this.showModal = false; this.mkErrors = {}; });
    }
}">
    <input x-model="mkForm.kode" type="text" />
    <p x-show="mkErrors.kode" x-text="mkErrors.kode && mkErrors.kode[0]"
       class="text-[11px] text-[#c62828] mt-1"></p>
</div>
```

**Kapan menggunakan `validator()` vs `$this->validate()`:**
| Situasi | Gunakan |
|---|---|
| Data form ada di `wire:model` property | `$this->validate()` — singkat dan idiomatis |
| Data form ada di parameter method (Alpine form state) | `validator()` helper + `dispatch('event', errors:)` |

---

#### Pola: Fresh DB Read setelah Update

Setelah update status/data di Livewire, jangan andalkan `$this->model->status` yang mungkin stale dari hydration sebelumnya. Gunakan query baru atau `->refresh()`.

```php
// ✗ JANGAN — bisa stale jika model sudah ter-hydrate sebelum update lain
if ($this->permohonan->status === 'dalam_review') { ... }

// ✓ BENAR — selalu ambil dari DB saat logic kritis
$statusSaatIni = PermohonanRpl::find($this->permohonan->id)?->status;
if (in_array($statusSaatIni, [StatusPermohonanEnum::Diajukan, StatusPermohonanEnum::DalamReview])) { ... }

// ✓ BENAR — refresh model setelah update untuk data yang di-render ke view
$this->permohonan->refresh();
```

---

### Alpine.js — hanya untuk client-side:
```html
<!-- Contoh modal sederhana dengan Alpine -->
<div x-data="{ open: false }">
    <button @click="open = true">Lihat Detail</button>
    <div x-show="open" x-transition @click.outside="open = false">
        <!-- isi modal -->
    </div>
</div>
```

---

## 15. CHECKLIST SEBELUM COMMIT

```
[ ] Validasi ada di tempat yang benar:
    - Livewire Form class (#[Validate]) untuk form ≥ 4 field
    - $this->validate() untuk form sederhana (1-3 field)
    - validator() helper untuk data dari Alpine form state (parameter method)
    - FormRequest hanya jika ada Controller yang menerima form input

[ ] Tidak ada query di Blade / View
[ ] Eager loading dipakai saat akses relasi
[ ] Operasi multi-tabel dalam DB::transaction()
[ ] Enum dipakai, bukan string literal
[ ] Tidak ada dd() / var_dump() / dump() tertinggal
[ ] Nama route, method, variabel konsisten dengan standar di atas
[ ] File upload divalidasi mime type dan ukuran
[ ] Livewire property sensitif diberi #[Locked]
[ ] Livewire list item menggunakan wire:key
[ ] Modal show/hide menggunakan Alpine x-show, bukan Livewire @if + $set()
[ ] Tombol toggle (rating, checkbox, VATM) menggunakan Alpine x-data untuk visual instan
[ ] Method Livewire yang hanya sync state (tanpa perlu re-render) diberi #[Renderless]
[ ] Tidak ada $this->model->load() di dalam method yang dipanggil per-klik kecil
[ ] Data yang dibutuhkan setiap render ada di with(), bukan mount()
[ ] Semua dropdown menggunakan x-form.select atau inline Alpine equivalent — tidak ada <select> native yang terekspos ke UI
[ ] Form modal dengan tab/role selector: tab switch menggunakan Alpine @click (bukan $set), submit mengirim state Alpine via $wire.method(arg)
[ ] Validasi data dari Alpine form (bukan wire:model property): gunakan validator() helper + dispatch('event', errors:), bukan $this->validate()
[ ] Setelah update status di Livewire: gunakan Model::find($id)?->status untuk baca ulang, bukan $this->model->status yang mungkin stale
[ ] Tidak ada Controller baru untuk CRUD/form — gunakan Livewire Volt
[ ] Enum case menggunakan PascalCase Indonesian (case Draf, case Diajukan) — bukan UPPER_CASE English
[ ] Model menggunakan protected function casts(): array (bukan $casts property)
```

---

*Berlaku untuk project Sistem RPL PCR | Laravel 11 + Livewire Volt + Tailwind*
