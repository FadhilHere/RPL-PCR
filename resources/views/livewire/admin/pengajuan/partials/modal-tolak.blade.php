{{-- Modal Tolak Pengajuan --}}
<div x-show="tolakOpen" style="display:none"
     class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 p-4"
     x-transition:enter="transition ease-out duration-150" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
     x-transition:leave="transition ease-in duration-100" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0"
     @click.self="tolakOpen = false">
    <div x-show="tolakOpen"
         x-transition:enter="transition ease-out duration-150" x-transition:enter-start="opacity-0 scale-95" x-transition:enter-end="opacity-100 scale-100"
         x-transition:leave="transition ease-in duration-100" x-transition:leave-start="opacity-100 scale-100" x-transition:leave-end="opacity-0 scale-95"
         class="bg-white rounded-2xl shadow-xl w-full max-w-sm p-6">
        <div class="flex items-center gap-3 mb-4">
            <div class="w-9 h-9 rounded-full bg-[#FCE8E6] flex items-center justify-center shrink-0">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#c62828" stroke-width="2.5" stroke-linecap="round">
                    <line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/>
                </svg>
            </div>
            <h4 class="text-[14px] font-semibold text-[#1a2a35]">Tolak Pengajuan</h4>
        </div>
        <p class="text-[12px] text-[#8a9ba8] mb-3">Berikan alasan penolakan untuk peserta (opsional):</p>
        <textarea x-model="catatanTolak" rows="3" placeholder="cth: Prodi tidak sesuai, berkas tidak lengkap..."
                  class="w-full px-3.5 py-2.5 text-[13px] text-[#1a2a35] bg-white border border-[#E0E5EA] rounded-xl outline-none focus:border-primary focus:ring-2 focus:ring-primary/10 resize-none mb-4"></textarea>
        <div class="flex gap-3">
            <button @click="tolakOpen = false"
                    class="flex-1 h-[40px] bg-white border border-[#D8DDE2] text-[#1a2a35] text-[13px] font-semibold rounded-xl hover:bg-[#F4F6F8] transition-colors">
                Batal
            </button>
            <button @click="$wire.tolakPermohonan(catatanTolak)"
                    wire:loading.attr="disabled" wire:target="tolakPermohonan"
                    class="flex-1 h-[40px] bg-[#D2092F] hover:bg-[#b8082a] text-white text-[13px] font-semibold rounded-xl transition-colors disabled:opacity-60">
                Tolak Pengajuan
            </button>
        </div>
    </div>
</div>
