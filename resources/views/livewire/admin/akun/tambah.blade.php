<?php

use Livewire\Attributes\Layout;
use Livewire\Volt\Component;
use App\Livewire\Forms\TambahAsesorForm;

new #[Layout('components.layouts.admin')] class extends Component {
    public TambahAsesorForm $form;

    public function save(): void
    {
        $this->form->validate();
        $this->form->store();
        $this->redirect(route('admin.akun.index'), navigate: true);
    }
}; ?>

<x-slot:title>Tambah Asesor</x-slot:title>
<x-slot:subtitle><a href="{{ route('admin.akun.index') }}" class="text-primary hover:underline">Kelola Akun</a> &rsaquo; Tambah Asesor</x-slot:subtitle>

<div>

    <div class="max-w-lg">
        <div class="bg-white rounded-[10px] border border-[#E5E8EC] p-6">

            <div class="space-y-5">

                <div>
                    <label class="block text-[11px] font-semibold text-[#5a6a75] uppercase tracking-[0.7px] mb-1.5">
                        Nama Lengkap
                    </label>
                    <input wire:model="form.nama" type="text" placeholder="Dr. Nama Lengkap, M.T."
                           class="w-full h-[44px] px-4 text-[13px] text-[#1a2a35] bg-white border border-[#E0E5EA] rounded-xl outline-none focus:border-primary focus:ring-2 focus:ring-primary/10 placeholder:text-[#b0bec5]" />
                    @error('form.nama') <p class="mt-1.5 text-[11px] text-[#c62828]">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label class="block text-[11px] font-semibold text-[#5a6a75] uppercase tracking-[0.7px] mb-1.5">
                        Email
                    </label>
                    <input wire:model="form.email" type="email" placeholder="nama@pcr.ac.id"
                           class="w-full h-[44px] px-4 text-[13px] text-[#1a2a35] bg-white border border-[#E0E5EA] rounded-xl outline-none focus:border-primary focus:ring-2 focus:ring-primary/10 placeholder:text-[#b0bec5]" />
                    @error('form.email') <p class="mt-1.5 text-[11px] text-[#c62828]">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label class="block text-[11px] font-semibold text-[#5a6a75] uppercase tracking-[0.7px] mb-1.5">
                        Password Awal
                    </label>
                    <input wire:model="form.password" type="password" placeholder="Min. 8 karakter"
                           class="w-full h-[44px] px-4 text-[13px] text-[#1a2a35] bg-white border border-[#E0E5EA] rounded-xl outline-none focus:border-primary focus:ring-2 focus:ring-primary/10 placeholder:text-[#b0bec5]" />
                    @error('form.password') <p class="mt-1.5 text-[11px] text-[#c62828]">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label class="block text-[11px] font-semibold text-[#5a6a75] uppercase tracking-[0.7px] mb-1.5">
                        NIDN <span class="normal-case font-normal text-[#b0bec5]">(opsional)</span>
                    </label>
                    <input wire:model="form.nidn" type="text" placeholder="0123456789"
                           class="w-full h-[44px] px-4 text-[13px] text-[#1a2a35] bg-white border border-[#E0E5EA] rounded-xl outline-none focus:border-primary focus:ring-2 focus:ring-primary/10 placeholder:text-[#b0bec5]" />
                </div>

                <div>
                    <label class="block text-[11px] font-semibold text-[#5a6a75] uppercase tracking-[0.7px] mb-1.5">
                        Bidang Keahlian
                    </label>
                    <input wire:model="form.bidangKeahlian" type="text" placeholder="Teknik Informatika, Jaringan Komputer, ..."
                           class="w-full h-[44px] px-4 text-[13px] text-[#1a2a35] bg-white border border-[#E0E5EA] rounded-xl outline-none focus:border-primary focus:ring-2 focus:ring-primary/10 placeholder:text-[#b0bec5]" />
                    @error('form.bidangKeahlian') <p class="mt-1.5 text-[11px] text-[#c62828]">{{ $message }}</p> @enderror
                </div>

                <div class="flex items-center gap-2.5">
                    <input wire:model="form.sudahPelatihan" type="checkbox" id="sudahPelatihan"
                           class="w-4 h-4 rounded border-[#D0D5DD] accent-primary cursor-pointer" />
                    <label for="sudahPelatihan" class="text-[13px] text-[#5a6a75] cursor-pointer select-none">
                        Sudah mengikuti pelatihan RPL
                    </label>
                </div>

            </div>

            <div class="flex gap-3 mt-6">
                <a href="{{ route('admin.akun.index') }}"
                   class="flex-1 h-[44px] flex items-center justify-center bg-white border border-[#D8DDE2] text-[#1a2a35] text-[13px] font-semibold rounded-xl hover:bg-[#F4F6F8] transition-colors no-underline">
                    Batal
                </a>
                <button wire:click="save"
                        class="flex-1 h-[44px] bg-primary hover:bg-[#005f78] text-white text-[13px] font-semibold rounded-xl transition-colors"
                        wire:loading.attr="disabled">
                    <span wire:loading.remove>Buat Akun Asesor</span>
                    <span wire:loading>Menyimpan...</span>
                </button>
            </div>

        </div>
    </div>

</div>
