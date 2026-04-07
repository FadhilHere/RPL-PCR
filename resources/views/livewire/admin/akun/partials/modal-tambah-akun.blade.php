{{-- Modal Tambah Akun --}}
@if ($showForm)
<div class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 p-4"
     x-data="{ role: @js($create->roleForm) }"
     @keydown.escape.window="$wire.set('showForm', false)"
     wire:click.self="$set('showForm', false)">

    <div class="bg-white rounded-2xl shadow-xl w-full max-w-md flex flex-col" style="max-height:90vh">

        <div class="flex items-center justify-between px-6 pt-5 pb-4 border-b border-[#F0F2F5] shrink-0">
            <h3 class="text-[15px] font-semibold text-[#1a2a35]">Tambah Akun Baru</h3>
            <button wire:click="$set('showForm', false)"
                    class="text-[#8a9ba8] hover:text-[#1a2a35] transition-colors p-1">
                <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/>
                </svg>
            </button>
        </div>

        <div class="px-6 py-5 overflow-y-auto flex-1 space-y-4">

            <div>
                <label class="block text-[11px] font-semibold text-[#5a6a75] uppercase tracking-[0.7px] mb-2">Role Akun</label>
                <div class="grid grid-cols-3 gap-1.5 p-1 bg-[#F4F6F8] rounded-xl mb-1.5">
                    @foreach (['asesor' => 'Asesor', 'peserta' => 'Peserta', 'admin' => 'Admin'] as $r => $label)
                    <button type="button"
                            @click="role = '{{ $r }}'"
                            :class="role === '{{ $r }}' ? 'bg-white text-primary shadow-sm' : 'text-[#8a9ba8] hover:text-[#5a6a75]'"
                            class="py-2 rounded-lg text-[12px] font-semibold transition-all">
                        {{ $label }}
                    </button>
                    @endforeach
                </div>
                <div class="grid grid-cols-2 gap-1.5 p-1 bg-[#F4F6F8] rounded-xl">
                    @foreach (['admin_pmb' => 'Admin PMB', 'admin_baak' => 'Admin BAAK'] as $r => $label)
                    <button type="button"
                            @click="role = '{{ $r }}'"
                            :class="role === '{{ $r }}' ? 'bg-white text-primary shadow-sm' : 'text-[#8a9ba8] hover:text-[#5a6a75]'"
                            class="py-2 rounded-lg text-[12px] font-semibold transition-all">
                        {{ $label }}
                    </button>
                    @endforeach
                </div>
            </div>

            <div>
                <label class="block text-[11px] font-semibold text-[#5a6a75] uppercase tracking-[0.7px] mb-1.5">Nama Lengkap</label>
                <input wire:model="create.nama" type="text" placeholder="Nama lengkap"
                       class="w-full h-[42px] px-3.5 text-[13px] text-[#1a2a35] bg-white border border-[#E0E5EA] rounded-xl outline-none focus:border-primary focus:ring-2 focus:ring-primary/10 placeholder:text-[#b0bec5]" />
                @error('create.nama') <p class="mt-1 text-[11px] text-[#c62828]">{{ $message }}</p> @enderror
            </div>

            <div>
                <label class="block text-[11px] font-semibold text-[#5a6a75] uppercase tracking-[0.7px] mb-1.5">Email</label>
                <input wire:model="create.email" type="email" placeholder="email@contoh.com"
                       class="w-full h-[42px] px-3.5 text-[13px] text-[#1a2a35] bg-white border border-[#E0E5EA] rounded-xl outline-none focus:border-primary focus:ring-2 focus:ring-primary/10 placeholder:text-[#b0bec5]" />
                @error('create.email') <p class="mt-1 text-[11px] text-[#c62828]">{{ $message }}</p> @enderror
            </div>

            <div>
                <label class="block text-[11px] font-semibold text-[#5a6a75] uppercase tracking-[0.7px] mb-1.5">Password Awal</label>
                <input wire:model="create.password" type="password" placeholder="Min. 8 karakter"
                       class="w-full h-[42px] px-3.5 text-[13px] text-[#1a2a35] bg-white border border-[#E0E5EA] rounded-xl outline-none focus:border-primary focus:ring-2 focus:ring-primary/10 placeholder:text-[#b0bec5]" />
                @error('create.password') <p class="mt-1 text-[11px] text-[#c62828]">{{ $message }}</p> @enderror
            </div>

            <div x-show="role === 'asesor'" style="display:none" class="pt-1 border-t border-[#F4F6F8] space-y-4">
                <p class="text-[11px] font-semibold text-[#8a9ba8] uppercase tracking-[0.7px] pt-3">Data Asesor</p>

                <div>
                    <label class="block text-[11px] font-semibold text-[#5a6a75] uppercase tracking-[0.7px] mb-1.5">
                        NIDN <span class="normal-case font-normal text-[#b0bec5]">(opsional)</span>
                    </label>
                    <input wire:model="create.nidn" type="text" placeholder="0123456789"
                           class="w-full h-[42px] px-3.5 text-[13px] text-[#1a2a35] bg-white border border-[#E0E5EA] rounded-xl outline-none focus:border-primary focus:ring-2 focus:ring-primary/10 placeholder:text-[#b0bec5]" />
                </div>

                <div>
                    <label class="block text-[11px] font-semibold text-[#5a6a75] uppercase tracking-[0.7px] mb-1.5">Bidang Keahlian</label>
                    <input wire:model="create.bidangKeahlian" type="text" placeholder="Teknik Informatika, Jaringan, ..."
                           class="w-full h-[42px] px-3.5 text-[13px] text-[#1a2a35] bg-white border border-[#E0E5EA] rounded-xl outline-none focus:border-primary focus:ring-2 focus:ring-primary/10 placeholder:text-[#b0bec5]" />
                    @error('create.bidangKeahlian') <p class="mt-1 text-[11px] text-[#c62828]">{{ $message }}</p> @enderror
                </div>

                <label class="flex items-center gap-2.5 cursor-pointer select-none">
                    <input wire:model="create.sudahPelatihan" type="checkbox"
                           class="w-4 h-4 rounded border-[#D0D5DD] accent-primary cursor-pointer" />
                    <span class="text-[13px] text-[#5a6a75]">Sudah mengikuti pelatihan RPL</span>
                </label>

                <div>
                    <label class="block text-[11px] font-semibold text-[#5a6a75] uppercase tracking-[0.7px] mb-1.5">
                        Program Studi <span class="normal-case font-normal text-[#b0bec5]">(opsional)</span>
                    </label>
                    <x-form.select wire:model="createProdiId"
                                   placeholder="— Pilih program studi —"
                                   :options="$prodiOptions->mapWithKeys(fn($p) => [$p->id => $p->nama . ' (' . $p->kode . ')'])->all()" />
                    <p class="mt-1 text-[10px] text-[#8a9ba8]">Asesor hanya akan melihat pengajuan dari prodi yang dipilih.</p>
                </div>
            </div>

            <div x-show="['admin','admin_pmb','admin_baak'].includes(role)" style="display:none" class="pt-1 border-t border-[#F4F6F8]">
                <div class="flex items-start gap-3 bg-[#FFF8E1] rounded-xl px-4 py-3 mt-3">
                    <svg class="w-4 h-4 text-[#b45309] shrink-0 mt-0.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/>
                    </svg>
                    <p class="text-[12px] text-[#b45309] leading-[1.5]" x-show="role === 'admin'">
                        Akun Admin memiliki akses penuh ke seluruh fitur sistem. Pastikan hanya diberikan kepada pengelola yang berwenang.
                    </p>
                    <p class="text-[12px] text-[#b45309] leading-[1.5]" x-show="role === 'admin_pmb'">
                        Akun Admin PMB hanya dapat mengakses kelola akun dan verifikasi berkas peserta.
                    </p>
                    <p class="text-[12px] text-[#b45309] leading-[1.5]" x-show="role === 'admin_baak'">
                        Akun Admin BAAK hanya dapat mengakses proses asesmen, atur jadwal, dan resume.
                    </p>
                </div>
            </div>

        </div>

        <div class="flex gap-3 px-6 py-4 border-t border-[#F0F2F5] shrink-0">
            <button wire:click="$set('showForm', false)"
                    class="flex-1 h-[42px] bg-white border border-[#D8DDE2] text-[#1a2a35] text-[13px] font-semibold rounded-xl hover:bg-[#F4F6F8] transition-colors">
                Batal
            </button>
            <button @click="$wire.save(role)"
                    wire:loading.attr="disabled"
                    wire:target="save"
                    class="flex-1 h-[42px] bg-primary hover:bg-[#005f78] text-white text-[13px] font-semibold rounded-xl transition-colors disabled:opacity-60">
                <span wire:loading.remove wire:target="save"
                      x-text="{ asesor: 'Buat Akun Asesor', peserta: 'Buat Akun Peserta', admin: 'Buat Akun Admin', admin_pmb: 'Buat Akun Admin PMB', admin_baak: 'Buat Akun Admin BAAK' }[role]">
                </span>
                <span wire:loading wire:target="save">Menyimpan...</span>
            </button>
        </div>

    </div>
</div>
@endif
