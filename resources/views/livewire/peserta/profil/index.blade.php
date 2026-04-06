<?php

use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;
use Livewire\WithFileUploads;
use App\Models\KonferensiSeminar;
use App\Models\OrganisasiProfesi;
use App\Models\Penghargaan;
use App\Models\PelatihanProfesional;
use App\Models\RiwayatPendidikan;

new #[Layout('components.layouts.peserta')] class extends Component {
    use WithFileUploads;

    // ── Biodata ──────────────────────────────────────────────────────────────
    public string $nama          = '';
    public string $email         = '';
    public string $nik           = '';
    public string $telepon       = '';
    public string $teleponFaks   = '';
    public string $alamat        = '';
    public string $kota          = '';
    public string $provinsi      = '';
    public string $kodePos       = '';
    public string $tempatLahir   = '';
    public string $tanggalLahir  = '';
    public string $jenisKelamin  = '';
    public string $agama         = '';
    public string $golonganPangkat = '';
    public string $instansi      = '';
    public string $pekerjaan     = '';
    public $foto = null;

    // ── Password ─────────────────────────────────────────────────────────────
    public string $password             = '';
    public string $passwordConfirmation = '';

    public bool $mustChangePassword = false;

    public function mount(): void
    {
        $user    = auth()->user();
        $peserta = $user->peserta;

        // Paksa ganti password jika password masih sama dgn email atau nama
        if (\Illuminate\Support\Facades\Hash::check($user->email, $user->password)
            || \Illuminate\Support\Facades\Hash::check($user->nama, $user->password)) {
            $this->mustChangePassword = true;
        }

        $this->nama        = $user->nama;
        $this->email       = $user->email;

        if ($peserta) {
            $this->nik             = $peserta->nik ?? '';
            $this->telepon         = $peserta->telepon ?? '';
            $this->teleponFaks     = $peserta->telepon_faks ?? '';
            $this->alamat          = $peserta->alamat ?? '';
            $this->kota            = $peserta->kota ?? '';
            $this->provinsi        = $peserta->provinsi ?? '';
            $this->kodePos         = $peserta->kode_pos ?? '';
            $this->tempatLahir     = $peserta->tempat_lahir ?? '';
            $this->tanggalLahir    = $peserta->tanggal_lahir?->format('Y-m-d') ?? '';
            $this->jenisKelamin    = $peserta->jenis_kelamin ?? '';
            $this->agama           = $peserta->agama ?? '';
            $this->golonganPangkat = $peserta->golongan_pangkat ?? '';
            $this->instansi        = $peserta->instansi ?? '';
            $this->pekerjaan       = $peserta->pekerjaan ?? '';
        }
    }

    // ── Biodata ──────────────────────────────────────────────────────────────

    public function simpanBiodata(): void
    {
        $this->validate([
            'nama'         => 'required|string|max:255',
            'email'        => 'required|email|max:255|unique:users,email,' . auth()->id(),
            'nik'          => 'nullable|string|max:20',
            'telepon'      => 'nullable|string|max:20',
            'teleponFaks'  => 'nullable|string|max:20',
            'alamat'       => 'nullable|string|max:500',
            'kota'         => 'nullable|string|max:100',
            'provinsi'     => 'nullable|string|max:100',
            'kodePos'      => 'nullable|string|max:10',
            'tempatLahir'  => 'nullable|string|max:100',
            'tanggalLahir' => 'nullable|date|before:today',
            'jenisKelamin' => 'nullable|in:L,P',
            'agama'        => 'nullable|string|max:50',
            'golonganPangkat' => 'nullable|string|max:50',
            'instansi'     => 'nullable|string|max:255',
            'pekerjaan'    => 'nullable|string|max:255',
            'foto'         => 'nullable|image|mimes:jpg,jpeg,png|max:2048',
        ]);

        $user    = auth()->user();
        $peserta = $user->peserta;

        $user->update([
            'nama'  => $this->nama,
            'email' => $this->email,
        ]);

        $fotoPath = null;
        if ($this->foto) {
            $fotoPath = $this->foto->storeAs(
                'peserta/foto',
                uniqid('foto_', true) . '.' . $this->foto->getClientOriginalExtension(),
                'public'
            );
        }

        if ($peserta) {
            $peserta->update(array_filter([
                'nik'             => $this->nik ?: null,
                'telepon'         => $this->telepon ?: null,
                'telepon_faks'    => $this->teleponFaks ?: null,
                'alamat'          => $this->alamat ?: null,
                'kota'            => $this->kota ?: null,
                'provinsi'        => $this->provinsi ?: null,
                'kode_pos'        => $this->kodePos ?: null,
                'tempat_lahir'    => $this->tempatLahir ?: null,
                'tanggal_lahir'   => $this->tanggalLahir ?: null,
                'jenis_kelamin'   => $this->jenisKelamin ?: null,
                'agama'           => $this->agama ?: null,
                'golongan_pangkat' => $this->golonganPangkat ?: null,
                'instansi'        => $this->instansi ?: null,
                'pekerjaan'       => $this->pekerjaan ?: null,
            ], fn($v) => $v !== null) + ($fotoPath ? ['foto' => $fotoPath] : []));
        }

        $this->reset('foto');
        $this->dispatch('biodata-saved');
    }

    // ── Password ─────────────────────────────────────────────────────────────

    public function simpanPassword(): void
    {
        $this->validate([
            'password'             => ['required', 'string', Password::min(8), 'confirmed'],
            'passwordConfirmation' => ['required'],
        ], [
            'password.required'  => 'Password wajib diisi.',
            'password.min'       => 'Password minimal 8 karakter.',
            'password.confirmed' => 'Konfirmasi password tidak cocok.',
        ]);

        auth()->user()->update(['password' => Hash::make($this->password)]);
        $this->reset('password', 'passwordConfirmation');
        $this->mustChangePassword = false;
        $this->dispatch('password-saved');
    }

    // ── Riwayat Pendidikan ────────────────────────────────────────────────────

    public function simpanPendidikan(?int $id, string $namaSekolah, ?string $tahunLulus, ?string $jurusan): void
    {
        abort_if(blank($namaSekolah), 422);
        $peserta = auth()->user()->peserta;
        abort_if(! $peserta, 403);

        if ($id) {
            $record = RiwayatPendidikan::findOrFail($id);
            abort_if($record->peserta_id !== $peserta->id, 403);
            $record->update(['nama_sekolah' => $namaSekolah, 'tahun_lulus' => $tahunLulus ?: null, 'jurusan' => $jurusan ?: null]);
        } else {
            RiwayatPendidikan::create(['peserta_id' => $peserta->id, 'nama_sekolah' => $namaSekolah, 'tahun_lulus' => $tahunLulus ?: null, 'jurusan' => $jurusan ?: null]);
        }
    }

    public function hapusPendidikan(int $id): void
    {
        $peserta = auth()->user()->peserta;
        $record  = RiwayatPendidikan::findOrFail($id);
        abort_if($record->peserta_id !== $peserta->id, 403);
        $record->delete();
    }

    // ── Pelatihan Profesional ─────────────────────────────────────────────────

    public function simpanPelatihan(?int $id, string $tahun, string $jenisPelatihan, string $penyelenggara, ?string $jangkaWaktu): void
    {
        abort_if(blank($jenisPelatihan), 422);
        $peserta = auth()->user()->peserta;
        abort_if(! $peserta, 403);

        $data = ['peserta_id' => $peserta->id, 'tahun' => $tahun, 'jenis_pelatihan' => $jenisPelatihan, 'penyelenggara' => $penyelenggara, 'jangka_waktu' => $jangkaWaktu ?: null];

        if ($id) {
            $record = PelatihanProfesional::findOrFail($id);
            abort_if($record->peserta_id !== $peserta->id, 403);
            $record->update($data);
        } else {
            PelatihanProfesional::create($data);
        }
    }

    public function hapusPelatihan(int $id): void
    {
        $peserta = auth()->user()->peserta;
        $record  = PelatihanProfesional::findOrFail($id);
        abort_if($record->peserta_id !== $peserta->id, 403);
        $record->delete();
    }

    // ── Konferensi / Seminar ──────────────────────────────────────────────────

    public function simpanKonferensi(?int $id, string $tahun, string $judulKegiatan, string $penyelenggara, ?string $peran): void
    {
        abort_if(blank($judulKegiatan), 422);
        $peserta = auth()->user()->peserta;
        abort_if(! $peserta, 403);

        $data = ['peserta_id' => $peserta->id, 'tahun' => $tahun, 'judul_kegiatan' => $judulKegiatan, 'penyelenggara' => $penyelenggara, 'peran' => $peran ?: null];

        if ($id) {
            $record = KonferensiSeminar::findOrFail($id);
            abort_if($record->peserta_id !== $peserta->id, 403);
            $record->update($data);
        } else {
            KonferensiSeminar::create($data);
        }
    }

    public function hapusKonferensi(int $id): void
    {
        $peserta = auth()->user()->peserta;
        $record  = KonferensiSeminar::findOrFail($id);
        abort_if($record->peserta_id !== $peserta->id, 403);
        $record->delete();
    }

    // ── Penghargaan ───────────────────────────────────────────────────────────

    public function simpanPenghargaan(?int $id, string $tahun, string $bentukPenghargaan, string $pemberi): void
    {
        abort_if(blank($bentukPenghargaan), 422);
        $peserta = auth()->user()->peserta;
        abort_if(! $peserta, 403);

        $data = ['peserta_id' => $peserta->id, 'tahun' => $tahun, 'bentuk_penghargaan' => $bentukPenghargaan, 'pemberi' => $pemberi];

        if ($id) {
            $record = Penghargaan::findOrFail($id);
            abort_if($record->peserta_id !== $peserta->id, 403);
            $record->update($data);
        } else {
            Penghargaan::create($data);
        }
    }

    public function hapusPenghargaan(int $id): void
    {
        $peserta = auth()->user()->peserta;
        $record  = Penghargaan::findOrFail($id);
        abort_if($record->peserta_id !== $peserta->id, 403);
        $record->delete();
    }

    // ── Organisasi Profesi ────────────────────────────────────────────────────

    public function simpanOrganisasi(?int $id, string $tahun, string $namaOrganisasi, ?string $jabatan): void
    {
        abort_if(blank($namaOrganisasi), 422);
        $peserta = auth()->user()->peserta;
        abort_if(! $peserta, 403);

        $data = ['peserta_id' => $peserta->id, 'tahun' => $tahun, 'nama_organisasi' => $namaOrganisasi, 'jabatan' => $jabatan ?: null];

        if ($id) {
            $record = OrganisasiProfesi::findOrFail($id);
            abort_if($record->peserta_id !== $peserta->id, 403);
            $record->update($data);
        } else {
            OrganisasiProfesi::create($data);
        }
    }

    public function hapusOrganisasi(int $id): void
    {
        $peserta = auth()->user()->peserta;
        $record  = OrganisasiProfesi::findOrFail($id);
        abort_if($record->peserta_id !== $peserta->id, 403);
        $record->delete();
    }

    public function with(): array
    {
        $peserta = auth()->user()->peserta;

        return [
            'riwayatPendidikan'  => $peserta?->riwayatPendidikan()->latest()->get() ?? collect(),
            'pelatihan'          => $peserta?->pelatihanProfesional()->latest()->get() ?? collect(),
            'konferensi'         => $peserta?->konferensiSeminar()->latest()->get() ?? collect(),
            'penghargaan'        => $peserta?->penghargaan()->latest()->get() ?? collect(),
            'organisasi'         => $peserta?->organisasiProfesi()->latest()->get() ?? collect(),
            'fotoUrl'            => $peserta?->foto ? route('peserta.foto', $peserta) : null,
        ];
    }
}; ?>

