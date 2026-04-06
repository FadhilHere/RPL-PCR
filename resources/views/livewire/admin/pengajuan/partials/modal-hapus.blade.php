{{-- Modal Hapus Pengajuan --}}
<div x-show="hapusOpen" style="display:none"
     class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 p-4"
     x-transition:enter="transition ease-out duration-150" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
     x-transition:leave="transition ease-in duration-100" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0"
     @click.self="hapusOpen = false">
    <div x-show="hapusOpen"
         x-transition:enter="transition ease-out duration-150" x-transition:enter-start="opacity-0 scale-95" x-transition:enter-end="opacity-100 scale-100"
         x-transition:leave="transition ease-in duration-100" x-transition:leave-start="opacity-100 scale-100" x-transition:leave-end="opacity-0 scale-95"
         class="bg-white rounded-2xl shadow-xl w-full max-w-sm p-6">
        <div class="w-12 h-12 rounded-full bg-[#FCE8E6] flex items-center justify-center mb-4 mx-auto">
            <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="#c62828" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <polyline points="3 6 5 6 21 6"/><path d="M19 6l-1 14a2 2 0 01-2 2H8a2 2 0 01-2-2L5 6"/>
                <path d="M10 11v6"/><path d="M14 11v6"/><path d="M9 6V4a1 1 0 011-1h4a1 1 0 011 1v2"/>
            </svg>
        </div>
        <h4 class="text-[15px] font-semibold text-[#1a2a35] text-center mb-2">Hapus Pengajuan?</h4>
        <p class="text-[12px] text-[#8a9ba8] text-center leading-[1.6] mb-5">
            Pengajuan <strong class="text-[#1a2a35]">{{ $permohonan->nomor_permohonan }}</strong> akan dihapus permanen
            beserta seluruh data MK dan asesmen terkait. Tindakan ini tidak dapat dibatalkan.
        </p>
        <div class="flex gap-3">
            <button @click="hapusOpen = false"
                    class="flex-1 h-[40px] bg-white border border-[#D8DDE2] text-[#1a2a35] text-[13px] font-semibold rounded-xl hover:bg-[#F4F6F8] transition-colors">
                Batal
            </button>
            <button wire:click="hapusPermohonan"
                    wire:loading.attr="disabled" wire:target="hapusPermohonan"
                    class="flex-1 h-[40px] bg-[#D2092F] hover:bg-[#b8082a] text-white text-[13px] font-semibold rounded-xl transition-colors disabled:opacity-60">
                <span wire:loading.remove wire:target="hapusPermohonan">Ya, Hapus</span>
                <span wire:loading wire:target="hapusPermohonan">Menghapus...</span>
            </button>
        </div>
    </div>
</div>
