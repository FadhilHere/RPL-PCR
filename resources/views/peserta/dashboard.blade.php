<x-layouts.peserta
    title="Selamat datang, {{ auth()->user()->nama }}"
    subtitle="{{ \Carbon\Carbon::now()->locale('id')->translatedFormat('l\, d F Y') }}&nbsp;&nbsp;·&nbsp;&nbsp;Teknik Informatika"
>

    {{-- ===== WELCOME BANNER ===== --}}
    <div class="flex items-center justify-between bg-primary rounded-xl px-6 py-5 mb-6">
        <div>
            <h2 class="text-white font-semibold text-[16px] mb-1">Pengajuan RPL Anda sedang diproses</h2>
            <p class="text-white/65 text-[12px] leading-[1.5] max-w-[380px]">
                Dokumen bukti Anda telah diterima. Asesor sedang melakukan evaluasi VATM. Pantau perkembangan di halaman Pengajuan RPL.
            </p>
        </div>
        <button class="shrink-0 bg-white text-primary text-[12px] font-semibold px-4 py-2 rounded-md hover:bg-[#F0F7FA] transition-colors">
            Lihat Detail →
        </button>
    </div>

    {{-- ===== STAT CARDS ===== --}}
    <div class="flex gap-3.5 mb-6">

        {{-- MK Diajukan --}}
        <div class="flex-1 flex items-start gap-3.5 bg-white rounded-lg border border-[#E5E8EC] p-[16px_18px]">
            <div class="w-[38px] h-[38px] rounded-lg bg-[#E8F0FE] flex items-center justify-center shrink-0">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#1a73e8" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M22 11.08V12a10 10 0 11-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/>
                </svg>
            </div>
            <div>
                <div class="text-[11px] text-[#8a9ba8] mb-1">Mata Kuliah Diajukan</div>
                <div class="text-[22px] font-semibold text-[#1a2a35] leading-none mb-0.5">8</div>
                <div class="text-[11px] text-[#8a9ba8]">dari 3 prodi berbeda</div>
            </div>
        </div>

        {{-- Dokumen --}}
        <div class="flex-1 flex items-start gap-3.5 bg-white rounded-lg border border-[#E5E8EC] p-[16px_18px]">
            <div class="w-[38px] h-[38px] rounded-lg bg-[#E6F4EA] flex items-center justify-center shrink-0">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#1e8e3e" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M9 11l3 3L22 4"/><path d="M21 12v7a2 2 0 01-2 2H5a2 2 0 01-2-2V5a2 2 0 012-2h11"/>
                </svg>
            </div>
            <div>
                <div class="text-[11px] text-[#8a9ba8] mb-1">Dokumen Diunggah</div>
                <div class="text-[22px] font-semibold text-[#1a2a35] leading-none mb-0.5">12</div>
                <div class="text-[11px] text-[#8a9ba8]">5 dokumen belum diverifikasi</div>
            </div>
        </div>

        {{-- Status --}}
        <div class="flex-1 flex items-start gap-3.5 bg-white rounded-lg border border-[#E5E8EC] p-[16px_18px]">
            <div class="w-[38px] h-[38px] rounded-lg bg-[#FEF3E2] flex items-center justify-center shrink-0">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#e37400" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/>
                </svg>
            </div>
            <div>
                <div class="text-[11px] text-[#8a9ba8] mb-1">Status Pengajuan</div>
                <div class="mt-1 mb-0.5">
                    <span class="text-[10px] font-semibold px-2 py-0.5 rounded-full bg-[#FFF8E1] text-[#b45309]">Sedang Dievaluasi</span>
                </div>
                <div class="text-[11px] text-[#8a9ba8]">Diperbarui 2 hari lalu</div>
            </div>
        </div>

    </div>

    {{-- ===== BOTTOM GRID ===== --}}
    <div class="flex gap-[18px]">

        {{-- Kiri: Daftar MK --}}
        <div class="flex-1 bg-white rounded-[10px] border border-[#E5E8EC] overflow-hidden">
            <div class="flex items-center justify-between px-[18px] py-3.5 border-b border-[#F0F2F5]">
                <div class="text-[13px] font-semibold text-[#1a2a35]">Daftar Mata Kuliah yang Diajukan</div>
                <button class="text-[12px] text-primary font-medium hover:underline">Lihat semua</button>
            </div>

            @php
                $mkList = [
                    ['IF201', 'Algoritma dan Pemrograman',  '3 SKS', 'green',  'Diakui'],
                    ['IF304', 'Basis Data',                 '3 SKS', 'yellow', 'Dievaluasi'],
                    ['IF312', 'Rekayasa Perangkat Lunak',   '3 SKS', 'yellow', 'Dievaluasi'],
                    ['IF215', 'Jaringan Komputer',          '2 SKS', 'blue',   'Menunggu Asesor'],
                    ['IF401', 'Kecerdasan Buatan',          '3 SKS', 'gray',   'Belum Dinilai'],
                ];
                $badge = [
                    'green'  => 'bg-[#E6F4EA] text-[#1e7e3e]',
                    'yellow' => 'bg-[#FFF8E1] text-[#b45309]',
                    'blue'   => 'bg-[#E8F0FE] text-[#1557b0]',
                    'gray'   => 'bg-[#F1F3F4] text-[#5f6368]',
                    'red'    => 'bg-[#FCE8E6] text-[#c62828]',
                ];
            @endphp

            @foreach ($mkList as $mk)
            <div class="flex items-center gap-3.5 px-[18px] py-3 border-b border-[#F6F8FA] last:border-0">
                <span class="text-[10px] font-semibold text-primary bg-[#E8F4F8] px-[7px] py-[3px] rounded shrink-0">{{ $mk[0] }}</span>
                <span class="flex-1 text-[12px] text-[#1a2a35]">{{ $mk[1] }}</span>
                <span class="text-[11px] text-[#8a9ba8] shrink-0">{{ $mk[2] }}</span>
                <span class="text-[10px] font-semibold px-2 py-[3px] rounded-full shrink-0 {{ $badge[$mk[3]] }}">{{ $mk[4] }}</span>
            </div>
            @endforeach
        </div>

        {{-- Kanan: Timeline + Konsultasi --}}
        <div class="w-[320px] shrink-0 flex flex-col gap-[18px]">

            {{-- Timeline --}}
            <div class="bg-white rounded-[10px] border border-[#E5E8EC] overflow-hidden">
                <div class="px-[18px] py-3.5 border-b border-[#F0F2F5]">
                    <div class="text-[13px] font-semibold text-[#1a2a35]">Alur Pengajuan</div>
                </div>
                <div class="px-[18px] py-4">
                    @php
                        $steps = [
                            ['done',    '✓', 'Pendaftaran Akun',             '10 Mar 2026',     null],
                            ['done',    '✓', 'Konsultasi Awal',              '13 Mar 2026',     null],
                            ['done',    '✓', 'Pengajuan & Upload Dokumen',   '17 Mar 2026',     null],
                            ['current', '4', 'Evaluasi VATM oleh Asesor',    'Sedang berjalan', 'Tahap saat ini'],
                            ['pending', '5', 'Keputusan & SK Rekognisi',     'Menunggu',        null],
                        ];
                        $dotClass = [
                            'done'    => 'bg-[#E6F4EA] text-[#1e8e3e]',
                            'current' => 'bg-primary text-white',
                            'pending' => 'bg-[#F1F3F4] text-[#9aa0a6]',
                        ];
                    @endphp

                    @foreach ($steps as $step)
                    <div class="flex gap-3 relative {{ !$loop->last ? 'pb-4' : '' }}">
                        @if (!$loop->last)
                            <div class="absolute left-3 top-[26px] bottom-0 w-px bg-[#E5E8EC]"></div>
                        @endif
                        <div class="w-6 h-6 rounded-full shrink-0 flex items-center justify-center text-[10px] font-semibold relative z-10 {{ $dotClass[$step[0]] }}">
                            {{ $step[1] }}
                        </div>
                        <div class="flex-1 pt-0.5">
                            <div class="text-[12px] font-medium {{ $step[0] === 'pending' ? 'text-[#9aa0a6]' : 'text-[#1a2a35]' }}">{{ $step[2] }}</div>
                            <div class="text-[11px] text-[#9aa0a6] mt-px">{{ $step[3] }}</div>
                            @if ($step[4])
                                <span class="inline-block mt-1 text-[10px] font-medium text-primary bg-[#E8F4F8] px-1.5 py-px rounded">{{ $step[4] }}</span>
                            @endif
                        </div>
                    </div>
                    @endforeach
                </div>
            </div>

            {{-- Konsultasi --}}
            <div class="bg-white rounded-[10px] border border-[#E5E8EC] overflow-hidden">
                <div class="px-[18px] py-3.5 border-b border-[#F0F2F5]">
                    <div class="text-[13px] font-semibold text-[#1a2a35]">Konsultasi Berikutnya</div>
                </div>
                <div class="p-[18px]">
                    <div class="bg-[#F8FAFB] border border-[#E5E8EC] rounded-lg p-[12px_14px]">
                        <div class="text-[12px] font-semibold text-[#1a2a35] mb-0.5">Konsultasi Lanjutan</div>
                        <div class="text-[11px] text-[#8a9ba8] mb-2.5">Senin, 23 Maret 2026 · 09.00 WIB</div>
                        <div class="flex items-center gap-2 mb-2.5">
                            <div class="w-7 h-7 rounded-full bg-[#D0E4ED] flex items-center justify-center text-[10px] font-semibold text-primary shrink-0">DR</div>
                            <div>
                                <div class="text-[12px] text-[#1a2a35]">Dr. Rini Sartika, M.T.</div>
                                <div class="text-[10px] text-[#8a9ba8]">Asesor Teknik Informatika</div>
                            </div>
                        </div>
                        <button class="w-full bg-primary hover:bg-[#005f78] text-white text-[12px] font-medium py-2 rounded-md transition-colors">
                            Lihat Detail Jadwal
                        </button>
                    </div>
                </div>
            </div>

        </div>

    </div>

</x-layouts.peserta>
