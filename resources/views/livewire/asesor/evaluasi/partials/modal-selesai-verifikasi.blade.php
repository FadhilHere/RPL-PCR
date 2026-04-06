{{-- Modal Konfirmasi Selesaikan Verifikasi --}}
<div x-show="konfirmasiOpen" x-cloak x-transition.opacity
     class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 p-4"
     @click.self="konfirmasiOpen = false"
     @keydown.escape.window="konfirmasiOpen = false">
    <div x-show="konfirmasiOpen"
         x-transition:enter="transition ease-out duration-150"
         x-transition:enter-start="opacity-0 scale-95"
         x-transition:enter-end="opacity-100 scale-100"
         x-transition:leave="transition ease-in duration-100"
         x-transition:leave-start="opacity-100 scale-100"
         x-transition:leave-end="opacity-0 scale-95"
         class="bg-white rounded-2xl shadow-xl w-full max-w-sm p-6">
        <div class="flex items-center gap-3 mb-3">
            <div class="w-9 h-9 rounded-full bg-[#E8F4F8] flex items-center justify-center shrink-0">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#004B5F" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                    <polyline points="20 6 9 17 4 12"/>
                </svg>
            </div>
            <h4 class="text-[14px] font-semibold text-[#1a2a35]">Selesaikan Verifikasi?</h4>
        </div>
        <p class="text-[12px] text-[#5a6a75] mb-5 leading-[1.6]">
            Status pengajuan akan berubah ke <strong class="text-[#1a2a35]">Dalam Review</strong>.
            Asesor dapat mulai mengevaluasi VATM setiap mata kuliah.
        </p>
        <div class="flex gap-2">
            <button @click="konfirmasiOpen = false"
                    class="flex-1 h-[40px] bg-white border border-[#D8DDE2] text-[#1a2a35] text-[13px] font-semibold rounded-xl hover:bg-[#F4F6F8] transition-colors">
                Batal
            </button>
            <button @click="$wire.selesaikanVerifikasi(catatanHasil); konfirmasiOpen = false"
                    wire:loading.attr="disabled" wire:target="selesaikanVerifikasi"
                    class="flex-1 h-[40px] bg-primary hover:bg-[#005f78] text-white text-[13px] font-semibold rounded-xl transition-colors disabled:opacity-60">
                <span wire:loading.remove wire:target="selesaikanVerifikasi">Selesaikan</span>
                <span wire:loading wire:target="selesaikanVerifikasi">Menyimpan...</span>
            </button>
        </div>
    </div>
</div>
