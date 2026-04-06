<?php

use App\Livewire\Forms\LoginForm;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;

new #[Layout('layouts.guest')] class extends Component {
    public LoginForm $form;

    public function login(): void
    {
        $this->validate();
        $this->form->authenticate();
        Session::regenerate();

        $this->redirect(Auth::user()->role->dashboardRoute(), navigate: true);
    }
}; ?>

<div class="min-h-screen flex">

    {{-- ===================== PANEL KIRI (hidden mobile, tampil lg+) ===================== --}}
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

    {{-- ===================== PANEL KANAN ===================== --}}
    <div class="flex-1 flex flex-col items-center justify-center bg-[#F4F6F8] px-6 py-12 sm:px-10">

        {{-- Logo mobile (tampil hanya di bawah lg) --}}
        <div class="flex lg:hidden items-center gap-3 mb-10">
            <div class="w-9 h-9 bg-primary rounded-xl flex items-center justify-center shrink-0">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="2.2"
                    stroke-linecap="round" stroke-linejoin="round">
                    <path d="M12 2L2 7l10 5 10-5-10-5z" />
                    <path d="M2 17l10 5 10-5" />
                    <path d="M2 12l10 5 10-5" />
                </svg>
            </div>
            <div>
                <div class="text-primary font-semibold text-[15px] leading-none mb-0.5">Sistem RPL</div>
                <div class="text-[10px] text-[#8a9ba8] uppercase tracking-[1px]">Politeknik Caltex Riau</div>
            </div>
        </div>

        {{-- Form card --}}
        <div class="w-full max-w-[420px]">

            <h2 class="text-[28px] sm:text-[32px] font-bold text-[#1a2a35] leading-tight mb-2">
                Masuk ke Akun Anda
            </h2>
            <p class="text-[13px] text-[#8a9ba8] leading-[1.6] mb-8">
                Silakan gunakan kredensial terdaftar untuk melanjutkan proses RPL.
            </p>

            {{-- Session status --}}
            @if (session('status'))
                <div class="mb-5 px-4 py-3 text-[12px] text-[#3d5260] bg-[#F0F7FA] border border-[#C5DDE5] rounded-xl">
                    {{ session('status') }}
                </div>
            @endif

            <form wire:submit="login" class="space-y-5">

                {{-- Email --}}
                <div>
                    <label class="block text-[11px] font-semibold text-[#5a6a75] uppercase tracking-[0.8px] mb-2">
                        Alamat Email
                    </label>
                    <div class="relative">
                        <input wire:model="form.email" type="email" placeholder="nama@email.com"
                            autocomplete="username" required autofocus
                            class="w-full h-[50px] pl-4 pr-12 text-[13px] text-[#1a2a35] bg-white border border-[#E0E5EA] rounded-xl outline-none transition-all
                                   focus:border-primary focus:ring-2 focus:ring-primary/10 placeholder:text-[#b0bec5]" />
                        <div class="absolute right-4 top-1/2 -translate-y-1/2 text-[#b0bec5] pointer-events-none">
                            <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z" />
                                <polyline points="22,6 12,13 2,6" />
                            </svg>
                        </div>
                    </div>
                    @error('form.email')
                        <p class="mt-1.5 text-[11px] text-[#c62828]">{{ $message }}</p>
                    @enderror
                </div>

                {{-- Password --}}
                <div>
                    <div class="flex items-center justify-between mb-2">
                        <label class="text-[11px] font-semibold text-[#5a6a75] uppercase tracking-[0.8px]">
                            Kata Sandi
                        </label>
                        @if (Route::has('password.request'))
                            <a href="{{ route('password.request') }}" wire:navigate
                                class="text-[12px] font-medium text-primary hover:text-primary-600 transition-colors no-underline">
                                Lupa kata sandi?
                            </a>
                        @endif
                    </div>
                    <div class="relative" x-data="{ show: false }">
                        <input wire:model="form.password" :type="show ? 'text' : 'password'" placeholder="••••••••••"
                            autocomplete="current-password" required
                            class="w-full h-[50px] pl-4 pr-12 text-[13px] text-[#1a2a35] bg-white border border-[#E0E5EA] rounded-xl outline-none transition-all
                                   focus:border-primary focus:ring-2 focus:ring-primary/10 placeholder:text-[#b0bec5]" />
                        <button type="button" @click="show = !show"
                            class="absolute right-4 top-1/2 -translate-y-1/2 text-[#b0bec5] hover:text-[#5a6a75] transition-colors">
                            {{-- Lock icon (password hidden) --}}
                            <svg x-show="!show" width="17" height="17" viewBox="0 0 24 24" fill="none"
                                stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                                <rect x="3" y="11" width="18" height="11" rx="2" ry="2" />
                                <path d="M7 11V7a5 5 0 0110 0v4" />
                            </svg>
                            {{-- Eye icon (password shown) --}}
                            <svg x-show="show" width="17" height="17" viewBox="0 0 24 24" fill="none"
                                stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"
                                style="display:none">
                                <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z" />
                                <circle cx="12" cy="12" r="3" />
                            </svg>
                        </button>
                    </div>
                    @error('form.password')
                        <p class="mt-1.5 text-[11px] text-[#c62828]">{{ $message }}</p>
                    @enderror
                </div>

                {{-- Ingat saya --}}
                <div class="flex items-center gap-2.5">
                    <input wire:model="form.remember" type="checkbox" id="remember"
                        class="w-4 h-4 rounded border-[#D0D5DD] accent-primary cursor-pointer" />
                    <label for="remember" class="text-[13px] text-[#5a6a75] cursor-pointer select-none">
                        Ingat saya di perangkat ini
                    </label>
                </div>

                {{-- Tombol Masuk --}}
                <button type="submit"
                    class="w-full h-[52px] bg-primary hover:bg-[#005f78] active:bg-primary-600 text-white font-semibold text-[15px] rounded-xl tracking-wide transition-colors disabled:opacity-70 disabled:cursor-not-allowed"
                    wire:loading.attr="disabled">
                    <span wire:loading.remove>Masuk &nbsp;→</span>
                    <span wire:loading>Memproses...</span>
                </button>

            </form>

            {{-- Divider ATAU --}}
            <div class="flex items-center gap-4 my-6">
                <div class="flex-1 h-px bg-[#E0E5EA]"></div>
                <span class="text-[11px] font-semibold text-[#b0bec5] uppercase tracking-[1px]">Atau</span>
                <div class="flex-1 h-px bg-[#E0E5EA]"></div>
            </div>

            {{-- Tombol Daftar --}}
            @if (Route::has('register'))
                <a href="{{ route('register') }}" wire:navigate
                    class="flex items-center justify-center gap-2.5 w-full h-[52px] bg-white hover:bg-[#F0F7FA] text-[#1a2a35] font-semibold text-[14px] border border-[#D8DDE2] hover:border-primary rounded-xl transition-all no-underline">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                        stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M16 21v-2a4 4 0 00-4-4H5a4 4 0 00-4 4v2" />
                        <circle cx="8.5" cy="7" r="4" />
                        <line x1="20" y1="8" x2="20" y2="14" />
                        <line x1="23" y1="11" x2="17" y2="11" />
                    </svg>
                    Daftar
                </a>
            @endif

            {{-- Helpdesk --}}
            <p class="text-center text-[11px] text-[#b0bec5] leading-[1.7] mt-7">
                Butuh bantuan akses? Hubungi help@pcr.ac.id <br>
                atau kunjungi .....
            </p>

        </div>
    </div>

</div>
