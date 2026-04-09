<?php

use App\Models\Peserta;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;

new #[Layout('components.layouts.admin')] class extends Component {
    public Peserta $peserta;

    public function mount(Peserta $peserta): void
    {
        $this->peserta = $peserta->load('user');
    }
}; ?>

<x-slot:title>Profil Peserta</x-slot:title>
<x-slot:subtitle>
    <a href="{{ route('admin.akun.index') }}" class="text-primary hover:underline">Kelola Akun</a>
    &rsaquo; {{ $peserta->user->nama }} &rsaquo; Profil
</x-slot:subtitle>

<div
    x-data="{
        tab: 'biodata',
        tabs: ['biodata','pendidikan','pelatihan','konferensi','penghargaan','organisasi'],
        tabLabels: {
            biodata: 'Biodata',
            pendidikan: 'Riwayat Pendidikan',
            pelatihan: 'Pelatihan',
            konferensi: 'Konferensi / Seminar',
            penghargaan: 'Penghargaan',
            organisasi: 'Organisasi Profesi',
        },
        savedMsg: '',
    }"
    @biodata-saved.window="savedMsg = 'Biodata berhasil disimpan.'; setTimeout(() => savedMsg = '', 3500)"
>

    <div x-show="savedMsg" x-cloak
         x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0 translate-y-2" x-transition:enter-end="opacity-100 translate-y-0"
         x-transition:leave="transition ease-in duration-150" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0"
         class="fixed bottom-5 right-5 z-50 bg-[#1e7e3e] text-white text-[12px] font-semibold px-4 py-2.5 rounded-xl shadow-lg flex items-center gap-2">
        <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>
        <span x-text="savedMsg"></span>
    </div>

    <div class="bg-white rounded-[10px] border border-[#E5E8EC] px-5 py-4 mb-5 flex items-center gap-6 flex-wrap">
        <div>
            <div class="text-[11px] text-[#8a9ba8] mb-0.5">Nama</div>
            <div class="text-[13px] font-semibold text-[#1a2a35]">{{ $peserta->user->nama }}</div>
        </div>
        <div>
            <div class="text-[11px] text-[#8a9ba8] mb-0.5">Email</div>
            <div class="text-[13px] text-[#5a6a75]">{{ $peserta->user->email }}</div>
        </div>
        @if ($peserta->telepon)
        <div>
            <div class="text-[11px] text-[#8a9ba8] mb-0.5">Telepon</div>
            <div class="text-[13px] text-[#5a6a75]">{{ $peserta->telepon }}</div>
        </div>
        @endif
        <div class="ml-auto flex items-center gap-3">
            <a href="{{ route('admin.akun.berkas', $peserta) }}"
               class="flex items-center gap-1.5 h-[34px] px-3.5 border border-[#D0D5DD] text-[#5a6a75] hover:border-primary hover:text-primary hover:bg-[#E8F4F8] rounded-lg text-[12px] font-medium transition-colors no-underline">
                <svg class="w-3.5 h-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 19a2 2 0 01-2 2H4a2 2 0 01-2-2V5a2 2 0 012-2h5l2 3h9a2 2 0 012 2z"/></svg>
                Berkas
            </a>
            <span class="text-[10px] font-semibold px-2.5 py-1 rounded-full {{ $peserta->user->aktif ? 'bg-[#E6F4EA] text-[#1e7e3e]' : 'bg-[#FCE8E6] text-[#c62828]' }}">
                {{ $peserta->user->aktif ? 'Aktif' : 'Nonaktif' }}
            </span>
        </div>
    </div>

    <div class="bg-white rounded-xl border border-[#E5E8EC] overflow-hidden mb-4">
        <div class="flex overflow-x-auto border-b border-[#F0F2F5]">
            <template x-for="t in tabs" :key="t">
                <button @click="tab = t"
                    :class="tab === t ? 'border-b-2 border-primary text-primary font-semibold' : 'text-[#8a9ba8] hover:text-[#1a2a35]'"
                    class="px-4 py-3 text-[12px] whitespace-nowrap transition-colors shrink-0"
                    x-text="tabLabels[t]">
                </button>
            </template>
        </div>
    </div>

    <div x-show="tab === 'biodata'" x-cloak>
        <livewire:profile.tabs.biodata :peserta-id="$peserta->id" :enforce-ownership="false" wire:key="admin-biodata-{{ $peserta->id }}" />
    </div>

    <div x-show="tab === 'pendidikan'" x-cloak>
        <livewire:profile.tabs.pendidikan
            :peserta-id="$peserta->id"
            :title="'Riwayat Pendidikan'"
            :desc="'Tambah riwayat pendidikan formal peserta.'"
            :empty-label="'data riwayat pendidikan.'"
            :enforce-ownership="false"
            wire:key="admin-pendidikan-{{ $peserta->id }}"
        />
    </div>

    <div x-show="tab === 'pelatihan'" x-cloak>
        <livewire:profile.tabs.pelatihan :peserta-id="$peserta->id" :enforce-ownership="false" wire:key="admin-pelatihan-{{ $peserta->id }}" />
    </div>

    <div x-show="tab === 'konferensi'" x-cloak>
        <livewire:profile.tabs.konferensi :peserta-id="$peserta->id" :enforce-ownership="false" wire:key="admin-konferensi-{{ $peserta->id }}" />
    </div>

    <div x-show="tab === 'penghargaan'" x-cloak>
        <livewire:profile.tabs.penghargaan :peserta-id="$peserta->id" :enforce-ownership="false" wire:key="admin-penghargaan-{{ $peserta->id }}" />
    </div>

    <div x-show="tab === 'organisasi'" x-cloak>
        <livewire:profile.tabs.organisasi
            :peserta-id="$peserta->id"
            :title="'Organisasi Profesi'"
            :desc="'Keanggotaan dalam organisasi profesi.'"
            :empty-label="'data organisasi profesi.'"
            :enforce-ownership="false"
            wire:key="admin-organisasi-{{ $peserta->id }}"
        />
    </div>

</div>
