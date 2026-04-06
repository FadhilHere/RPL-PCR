<div class="flex items-center justify-between mb-3">
    <div>
        <div class="text-[13px] font-semibold text-[#1a2a35]">{{ $title }}</div>
        <div class="text-[11px] text-[#8a9ba8]">{{ $desc }}</div>
    </div>
    <button @click="openTambah()" class="flex items-center gap-1.5 h-[36px] px-4 bg-primary hover:bg-[#005f78] text-white text-[12px] font-semibold rounded-xl transition-colors">
        <svg class="w-3.5 h-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
        Tambah
    </button>
</div>
