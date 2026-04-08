{{-- Modal Edit Akun --}}
@if ($editUserId)
<div class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 p-4"
     @keydown.escape.window="$wire.set('editUserId', null)"
     wire:click.self="$set('editUserId', null)">

    <div class="bg-white rounded-2xl shadow-xl w-full max-w-md flex flex-col" style="max-height:90vh">

        {{-- Header --}}
        <div class="flex items-center justify-between px-6 pt-5 pb-4 border-b border-[#F0F2F5] shrink-0">
            <div>
                <h3 class="text-[15px] font-semibold text-[#1a2a35]">Edit Akun</h3>
                <p class="text-[12px] text-[#8a9ba8] mt-px">{{ $editUser?->nama }}</p>
            </div>
            <button wire:click="$set('editUserId', null)"
                    class="text-[#8a9ba8] hover:text-[#1a2a35] transition-colors p-1">
                <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/>
                </svg>
            </button>
        </div>

        {{-- Body --}}
        <div class="px-6 py-5 overflow-y-auto flex-1 space-y-4">

            <p class="text-[11px] font-semibold text-[#8a9ba8] uppercase tracking-[0.7px]">Info Dasar</p>

            <div>
                <label class="block text-[11px] font-semibold text-[#5a6a75] uppercase tracking-[0.7px] mb-1.5">Nama Lengkap</label>
                <input wire:model="edit.nama" type="text"
                       class="w-full h-[42px] px-3.5 text-[13px] text-[#1a2a35] bg-white border border-[#E0E5EA] rounded-xl outline-none focus:border-primary focus:ring-2 focus:ring-primary/10" />
                @error('edit.nama') <p class="mt-1 text-[11px] text-[#c62828]">{{ $message }}</p> @enderror
            </div>

            <div>
                <label class="block text-[11px] font-semibold text-[#5a6a75] uppercase tracking-[0.7px] mb-1.5">Email</label>
                <input wire:model="edit.email" type="email"
                       class="w-full h-[42px] px-3.5 text-[13px] text-[#1a2a35] bg-white border border-[#E0E5EA] rounded-xl outline-none focus:border-primary focus:ring-2 focus:ring-primary/10" />
                @error('edit.email') <p class="mt-1 text-[11px] text-[#c62828]">{{ $message }}</p> @enderror
            </div>

            <div>
                <label class="block text-[11px] font-semibold text-[#5a6a75] uppercase tracking-[0.7px] mb-1.5">
                    Password Baru <span class="normal-case font-normal text-[#b0bec5]">(kosongkan jika tidak ingin ganti)</span>
                </label>
                <input wire:model="edit.newPassword" type="password" placeholder="Min. 8 karakter"
                       class="w-full h-[42px] px-3.5 text-[13px] text-[#1a2a35] bg-white border border-[#E0E5EA] rounded-xl outline-none focus:border-primary focus:ring-2 focus:ring-primary/10 placeholder:text-[#b0bec5]" />
                @error('edit.newPassword') <p class="mt-1 text-[11px] text-[#c62828]">{{ $message }}</p> @enderror
            </div>

            {{-- Data Asesor --}}
            @if ($editUser?->role === \App\Enums\RoleEnum::Asesor)
            <div class="pt-1 border-t border-[#F4F6F8] space-y-4">
                <p class="text-[11px] font-semibold text-[#8a9ba8] uppercase tracking-[0.7px] pt-3">Data Asesor</p>

                <div>
                    <label class="block text-[11px] font-semibold text-[#5a6a75] uppercase tracking-[0.7px] mb-1.5">
                        NIDN <span class="normal-case font-normal text-[#b0bec5]">(opsional)</span>
                    </label>
                    <input wire:model="edit.nidn" type="text" placeholder="0123456789"
                           class="w-full h-[42px] px-3.5 text-[13px] text-[#1a2a35] bg-white border border-[#E0E5EA] rounded-xl outline-none focus:border-primary focus:ring-2 focus:ring-primary/10 placeholder:text-[#b0bec5]" />
                </div>

                <div>
                    <label class="block text-[11px] font-semibold text-[#5a6a75] uppercase tracking-[0.7px] mb-1.5">Bidang Keahlian</label>
                    <input wire:model="edit.bidangKeahlian" type="text" placeholder="Teknik Informatika, Jaringan, ..."
                           class="w-full h-[42px] px-3.5 text-[13px] text-[#1a2a35] bg-white border border-[#E0E5EA] rounded-xl outline-none focus:border-primary focus:ring-2 focus:ring-primary/10 placeholder:text-[#b0bec5]" />
                    @error('edit.bidangKeahlian') <p class="mt-1 text-[11px] text-[#c62828]">{{ $message }}</p> @enderror
                </div>

                <label class="flex items-center gap-2.5 cursor-pointer select-none">
                    <input wire:model="edit.sudahPelatihan" type="checkbox"
                           class="w-4 h-4 rounded border-[#D0D5DD] accent-primary cursor-pointer" />
                    <span class="text-[13px] text-[#5a6a75]">Sudah mengikuti pelatihan RPL</span>
                </label>

                <div>
                    <label class="block text-[11px] font-semibold text-[#5a6a75] uppercase tracking-[0.7px] mb-1.5">Program Studi</label>
                    <x-form.select wire:model="editProdiId"
                                   placeholder="— Pilih program studi —"
                                   :options="$prodiOptions->mapWithKeys(fn($p) => [$p->id => $p->nama . ' (' . $p->kode . ')'])->all()" />
                    <p class="mt-1 text-[10px] text-[#8a9ba8]">Asesor hanya akan melihat pengajuan dari prodi yang dipilih.</p>
                </div>

                {{-- Upload Tanda Tangan --}}
                <div>
                    <label class="block text-[11px] font-semibold text-[#5a6a75] uppercase tracking-[0.7px] mb-1.5">
                        Tanda Tangan <span class="normal-case font-normal text-[#b0bec5]">(JPG/PNG, maks 2MB, opsional)</span>
                    </label>
                    @php $asesorModel = $editUser?->asesor; @endphp
                    @if ($asesorModel?->tanda_tangan && \Illuminate\Support\Facades\Storage::disk('local')->exists($asesorModel->tanda_tangan))
                    <div class="mb-2 flex items-center gap-2">
                        <div class="border border-[#E5E8EC] rounded-lg p-1.5 bg-[#FAFBFC]">
                            <img src="{{ route('berkas.ttd.asesor', $asesorModel) }}" alt="TTD" class="h-10 object-contain">
                        </div>
                        <span class="text-[11px] text-[#8a9ba8]">TTD saat ini · upload baru untuk mengganti</span>
                    </div>
                    @endif
                    <label class="flex items-center gap-2 px-3.5 py-2.5 border border-dashed border-[#D0D5DD] rounded-xl cursor-pointer hover:border-primary hover:bg-[#F8FBFC] transition-colors text-[12px] text-[#5a6a75]">
                        <svg class="w-4 h-4 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M21.44 11.05l-9.19 9.19a6 6 0 01-8.49-8.49l9.19-9.19a4 4 0 015.66 5.66l-9.2 9.19a2 2 0 01-2.83-2.83l8.49-8.48"/></svg>
                        <span wire:loading.remove wire:target="ttdAsesor">
                            @if ($ttdAsesor) {{ $ttdAsesor->getClientOriginalName() }} @else Pilih gambar tanda tangan @endif
                        </span>
                        <span wire:loading wire:target="ttdAsesor" class="text-[#8a9ba8]">Mengunggah...</span>
                        <input type="file" wire:model="ttdAsesor" accept=".jpg,.jpeg,.png" class="hidden">
                    </label>
                    @error('ttdAsesor') <p class="mt-1 text-[11px] text-[#c62828]">{{ $message }}</p> @enderror
                    @if ($ttdAsesor)
                    <div class="mt-2 border border-[#E5E8EC] rounded-lg p-2 bg-[#F8FBFC] inline-block">
                        <img src="{{ $ttdAsesor->temporaryUrl() }}" alt="Preview TTD" class="h-12 object-contain">
                    </div>
                    @endif
                </div>
            </div>
            @endif


        </div>

        {{-- Footer --}}
        <div class="flex gap-3 px-6 py-4 border-t border-[#F0F2F5] shrink-0">
            <button wire:click="$set('editUserId', null)"
                    class="flex-1 h-[42px] bg-white border border-[#D8DDE2] text-[#1a2a35] text-[13px] font-semibold rounded-xl hover:bg-[#F4F6F8] transition-colors">
                Batal
            </button>
            <button wire:click="saveEdit"
                    wire:loading.attr="disabled"
                    wire:target="saveEdit"
                    class="flex-1 h-[42px] bg-primary hover:bg-[#005f78] text-white text-[13px] font-semibold rounded-xl transition-colors disabled:opacity-60">
                <span wire:loading.remove wire:target="saveEdit">Simpan Perubahan</span>
                <span wire:loading wire:target="saveEdit">Menyimpan...</span>
            </button>
        </div>

    </div>
</div>
@endif
