<?php

use App\Actions\Auth\RegisterPesertaAction;
use App\Livewire\Forms\RegisterForm;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;
use Livewire\WithFileUploads;

new #[Layout('layouts.guest')] class extends Component {
    use WithFileUploads;

    public RegisterForm $form;

    public $foto           = null;
    public $berkasCV       = null;
    public $berkasTranskrip    = null;
    public $berkasKeteranganMK = null;

    public function register(): void
    {
        $this->form->validate();

        $isDoPcr = $this->form->isDoPcr;

        $this->validate([
            'foto'               => 'nullable|image|mimes:jpg,jpeg,png|max:2048',
            'berkasCV'           => 'required|file|mimes:pdf,jpg,jpeg,png|max:10240',
            'berkasTranskrip'    => 'required|file|mimes:pdf,jpg,jpeg,png|max:10240',
            'berkasKeteranganMK' => $isDoPcr ? 'nullable|file|mimes:pdf,jpg,jpeg,png|max:10240' : 'required|file|mimes:pdf,jpg,jpeg,png|max:10240',
        ], [
            'berkasCV.required'           => 'CV wajib diunggah.',
            'berkasCV.mimes'              => 'Format CV harus PDF, JPG, atau PNG.',
            'berkasCV.max'                => 'Ukuran CV maksimal 10 MB.',
            'berkasTranskrip.required'    => 'Transkrip wajib diunggah.',
            'berkasTranskrip.mimes'       => 'Format transkrip harus PDF, JPG, atau PNG.',
            'berkasTranskrip.max'         => 'Ukuran transkrip maksimal 10 MB.',
            'berkasKeteranganMK.required' => 'Dokumen keterangan mata kuliah wajib diunggah.',
            'berkasKeteranganMK.mimes'    => 'Format dokumen harus PDF, JPG, atau PNG.',
            'berkasKeteranganMK.max'      => 'Ukuran dokumen maksimal 10 MB.',
        ]);

        $fotoPath = null;
        if ($this->foto) {
            $fotoPath = $this->foto->storeAs(
                'peserta/foto',
                uniqid('foto_', true) . '.' . $this->foto->getClientOriginalExtension(),
                'public'
            );
        }

        app(RegisterPesertaAction::class)->execute(
            nama: $this->form->nama,
            email: $this->form->email,
            password: $this->form->password,
            jenisKelamin: $this->form->jenisKelamin,
            tanggalLahir: $this->form->tanggalLahir,
            alamat: $this->form->alamat,
            kota: $this->form->kota,
            provinsi: $this->form->provinsi,
            kodePos: $this->form->kodePos,
            telepon: $this->form->telepon,
            foto: $fotoPath,
            isDoPcr: $isDoPcr,
            semester: $this->form->semester,
            berkasCV: $this->berkasCV,
            berkasTranskrip: $this->berkasTranskrip,
            berkasKeteranganMK: $isDoPcr ? null : $this->berkasKeteranganMK,
        );

        session()->flash('status', 'Pendaftaran berhasil! Akun Anda akan diaktifkan oleh admin. Silakan tunggu konfirmasi sebelum login.');

        $this->redirect(route('login'), navigate: true);
    }

    public function with(): array
    {
        return [
            'tahunAjaran'    => \App\Models\TahunAjaran::aktif()->first(),
            'semesterOptions' => \App\Enums\SemesterEnum::options(),
        ];
    }
}; ?>