<x-slot:title>Profil Saya</x-slot:title>
<x-slot:subtitle>Kelola data diri, riwayat, dan keamanan akun Anda</x-slot:subtitle>

<div
    x-data="{
        tab: {{ $mustChangePassword ? "'password'" : "'biodata'" }},
        tabs: ['biodata','pendidikan','pelatihan','konferensi','penghargaan','organisasi','password'],
        mustChangePassword: @entangle('mustChangePassword'),
        tabLabels: {
            biodata:     'Biodata',
            pendidikan:  'Riwayat Pendidikan',
            pelatihan:   'Pelatihan',
            konferensi:  'Konferensi / Seminar',
            penghargaan: 'Penghargaan',
            organisasi:  'Organisasi Profesi',
            password:    'Ganti Password',
        },
        savedMsg: '',
        get canAccessTab() { return !this.mustChangePassword || this.tab === 'password'; },
    }"
    @biodata-saved.window="savedMsg = 'Biodata berhasil disimpan.'; setTimeout(() => savedMsg = '', 3500)"
    @password-saved.window="savedMsg = 'Password berhasil diubah.'; setTimeout(() => savedMsg = '', 3500)"
>

    {{-- Toast --}}
    <div x-show="savedMsg" x-cloak
         x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0 translate-y-2" x-transition:enter-end="opacity-100 translate-y-0"
         x-transition:leave="transition ease-in duration-150" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0"
         class="fixed bottom-5 right-5 z-50 bg-[#1e7e3e] text-white text-[12px] font-semibold px-4 py-2.5 rounded-xl shadow-lg flex items-center gap-2">
        <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>
        <span x-text="savedMsg"></span>
    </div>

    {{-- Tab bar --}}
    <div class="bg-white rounded-xl border border-[#E5E8EC] overflow-hidden mb-4">
        <div class="flex overflow-x-auto border-b border-[#F0F2F5]">
            <template x-for="t in tabs" :key="t">
                <button @click="if (!mustChangePassword || t === 'password') tab = t"
                    :class="{
                        'border-b-2 border-primary text-primary font-semibold': tab === t,
                        'text-[#8a9ba8]': tab !== t && (!mustChangePassword || t === 'password'),
                        'text-[#D0D5DD] cursor-not-allowed': mustChangePassword && t !== 'password',
                        'hover:text-[#1a2a35]': !mustChangePassword || t === 'password',
                    }"
                    class="px-4 py-3 text-[12px] whitespace-nowrap transition-colors shrink-0"
                    x-text="tabLabels[t]">
                </button>
            </template>
        </div>
        {{-- Warning banner jika harus ganti password --}}
        <div x-show="mustChangePassword" x-cloak class="px-4 py-2.5 bg-[#FFF8E1] border-t border-[#FFE082] flex items-center gap-2 text-[11px] text-[#b45309]">
            <svg class="w-4 h-4 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
            Ganti password Anda terlebih dahulu sebelum dapat mengisi data profil lainnya.
        </div>
    </div>

    {{-- ============ TAB: BIODATA ============ --}}
    <div x-show="tab === 'biodata'" x-cloak>
        <div class="bg-white rounded-xl border border-[#E5E8EC] p-5 mb-4">
            <div class="text-[13px] font-semibold text-[#1a2a35] mb-4">Data Akun &amp; Identitas</div>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">

                {{-- Nama --}}
                <div>
                    <label class="block text-[11px] font-semibold text-[#5a6a75] uppercase tracking-[0.7px] mb-1.5">Nama Lengkap <span class="text-[#D2092F]">*</span></label>
                    <input wire:model="nama" type="text" placeholder="Nama sesuai KTP"
                        class="w-full h-[40px] px-3.5 text-[13px] text-[#1a2a35] bg-white border border-[#E0E5EA] rounded-xl outline-none focus:border-primary focus:ring-2 focus:ring-primary/10 placeholder:text-[#b0bec5]" />
                    @error('nama') <p class="mt-1 text-[11px] text-[#c62828]">{{ $message }}</p> @enderror
                </div>

                {{-- Email --}}
                <div>
                    <label class="block text-[11px] font-semibold text-[#5a6a75] uppercase tracking-[0.7px] mb-1.5">Email <span class="text-[#D2092F]">*</span></label>
                    <input wire:model="email" type="email" placeholder="nama@email.com"
                        class="w-full h-[40px] px-3.5 text-[13px] text-[#1a2a35] bg-white border border-[#E0E5EA] rounded-xl outline-none focus:border-primary focus:ring-2 focus:ring-primary/10 placeholder:text-[#b0bec5]" />
                    @error('email') <p class="mt-1 text-[11px] text-[#c62828]">{{ $message }}</p> @enderror
                </div>

                {{-- NIK --}}
                <div>
                    <label class="block text-[11px] font-semibold text-[#5a6a75] uppercase tracking-[0.7px] mb-1.5">NIP / NIK</label>
                    <input wire:model="nik" type="text" placeholder="Nomor identitas"
                        class="w-full h-[40px] px-3.5 text-[13px] text-[#1a2a35] bg-white border border-[#E0E5EA] rounded-xl outline-none focus:border-primary focus:ring-2 focus:ring-primary/10 placeholder:text-[#b0bec5]" />
                </div>

                {{-- Telepon --}}
                <div>
                    <label class="block text-[11px] font-semibold text-[#5a6a75] uppercase tracking-[0.7px] mb-1.5">No. HP / WA</label>
                    <input wire:model="telepon" type="text" placeholder="08xxxxxxxxxx"
                        class="w-full h-[40px] px-3.5 text-[13px] text-[#1a2a35] bg-white border border-[#E0E5EA] rounded-xl outline-none focus:border-primary focus:ring-2 focus:ring-primary/10 placeholder:text-[#b0bec5]" />
                </div>

                {{-- Telepon Faks --}}
                <div>
                    <label class="block text-[11px] font-semibold text-[#5a6a75] uppercase tracking-[0.7px] mb-1.5">Telepon / Faks <span class="font-normal text-[#b0bec5] normal-case">(opsional)</span></label>
                    <input wire:model="teleponFaks" type="text" placeholder="Nomor faks jika ada"
                        class="w-full h-[40px] px-3.5 text-[13px] text-[#1a2a35] bg-white border border-[#E0E5EA] rounded-xl outline-none focus:border-primary focus:ring-2 focus:ring-primary/10 placeholder:text-[#b0bec5]" />
                </div>

                {{-- Jenis Kelamin --}}
                <div x-data="{
                    open: false,
                    val: @entangle('jenisKelamin').live,
                    opts: [{v:'L',l:'Laki-laki'},{v:'P',l:'Perempuan'}],
                    get label() { return this.opts.find(o=>o.v===this.val)?.l ?? 'Pilih jenis kelamin'; }
                }">
                    <label class="block text-[11px] font-semibold text-[#5a6a75] uppercase tracking-[0.7px] mb-1.5">Jenis Kelamin</label>
                    <div class="relative">
                        <button type="button" @click="open=!open"
                            :class="open ? 'border-primary ring-2 ring-primary/10' : 'border-[#E0E5EA] hover:border-[#C5CDD5]'"
                            class="w-full h-[40px] px-3.5 flex items-center justify-between bg-white border rounded-xl text-[13px] transition-all">
                            <span :class="val ? 'text-[#1a2a35]' : 'text-[#b0bec5]'" x-text="label"></span>
                            <svg class="w-4 h-4 text-[#8a9ba8] shrink-0" :class="open && 'rotate-180'" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="6 9 12 15 18 9"/></svg>
                        </button>
                        <div x-show="open" @click.outside="open=false" x-cloak
                            x-transition:enter="transition ease-out duration-100" x-transition:enter-start="opacity-0 -translate-y-1" x-transition:enter-end="opacity-100 translate-y-0"
                            class="absolute z-20 mt-1 w-full bg-white border border-[#E0E5EA] rounded-xl shadow-lg overflow-hidden">
                            <template x-for="o in opts" :key="o.v">
                                <button type="button" @click="val=o.v; open=false"
                                    :class="val===o.v ? 'bg-[#E8F4F8] text-primary font-semibold' : 'hover:bg-[#F4F6F8] text-[#1a2a35]'"
                                    class="w-full px-3.5 py-2 text-left text-[13px] transition-colors" x-text="o.l"></button>
                            </template>
                        </div>
                    </div>
                </div>

                {{-- Tempat Lahir --}}
                <div>
                    <label class="block text-[11px] font-semibold text-[#5a6a75] uppercase tracking-[0.7px] mb-1.5">Tempat Lahir</label>
                    <input wire:model="tempatLahir" type="text" placeholder="Kota tempat lahir"
                        class="w-full h-[40px] px-3.5 text-[13px] text-[#1a2a35] bg-white border border-[#E0E5EA] rounded-xl outline-none focus:border-primary focus:ring-2 focus:ring-primary/10 placeholder:text-[#b0bec5]" />
                </div>

                {{-- Tanggal Lahir --}}
                <div x-data="{ val: @entangle('tanggalLahir').live }">
                    <label class="block text-[11px] font-semibold text-[#5a6a75] uppercase tracking-[0.7px] mb-1.5">Tanggal Lahir</label>
                    <x-form.date-picker x-model="val" :enable-time="false" placeholder="Pilih tanggal lahir" class="w-full" />
                    @error('tanggalLahir') <p class="mt-1 text-[11px] text-[#c62828]">{{ $message }}</p> @enderror
                </div>

                {{-- Agama --}}
                <div>
                    <label class="block text-[11px] font-semibold text-[#5a6a75] uppercase tracking-[0.7px] mb-1.5">Agama</label>
                    <input wire:model="agama" type="text" placeholder="Islam, Kristen, dll."
                        class="w-full h-[40px] px-3.5 text-[13px] text-[#1a2a35] bg-white border border-[#E0E5EA] rounded-xl outline-none focus:border-primary focus:ring-2 focus:ring-primary/10 placeholder:text-[#b0bec5]" />
                </div>

                {{-- Golongan/Pangkat --}}
                <div>
                    <label class="block text-[11px] font-semibold text-[#5a6a75] uppercase tracking-[0.7px] mb-1.5">Golongan / Pangkat <span class="font-normal text-[#b0bec5] normal-case">(opsional)</span></label>
                    <input wire:model="golonganPangkat" type="text" placeholder="Contoh: III/A"
                        class="w-full h-[40px] px-3.5 text-[13px] text-[#1a2a35] bg-white border border-[#E0E5EA] rounded-xl outline-none focus:border-primary focus:ring-2 focus:ring-primary/10 placeholder:text-[#b0bec5]" />
                </div>

                {{-- Instansi --}}
                <div>
                    <label class="block text-[11px] font-semibold text-[#5a6a75] uppercase tracking-[0.7px] mb-1.5">Instansi <span class="font-normal text-[#b0bec5] normal-case">(opsional)</span></label>
                    <input wire:model="instansi" type="text" placeholder="Nama perusahaan / instansi"
                        class="w-full h-[40px] px-3.5 text-[13px] text-[#1a2a35] bg-white border border-[#E0E5EA] rounded-xl outline-none focus:border-primary focus:ring-2 focus:ring-primary/10 placeholder:text-[#b0bec5]" />
                </div>

                {{-- Pekerjaan --}}
                <div>
                    <label class="block text-[11px] font-semibold text-[#5a6a75] uppercase tracking-[0.7px] mb-1.5">Pekerjaan / Jabatan <span class="font-normal text-[#b0bec5] normal-case">(opsional)</span></label>
                    <input wire:model="pekerjaan" type="text" placeholder="Teknisi, Supervisor, dll."
                        class="w-full h-[40px] px-3.5 text-[13px] text-[#1a2a35] bg-white border border-[#E0E5EA] rounded-xl outline-none focus:border-primary focus:ring-2 focus:ring-primary/10 placeholder:text-[#b0bec5]" />
                </div>

                {{-- Alamat --}}
                <div class="md:col-span-2">
                    <label class="block text-[11px] font-semibold text-[#5a6a75] uppercase tracking-[0.7px] mb-1.5">Alamat</label>
                    <textarea wire:model="alamat" rows="2" placeholder="Alamat lengkap"
                        class="w-full px-3.5 py-2 text-[13px] text-[#1a2a35] bg-white border border-[#E0E5EA] rounded-xl outline-none focus:border-primary focus:ring-2 focus:ring-primary/10 placeholder:text-[#b0bec5] resize-none"></textarea>
                </div>

                {{-- Provinsi, Kota, Kode Pos (satu baris) --}}
                <div class="md:col-span-2" x-data="{
                    provOpen: false, kotaOpen: false,
                    provinces: [], regencies: [],
                    loadingProv: true, loadingKota: false,
                    provName: @entangle('provinsi').live,
                    kotaName: @entangle('kota').live,
                    provCode: null,
                    provSearch: '', kotaSearch: '',
                    get filteredProv() { return this.provinces.filter(p => p.name.toLowerCase().includes(this.provSearch.toLowerCase())); },
                    get filteredKota() { return this.regencies.filter(r => r.name.toLowerCase().includes(this.kotaSearch.toLowerCase())); },
                    async init() {
                        const r = await fetch('https://wilayah.id/api/provinces.json');
                        const d = await r.json();
                        this.provinces = d.data ?? [];
                        this.loadingProv = false;
                        if (this.provName) {
                            const found = this.provinces.find(p => p.name === this.provName);
                            if (found) {
                                this.provCode = found.code;
                                const r2 = await fetch('https://wilayah.id/api/regencies/' + found.code + '.json');
                                const d2 = await r2.json();
                                this.regencies = d2.data ?? [];
                            }
                        }
                    },
                    async selectProv(code, name) {
                        this.provName = name; this.provCode = code; this.provOpen = false; this.provSearch = '';
                        this.kotaName = ''; this.regencies = []; this.loadingKota = true;
                        const r = await fetch('https://wilayah.id/api/regencies/' + code + '.json');
                        const d = await r.json();
                        this.regencies = d.data ?? []; this.loadingKota = false;
                    },
                    selectKota(name) { this.kotaName = name; this.kotaOpen = false; this.kotaSearch = ''; }
                }">
                    <div class="grid grid-cols-3 gap-x-3">

                        {{-- Provinsi --}}
                        <div>
                            <label class="block text-[11px] font-semibold text-[#5a6a75] uppercase tracking-[0.7px] mb-1.5">Provinsi</label>
                            <div class="relative">
                                <button type="button" @click="provOpen=!provOpen; if(provOpen) $nextTick(()=>$refs.provSearch.focus())"
                                    :class="provOpen ? 'border-primary ring-2 ring-primary/10' : 'border-[#E0E5EA] hover:border-[#C5CDD5]'"
                                    class="w-full h-[40px] px-3.5 flex items-center justify-between bg-white border rounded-xl text-[13px] transition-all">
                                    <span :class="provName ? 'text-[#1a2a35]' : 'text-[#b0bec5]'" x-text="provName || 'Pilih provinsi'" class="truncate mr-1"></span>
                                    <svg class="w-4 h-4 text-[#8a9ba8] shrink-0" :class="provOpen && 'rotate-180'" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="6 9 12 15 18 9"/></svg>
                                </button>
                                <div x-show="provOpen" @click.outside="provOpen=false" x-cloak
                                    x-transition:enter="transition ease-out duration-100" x-transition:enter-start="opacity-0 -translate-y-1" x-transition:enter-end="opacity-100 translate-y-0"
                                    class="absolute z-30 mt-1 w-full bg-white border border-[#E0E5EA] rounded-xl shadow-lg overflow-hidden">
                                    <div class="p-2 border-b border-[#F0F2F5]">
                                        <input x-ref="provSearch" x-model="provSearch" type="text" placeholder="Cari provinsi..." class="w-full h-[32px] px-2.5 text-[12px] bg-[#F4F6F8] border border-transparent rounded-lg outline-none focus:border-primary placeholder:text-[#b0bec5]" />
                                    </div>
                                    <div class="max-h-[180px] overflow-y-auto">
                                        <template x-if="loadingProv"><div class="py-3 text-center text-[12px] text-[#8a9ba8]">Memuat...</div></template>
                                        <template x-if="!loadingProv && filteredProv.length === 0"><div class="py-3 text-center text-[12px] text-[#8a9ba8]">Tidak ditemukan</div></template>
                                        <template x-for="p in filteredProv" :key="p.code">
                                            <button type="button" @click="selectProv(p.code, p.name)"
                                                :class="provName===p.name ? 'bg-[#E8F4F8] text-primary font-semibold' : 'hover:bg-[#F4F6F8] text-[#1a2a35]'"
                                                class="w-full px-3.5 py-2 text-left text-[12px] transition-colors" x-text="p.name"></button>
                                        </template>
                                    </div>
                                </div>
                            </div>
                        </div>

                        {{-- Kota --}}
                        <div>
                            <label class="block text-[11px] font-semibold text-[#5a6a75] uppercase tracking-[0.7px] mb-1.5">Kota / Kabupaten</label>
                            <div class="relative">
                                <button type="button"
                                    @click="if(provCode) { kotaOpen=!kotaOpen; if(kotaOpen) $nextTick(()=>$refs.kotaSearch.focus()); }"
                                    :disabled="!provCode"
                                    :class="kotaOpen ? 'border-primary ring-2 ring-primary/10' : (provCode ? 'border-[#E0E5EA] hover:border-[#C5CDD5]' : 'border-[#E0E5EA] opacity-50 cursor-not-allowed')"
                                    class="w-full h-[40px] px-3.5 flex items-center justify-between bg-white border rounded-xl text-[13px] transition-all">
                                    <span :class="kotaName ? 'text-[#1a2a35]' : 'text-[#b0bec5]'" x-text="loadingKota ? 'Memuat...' : (kotaName || (provCode ? 'Pilih kota' : 'Pilih provinsi dulu'))" class="truncate mr-1"></span>
                                    <svg class="w-4 h-4 text-[#8a9ba8] shrink-0" :class="kotaOpen && 'rotate-180'" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="6 9 12 15 18 9"/></svg>
                                </button>
                                <div x-show="kotaOpen" @click.outside="kotaOpen=false" x-cloak
                                    x-transition:enter="transition ease-out duration-100" x-transition:enter-start="opacity-0 -translate-y-1" x-transition:enter-end="opacity-100 translate-y-0"
                                    class="absolute z-30 mt-1 w-full bg-white border border-[#E0E5EA] rounded-xl shadow-lg overflow-hidden">
                                    <div class="p-2 border-b border-[#F0F2F5]">
                                        <input x-ref="kotaSearch" x-model="kotaSearch" type="text" placeholder="Cari kota..." class="w-full h-[32px] px-2.5 text-[12px] bg-[#F4F6F8] border border-transparent rounded-lg outline-none focus:border-primary placeholder:text-[#b0bec5]" />
                                    </div>
                                    <div class="max-h-[180px] overflow-y-auto">
                                        <template x-if="filteredKota.length === 0"><div class="py-3 text-center text-[12px] text-[#8a9ba8]">Tidak ditemukan</div></template>
                                        <template x-for="k in filteredKota" :key="k.code">
                                            <button type="button" @click="selectKota(k.name)"
                                                :class="kotaName===k.name ? 'bg-[#E8F4F8] text-primary font-semibold' : 'hover:bg-[#F4F6F8] text-[#1a2a35]'"
                                                class="w-full px-3.5 py-2 text-left text-[12px] transition-colors" x-text="k.name"></button>
                                        </template>
                                    </div>
                                </div>
                            </div>
                        </div>

                        {{-- Kode Pos --}}
                        <div>
                            <label class="block text-[11px] font-semibold text-[#5a6a75] uppercase tracking-[0.7px] mb-1.5">Kode Pos</label>
                            <input wire:model="kodePos" type="text" placeholder="Kode pos" maxlength="10"
                                class="w-full h-[40px] px-3.5 text-[13px] text-[#1a2a35] bg-white border border-[#E0E5EA] rounded-xl outline-none focus:border-primary focus:ring-2 focus:ring-primary/10 placeholder:text-[#b0bec5]" />
                        </div>

                    </div>
                </div>

            </div>
        </div>

        {{-- Foto --}}
        <div class="bg-white rounded-xl border border-[#E5E8EC] p-5 mb-4">
            <div class="text-[13px] font-semibold text-[#1a2a35] mb-4">Pas Foto</div>
            <div x-data="{ preview: null }" class="flex items-start gap-4">
                <div class="shrink-0 w-[48px] h-[64px] rounded-lg border-2 border-dashed border-[#D0D5DD] bg-[#F4F6F8] overflow-hidden flex items-center justify-center">
                    <template x-if="preview">
                        <img :src="preview" class="w-full h-full object-cover" />
                    </template>
                    <template x-if="!preview">
                        @if ($fotoUrl)
                        <img src="{{ $fotoUrl }}" class="w-full h-full object-cover" />
                        @else
                        <svg class="w-4 h-4 text-[#b0bec5]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><rect x="3" y="3" width="18" height="18" rx="2"/><circle cx="8.5" cy="8.5" r="1.5"/><polyline points="21 15 16 10 5 21"/></svg>
                        @endif
                    </template>
                </div>
                <div class="flex-1">
                    <label class="flex items-center gap-3 h-[64px] px-4 border-2 border-dashed border-[#D0D5DD] rounded-xl bg-white hover:border-primary hover:bg-[#F0F7FA] transition-all cursor-pointer group">
                        <input type="file" wire:model="foto" accept="image/jpg,image/jpeg,image/png" class="hidden"
                            @change="const f=$event.target.files[0]; if(f){const r=new FileReader();r.onload=e=>preview=e.target.result;r.readAsDataURL(f);}" />
                        <svg class="w-5 h-5 text-[#8a9ba8] group-hover:text-primary transition-colors shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="16 16 12 12 8 16"/><line x1="12" y1="12" x2="12" y2="21"/><path d="M20.39 18.39A5 5 0 0018 9h-1.26A8 8 0 103 16.3"/></svg>
                        <div>
                            <div class="text-[12px] font-medium text-[#5a6a75] group-hover:text-primary transition-colors">Latar merah, ukuran 3×4</div>
                            <div class="text-[11px] text-[#b0bec5] mt-0.5">JPG / PNG, maks 2 MB</div>
                        </div>
                    </label>
                    <div wire:loading wire:target="foto" class="mt-1 text-[11px] text-[#8a9ba8]">Mengupload...</div>
                    @error('foto') <p class="mt-1 text-[11px] text-[#c62828]">{{ $message }}</p> @enderror
                </div>
            </div>
        </div>

        <div class="flex justify-end">
            <button wire:click="simpanBiodata" wire:loading.attr="disabled"
                class="h-[42px] px-6 bg-primary hover:bg-[#005f78] text-white text-[13px] font-semibold rounded-xl transition-colors disabled:opacity-60">
                <span wire:loading.remove wire:target="simpanBiodata">Simpan Biodata</span>
                <span wire:loading wire:target="simpanBiodata">Menyimpan...</span>
            </button>
        </div>
    </div>

    {{-- ============ MACRO: Sub-tabel Section ============ --}}
    {{-- Pendidikan --}}
    <div x-show="tab === 'pendidikan'" x-cloak
         x-data="{
            modal: { open: false, editId: null, namaSekolah: '', tahunLulus: '', jurusan: '' },
            hapusModal: { open: false, id: null },
            openTambah() { this.modal = { open: true, editId: null, namaSekolah: '', tahunLulus: '', jurusan: '' }; },
            openEdit(id, namaSekolah, tahunLulus, jurusan) { this.modal = { open: true, editId: id, namaSekolah, tahunLulus: tahunLulus ?? '', jurusan: jurusan ?? '' }; },
            async simpan() { await $wire.simpanPendidikan(this.modal.editId, this.modal.namaSekolah, this.modal.tahunLulus || null, this.modal.jurusan || null); this.modal.open = false; },
            openHapus(id) { this.hapusModal = { open: true, id }; },
            doHapus() { $wire.hapusPendidikan(this.hapusModal.id); this.hapusModal.open = false; }
         }">

        @include('livewire.peserta.profil.partials.subtabel-header', ['title' => 'Riwayat Pendidikan', 'desc' => 'Riwayat pendidikan formal Anda.'])

        {{-- Tabel --}}
        @if ($riwayatPendidikan->isNotEmpty())
        <div class="bg-white rounded-xl border border-[#E5E8EC] overflow-hidden mb-4">
            <table class="w-full text-[12px]">
                <thead><tr class="border-b border-[#F0F2F5] bg-[#FAFBFC]">
                    <th class="text-left font-semibold text-[#8a9ba8] px-5 py-2.5">Nama Sekolah / Institusi</th>
                    <th class="text-left font-semibold text-[#8a9ba8] px-3 py-2.5">Jurusan</th>
                    <th class="text-left font-semibold text-[#8a9ba8] px-3 py-2.5">Tahun Lulus</th>
                    <th class="px-3 py-2.5 pr-5"></th>
                </tr></thead>
                <tbody>
                    @foreach ($riwayatPendidikan as $row)
                    <tr class="border-b border-[#F6F8FA] last:border-0" wire:key="rp-{{ $row->id }}">
                        <td class="px-5 py-3 font-medium text-[#1a2a35]">{{ $row->nama_sekolah }}</td>
                        <td class="px-3 py-3 text-[#5a6a75]">{{ $row->jurusan ?? '—' }}</td>
                        <td class="px-3 py-3 text-[#5a6a75]">{{ $row->tahun_lulus ?? '—' }}</td>
                        <td class="px-3 py-3 pr-5">
                            <div class="flex items-center gap-1 justify-end">
                                <button @click="openEdit({{ $row->id }}, @js($row->nama_sekolah), @js($row->tahun_lulus), @js($row->jurusan))"
                                    class="w-[28px] h-[28px] rounded-md border border-[#D0D5DD] text-[#5a6a75] hover:border-primary hover:text-primary hover:bg-[#E8F4F8] flex items-center justify-center transition-colors">
                                    <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M11 4H4a2 2 0 00-2 2v14a2 2 0 002 2h14a2 2 0 002-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 013 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
                                </button>
                                <button @click="openHapus({{ $row->id }})"
                                    class="w-[28px] h-[28px] rounded-md border border-[#D0D5DD] text-[#5a6a75] hover:border-[#c62828] hover:text-[#c62828] hover:bg-[#FCE8E6] flex items-center justify-center transition-colors">
                                    <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="3 6 5 6 21 6"/><path d="M19 6l-1 14a2 2 0 01-2 2H8a2 2 0 01-2-2L5 6"/></svg>
                                </button>
                            </div>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        @else
        @include('livewire.peserta.profil.partials.empty-state', ['label' => 'riwayat pendidikan'])
        @endif

        {{-- Modal Tambah/Edit --}}
        <div x-show="modal.open" x-cloak class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 p-4"
             x-transition:enter="transition ease-out duration-150" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
             x-transition:leave="transition ease-in duration-100" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0">
            <div @click.outside="modal.open = false" @keydown.escape.window="modal.open = false"
                 class="bg-white rounded-2xl shadow-xl w-full max-w-md p-6"
                 x-transition:enter="transition ease-out duration-150" x-transition:enter-start="opacity-0 scale-95" x-transition:enter-end="opacity-100 scale-100"
                 x-transition:leave="transition ease-in duration-100" x-transition:leave-start="opacity-100 scale-100" x-transition:leave-end="opacity-0 scale-95">
                <div class="text-[14px] font-semibold text-[#1a2a35] mb-4" x-text="modal.editId ? 'Edit Pendidikan' : 'Tambah Pendidikan'"></div>
                <div class="space-y-3">
                    <div>
                        <label class="block text-[11px] font-semibold text-[#5a6a75] uppercase tracking-[0.7px] mb-1">Nama Sekolah / Institusi <span class="text-[#D2092F]">*</span></label>
                        <input x-model="modal.namaSekolah" type="text" placeholder="Universitas, SMA, dll."
                            class="w-full h-[40px] px-3.5 text-[13px] text-[#1a2a35] bg-white border border-[#E0E5EA] rounded-xl outline-none focus:border-primary focus:ring-2 focus:ring-primary/10 placeholder:text-[#b0bec5]" />
                    </div>
                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block text-[11px] font-semibold text-[#5a6a75] uppercase tracking-[0.7px] mb-1">Jurusan</label>
                            <input x-model="modal.jurusan" type="text" placeholder="IPA/IPS, RPL, TKJ"
                                class="w-full h-[40px] px-3.5 text-[13px] text-[#1a2a35] bg-white border border-[#E0E5EA] rounded-xl outline-none focus:border-primary focus:ring-2 focus:ring-primary/10 placeholder:text-[#b0bec5]" />
                        </div>
                        <div>
                            <label class="block text-[11px] font-semibold text-[#5a6a75] uppercase tracking-[0.7px] mb-1">Tahun Lulus</label>
                            <input x-model="modal.tahunLulus" type="text" placeholder="2020" maxlength="4"
                                class="w-full h-[40px] px-3.5 text-[13px] text-[#1a2a35] bg-white border border-[#E0E5EA] rounded-xl outline-none focus:border-primary focus:ring-2 focus:ring-primary/10 placeholder:text-[#b0bec5]" />
                        </div>
                    </div>
                </div>
                <div class="flex gap-3 mt-5">
                    <button type="button" @click="modal.open = false" class="flex-1 h-[40px] border border-[#D8DDE2] text-[#1a2a35] text-[13px] font-semibold rounded-xl hover:bg-[#F4F6F8] transition-colors">Batal</button>
                    <button type="button" @click="simpan()" class="flex-1 h-[40px] bg-primary hover:bg-[#005f78] text-white text-[13px] font-semibold rounded-xl transition-colors">Simpan</button>
                </div>
            </div>
        </div>

        {{-- Modal Hapus --}}
        @include('livewire.peserta.profil.partials.hapus-modal-inline')
        <div x-show="hapusModal.open" x-cloak class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 p-4"
             x-transition:enter="transition ease-out duration-150" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
             x-transition:leave="transition ease-in duration-100" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0">
            <div @click.outside="hapusModal.open = false" @keydown.escape.window="hapusModal.open = false"
                 class="bg-white rounded-2xl shadow-xl w-full max-w-sm p-6"
                 x-transition:enter="transition ease-out duration-150" x-transition:enter-start="opacity-0 scale-95" x-transition:enter-end="opacity-100 scale-100">
                <div class="flex items-center gap-3 mb-4">
                    <div class="w-10 h-10 rounded-full bg-[#FCE8E6] flex items-center justify-center shrink-0">
                        <svg class="w-5 h-5 text-[#c62828]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="3 6 5 6 21 6"/><path d="M19 6l-1 14a2 2 0 01-2 2H8a2 2 0 01-2-2L5 6"/></svg>
                    </div>
                    <div>
                        <div class="text-[14px] font-semibold text-[#1a2a35]">Hapus Data?</div>
                        <div class="text-[12px] text-[#8a9ba8]">Data akan dihapus permanen.</div>
                    </div>
                </div>
                <div class="flex gap-3">
                    <button @click="hapusModal.open = false" class="flex-1 h-[40px] border border-[#D8DDE2] text-[#1a2a35] text-[13px] font-semibold rounded-xl hover:bg-[#F4F6F8] transition-colors">Batal</button>
                    <button @click="doHapus()" class="flex-1 h-[40px] bg-[#c62828] hover:bg-[#b71c1c] text-white text-[13px] font-semibold rounded-xl transition-colors">Ya, Hapus</button>
                </div>
            </div>
        </div>
    </div>

    {{-- Pelatihan Profesional --}}
    <div x-show="tab === 'pelatihan'" x-cloak
         x-data="{
            modal: { open: false, editId: null, tahun: '', jenisPelatihan: '', penyelenggara: '', jangkaWaktu: '' },
            hapusModal: { open: false, id: null },
            openTambah() { this.modal = { open: true, editId: null, tahun: '', jenisPelatihan: '', penyelenggara: '', jangkaWaktu: '' }; },
            openEdit(id, tahun, jenisPelatihan, penyelenggara, jangkaWaktu) { this.modal = { open: true, editId: id, tahun: tahun ?? '', jenisPelatihan, penyelenggara, jangkaWaktu: jangkaWaktu ?? '' }; },
            async simpan() { await $wire.simpanPelatihan(this.modal.editId, this.modal.tahun, this.modal.jenisPelatihan, this.modal.penyelenggara, this.modal.jangkaWaktu || null); this.modal.open = false; },
            openHapus(id) { this.hapusModal = { open: true, id }; },
            doHapus() { $wire.hapusPelatihan(this.hapusModal.id); this.hapusModal.open = false; }
         }">

        <div class="flex items-center justify-between mb-3">
            <div>
                <div class="text-[13px] font-semibold text-[#1a2a35]">Pelatihan Profesional</div>
                <div class="text-[11px] text-[#8a9ba8]">Pelatihan dalam / luar negeri yang pernah diikuti.</div>
            </div>
            <button @click="openTambah()" class="flex items-center gap-1.5 h-[36px] px-4 bg-primary hover:bg-[#005f78] text-white text-[12px] font-semibold rounded-xl transition-colors">
                <svg class="w-3.5 h-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
                Tambah
            </button>
        </div>

        @if ($pelatihan->isNotEmpty())
        <div class="bg-white rounded-xl border border-[#E5E8EC] overflow-hidden mb-4">
            <table class="w-full text-[12px]">
                <thead><tr class="border-b border-[#F0F2F5] bg-[#FAFBFC]">
                    <th class="text-left font-semibold text-[#8a9ba8] px-5 py-2.5">Jenis Pelatihan</th>
                    <th class="text-left font-semibold text-[#8a9ba8] px-3 py-2.5">Penyelenggara</th>
                    <th class="text-left font-semibold text-[#8a9ba8] px-3 py-2.5">Jangka Waktu</th>
                    <th class="text-left font-semibold text-[#8a9ba8] px-3 py-2.5">Tahun</th>
                    <th class="px-3 py-2.5 pr-5"></th>
                </tr></thead>
                <tbody>
                    @foreach ($pelatihan as $row)
                    <tr class="border-b border-[#F6F8FA] last:border-0" wire:key="plt-{{ $row->id }}">
                        <td class="px-5 py-3 font-medium text-[#1a2a35]">{{ $row->jenis_pelatihan }}</td>
                        <td class="px-3 py-3 text-[#5a6a75]">{{ $row->penyelenggara }}</td>
                        <td class="px-3 py-3 text-[#5a6a75]">{{ $row->jangka_waktu ?? '—' }}</td>
                        <td class="px-3 py-3 text-[#5a6a75]">{{ $row->tahun }}</td>
                        <td class="px-3 py-3 pr-5">
                            <div class="flex items-center gap-1 justify-end">
                                <button @click="openEdit({{ $row->id }}, @js($row->tahun), @js($row->jenis_pelatihan), @js($row->penyelenggara), @js($row->jangka_waktu))"
                                    class="w-[28px] h-[28px] rounded-md border border-[#D0D5DD] text-[#5a6a75] hover:border-primary hover:text-primary hover:bg-[#E8F4F8] flex items-center justify-center transition-colors">
                                    <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M11 4H4a2 2 0 00-2 2v14a2 2 0 002 2h14a2 2 0 002-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 013 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
                                </button>
                                <button @click="openHapus({{ $row->id }})"
                                    class="w-[28px] h-[28px] rounded-md border border-[#D0D5DD] text-[#5a6a75] hover:border-[#c62828] hover:text-[#c62828] hover:bg-[#FCE8E6] flex items-center justify-center transition-colors">
                                    <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="3 6 5 6 21 6"/><path d="M19 6l-1 14a2 2 0 01-2 2H8a2 2 0 01-2-2L5 6"/></svg>
                                </button>
                            </div>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        @else
        <div class="bg-white rounded-xl border border-[#E5E8EC] px-5 py-8 text-center text-[12px] text-[#8a9ba8] mb-4">Belum ada data pelatihan.</div>
        @endif

        {{-- Modal Tambah/Edit Pelatihan --}}
        <div x-show="modal.open" x-cloak class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 p-4"
             x-transition:enter="transition ease-out duration-150" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
             x-transition:leave="transition ease-in duration-100" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0">
            <div @click.outside="modal.open = false" @keydown.escape.window="modal.open = false"
                 class="bg-white rounded-2xl shadow-xl w-full max-w-md p-6"
                 x-transition:enter="transition ease-out duration-150" x-transition:enter-start="opacity-0 scale-95" x-transition:enter-end="opacity-100 scale-100"
                 x-transition:leave="transition ease-in duration-100" x-transition:leave-start="opacity-100 scale-100" x-transition:leave-end="opacity-0 scale-95">
                <div class="text-[14px] font-semibold text-[#1a2a35] mb-4" x-text="modal.editId ? 'Edit Pelatihan' : 'Tambah Pelatihan'"></div>
                <div class="space-y-3">
                    <div>
                        <label class="block text-[11px] font-semibold text-[#5a6a75] uppercase tracking-[0.7px] mb-1">Jenis Pelatihan <span class="text-[#D2092F]">*</span></label>
                        <input x-model="modal.jenisPelatihan" type="text" placeholder="Dalam Negeri / Luar Negeri / nama pelatihan"
                            class="w-full h-[40px] px-3.5 text-[13px] text-[#1a2a35] bg-white border border-[#E0E5EA] rounded-xl outline-none focus:border-primary focus:ring-2 focus:ring-primary/10 placeholder:text-[#b0bec5]" />
                    </div>
                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block text-[11px] font-semibold text-[#5a6a75] uppercase tracking-[0.7px] mb-1">Tahun</label>
                            <input x-model="modal.tahun" type="text" placeholder="2023" maxlength="4"
                                class="w-full h-[40px] px-3.5 text-[13px] text-[#1a2a35] bg-white border border-[#E0E5EA] rounded-xl outline-none focus:border-primary focus:ring-2 focus:ring-primary/10 placeholder:text-[#b0bec5]" />
                        </div>
                        <div>
                            <label class="block text-[11px] font-semibold text-[#5a6a75] uppercase tracking-[0.7px] mb-1">Jangka Waktu</label>
                            <input x-model="modal.jangkaWaktu" type="text" placeholder="3 hari / 2 minggu"
                                class="w-full h-[40px] px-3.5 text-[13px] text-[#1a2a35] bg-white border border-[#E0E5EA] rounded-xl outline-none focus:border-primary focus:ring-2 focus:ring-primary/10 placeholder:text-[#b0bec5]" />
                        </div>
                    </div>
                    <div>
                        <label class="block text-[11px] font-semibold text-[#5a6a75] uppercase tracking-[0.7px] mb-1">Penyelenggara</label>
                        <input x-model="modal.penyelenggara" type="text" placeholder="Nama lembaga penyelenggara"
                            class="w-full h-[40px] px-3.5 text-[13px] text-[#1a2a35] bg-white border border-[#E0E5EA] rounded-xl outline-none focus:border-primary focus:ring-2 focus:ring-primary/10 placeholder:text-[#b0bec5]" />
                    </div>
                </div>
                <div class="flex gap-3 mt-5">
                    <button @click="modal.open = false" class="flex-1 h-[40px] border border-[#D8DDE2] text-[#1a2a35] text-[13px] font-semibold rounded-xl hover:bg-[#F4F6F8] transition-colors">Batal</button>
                    <button @click="simpan()" class="flex-1 h-[40px] bg-primary hover:bg-[#005f78] text-white text-[13px] font-semibold rounded-xl transition-colors">Simpan</button>
                </div>
            </div>
        </div>
        {{-- Modal Hapus Pelatihan --}}
        <div x-show="hapusModal.open" x-cloak class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 p-4"
             x-transition:enter="transition ease-out duration-150" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
             x-transition:leave="transition ease-in duration-100" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0">
            <div @click.outside="hapusModal.open = false" class="bg-white rounded-2xl shadow-xl w-full max-w-sm p-6"
                 x-transition:enter="transition ease-out duration-150" x-transition:enter-start="opacity-0 scale-95" x-transition:enter-end="opacity-100 scale-100">
                <div class="flex items-center gap-3 mb-4">
                    <div class="w-10 h-10 rounded-full bg-[#FCE8E6] flex items-center justify-center shrink-0">
                        <svg class="w-5 h-5 text-[#c62828]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="3 6 5 6 21 6"/><path d="M19 6l-1 14a2 2 0 01-2 2H8a2 2 0 01-2-2L5 6"/></svg>
                    </div>
                    <div><div class="text-[14px] font-semibold text-[#1a2a35]">Hapus Data?</div><div class="text-[12px] text-[#8a9ba8]">Data akan dihapus permanen.</div></div>
                </div>
                <div class="flex gap-3">
                    <button @click="hapusModal.open = false" class="flex-1 h-[40px] border border-[#D8DDE2] text-[#1a2a35] text-[13px] font-semibold rounded-xl hover:bg-[#F4F6F8] transition-colors">Batal</button>
                    <button @click="doHapus()" class="flex-1 h-[40px] bg-[#c62828] hover:bg-[#b71c1c] text-white text-[13px] font-semibold rounded-xl transition-colors">Ya, Hapus</button>
                </div>
            </div>
        </div>
    </div>

    {{-- Konferensi / Seminar --}}
    <div x-show="tab === 'konferensi'" x-cloak
         x-data="{
            modal: { open: false, editId: null, tahun: '', judulKegiatan: '', penyelenggara: '', peran: '' },
            hapusModal: { open: false, id: null },
            openTambah() { this.modal = { open: true, editId: null, tahun: '', judulKegiatan: '', penyelenggara: '', peran: '' }; },
            openEdit(id, tahun, judulKegiatan, penyelenggara, peran) { this.modal = { open: true, editId: id, tahun: tahun ?? '', judulKegiatan, penyelenggara, peran: peran ?? '' }; },
            async simpan() { await $wire.simpanKonferensi(this.modal.editId, this.modal.tahun, this.modal.judulKegiatan, this.modal.penyelenggara, this.modal.peran || null); this.modal.open = false; },
            openHapus(id) { this.hapusModal = { open: true, id }; },
            doHapus() { $wire.hapusKonferensi(this.hapusModal.id); this.hapusModal.open = false; }
         }">

        <div class="flex items-center justify-between mb-3">
            <div>
                <div class="text-[13px] font-semibold text-[#1a2a35]">Konferensi / Seminar / Lokakarya</div>
                <div class="text-[11px] text-[#8a9ba8]">Kegiatan konferensi, seminar, lokakarya, atau simposium.</div>
            </div>
            <button @click="openTambah()" class="flex items-center gap-1.5 h-[36px] px-4 bg-primary hover:bg-[#005f78] text-white text-[12px] font-semibold rounded-xl transition-colors">
                <svg class="w-3.5 h-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
                Tambah
            </button>
        </div>

        @if ($konferensi->isNotEmpty())
        <div class="bg-white rounded-xl border border-[#E5E8EC] overflow-hidden mb-4">
            <table class="w-full text-[12px]">
                <thead><tr class="border-b border-[#F0F2F5] bg-[#FAFBFC]">
                    <th class="text-left font-semibold text-[#8a9ba8] px-5 py-2.5">Judul Kegiatan</th>
                    <th class="text-left font-semibold text-[#8a9ba8] px-3 py-2.5">Penyelenggara</th>
                    <th class="text-left font-semibold text-[#8a9ba8] px-3 py-2.5">Peran</th>
                    <th class="text-left font-semibold text-[#8a9ba8] px-3 py-2.5">Tahun</th>
                    <th class="px-3 py-2.5 pr-5"></th>
                </tr></thead>
                <tbody>
                    @foreach ($konferensi as $row)
                    <tr class="border-b border-[#F6F8FA] last:border-0" wire:key="kon-{{ $row->id }}">
                        <td class="px-5 py-3 font-medium text-[#1a2a35]">{{ $row->judul_kegiatan }}</td>
                        <td class="px-3 py-3 text-[#5a6a75]">{{ $row->penyelenggara }}</td>
                        <td class="px-3 py-3 text-[#5a6a75]">{{ $row->peran ?? '—' }}</td>
                        <td class="px-3 py-3 text-[#5a6a75]">{{ $row->tahun }}</td>
                        <td class="px-3 py-3 pr-5">
                            <div class="flex items-center gap-1 justify-end">
                                <button @click="openEdit({{ $row->id }}, @js($row->tahun), @js($row->judul_kegiatan), @js($row->penyelenggara), @js($row->peran))"
                                    class="w-[28px] h-[28px] rounded-md border border-[#D0D5DD] text-[#5a6a75] hover:border-primary hover:text-primary hover:bg-[#E8F4F8] flex items-center justify-center transition-colors">
                                    <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M11 4H4a2 2 0 00-2 2v14a2 2 0 002 2h14a2 2 0 002-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 013 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
                                </button>
                                <button @click="openHapus({{ $row->id }})"
                                    class="w-[28px] h-[28px] rounded-md border border-[#D0D5DD] text-[#5a6a75] hover:border-[#c62828] hover:text-[#c62828] hover:bg-[#FCE8E6] flex items-center justify-center transition-colors">
                                    <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="3 6 5 6 21 6"/><path d="M19 6l-1 14a2 2 0 01-2 2H8a2 2 0 01-2-2L5 6"/></svg>
                                </button>
                            </div>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        @else
        <div class="bg-white rounded-xl border border-[#E5E8EC] px-5 py-8 text-center text-[12px] text-[#8a9ba8] mb-4">Belum ada data konferensi / seminar.</div>
        @endif

        <div x-show="modal.open" x-cloak class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 p-4"
             x-transition:enter="transition ease-out duration-150" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
             x-transition:leave="transition ease-in duration-100" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0">
            <div @click.outside="modal.open = false" @keydown.escape.window="modal.open = false"
                 class="bg-white rounded-2xl shadow-xl w-full max-w-md p-6"
                 x-transition:enter="transition ease-out duration-150" x-transition:enter-start="opacity-0 scale-95" x-transition:enter-end="opacity-100 scale-100"
                 x-transition:leave="transition ease-in duration-100" x-transition:leave-start="opacity-100 scale-100" x-transition:leave-end="opacity-0 scale-95">
                <div class="text-[14px] font-semibold text-[#1a2a35] mb-4" x-text="modal.editId ? 'Edit Konferensi' : 'Tambah Konferensi'"></div>
                <div class="space-y-3">
                    <div>
                        <label class="block text-[11px] font-semibold text-[#5a6a75] uppercase tracking-[0.7px] mb-1">Judul Kegiatan <span class="text-[#D2092F]">*</span></label>
                        <input x-model="modal.judulKegiatan" type="text" placeholder="Nama konferensi / seminar"
                            class="w-full h-[40px] px-3.5 text-[13px] text-[#1a2a35] bg-white border border-[#E0E5EA] rounded-xl outline-none focus:border-primary focus:ring-2 focus:ring-primary/10 placeholder:text-[#b0bec5]" />
                    </div>
                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block text-[11px] font-semibold text-[#5a6a75] uppercase tracking-[0.7px] mb-1">Penyelenggara</label>
                            <input x-model="modal.penyelenggara" type="text" placeholder="Nama lembaga"
                                class="w-full h-[40px] px-3.5 text-[13px] text-[#1a2a35] bg-white border border-[#E0E5EA] rounded-xl outline-none focus:border-primary focus:ring-2 focus:ring-primary/10 placeholder:text-[#b0bec5]" />
                        </div>
                        <div>
                            <label class="block text-[11px] font-semibold text-[#5a6a75] uppercase tracking-[0.7px] mb-1">Peran</label>
                            <input x-model="modal.peran" type="text" placeholder="Panitia / Peserta / Pembicara"
                                class="w-full h-[40px] px-3.5 text-[13px] text-[#1a2a35] bg-white border border-[#E0E5EA] rounded-xl outline-none focus:border-primary focus:ring-2 focus:ring-primary/10 placeholder:text-[#b0bec5]" />
                        </div>
                    </div>
                    <div>
                        <label class="block text-[11px] font-semibold text-[#5a6a75] uppercase tracking-[0.7px] mb-1">Tahun</label>
                        <input x-model="modal.tahun" type="text" placeholder="2023" maxlength="4"
                            class="w-full h-[40px] px-3.5 text-[13px] text-[#1a2a35] bg-white border border-[#E0E5EA] rounded-xl outline-none focus:border-primary focus:ring-2 focus:ring-primary/10 placeholder:text-[#b0bec5]" />
                    </div>
                </div>
                <div class="flex gap-3 mt-5">
                    <button @click="modal.open = false" class="flex-1 h-[40px] border border-[#D8DDE2] text-[#1a2a35] text-[13px] font-semibold rounded-xl hover:bg-[#F4F6F8] transition-colors">Batal</button>
                    <button @click="simpan()" class="flex-1 h-[40px] bg-primary hover:bg-[#005f78] text-white text-[13px] font-semibold rounded-xl transition-colors">Simpan</button>
                </div>
            </div>
        </div>
        <div x-show="hapusModal.open" x-cloak class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 p-4"
             x-transition:enter="transition ease-out duration-150" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
             x-transition:leave="transition ease-in duration-100" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0">
            <div @click.outside="hapusModal.open = false" class="bg-white rounded-2xl shadow-xl w-full max-w-sm p-6"
                 x-transition:enter="transition ease-out duration-150" x-transition:enter-start="opacity-0 scale-95" x-transition:enter-end="opacity-100 scale-100">
                <div class="flex items-center gap-3 mb-4">
                    <div class="w-10 h-10 rounded-full bg-[#FCE8E6] flex items-center justify-center shrink-0"><svg class="w-5 h-5 text-[#c62828]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="3 6 5 6 21 6"/><path d="M19 6l-1 14a2 2 0 01-2 2H8a2 2 0 01-2-2L5 6"/></svg></div>
                    <div><div class="text-[14px] font-semibold text-[#1a2a35]">Hapus Data?</div><div class="text-[12px] text-[#8a9ba8]">Data akan dihapus permanen.</div></div>
                </div>
                <div class="flex gap-3">
                    <button @click="hapusModal.open = false" class="flex-1 h-[40px] border border-[#D8DDE2] text-[#1a2a35] text-[13px] font-semibold rounded-xl hover:bg-[#F4F6F8] transition-colors">Batal</button>
                    <button @click="doHapus()" class="flex-1 h-[40px] bg-[#c62828] hover:bg-[#b71c1c] text-white text-[13px] font-semibold rounded-xl transition-colors">Ya, Hapus</button>
                </div>
            </div>
        </div>
    </div>

    {{-- Penghargaan --}}
    <div x-show="tab === 'penghargaan'" x-cloak
         x-data="{
            modal: { open: false, editId: null, tahun: '', bentukPenghargaan: '', pemberi: '' },
            hapusModal: { open: false, id: null },
            openTambah() { this.modal = { open: true, editId: null, tahun: '', bentukPenghargaan: '', pemberi: '' }; },
            openEdit(id, tahun, bentukPenghargaan, pemberi) { this.modal = { open: true, editId: id, tahun: tahun ?? '', bentukPenghargaan, pemberi }; },
            async simpan() { await $wire.simpanPenghargaan(this.modal.editId, this.modal.tahun, this.modal.bentukPenghargaan, this.modal.pemberi); this.modal.open = false; },
            openHapus(id) { this.hapusModal = { open: true, id }; },
            doHapus() { $wire.hapusPenghargaan(this.hapusModal.id); this.hapusModal.open = false; }
         }">

        <div class="flex items-center justify-between mb-3">
            <div>
                <div class="text-[13px] font-semibold text-[#1a2a35]">Penghargaan / Piagam</div>
                <div class="text-[11px] text-[#8a9ba8]">Penghargaan atau piagam yang pernah diterima.</div>
            </div>
            <button @click="openTambah()" class="flex items-center gap-1.5 h-[36px] px-4 bg-primary hover:bg-[#005f78] text-white text-[12px] font-semibold rounded-xl transition-colors">
                <svg class="w-3.5 h-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
                Tambah
            </button>
        </div>

        @if ($penghargaan->isNotEmpty())
        <div class="bg-white rounded-xl border border-[#E5E8EC] overflow-hidden mb-4">
            <table class="w-full text-[12px]">
                <thead><tr class="border-b border-[#F0F2F5] bg-[#FAFBFC]">
                    <th class="text-left font-semibold text-[#8a9ba8] px-5 py-2.5">Bentuk Penghargaan</th>
                    <th class="text-left font-semibold text-[#8a9ba8] px-3 py-2.5">Pemberi</th>
                    <th class="text-left font-semibold text-[#8a9ba8] px-3 py-2.5">Tahun</th>
                    <th class="px-3 py-2.5 pr-5"></th>
                </tr></thead>
                <tbody>
                    @foreach ($penghargaan as $row)
                    <tr class="border-b border-[#F6F8FA] last:border-0" wire:key="phg-{{ $row->id }}">
                        <td class="px-5 py-3 font-medium text-[#1a2a35]">{{ $row->bentuk_penghargaan }}</td>
                        <td class="px-3 py-3 text-[#5a6a75]">{{ $row->pemberi }}</td>
                        <td class="px-3 py-3 text-[#5a6a75]">{{ $row->tahun }}</td>
                        <td class="px-3 py-3 pr-5">
                            <div class="flex items-center gap-1 justify-end">
                                <button @click="openEdit({{ $row->id }}, @js($row->tahun), @js($row->bentuk_penghargaan), @js($row->pemberi))"
                                    class="w-[28px] h-[28px] rounded-md border border-[#D0D5DD] text-[#5a6a75] hover:border-primary hover:text-primary hover:bg-[#E8F4F8] flex items-center justify-center transition-colors">
                                    <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M11 4H4a2 2 0 00-2 2v14a2 2 0 002 2h14a2 2 0 002-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 013 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
                                </button>
                                <button @click="openHapus({{ $row->id }})"
                                    class="w-[28px] h-[28px] rounded-md border border-[#D0D5DD] text-[#5a6a75] hover:border-[#c62828] hover:text-[#c62828] hover:bg-[#FCE8E6] flex items-center justify-center transition-colors">
                                    <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="3 6 5 6 21 6"/><path d="M19 6l-1 14a2 2 0 01-2 2H8a2 2 0 01-2-2L5 6"/></svg>
                                </button>
                            </div>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        @else
        <div class="bg-white rounded-xl border border-[#E5E8EC] px-5 py-8 text-center text-[12px] text-[#8a9ba8] mb-4">Belum ada data penghargaan.</div>
        @endif

        <div x-show="modal.open" x-cloak class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 p-4"
             x-transition:enter="transition ease-out duration-150" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
             x-transition:leave="transition ease-in duration-100" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0">
            <div @click.outside="modal.open = false" @keydown.escape.window="modal.open = false"
                 class="bg-white rounded-2xl shadow-xl w-full max-w-md p-6"
                 x-transition:enter="transition ease-out duration-150" x-transition:enter-start="opacity-0 scale-95" x-transition:enter-end="opacity-100 scale-100"
                 x-transition:leave="transition ease-in duration-100" x-transition:leave-start="opacity-100 scale-100" x-transition:leave-end="opacity-0 scale-95">
                <div class="text-[14px] font-semibold text-[#1a2a35] mb-4" x-text="modal.editId ? 'Edit Penghargaan' : 'Tambah Penghargaan'"></div>
                <div class="space-y-3">
                    <div>
                        <label class="block text-[11px] font-semibold text-[#5a6a75] uppercase tracking-[0.7px] mb-1">Bentuk Penghargaan <span class="text-[#D2092F]">*</span></label>
                        <input x-model="modal.bentukPenghargaan" type="text" placeholder="Nama / jenis penghargaan"
                            class="w-full h-[40px] px-3.5 text-[13px] text-[#1a2a35] bg-white border border-[#E0E5EA] rounded-xl outline-none focus:border-primary focus:ring-2 focus:ring-primary/10 placeholder:text-[#b0bec5]" />
                    </div>
                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block text-[11px] font-semibold text-[#5a6a75] uppercase tracking-[0.7px] mb-1">Pemberi</label>
                            <input x-model="modal.pemberi" type="text" placeholder="Instansi pemberi"
                                class="w-full h-[40px] px-3.5 text-[13px] text-[#1a2a35] bg-white border border-[#E0E5EA] rounded-xl outline-none focus:border-primary focus:ring-2 focus:ring-primary/10 placeholder:text-[#b0bec5]" />
                        </div>
                        <div>
                            <label class="block text-[11px] font-semibold text-[#5a6a75] uppercase tracking-[0.7px] mb-1">Tahun</label>
                            <input x-model="modal.tahun" type="text" placeholder="2022" maxlength="4"
                                class="w-full h-[40px] px-3.5 text-[13px] text-[#1a2a35] bg-white border border-[#E0E5EA] rounded-xl outline-none focus:border-primary focus:ring-2 focus:ring-primary/10 placeholder:text-[#b0bec5]" />
                        </div>
                    </div>
                </div>
                <div class="flex gap-3 mt-5">
                    <button @click="modal.open = false" class="flex-1 h-[40px] border border-[#D8DDE2] text-[#1a2a35] text-[13px] font-semibold rounded-xl hover:bg-[#F4F6F8] transition-colors">Batal</button>
                    <button @click="simpan()" class="flex-1 h-[40px] bg-primary hover:bg-[#005f78] text-white text-[13px] font-semibold rounded-xl transition-colors">Simpan</button>
                </div>
            </div>
        </div>
        <div x-show="hapusModal.open" x-cloak class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 p-4"
             x-transition:enter="transition ease-out duration-150" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
             x-transition:leave="transition ease-in duration-100" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0">
            <div @click.outside="hapusModal.open = false" class="bg-white rounded-2xl shadow-xl w-full max-w-sm p-6"
                 x-transition:enter="transition ease-out duration-150" x-transition:enter-start="opacity-0 scale-95" x-transition:enter-end="opacity-100 scale-100">
                <div class="flex items-center gap-3 mb-4">
                    <div class="w-10 h-10 rounded-full bg-[#FCE8E6] flex items-center justify-center shrink-0"><svg class="w-5 h-5 text-[#c62828]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="3 6 5 6 21 6"/><path d="M19 6l-1 14a2 2 0 01-2 2H8a2 2 0 01-2-2L5 6"/></svg></div>
                    <div><div class="text-[14px] font-semibold text-[#1a2a35]">Hapus Data?</div><div class="text-[12px] text-[#8a9ba8]">Data akan dihapus permanen.</div></div>
                </div>
                <div class="flex gap-3">
                    <button @click="hapusModal.open = false" class="flex-1 h-[40px] border border-[#D8DDE2] text-[#1a2a35] text-[13px] font-semibold rounded-xl hover:bg-[#F4F6F8] transition-colors">Batal</button>
                    <button @click="doHapus()" class="flex-1 h-[40px] bg-[#c62828] hover:bg-[#b71c1c] text-white text-[13px] font-semibold rounded-xl transition-colors">Ya, Hapus</button>
                </div>
            </div>
        </div>
    </div>

    {{-- Organisasi Profesi --}}
    <div x-show="tab === 'organisasi'" x-cloak
         x-data="{
            modal: { open: false, editId: null, tahun: '', namaOrganisasi: '', jabatan: '' },
            hapusModal: { open: false, id: null },
            openTambah() { this.modal = { open: true, editId: null, tahun: '', namaOrganisasi: '', jabatan: '' }; },
            openEdit(id, tahun, namaOrganisasi, jabatan) { this.modal = { open: true, editId: id, tahun: tahun ?? '', namaOrganisasi, jabatan: jabatan ?? '' }; },
            async simpan() { await $wire.simpanOrganisasi(this.modal.editId, this.modal.tahun, this.modal.namaOrganisasi, this.modal.jabatan || null); this.modal.open = false; },
            openHapus(id) { this.hapusModal = { open: true, id }; },
            doHapus() { $wire.hapusOrganisasi(this.hapusModal.id); this.hapusModal.open = false; }
         }">

        <div class="flex items-center justify-between mb-3">
            <div>
                <div class="text-[13px] font-semibold text-[#1a2a35]">Organisasi Profesi / Ilmiah</div>
                <div class="text-[11px] text-[#8a9ba8]">Keanggotaan dalam organisasi profesi atau ilmiah.</div>
            </div>
            <button @click="openTambah()" class="flex items-center gap-1.5 h-[36px] px-4 bg-primary hover:bg-[#005f78] text-white text-[12px] font-semibold rounded-xl transition-colors">
                <svg class="w-3.5 h-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
                Tambah
            </button>
        </div>

        @if ($organisasi->isNotEmpty())
        <div class="bg-white rounded-xl border border-[#E5E8EC] overflow-hidden mb-4">
            <table class="w-full text-[12px]">
                <thead><tr class="border-b border-[#F0F2F5] bg-[#FAFBFC]">
                    <th class="text-left font-semibold text-[#8a9ba8] px-5 py-2.5">Nama Organisasi</th>
                    <th class="text-left font-semibold text-[#8a9ba8] px-3 py-2.5">Jabatan</th>
                    <th class="text-left font-semibold text-[#8a9ba8] px-3 py-2.5">Tahun</th>
                    <th class="px-3 py-2.5 pr-5"></th>
                </tr></thead>
                <tbody>
                    @foreach ($organisasi as $row)
                    <tr class="border-b border-[#F6F8FA] last:border-0" wire:key="org-{{ $row->id }}">
                        <td class="px-5 py-3 font-medium text-[#1a2a35]">{{ $row->nama_organisasi }}</td>
                        <td class="px-3 py-3 text-[#5a6a75]">{{ $row->jabatan ?? '—' }}</td>
                        <td class="px-3 py-3 text-[#5a6a75]">{{ $row->tahun }}</td>
                        <td class="px-3 py-3 pr-5">
                            <div class="flex items-center gap-1 justify-end">
                                <button @click="openEdit({{ $row->id }}, @js($row->tahun), @js($row->nama_organisasi), @js($row->jabatan))"
                                    class="w-[28px] h-[28px] rounded-md border border-[#D0D5DD] text-[#5a6a75] hover:border-primary hover:text-primary hover:bg-[#E8F4F8] flex items-center justify-center transition-colors">
                                    <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M11 4H4a2 2 0 00-2 2v14a2 2 0 002 2h14a2 2 0 002-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 013 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
                                </button>
                                <button @click="openHapus({{ $row->id }})"
                                    class="w-[28px] h-[28px] rounded-md border border-[#D0D5DD] text-[#5a6a75] hover:border-[#c62828] hover:text-[#c62828] hover:bg-[#FCE8E6] flex items-center justify-center transition-colors">
                                    <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="3 6 5 6 21 6"/><path d="M19 6l-1 14a2 2 0 01-2 2H8a2 2 0 01-2-2L5 6"/></svg>
                                </button>
                            </div>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        @else
        <div class="bg-white rounded-xl border border-[#E5E8EC] px-5 py-8 text-center text-[12px] text-[#8a9ba8] mb-4">Belum ada data organisasi profesi.</div>
        @endif

        <div x-show="modal.open" x-cloak class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 p-4"
             x-transition:enter="transition ease-out duration-150" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
             x-transition:leave="transition ease-in duration-100" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0">
            <div @click.outside="modal.open = false" @keydown.escape.window="modal.open = false"
                 class="bg-white rounded-2xl shadow-xl w-full max-w-md p-6"
                 x-transition:enter="transition ease-out duration-150" x-transition:enter-start="opacity-0 scale-95" x-transition:enter-end="opacity-100 scale-100"
                 x-transition:leave="transition ease-in duration-100" x-transition:leave-start="opacity-100 scale-100" x-transition:leave-end="opacity-0 scale-95">
                <div class="text-[14px] font-semibold text-[#1a2a35] mb-4" x-text="modal.editId ? 'Edit Organisasi' : 'Tambah Organisasi'"></div>
                <div class="space-y-3">
                    <div>
                        <label class="block text-[11px] font-semibold text-[#5a6a75] uppercase tracking-[0.7px] mb-1">Nama Organisasi <span class="text-[#D2092F]">*</span></label>
                        <input x-model="modal.namaOrganisasi" type="text" placeholder="Nama organisasi profesi"
                            class="w-full h-[40px] px-3.5 text-[13px] text-[#1a2a35] bg-white border border-[#E0E5EA] rounded-xl outline-none focus:border-primary focus:ring-2 focus:ring-primary/10 placeholder:text-[#b0bec5]" />
                    </div>
                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block text-[11px] font-semibold text-[#5a6a75] uppercase tracking-[0.7px] mb-1">Jabatan</label>
                            <input x-model="modal.jabatan" type="text" placeholder="Anggota, Ketua, dll."
                                class="w-full h-[40px] px-3.5 text-[13px] text-[#1a2a35] bg-white border border-[#E0E5EA] rounded-xl outline-none focus:border-primary focus:ring-2 focus:ring-primary/10 placeholder:text-[#b0bec5]" />
                        </div>
                        <div>
                            <label class="block text-[11px] font-semibold text-[#5a6a75] uppercase tracking-[0.7px] mb-1">Tahun</label>
                            <input x-model="modal.tahun" type="text" placeholder="2021" maxlength="4"
                                class="w-full h-[40px] px-3.5 text-[13px] text-[#1a2a35] bg-white border border-[#E0E5EA] rounded-xl outline-none focus:border-primary focus:ring-2 focus:ring-primary/10 placeholder:text-[#b0bec5]" />
                        </div>
                    </div>
                </div>
                <div class="flex gap-3 mt-5">
                    <button @click="modal.open = false" class="flex-1 h-[40px] border border-[#D8DDE2] text-[#1a2a35] text-[13px] font-semibold rounded-xl hover:bg-[#F4F6F8] transition-colors">Batal</button>
                    <button @click="simpan()" class="flex-1 h-[40px] bg-primary hover:bg-[#005f78] text-white text-[13px] font-semibold rounded-xl transition-colors">Simpan</button>
                </div>
            </div>
        </div>
        <div x-show="hapusModal.open" x-cloak class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 p-4"
             x-transition:enter="transition ease-out duration-150" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
             x-transition:leave="transition ease-in duration-100" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0">
            <div @click.outside="hapusModal.open = false" class="bg-white rounded-2xl shadow-xl w-full max-w-sm p-6"
                 x-transition:enter="transition ease-out duration-150" x-transition:enter-start="opacity-0 scale-95" x-transition:enter-end="opacity-100 scale-100">
                <div class="flex items-center gap-3 mb-4">
                    <div class="w-10 h-10 rounded-full bg-[#FCE8E6] flex items-center justify-center shrink-0"><svg class="w-5 h-5 text-[#c62828]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="3 6 5 6 21 6"/><path d="M19 6l-1 14a2 2 0 01-2 2H8a2 2 0 01-2-2L5 6"/></svg></div>
                    <div><div class="text-[14px] font-semibold text-[#1a2a35]">Hapus Data?</div><div class="text-[12px] text-[#8a9ba8]">Data akan dihapus permanen.</div></div>
                </div>
                <div class="flex gap-3">
                    <button @click="hapusModal.open = false" class="flex-1 h-[40px] border border-[#D8DDE2] text-[#1a2a35] text-[13px] font-semibold rounded-xl hover:bg-[#F4F6F8] transition-colors">Batal</button>
                    <button @click="doHapus()" class="flex-1 h-[40px] bg-[#c62828] hover:bg-[#b71c1c] text-white text-[13px] font-semibold rounded-xl transition-colors">Ya, Hapus</button>
                </div>
            </div>
        </div>
    </div>

    {{-- ============ TAB: PASSWORD ============ --}}
    <div x-show="tab === 'password'" x-cloak>
        <div class="bg-white rounded-xl border border-[#E5E8EC] p-5 max-w-md">
            <div class="text-[13px] font-semibold text-[#1a2a35] mb-4">Ganti Password</div>
            <div class="space-y-4">
                <div>
                    <label class="block text-[11px] font-semibold text-[#5a6a75] uppercase tracking-[0.7px] mb-1.5">Password Baru <span class="text-[#D2092F]">*</span></label>
                    <div class="relative" x-data="{ show: false }">
                        <input wire:model="password" :type="show ? 'text' : 'password'" placeholder="Minimal 8 karakter"
                            class="w-full h-[40px] pl-3.5 pr-9 text-[13px] text-[#1a2a35] bg-white border border-[#E0E5EA] rounded-xl outline-none focus:border-primary focus:ring-2 focus:ring-primary/10 placeholder:text-[#b0bec5]" />
                        <button type="button" @click="show = !show" class="absolute right-3 top-1/2 -translate-y-1/2 text-[#b0bec5] hover:text-[#5a6a75] transition-colors">
                            <svg x-show="!show" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="11" width="18" height="11" rx="2"/><path d="M7 11V7a5 5 0 0110 0v4"/></svg>
                            <svg x-show="show" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" style="display:none"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
                        </button>
                    </div>
                    @error('password') <p class="mt-1 text-[11px] text-[#c62828]">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="block text-[11px] font-semibold text-[#5a6a75] uppercase tracking-[0.7px] mb-1.5">Konfirmasi Password <span class="text-[#D2092F]">*</span></label>
                    <div class="relative" x-data="{ show: false }">
                        <input wire:model="passwordConfirmation" :type="show ? 'text' : 'password'" placeholder="Ulangi password baru"
                            class="w-full h-[40px] pl-3.5 pr-9 text-[13px] text-[#1a2a35] bg-white border border-[#E0E5EA] rounded-xl outline-none focus:border-primary focus:ring-2 focus:ring-primary/10 placeholder:text-[#b0bec5]" />
                        <button type="button" @click="show = !show" class="absolute right-3 top-1/2 -translate-y-1/2 text-[#b0bec5] hover:text-[#5a6a75] transition-colors">
                            <svg x-show="!show" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="11" width="18" height="11" rx="2"/><path d="M7 11V7a5 5 0 0110 0v4"/></svg>
                            <svg x-show="show" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" style="display:none"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
                        </button>
                    </div>
                    @error('passwordConfirmation') <p class="mt-1 text-[11px] text-[#c62828]">{{ $message }}</p> @enderror
                </div>
            </div>
            <button wire:click="simpanPassword" wire:loading.attr="disabled"
                class="mt-5 h-[42px] px-6 bg-primary hover:bg-[#005f78] text-white text-[13px] font-semibold rounded-xl transition-colors disabled:opacity-60">
                <span wire:loading.remove wire:target="simpanPassword">Simpan Password</span>
                <span wire:loading wire:target="simpanPassword">Menyimpan...</span>
            </button>
        </div>
    </div>

    {{-- Pendidikan Tab: tambah/tambah button OUTSIDE x-data scope needs re-open --}}
    {{-- Note: pendidikan tambah button already in x-data scope above --}}
    <div x-show="tab === 'pendidikan'" class="mt-3" x-cloak>
        <button x-data @click="$dispatch('open-tambah-pendidikan')"
            class="flex items-center gap-1.5 h-[36px] px-4 bg-primary hover:bg-[#005f78] text-white text-[12px] font-semibold rounded-xl transition-colors"
            style="display:none">
        </button>
    </div>

</div>
