<div x-show="hapusModal.open" x-cloak class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 p-4"
     x-transition:enter="transition ease-out duration-150" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
     x-transition:leave="transition ease-in duration-100" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0">
    <div @click.outside="hapusModal.open = false" @keydown.escape.window="hapusModal.open = false" class="bg-white rounded-2xl shadow-xl w-full max-w-sm p-6"
         x-transition:enter="transition ease-out duration-150" x-transition:enter-start="opacity-0 scale-95" x-transition:enter-end="opacity-100 scale-100">
        <div class="flex items-center gap-3 mb-4">
            <div class="w-10 h-10 rounded-full bg-[#FCE8E6] flex items-center justify-center shrink-0">
                <svg class="w-5 h-5 text-[#c62828]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="3 6 5 6 21 6"/><path d="M19 6l-1 14a2 2 0 01-2 2H8a2 2 0 01-2-2L5 6"/></svg>
            </div>
            <div>
                <div class="text-[14px] font-semibold text-[#1a2a35]">Hapus Data?</div>
                <div class="text-[12px] text-[#8a9ba8]">Data akan dihapus permanen.</div>
            </div>
        </div>
        <div class="flex gap-3">
            <button @click="hapusModal.open = false" class="flex-1 h-[40px] border border-[#D8DDE2] text-[#1a2a35] text-[13px] font-semibold rounded-xl hover:bg-[#F4F6F8] transition-colors">Batal</button>
            <button @click="doHapus()" class="flex-1 h-[40px] bg-[#c62828] hover:bg-[#b71c1c] text-white text-[13px] font-semibold rounded-xl transition-colors">Ya, Hapus</button>
        </div>
    </div>
</div>