{{-- h-screen + overflow-hidden agar panel kiri tidak ikut scroll --}}
<div class="h-screen flex overflow-hidden">

    {{-- ===================== PANEL KIRI (identik dengan login) ===================== --}}
    <div class="hidden lg:flex relative flex-col w-[460px] xl:w-[500px] shrink-0 overflow-hidden bg-primary px-12 py-10">

        {{-- Dekorasi lingkaran --}}
        <div class="absolute -bottom-20 -right-20 w-[340px] h-[340px] rounded-full bg-white/[0.04] pointer-events-none">
        </div>
        <div class="absolute -top-16 -left-16 w-[240px] h-[240px] rounded-full bg-white/[0.04] pointer-events-none">
        </div>

        {{-- Logo --}}
        <div class="relative z-10 flex items-center gap-3 mb-16">
            <div class="w-10 h-10 bg-white rounded-xl flex items-center justify-center shrink-0">
                <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="#004B5F" stroke-width="2.2"
                    stroke-linecap="round" stroke-linejoin="round">
                    <path d="M12 2L2 7l10 5 10-5-10-5z" />
                    <path d="M2 17l10 5 10-5" />
                    <path d="M2 12l10 5 10-5" />
                </svg>
            </div>
            <div>
                <div class="text-white font-semibold text-[15px] leading-none mb-0.5">Sistem RPL</div>
                <div class="text-[10px] text-white/55 uppercase tracking-[1px]">Politeknik Caltex Riau</div>
            </div>
        </div>

        {{-- Heading + deskripsi + steps --}}
        <div class="relative z-10 flex-1 flex flex-col justify-center">

            <h1 class="text-white font-bold text-[42px] xl:text-[48px] leading-[1.15] mb-5">
                Rekognisi<br>Pembelajaran<br>Lampau
            </h1>

            <p class="text-white/60 text-[14px] leading-[1.7] mb-10">
                Ubah pengalaman kerja dan portofolio Anda menjadi kredit akademik yang diakui secara resmi.
            </p>

            <div class="flex flex-col gap-5">
                @foreach ([['01', 'Daftar & Lengkapi Profil', 'Mulai perjalanan akademik Anda dengan registrasi data diri.'], ['02', 'Konsultasi Awal dengan Asesor', 'Validasi kualifikasi pengalaman Anda bersama ahli kami.'], ['03', 'Ajukan & Upload Dokumen', 'Unggah berkas portofolio untuk proses penilaian teknis.'], ['04', 'Terima SK Rekognisi', 'Dapatkan pengakuan kredit mata kuliah secara resmi.']] as $step)
                    <div class="flex items-start gap-4">
                        <div
                            class="w-8 h-8 rounded-full border border-white/25 flex items-center justify-center shrink-0 mt-0.5">
                            <span class="text-[11px] font-semibold text-white/75">{{ $step[0] }}</span>
                        </div>
                        <div>
                            <div class="text-white font-semibold text-[13px] mb-0.5">{{ $step[1] }}</div>
                            <div class="text-white/50 text-[12px] leading-[1.5]">{{ $step[2] }}</div>
                        </div>
                    </div>
                @endforeach
            </div>

        </div>

        {{-- Footer --}}
        <div class="relative z-10 text-[11px] text-white/30 uppercase tracking-[0.5px]">
            © 2026 Politeknik Caltex Riau
        </div>

    </div>

    {{-- ===================== PANEL KANAN — hanya ini yang scroll ===================== --}}
    <div class="flex-1 overflow-y-auto bg-[#F4F6F8]">
        <div class="flex flex-col items-center px-6 py-8 sm:px-10 min-h-full">

            {{-- Logo mobile --}}
            <div class="flex lg:hidden items-center gap-3 mb-6 self-start">
                <div class="w-8 h-8 bg-primary rounded-xl flex items-center justify-center shrink-0">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="2.2"
                        stroke-linecap="round" stroke-linejoin="round">
                        <path d="M12 2L2 7l10 5 10-5-10-5z" /><path d="M2 17l10 5 10-5" /><path d="M2 12l10 5 10-5" />
                    </svg>
                </div>
                <div>
                    <div class="text-primary font-semibold text-[14px] leading-none mb-0.5">Sistem RPL</div>
                    <div class="text-[10px] text-[#8a9ba8] uppercase tracking-[1px]">Politeknik Caltex Riau</div>
                </div>
            </div>

            <div class="w-full max-w-[580px]">

                <h2 class="text-[24px] sm:text-[28px] font-bold text-[#1a2a35] leading-tight mb-1">Daftar Akun Baru</h2>
                <p class="text-[13px] text-[#8a9ba8] leading-[1.6] mb-6">Buat akun peserta untuk memulai proses RPL.</p>

                <form wire:submit="register">

                    {{-- ===== Data Akun ===== --}}
                    <p class="text-[10px] font-semibold text-[#8a9ba8] uppercase tracking-[0.8px] mb-3">Data Akun</p>

                    <div class="grid grid-cols-2 gap-x-3 gap-y-3 mb-3">

                        {{-- Nama --}}
                        <div class="col-span-2">
                            <label class="block text-[11px] font-semibold text-[#5a6a75] uppercase tracking-[0.8px] mb-1">Nama Lengkap <span class="text-[#D2092F]">*</span></label>
                            <input wire:model="form.nama" type="text" placeholder="Nama sesuai KTP" required autofocus autocomplete="name"
                                class="w-full h-[40px] px-3.5 text-[13px] text-[#1a2a35] bg-white border border-[#E0E5EA] rounded-xl outline-none transition-all focus:border-primary focus:ring-2 focus:ring-primary/10 placeholder:text-[#b0bec5]" />
                            @error('form.nama') <p class="mt-1 text-[11px] text-[#c62828]">{{ $message }}</p> @enderror
                        </div>

                        {{-- Email --}}
                        <div class="col-span-2">
                            <label class="block text-[11px] font-semibold text-[#5a6a75] uppercase tracking-[0.8px] mb-1">Alamat Email <span class="text-[#D2092F]">*</span></label>
                            <div class="relative">
                                <input wire:model="form.email" type="email" placeholder="nama@email.com" required autocomplete="email"
                                    class="w-full h-[40px] pl-3.5 pr-9 text-[13px] text-[#1a2a35] bg-white border border-[#E0E5EA] rounded-xl outline-none transition-all focus:border-primary focus:ring-2 focus:ring-primary/10 placeholder:text-[#b0bec5]" />
                                <div class="absolute right-3 top-1/2 -translate-y-1/2 text-[#b0bec5] pointer-events-none">
                                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/><polyline points="22,6 12,13 2,6"/></svg>
                                </div>
                            </div>
                            @error('form.email') <p class="mt-1 text-[11px] text-[#c62828]">{{ $message }}</p> @enderror
                        </div>

                        {{-- Password --}}
                        <div>
                            <label class="block text-[11px] font-semibold text-[#5a6a75] uppercase tracking-[0.8px] mb-1">Kata Sandi <span class="text-[#D2092F]">*</span></label>
                            <div class="relative" x-data="{ show: false }">
                                <input wire:model="form.password" :type="show ? 'text' : 'password'" placeholder="Min. 8 karakter" required autocomplete="new-password"
                                    class="w-full h-[40px] pl-3.5 pr-9 text-[13px] text-[#1a2a35] bg-white border border-[#E0E5EA] rounded-xl outline-none transition-all focus:border-primary focus:ring-2 focus:ring-primary/10 placeholder:text-[#b0bec5]" />
                                <button type="button" @click="show = !show" class="absolute right-3 top-1/2 -translate-y-1/2 text-[#b0bec5] hover:text-[#5a6a75] transition-colors">
                                    <svg x-show="!show" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0110 0v4"/></svg>
                                    <svg x-show="show" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" style="display:none"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
                                </button>
                            </div>
                            @error('form.password') <p class="mt-1 text-[11px] text-[#c62828]">{{ $message }}</p> @enderror
                        </div>

                        {{-- Konfirmasi Password --}}
                        <div>
                            <label class="block text-[11px] font-semibold text-[#5a6a75] uppercase tracking-[0.8px] mb-1">Konfirmasi Sandi <span class="text-[#D2092F]">*</span></label>
                            <div class="relative" x-data="{ show: false }">
                                <input wire:model="form.password_confirmation" :type="show ? 'text' : 'password'" placeholder="Ulangi kata sandi" required autocomplete="new-password"
                                    class="w-full h-[40px] pl-3.5 pr-9 text-[13px] text-[#1a2a35] bg-white border border-[#E0E5EA] rounded-xl outline-none transition-all focus:border-primary focus:ring-2 focus:ring-primary/10 placeholder:text-[#b0bec5]" />
                                <button type="button" @click="show = !show" class="absolute right-3 top-1/2 -translate-y-1/2 text-[#b0bec5] hover:text-[#5a6a75] transition-colors">
                                    <svg x-show="!show" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0110 0v4"/></svg>
                                    <svg x-show="show" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" style="display:none"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
                                </button>
                            </div>
                            @error('form.password_confirmation') <p class="mt-1 text-[11px] text-[#c62828]">{{ $message }}</p> @enderror
                        </div>

                    </div>

                    {{-- ===== Data Pribadi ===== --}}
                    <p class="text-[10px] font-semibold text-[#8a9ba8] uppercase tracking-[0.8px] mb-3 mt-5 pt-4 border-t border-[#EAECEF]">Data Pribadi</p>

                    <div class="grid grid-cols-2 gap-x-3 gap-y-3 mb-3">

                        {{-- Jenis Kelamin --}}
                        <div x-data="{
                                open: false,
                                val: @entangle('form.jenisKelamin').live,
                                opts: [{v:'L',l:'Laki-laki'},{v:'P',l:'Perempuan'}],
                                get label() { return this.opts.find(o=>o.v===this.val)?.l ?? 'Pilih jenis kelamin'; }
                            }">
                            <label class="block text-[11px] font-semibold text-[#5a6a75] uppercase tracking-[0.8px] mb-1">Jenis Kelamin <span class="text-[#D2092F]">*</span></label>
                            <div class="relative">
                                <button type="button" @click="open=!open"
                                    :class="open ? 'border-primary ring-2 ring-primary/10' : 'border-[#E0E5EA] hover:border-[#C5CDD5]'"
                                    class="w-full h-[40px] px-3.5 flex items-center justify-between bg-white border rounded-xl text-[13px] transition-all">
                                    <span :class="val ? 'text-[#1a2a35]' : 'text-[#b0bec5]'" x-text="label"></span>
                                    <svg class="w-4 h-4 text-[#8a9ba8] transition-transform duration-150 shrink-0" :class="open && 'rotate-180'" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="6 9 12 15 18 9"/></svg>
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
                            @error('form.jenisKelamin') <p class="mt-1 text-[11px] text-[#c62828]">{{ $message }}</p> @enderror
                        </div>

                        {{-- Tanggal Lahir --}}
                        <div x-data="{ val: @entangle('form.tanggalLahir').live }">
                            <label class="block text-[11px] font-semibold text-[#5a6a75] uppercase tracking-[0.8px] mb-1">Tanggal Lahir <span class="text-[#D2092F]">*</span></label>
                            <x-form.date-picker x-model="val" :enable-time="false" placeholder="Pilih tanggal lahir" class="w-full" />
                            @error('form.tanggalLahir') <p class="mt-1 text-[11px] text-[#c62828]">{{ $message }}</p> @enderror
                        </div>

                        {{-- No. HP/WA --}}
                        <div>
                            <label class="block text-[11px] font-semibold text-[#5a6a75] uppercase tracking-[0.8px] mb-1">No. HP / WA <span class="text-[#D2092F]">*</span></label>
                            <input wire:model="form.telepon" type="text" placeholder="08xxxxxxxxxx" maxlength="20" required
                                class="w-full h-[40px] px-3.5 text-[13px] text-[#1a2a35] bg-white border border-[#E0E5EA] rounded-xl outline-none transition-all focus:border-primary focus:ring-2 focus:ring-primary/10 placeholder:text-[#b0bec5]" />
                            @error('form.telepon') <p class="mt-1 text-[11px] text-[#c62828]">{{ $message }}</p> @enderror
                        </div>

                        {{-- Alamat --}}
                        <div class="col-span-2">
                            <label class="block text-[11px] font-semibold text-[#5a6a75] uppercase tracking-[0.8px] mb-1">Alamat <span class="text-[#D2092F]">*</span></label>
                            <textarea wire:model="form.alamat" rows="1" placeholder="Alamat lengkap sesuai KTP" required
                                class="w-full px-3.5 py-2 text-[13px] text-[#1a2a35] bg-white border border-[#E0E5EA] rounded-xl outline-none transition-all focus:border-primary focus:ring-2 focus:ring-primary/10 placeholder:text-[#b0bec5] resize-none"></textarea>
                            @error('form.alamat') <p class="mt-1 text-[11px] text-[#c62828]">{{ $message }}</p> @enderror
                        </div>

                        {{-- Provinsi, Kota, Kode Pos (satu baris) via wilayah.id API --}}
                        <div class="col-span-2" x-data="{
                            provOpen: false, kotaOpen: false,
                            provinces: [], regencies: [],
                            loadingProv: true, loadingKota: false,
                            provName: @entangle('form.provinsi').live,
                            kotaName: @entangle('form.kota').live,
                            provCode: null,
                            provSearch: '', kotaSearch: '',
                            get filteredProv() { return this.provinces.filter(p => p.name.toLowerCase().includes(this.provSearch.toLowerCase())); },
                            get filteredKota() { return this.regencies.filter(r => r.name.toLowerCase().includes(this.kotaSearch.toLowerCase())); },
                            async init() {
                                const r = await fetch('https://wilayah.id/api/provinces.json');
                                const d = await r.json();
                                this.provinces = d.data ?? [];
                                this.loadingProv = false;
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
                                    <label class="block text-[11px] font-semibold text-[#5a6a75] uppercase tracking-[0.8px] mb-1">Provinsi <span class="text-[#D2092F]">*</span></label>
                                    <div class="relative">
                                        <button type="button" @click="provOpen=!provOpen; if(provOpen) $nextTick(()=>$refs.provSearch.focus())"
                                            :class="provOpen ? 'border-primary ring-2 ring-primary/10' : 'border-[#E0E5EA] hover:border-[#C5CDD5]'"
                                            class="w-full h-[40px] px-3.5 flex items-center justify-between bg-white border rounded-xl text-[13px] transition-all">
                                            <span :class="provName ? 'text-[#1a2a35]' : 'text-[#b0bec5]'" x-text="provName || 'Pilih provinsi'" class="truncate mr-1"></span>
                                            <svg class="w-4 h-4 text-[#8a9ba8] transition-transform duration-150 shrink-0" :class="provOpen && 'rotate-180'" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="6 9 12 15 18 9"/></svg>
                                        </button>
                                        <div x-show="provOpen" @click.outside="provOpen=false" x-cloak
                                            x-transition:enter="transition ease-out duration-100" x-transition:enter-start="opacity-0 -translate-y-1" x-transition:enter-end="opacity-100 translate-y-0"
                                            class="absolute z-30 mt-1 w-full bg-white border border-[#E0E5EA] rounded-xl shadow-lg overflow-hidden">
                                            <div class="p-2 border-b border-[#F0F2F5]">
                                                <input x-ref="provSearch" x-model="provSearch" type="text" placeholder="Cari provinsi..." class="w-full h-[32px] px-2.5 text-[12px] bg-[#F4F6F8] border border-transparent rounded-lg outline-none focus:border-primary transition-all placeholder:text-[#b0bec5]" />
                                            </div>
                                            <div class="max-h-[180px] overflow-y-auto">
                                                <template x-if="loadingProv">
                                                    <div class="py-3 text-center text-[12px] text-[#8a9ba8]">Memuat...</div>
                                                </template>
                                                <template x-if="!loadingProv && filteredProv.length === 0">
                                                    <div class="py-3 text-center text-[12px] text-[#8a9ba8]">Tidak ditemukan</div>
                                                </template>
                                                <template x-for="p in filteredProv" :key="p.code">
                                                    <button type="button" @click="selectProv(p.code, p.name)"
                                                        :class="provName===p.name ? 'bg-[#E8F4F8] text-primary font-semibold' : 'hover:bg-[#F4F6F8] text-[#1a2a35]'"
                                                        class="w-full px-3.5 py-2 text-left text-[12px] transition-colors" x-text="p.name"></button>
                                                </template>
                                            </div>
                                        </div>
                                    </div>
                                    @error('form.provinsi') <p class="mt-1 text-[11px] text-[#c62828]">{{ $message }}</p> @enderror
                                </div>

                                {{-- Kota / Kabupaten --}}
                                <div>
                                    <label class="block text-[11px] font-semibold text-[#5a6a75] uppercase tracking-[0.8px] mb-1">Kota / Kabupaten <span class="text-[#D2092F]">*</span></label>
                                    <div class="relative">
                                        <button type="button"
                                            @click="if(provCode) { kotaOpen=!kotaOpen; if(kotaOpen) $nextTick(()=>$refs.kotaSearch.focus()); }"
                                            :disabled="!provCode"
                                            :class="kotaOpen ? 'border-primary ring-2 ring-primary/10' : (provCode ? 'border-[#E0E5EA] hover:border-[#C5CDD5]' : 'border-[#E0E5EA] opacity-50 cursor-not-allowed')"
                                            class="w-full h-[40px] px-3.5 flex items-center justify-between bg-white border rounded-xl text-[13px] transition-all">
                                            <span :class="kotaName ? 'text-[#1a2a35]' : 'text-[#b0bec5]'" x-text="loadingKota ? 'Memuat...' : (kotaName || (provCode ? 'Pilih kota/kab.' : 'Pilih provinsi dulu'))" class="truncate mr-1"></span>
                                            <svg class="w-4 h-4 text-[#8a9ba8] transition-transform duration-150 shrink-0" :class="kotaOpen && 'rotate-180'" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="6 9 12 15 18 9"/></svg>
                                        </button>
                                        <div x-show="kotaOpen" @click.outside="kotaOpen=false" x-cloak
                                            x-transition:enter="transition ease-out duration-100" x-transition:enter-start="opacity-0 -translate-y-1" x-transition:enter-end="opacity-100 translate-y-0"
                                            class="absolute z-30 mt-1 w-full bg-white border border-[#E0E5EA] rounded-xl shadow-lg overflow-hidden">
                                            <div class="p-2 border-b border-[#F0F2F5]">
                                                <input x-ref="kotaSearch" x-model="kotaSearch" type="text" placeholder="Cari kota/kabupaten..." class="w-full h-[32px] px-2.5 text-[12px] bg-[#F4F6F8] border border-transparent rounded-lg outline-none focus:border-primary transition-all placeholder:text-[#b0bec5]" />
                                            </div>
                                            <div class="max-h-[180px] overflow-y-auto">
                                                <template x-if="filteredKota.length === 0">
                                                    <div class="py-3 text-center text-[12px] text-[#8a9ba8]">Tidak ditemukan</div>
                                                </template>
                                                <template x-for="k in filteredKota" :key="k.code">
                                                    <button type="button" @click="selectKota(k.name)"
                                                        :class="kotaName===k.name ? 'bg-[#E8F4F8] text-primary font-semibold' : 'hover:bg-[#F4F6F8] text-[#1a2a35]'"
                                                        class="w-full px-3.5 py-2 text-left text-[12px] transition-colors" x-text="k.name"></button>
                                                </template>
                                            </div>
                                        </div>
                                    </div>
                                    @error('form.kota') <p class="mt-1 text-[11px] text-[#c62828]">{{ $message }}</p> @enderror
                                </div>

                                {{-- Kode Pos --}}
                                <div>
                                    <label class="block text-[11px] font-semibold text-[#5a6a75] uppercase tracking-[0.8px] mb-1">
                                        Kode Pos <span class="text-[11px] text-[#b0bec5] normal-case tracking-normal font-normal">(opsional)</span>
                                    </label>
                                    <input wire:model="form.kodePos" type="text" placeholder="Kode pos" maxlength="10"
                                        class="w-full h-[40px] px-3.5 text-[13px] text-[#1a2a35] bg-white border border-[#E0E5EA] rounded-xl outline-none transition-all focus:border-primary focus:ring-2 focus:ring-primary/10 placeholder:text-[#b0bec5]" />
                                    @error('form.kodePos') <p class="mt-1 text-[11px] text-[#c62828]">{{ $message }}</p> @enderror
                                </div>

                            </div>
                        </div>

                    </div>

                    {{-- ===== Pas Foto ===== --}}
                    <p class="text-[10px] font-semibold text-[#8a9ba8] uppercase tracking-[0.8px] mb-3 mt-5 pt-4 border-t border-[#EAECEF]">Pas Foto</p>

                    <div x-data="{ preview: null }" class="flex items-start gap-4 mb-4">
                        {{-- Preview 3:4 --}}
                        <div class="shrink-0 w-[48px] h-[64px] rounded-lg border-2 border-dashed border-[#D0D5DD] bg-[#F4F6F8] overflow-hidden flex items-center justify-center">
                            <template x-if="preview">
                                <img :src="preview" class="w-full h-full object-cover" />
                            </template>
                            <template x-if="!preview">
                                <svg class="w-4 h-4 text-[#b0bec5]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="18" height="18" rx="2"/><circle cx="8.5" cy="8.5" r="1.5"/><polyline points="21 15 16 10 5 21"/></svg>
                            </template>
                        </div>
                        <div class="flex-1">
                            <label class="flex items-center gap-3 h-[64px] px-4 border-2 border-dashed border-[#D0D5DD] rounded-xl bg-white hover:border-primary hover:bg-[#F0F7FA] transition-all cursor-pointer group">
                                <input type="file" wire:model="foto" accept="image/jpg,image/jpeg,image/png" class="hidden"
                                    @change="const f=$event.target.files[0]; if(f){const r=new FileReader();r.onload=e=>preview=e.target.result;r.readAsDataURL(f);}" />
                                <svg class="w-5 h-5 text-[#8a9ba8] group-hover:text-primary transition-colors shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="16 16 12 12 8 16"/><line x1="12" y1="12" x2="12" y2="21"/><path d="M20.39 18.39A5 5 0 0018 9h-1.26A8 8 0 103 16.3"/></svg>
                                <div>
                                    <div class="text-[12px] font-medium text-[#5a6a75] group-hover:text-primary transition-colors">Latar merah, ukuran 3×4</div>
                                    <div class="text-[11px] text-[#b0bec5] mt-0.5">JPG / PNG, maks 2 MB <span class="text-[10px] text-[#b0bec5] normal-case font-normal">(opsional)</span></div>
                                </div>
                            </label>
                            <div wire:loading wire:target="foto" class="mt-1 text-[11px] text-[#8a9ba8]">Mengupload...</div>
                            @error('foto') <p class="mt-1 text-[11px] text-[#c62828]">{{ $message }}</p> @enderror
                        </div>
                    </div>

                    {{-- ===== Alumni PCR ===== --}}
                    <p class="text-[10px] font-semibold text-[#8a9ba8] uppercase tracking-[0.8px] mb-3 mt-5 pt-4 border-t border-[#EAECEF]">Status Alumni</p>

                    <label class="flex items-center gap-3 px-4 py-3 bg-white border border-[#E0E5EA] rounded-xl cursor-pointer hover:border-primary hover:bg-[#F0F7FA] transition-all mb-1"
                           x-data>
                        <input type="checkbox" wire:model="form.isDoPcr" class="w-4 h-4 rounded accent-primary" />
                        <div>
                            <div class="text-[13px] font-semibold text-[#1a2a35]">Saya alumni Politeknik Caltex Riau</div>
                            <div class="text-[11px] text-[#8a9ba8] mt-0.5">Alumni PCR tidak perlu mengunggah transkrip nilai dan dokumen keterangan mata kuliah.</div>
                        </div>
                    </label>

                    {{-- ===== Periode Pengajuan ===== --}}
                    <p class="text-[10px] font-semibold text-[#8a9ba8] uppercase tracking-[0.8px] mb-3 mt-5 pt-4 border-t border-[#EAECEF]">Periode Pengajuan</p>

                    <div class="grid grid-cols-2 gap-x-3 gap-y-3 mb-3">
                        <div>
                            <label class="block text-[11px] font-semibold text-[#5a6a75] uppercase tracking-[0.8px] mb-1">Tahun Ajaran</label>
                            <div class="w-full h-[40px] px-3.5 text-[13px] text-[#8a9ba8] bg-[#F4F6F8] border border-[#E0E5EA] rounded-xl flex items-center">
                                {{ $tahunAjaran?->nama ?? 'Belum ada tahun ajaran aktif' }}
                            </div>
                        </div>
                        <div>
                            <label class="block text-[11px] font-semibold text-[#5a6a75] uppercase tracking-[0.8px] mb-1">Semester <span class="text-[#D2092F]">*</span></label>
                            <x-form.select wire:model="form.semester" placeholder="— Pilih semester —" :options="$semesterOptions" />
                            @error('form.semester') <p class="mt-1 text-[11px] text-[#c62828]">{{ $message }}</p> @enderror
                        </div>
                    </div>

                    {{-- ===== Berkas Wajib ===== --}}
                    <p class="text-[10px] font-semibold text-[#8a9ba8] uppercase tracking-[0.8px] mb-3 mt-5 pt-4 border-t border-[#EAECEF]">Berkas Pendukung</p>

                    <div class="space-y-3 mb-3">

                        {{-- CV --}}
                        <div>
                            <label class="block text-[11px] font-semibold text-[#5a6a75] uppercase tracking-[0.8px] mb-1">
                                CV / Daftar Riwayat Hidup <span class="text-[#D2092F]">*</span>
                                <span class="normal-case font-normal text-[#b0bec5]">PDF/JPG/PNG, maks 10 MB</span>
                            </label>
                            <label class="flex items-center gap-3 h-[48px] px-4 border-2 border-dashed border-[#D0D5DD] rounded-xl bg-[#FAFBFC] hover:border-primary hover:bg-[#F0F7FA] transition-all cursor-pointer group">
                                <input type="file" wire:model="berkasCV" accept=".pdf,.jpg,.jpeg,.png" class="hidden" />
                                <svg class="w-4 h-4 text-[#8a9ba8] group-hover:text-primary transition-colors shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="16 16 12 12 8 16"/><line x1="12" y1="12" x2="12" y2="21"/><path d="M20.39 18.39A5 5 0 0018 9h-1.26A8 8 0 103 16.3"/></svg>
                                <span class="text-[12px] text-[#5a6a75] group-hover:text-primary transition-colors truncate">
                                    @if ($berkasCV) {{ $berkasCV->getClientOriginalName() }} @else Klik untuk pilih file CV @endif
                                </span>
                            </label>
                            <div wire:loading wire:target="berkasCV" class="mt-1 text-[11px] text-[#8a9ba8]">Mengupload...</div>
                            @error('berkasCV') <p class="mt-1 text-[11px] text-[#c62828]">{{ $message }}</p> @enderror
                        </div>

                        {{-- Transkrip (wajib untuk semua, termasuk alumni PCR) --}}
                        <div>
                            <label class="block text-[11px] font-semibold text-[#5a6a75] uppercase tracking-[0.8px] mb-1">
                                Transkrip Nilai <span class="text-[#D2092F]">*</span>
                                <span class="normal-case font-normal text-[#b0bec5]">PDF/JPG/PNG, maks 10 MB</span>
                            </label>
                            <label class="flex items-center gap-3 h-[48px] px-4 border-2 border-dashed border-[#D0D5DD] rounded-xl bg-[#FAFBFC] hover:border-primary hover:bg-[#F0F7FA] transition-all cursor-pointer group">
                                <input type="file" wire:model="berkasTranskrip" accept=".pdf,.jpg,.jpeg,.png" class="hidden" />
                                <svg class="w-4 h-4 text-[#8a9ba8] group-hover:text-primary transition-colors shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="16 16 12 12 8 16"/><line x1="12" y1="12" x2="12" y2="21"/><path d="M20.39 18.39A5 5 0 0018 9h-1.26A8 8 0 103 16.3"/></svg>
                                <span class="text-[12px] text-[#5a6a75] group-hover:text-primary transition-colors truncate">
                                    @if ($berkasTranskrip) {{ $berkasTranskrip->getClientOriginalName() }} @else Klik untuk pilih file transkrip @endif
                                </span>
                            </label>
                            <div wire:loading wire:target="berkasTranskrip" class="mt-1 text-[11px] text-[#8a9ba8]">Mengupload...</div>
                            @error('berkasTranskrip') <p class="mt-1 text-[11px] text-[#c62828]">{{ $message }}</p> @enderror
                        </div>

                        {{-- Keterangan MK (skip jika alumni PCR) --}}
                        <div x-data x-show="!$wire.form.isDoPcr">
                            <label class="block text-[11px] font-semibold text-[#5a6a75] uppercase tracking-[0.8px] mb-1">
                                Dokumen Keterangan Mata Kuliah <span class="text-[#D2092F]">*</span>
                                <span class="normal-case font-normal text-[#b0bec5]">PDF/JPG/PNG, maks 10 MB</span>
                            </label>
                            <label class="flex items-center gap-3 h-[48px] px-4 border-2 border-dashed border-[#D0D5DD] rounded-xl bg-[#FAFBFC] hover:border-primary hover:bg-[#F0F7FA] transition-all cursor-pointer group">
                                <input type="file" wire:model="berkasKeteranganMK" accept=".pdf,.jpg,.jpeg,.png" class="hidden" />
                                <svg class="w-4 h-4 text-[#8a9ba8] group-hover:text-primary transition-colors shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="16 16 12 12 8 16"/><line x1="12" y1="12" x2="12" y2="21"/><path d="M20.39 18.39A5 5 0 0018 9h-1.26A8 8 0 103 16.3"/></svg>
                                <span class="text-[12px] text-[#5a6a75] group-hover:text-primary transition-colors truncate">
                                    @if ($berkasKeteranganMK) {{ $berkasKeteranganMK->getClientOriginalName() }} @else Klik untuk pilih file keterangan MK @endif
                                </span>
                            </label>
                            <div wire:loading wire:target="berkasKeteranganMK" class="mt-1 text-[11px] text-[#8a9ba8]">Mengupload...</div>
                            @error('berkasKeteranganMK') <p class="mt-1 text-[11px] text-[#c62828]">{{ $message }}</p> @enderror
                        </div>

                    </div>

                    {{-- ===== Pernyataan ===== --}}
                    <p class="text-[10px] font-semibold text-[#8a9ba8] uppercase tracking-[0.8px] mb-3 mt-5 pt-4 border-t border-[#EAECEF]">Pernyataan</p>

                    <div class="border border-[#E0E5EA] rounded-xl overflow-hidden mb-4">
                        <div class="max-h-[140px] overflow-y-auto px-4 py-3 text-[12px] text-[#5a6a75] leading-[1.7] bg-[#FAFBFC]">
                            <p class="font-semibold text-[#1a2a35] mb-2">Pernyataan Peserta RPL Politeknik Caltex Riau</p>
                            <p>Dengan mendaftarkan diri pada program Rekognisi Pembelajaran Lampau (RPL) ini, saya menyatakan bahwa:</p>
                            <ol class="list-decimal ml-5 mt-2 space-y-1">
                                <li>Semua informasi yang saya tuliskan adalah sepenuhnya benar dan saya bertanggungjawab atas seluruh data dalam formulir ini.</li>
                                <li>Saya memberikan ijin kepada pihak pengelola program RPL, untuk melakukan pemeriksaan kebenaran informasi yang saya berikan dalam formulir aplikasi ini kepada seluruh pihak yang terkait dengan jenjang akademik sebelumnya dan kepada perusahaan tempat saya bekerja sebelumnya dan atau saat ini saya bekerja.</li>
                                <li>Saya bersedia melengkapi berkas yang dibutuhkan untuk pelaksanaan proses credit transfer dan atau asesmen pengalaman kerja.</li>
                                <li>Saya akan mengikuti proses asesmen sesuai dengan kesepakatan waktu yang ditetapkan dan saya akan melunasi biaya pendaftaran setelah pengisian aplikasi ini selesai.</li>
                                <li>Saya akan mentaati seluruh hal yang tercantum dalam peraturan akademik dan hal-hal terkait administrasi selama saya mengikuti perkuliahan di PCR.</li>
                            </ol>
                        </div>
                        <div class="px-4 py-3 border-t border-[#E0E5EA] bg-white">
                            <label class="flex items-center gap-2.5 cursor-pointer">
                                <input type="checkbox" wire:model="form.setuju" class="w-4 h-4 rounded accent-primary" />
                                <span class="text-[12px] text-[#1a2a35]">Saya telah membaca dan menyetujui pernyataan di atas</span>
                            </label>
                            @error('form.setuju') <p class="mt-1 text-[11px] text-[#c62828]">{{ $message }}</p> @enderror
                        </div>
                    </div>

                    {{-- Submit --}}
                    <button type="submit"
                        class="w-full h-[48px] bg-primary hover:bg-[#005f78] text-white font-semibold text-[14px] rounded-xl tracking-wide transition-colors disabled:opacity-70 disabled:cursor-not-allowed"
                        wire:loading.attr="disabled">
                        <span wire:loading.remove>Daftar Sekarang &nbsp;→</span>
                        <span wire:loading>Memproses...</span>
                    </button>

                </form>

                <p class="text-center text-[13px] text-[#8a9ba8] mt-4">
                    Sudah punya akun?
                    <a href="{{ route('login') }}" wire:navigate class="font-semibold text-primary hover:text-[#005f78] transition-colors no-underline">Masuk</a>
                </p>

            </div>
        </div>
    </div>

</div>
