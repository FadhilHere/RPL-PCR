<?php

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;

new #[Layout('components.layouts.peserta')] class extends Component {

    public string $password             = '';
    public string $password_confirmation = '';

    public function simpan(): void
    {
        try {
            $this->validate([
                'password'              => ['required', 'string', Password::min(8), 'confirmed'],
                'password_confirmation' => ['required'],
            ], [
                'password.min'       => 'Password minimal 8 karakter.',
                'password.confirmed' => 'Konfirmasi password tidak cocok.',
                'password.required'  => 'Password wajib diisi.',
            ]);
        } catch (ValidationException $e) {
            $this->reset('password', 'password_confirmation');
            throw $e;
        }

        $user = Auth::user();

        // Pastikan tidak bisa pakai password sama dengan nama
        abort_if(Hash::check($user->nama, Hash::make($this->password)), 422,
            'Password tidak boleh sama dengan nama Anda.');

        $user->update(['password' => Hash::make($this->password)]);

        $this->reset('password', 'password_confirmation');

        $this->redirect(route('peserta.lengkapi-profil'), navigate: true);
    }
}; ?>

<x-slot:title>Ganti Password</x-slot:title>

<div class="max-w-md mx-auto">

    {{-- Banner info --}}
    <div class="bg-[#FFF8E1] border border-[#FFE082] rounded-xl px-4 py-3.5 mb-6 flex gap-3">
        <svg class="w-4 h-4 text-[#b45309] shrink-0 mt-0.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/>
        </svg>
        <div>
            <p class="text-[12px] font-semibold text-[#b45309] mb-0.5">Ganti Password Sebelum Melanjutkan</p>
            <p class="text-[12px] text-[#1a2a35] leading-[1.6]">
                Akun Anda masih menggunakan password default. Ganti password terlebih dahulu untuk keamanan akun Anda.
            </p>
        </div>
    </div>

    {{-- Form card --}}
    <div class="bg-white rounded-xl border border-[#E5E8EC] p-6">
        <h2 class="text-[15px] font-semibold text-[#1a2a35] mb-5">Buat Password Baru</h2>

        <div class="space-y-4">

            <div>
                <label class="block text-[11px] font-semibold text-[#5a6a75] uppercase tracking-[0.7px] mb-1.5">
                    Password Baru
                </label>
                <input wire:model="password"
                       type="password"
                       placeholder="Minimal 8 karakter"
                       class="w-full h-[44px] px-3 text-[13px] text-[#1a2a35] bg-white border border-[#E0E5EA] rounded-xl outline-none focus:border-primary focus:ring-2 focus:ring-primary/10 placeholder:text-[#b0bec5]" />
                @error('password')
                    <p class="mt-1 text-[10px] text-[#c62828]">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label class="block text-[11px] font-semibold text-[#5a6a75] uppercase tracking-[0.7px] mb-1.5">
                    Konfirmasi Password
                </label>
                <input wire:model="password_confirmation"
                       type="password"
                       placeholder="Ulangi password baru"
                       class="w-full h-[44px] px-3 text-[13px] text-[#1a2a35] bg-white border border-[#E0E5EA] rounded-xl outline-none focus:border-primary focus:ring-2 focus:ring-primary/10 placeholder:text-[#b0bec5]" />
                @error('password_confirmation')
                    <p class="mt-1 text-[10px] text-[#c62828]">{{ $message }}</p>
                @enderror
            </div>

        </div>

        <button wire:click="simpan"
                wire:loading.attr="disabled"
                class="w-full h-[44px] mt-6 bg-primary hover:bg-[#005f78] text-white text-[13px] font-semibold rounded-xl transition-colors disabled:opacity-60">
            <span wire:loading.remove wire:target="simpan">Simpan & Lanjutkan</span>
            <span wire:loading wire:target="simpan">Menyimpan...</span>
        </button>
    </div>

</div>
