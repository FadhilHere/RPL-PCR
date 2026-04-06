{{-- Modal Konfirmasi Hapus Akun --}}
<div x-show="confirm.open"
     x-transition:enter="transition ease-out duration-150"
     x-transition:enter-start="opacity-0"
     x-transition:enter-end="opacity-100"
     x-transition:leave="transition ease-in duration-100"
     x-transition:leave-start="opacity-100"
     x-transition:leave-end="opacity-0"
     style="display:none"
     class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 p-4"
     @keydown.escape.window="confirm.open = false"
     @click.self="confirm.open = false">

    <div x-show="confirm.open"
         x-transition:enter="transition ease-out duration-150"
         x-transition:enter-start="opacity-0 scale-95"
         x-transition:enter-end="opacity-100 scale-100"
         x-transition:leave="transition ease-in duration-100"
         x-transition:leave-start="opacity-100 scale-100"
         x-transition:leave-end="opacity-0 scale-95"
         class="bg-white rounded-2xl shadow-xl w-full max-w-sm p-6">

        <div class="flex items-center gap-3 mb-4">
            <div class="w-10 h-10 rounded-full bg-[#FCE8E6] flex items-center justify-center shrink-0">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#c62828" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <polyline points="3 6 5 6 21 6"/>
                    <path d="M19 6l-1 14a2 2 0 01-2 2H8a2 2 0 01-2-2L5 6"/>
                    <path d="M10 11v6M14 11v6"/>
                    <path d="M9 6V4a1 1 0 011-1h4a1 1 0 011 1v2"/>
                </svg>
            </div>
            <div>
                <h3 class="text-[15px] font-semibold text-[#1a2a35]">Hapus Akun</h3>
                <p class="text-[12px] text-[#8a9ba8] mt-0.5" x-text="'Akun \'' + confirm.userName + '\' akan dihapus permanen.'"></p>
            </div>
        </div>

        <p class="text-[13px] text-[#5a6a75] mb-5">Tindakan ini tidak dapat dibatalkan. Seluruh data terkait akun ini akan ikut terhapus.</p>

        <div class="flex gap-3">
            <button @click="confirm.open = false"
                    class="flex-1 h-[42px] bg-white border border-[#D8DDE2] text-[#1a2a35] text-[13px] font-semibold rounded-xl hover:bg-[#F4F6F8] transition-colors">
                Batal
            </button>
            <button @click="doDelete()"
                    class="flex-1 h-[42px] bg-[#c62828] hover:bg-[#a31f1f] text-white text-[13px] font-semibold rounded-xl transition-colors">
                Ya, Hapus
            </button>
        </div>

    </div>
</div>
