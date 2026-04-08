{{-- Info Peserta + Aksi Admin (grid 2 kolom) --}}
<div class="grid grid-cols-2 gap-5 mb-5">

    {{-- Info Peserta --}}
    <div class="bg-white rounded-xl border border-[#E5E8EC] px-5 py-4">
        <div class="text-[11px] font-semibold text-[#8a9ba8] uppercase tracking-[0.8px] mb-3">Informasi Peserta</div>
        <div class="space-y-2.5">
            <div class="flex items-center gap-2">
                <span class="text-[12px] text-[#8a9ba8] w-[130px] shrink-0">Nama</span>
                <span class="text-[12px] font-medium text-[#1a2a35]">{{ $permohonan->peserta->user->nama ?? '-' }}</span>
            </div>
            <div class="flex items-center gap-2">
                <span class="text-[12px] text-[#8a9ba8] w-[130px] shrink-0">Email</span>
                <span class="text-[12px] text-[#1a2a35]">{{ $permohonan->peserta->user->email ?? '-' }}</span>
            </div>
            <div class="flex items-start gap-2">
                <span class="text-[12px] text-[#8a9ba8] w-[130px] shrink-0 pt-0.5">Program Studi</span>
                <span class="text-[12px] text-[#1a2a35]">{{ $permohonan->programStudi->nama ?? '-' }}</span>
            </div>
            <div class="flex items-center gap-2">
                <span class="text-[12px] text-[#8a9ba8] w-[130px] shrink-0">Tgl Pengajuan</span>
                <span class="text-[12px] text-[#1a2a35]">
                    {{ $permohonan->tanggal_pengajuan?->format('d M Y, H:i') ?? '-' }}
                </span>
            </div>
            <div class="flex items-center gap-2">
                <span class="text-[12px] text-[#8a9ba8] w-[130px] shrink-0">Status</span>
                <span class="text-[11px] font-semibold px-2.5 py-1 rounded-full {{ $status->badgeClass() }}">
                    {{ $status->label() }}
                </span>
            </div>
            @if ($permohonan->catatan_admin)
            <div class="flex items-start gap-2 pt-1">
                <span class="text-[12px] text-[#8a9ba8] w-[130px] shrink-0 pt-0.5">Catatan Admin</span>
                <span class="text-[12px] text-[#5a6a75] leading-relaxed">{{ $permohonan->catatan_admin }}</span>
            </div>
            @endif
        </div>
    </div>

    {{-- Aksi Admin --}}
    <div class="bg-white rounded-xl border border-[#E5E8EC] px-5 py-4">
        <div class="text-[11px] font-semibold text-[#8a9ba8] uppercase tracking-[0.8px] mb-3">Aksi Admin</div>

        @if ($isDiajukan)
        {{-- === Status: Menunggu Verifikasi === --}}
        <div class="space-y-3">
            <div class="flex gap-2 p-3 bg-[#FFF8E1] border border-[#FFE082] rounded-xl">
                <svg class="w-4 h-4 text-[#b45309] shrink-0 mt-0.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/>
                </svg>
                <p class="text-[11px] text-[#b45309] leading-[1.6]">
                    Verifikasi prodi yang dipilih peserta. Jika perlu diubah, ganti dulu sebelum memproses.
                    Seluruh MK aktif dari prodi akan otomatis di-assign setelah diproses.
                </p>
            </div>

            {{-- Ubah Prodi --}}
            <div>
                <label class="block text-[12px] text-[#5a6a75] mb-1.5">Program Studi</label>
                <div class="relative" x-data="{ open: false }" @click.outside="open = false">
                    <button type="button" @click="open = !open"
                            class="w-full h-[42px] px-3.5 text-[13px] text-left bg-white border rounded-xl outline-none flex items-center justify-between gap-2 transition-all"
                            :class="open ? 'border-primary ring-2 ring-primary/10' : 'border-[#E0E5EA] hover:border-[#C5CDD5]'">
                        <span class="text-[#1a2a35] truncate">
                            @foreach ($prodiOptions as $pid => $pnama)
                                <span x-show="prodiId === {{ $pid }}">{{ $pnama }}</span>
                            @endforeach
                        </span>
                        <svg class="w-4 h-4 text-[#8a9ba8] shrink-0 transition-transform" :class="open && 'rotate-180'" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><polyline points="6 9 12 15 18 9"/></svg>
                    </button>
                    <div x-show="open" x-cloak
                         x-transition:enter="transition ease-out duration-100"
                         x-transition:enter-start="opacity-0 -translate-y-1"
                         x-transition:enter-end="opacity-100 translate-y-0"
                         class="absolute top-[calc(100%+4px)] left-0 right-0 z-10 bg-white border border-[#E0E5EA] rounded-xl shadow-lg overflow-hidden">
                        <div class="py-1 max-h-[220px] overflow-y-auto">
                            @foreach ($prodiOptions as $pid => $pnama)
                            <button type="button"
                                    @click="prodiId = {{ $pid }}; open = false"
                                    class="w-full text-left px-3.5 py-2 text-[13px] transition-colors"
                                    :class="prodiId === {{ $pid }} ? 'bg-[#E8F4F8] text-primary font-semibold' : 'text-[#1a2a35] hover:bg-[#F4F6F8]'">
                                {{ $pnama }}
                            </button>
                            @endforeach
                        </div>
                    </div>
                </div>
            </div>

            <div class="flex gap-2 pt-1" x-data="{ bayarWarnOpen: false, sudahBayar: {{ $permohonan->pembayaran_terverifikasi ? 'true' : 'false' }} }">
                <button @click="sudahBayar ? $wire.prosesPermohonan(prodiId) : (bayarWarnOpen = true)"
                        wire:loading.attr="disabled" wire:target="prosesPermohonan"
                        class="flex-1 h-[42px] bg-primary hover:bg-[#005f78] text-white text-[13px] font-semibold rounded-xl transition-colors disabled:opacity-60">
                    <span wire:loading.remove wire:target="prosesPermohonan">Verifikasi & Proses Pengajuan</span>
                    <span wire:loading wire:target="prosesPermohonan">Memproses...</span>
                </button>
                <button @click="tolakOpen = true"
                        class="h-[42px] px-4 border border-[#D2092F] text-[#D2092F] text-[13px] font-semibold rounded-xl hover:bg-[#FCE8E6] transition-colors">
                    Tolak
                </button>

                {{-- Modal: pembayaran belum diverifikasi --}}
                <div x-show="bayarWarnOpen" x-cloak class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 p-4"
                     x-transition:enter="transition ease-out duration-150" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
                     x-transition:leave="transition ease-in duration-100" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0">
                    <div @click.outside="bayarWarnOpen = false" @keydown.escape.window="bayarWarnOpen = false"
                         class="bg-white rounded-2xl shadow-xl w-full max-w-sm p-6"
                         x-transition:enter="transition ease-out duration-150" x-transition:enter-start="opacity-0 scale-95" x-transition:enter-end="opacity-100 scale-100"
                         x-transition:leave="transition ease-in duration-100" x-transition:leave-start="opacity-100 scale-100" x-transition:leave-end="opacity-0 scale-95">
                        <div class="flex items-center gap-3 mb-4">
                            <div class="w-10 h-10 rounded-full bg-[#FFF8E1] flex items-center justify-center shrink-0">
                                <svg class="w-5 h-5 text-[#F57C00]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/>
                                </svg>
                            </div>
                            <div>
                                <div class="text-[14px] font-semibold text-[#1a2a35]">Pembayaran Belum Diverifikasi</div>
                                <div class="text-[12px] text-[#8a9ba8]">Peserta belum menyelesaikan pembayaran.</div>
                            </div>
                        </div>
                        <p class="text-[12px] text-[#5a6a75] mb-5">Pembayaran peserta belum diverifikasi. Pastikan pembayaran telah dikonfirmasi sebelum memproses pengajuan ini.</p>
                        <div class="flex gap-3">
                            <button @click="bayarWarnOpen = false" class="flex-1 h-[40px] bg-white border border-[#D8DDE2] text-[#1a2a35] text-[13px] font-semibold rounded-xl hover:bg-[#F4F6F8] transition-colors">Tutup</button>
                            <button @click="bayarWarnOpen = false; $wire.prosesPermohonan(prodiId)"
                                    class="flex-1 h-[40px] bg-[#F57C00] hover:bg-[#E65100] text-white text-[13px] font-semibold rounded-xl transition-colors">
                                Tetap Proses
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        @elseif ($isSelesai)
        {{-- === Status: Selesai (disetujui / ditolak) === --}}
        <div class="flex flex-col items-center justify-center py-6 text-center">
            @if ($status === \App\Enums\StatusPermohonanEnum::Disetujui)
            <div class="w-10 h-10 rounded-full bg-[#E6F4EA] flex items-center justify-center mb-2">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#1e7e3e" stroke-width="2.5" stroke-linecap="round"><polyline points="20 6 9 17 4 12"/></svg>
            </div>
            <p class="text-[13px] font-semibold text-[#1e7e3e]">Permohonan Disetujui</p>
            <a href="{{ route('export.hasil.word', $permohonan) }}"
               class="inline-flex items-center gap-1.5 h-[34px] px-4 mt-3 text-[12px] font-semibold text-primary border border-[#BDE0EB] rounded-lg hover:bg-[#E8F4F8] transition-colors no-underline">
                <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M21 15v4a2 2 0 01-2 2H5a2 2 0 01-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/>
                </svg>
                Download Hasil (Word)
            </a>
            @else
            <div class="w-10 h-10 rounded-full bg-[#FCE8E6] flex items-center justify-center mb-2">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#c62828" stroke-width="2.5" stroke-linecap="round"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
            </div>
            <p class="text-[13px] font-semibold text-[#c62828]">Permohonan Ditolak</p>
            @endif
        </div>

        @else
        {{-- === Status: Diproses / Verifikasi / Dalam Review === --}}
        <div class="space-y-3">
            <div class="flex gap-2 p-3 bg-[#E8F4F8] border border-[#C5DDE5] rounded-xl">
                <svg class="w-4 h-4 text-primary shrink-0 mt-0.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><circle cx="12" cy="12" r="10"/><polyline points="20 6 9 17 4 12"/></svg>
                <p class="text-[11px] text-primary leading-[1.6]">
                    Pengajuan sedang <strong>{{ $status->label() }}</strong>.
                    Mata kuliah sudah di-assign otomatis. Anda bisa menambah atau menghapus MK secara individual.
                </p>
            </div>
            @if (! $isSelesai)
            <button @click="tolakOpen = true"
                    class="w-full h-[38px] border border-[#D2092F] text-[#D2092F] text-[13px] font-semibold rounded-xl hover:bg-[#FCE8E6] transition-colors">
                Tolak Pengajuan
            </button>
            @endif
        </div>
        @endif

    </div>
</div>
