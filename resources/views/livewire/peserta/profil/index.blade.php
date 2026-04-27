<?php

use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;

new #[Layout('components.layouts.peserta')] class extends Component {
    public int $pesertaId;

    public string $password = '';
    public string $password_confirmation = '';

    public bool $mustChangePassword = false;

    public function mount(): void
    {
        $user = auth()->user();

        abort_if(! $user || ! $user->peserta, 403);

        $this->pesertaId = (int) $user->peserta->id;

        if (Hash::check($user->email, $user->password)
            || Hash::check($user->nama, $user->password)) {
            $this->mustChangePassword = true;
        }
    }

    public function simpanPassword(): void
    {
        $this->validate([
            'password' => ['required', 'string', Password::min(8), 'confirmed'],
            'password_confirmation' => ['required'],
        ], [
            'password.required' => 'Password wajib diisi.',
            'password.min' => 'Password minimal 8 karakter.',
            'password.confirmed' => 'Konfirmasi password tidak cocok.',
        ]);

        auth()->user()->update(['password' => Hash::make($this->password)]);

        $this->reset('password', 'password_confirmation');
        $this->mustChangePassword = false;

        $this->dispatch('password-saved');
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
            biodata: 'Biodata',
            pendidikan: 'Riwayat Pendidikan',
            pelatihan: 'Pelatihan',
            konferensi: 'Konferensi / Seminar',
            penghargaan: 'Penghargaan',
            organisasi: 'Organisasi Profesi',
            password: 'Ganti Password',
        },
        savedMsg: '',
    }"
    @biodata-saved.window="savedMsg = 'Biodata berhasil disimpan.'; setTimeout(() => savedMsg = '', 3500)"
    @password-saved.window="savedMsg = 'Password berhasil diubah.'; setTimeout(() => savedMsg = '', 3500)"
>

    <div x-show="savedMsg" x-cloak
         x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0 translate-y-2" x-transition:enter-end="opacity-100 translate-y-0"
         x-transition:leave="transition ease-in duration-150" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0"
         class="fixed bottom-5 right-5 z-50 bg-[#1e7e3e] text-white text-[12px] font-semibold px-4 py-2.5 rounded-xl shadow-lg flex items-center gap-2">
        <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>
        <span x-text="savedMsg"></span>
    </div>

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

        <div x-show="mustChangePassword" x-cloak class="px-4 py-2.5 bg-[#FFF8E1] border-t border-[#FFE082] flex items-center gap-2 text-[11px] text-[#b45309]">
            <svg class="w-4 h-4 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
            Ganti password Anda terlebih dahulu sebelum dapat mengisi data profil lainnya.
        </div>
    </div>

    <div x-show="tab === 'biodata'" x-cloak>
        <livewire:profile.tabs.biodata :peserta-id="$pesertaId" :enforce-ownership="true" wire:key="peserta-biodata-{{ $pesertaId }}" />
    </div>

    <div x-show="tab === 'pendidikan'" x-cloak>
        <livewire:profile.tabs.pendidikan :peserta-id="$pesertaId" :enforce-ownership="true" wire:key="peserta-pendidikan-{{ $pesertaId }}" />
    </div>

    <div x-show="tab === 'pelatihan'" x-cloak>
        <livewire:profile.tabs.pelatihan :peserta-id="$pesertaId" :enforce-ownership="true" wire:key="peserta-pelatihan-{{ $pesertaId }}" />
    </div>

    <div x-show="tab === 'konferensi'" x-cloak>
        <livewire:profile.tabs.konferensi :peserta-id="$pesertaId" :enforce-ownership="true" wire:key="peserta-konferensi-{{ $pesertaId }}" />
    </div>

    <div x-show="tab === 'penghargaan'" x-cloak>
        <livewire:profile.tabs.penghargaan :peserta-id="$pesertaId" :enforce-ownership="true" wire:key="peserta-penghargaan-{{ $pesertaId }}" />
    </div>

    <div x-show="tab === 'organisasi'" x-cloak>
        <livewire:profile.tabs.organisasi :peserta-id="$pesertaId" :enforce-ownership="true" wire:key="peserta-organisasi-{{ $pesertaId }}" />
    </div>

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
                        <input wire:model="password_confirmation" :type="show ? 'text' : 'password'" placeholder="Ulangi password baru"
                            class="w-full h-[40px] pl-3.5 pr-9 text-[13px] text-[#1a2a35] bg-white border border-[#E0E5EA] rounded-xl outline-none focus:border-primary focus:ring-2 focus:ring-primary/10 placeholder:text-[#b0bec5]" />
                        <button type="button" @click="show = !show" class="absolute right-3 top-1/2 -translate-y-1/2 text-[#b0bec5] hover:text-[#5a6a75] transition-colors">
                            <svg x-show="!show" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="11" width="18" height="11" rx="2"/><path d="M7 11V7a5 5 0 0110 0v4"/></svg>
                            <svg x-show="show" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" style="display:none"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
                        </button>
                    </div>
                    @error('password_confirmation') <p class="mt-1 text-[11px] text-[#c62828]">{{ $message }}</p> @enderror
                </div>
            </div>

            <button wire:click="simpanPassword" wire:loading.attr="disabled"
                class="mt-5 h-[42px] px-6 bg-primary hover:bg-[#005f78] text-white text-[13px] font-semibold rounded-xl transition-colors disabled:opacity-60">
                <span wire:loading.remove wire:target="simpanPassword">Simpan Password</span>
                <span wire:loading wire:target="simpanPassword">Menyimpan...</span>
            </button>
        </div>
    </div>

</div>
