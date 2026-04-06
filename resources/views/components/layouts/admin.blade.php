@props(['title' => 'Dashboard', 'subtitle' => null])

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title }} — Sistem RPL PCR</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet"/>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <link rel="stylesheet" href="https://unpkg.com/nprogress@0.2.0/nprogress.css">
    <style>
        #nprogress .bar { background: #004B5F !important; height: 2.5px !important; }
        #nprogress .peg { box-shadow: 0 0 10px #004B5F, 0 0 5px #004B5F !important; }
    </style>
    <script src="https://unpkg.com/nprogress@0.2.0/nprogress.js"></script>
    <script>NProgress.configure({ showSpinner: false, trickleSpeed: 150 }); NProgress.start();</script>
</head>
<body class="antialiased bg-[#F4F6F8]">
<div x-data="{ open: false }">

    {{-- ===================== OVERLAY (mobile) ===================== --}}
    <div x-show="open" @click="open = false" style="display:none"
         x-transition:enter="transition-opacity duration-200" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
         x-transition:leave="transition-opacity duration-200" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0"
         class="fixed inset-0 z-30 bg-black/40 lg:hidden"></div>

    {{-- ===================== SIDEBAR ===================== --}}
    <aside :class="open ? 'translate-x-0' : '-translate-x-full lg:translate-x-0'"
           class="fixed inset-y-0 left-0 w-[240px] bg-primary flex flex-col overflow-y-auto z-40 transition-transform duration-200 ease-in-out">

        {{-- Logo --}}
        <div class="flex items-center gap-2.5 px-5 py-[18px] border-b border-white/10">
            <div class="w-8 h-8 bg-white rounded-md flex items-center justify-center shrink-0">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#004B5F" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M12 2L2 7l10 5 10-5-10-5z"/>
                    <path d="M2 17l10 5 10-5"/>
                    <path d="M2 12l10 5 10-5"/>
                </svg>
            </div>
            <div>
                <div class="text-white font-semibold text-[13px] leading-[1.3]">Sistem RPL</div>
                <div class="text-[10px] text-white/60 uppercase tracking-[0.5px]">Politeknik Caltex Riau</div>
            </div>
        </div>

        {{-- Navigasi --}}
        <nav class="flex-1 py-2">

            <div class="px-3 pt-5 pb-2 text-[10px] font-semibold text-white/35 uppercase tracking-[1px]">Menu</div>

            @php
                $menus = [
                    [
                        'route' => 'admin.dashboard',
                        'label' => 'Dashboard',
                        'icon'  => '<rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="14" y="14" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/>',
                        'match' => 'admin.dashboard',
                    ],
                    [
                        'route' => 'admin.materi.index',
                        'label' => 'Materi Asesmen',
                        'icon'  => '<path d="M4 19.5A2.5 2.5 0 016.5 17H20"/><path d="M6.5 2H20v20H6.5A2.5 2.5 0 014 19.5v-15A2.5 2.5 0 016.5 2z"/>',
                        'match' => 'admin.materi.*',
                    ],
                    [
                        'route' => 'admin.pengajuan.index',
                        'label' => 'Semua Pengajuan',
                        'icon'  => '<path d="M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/>',
                        'match' => 'admin.pengajuan.*',
                    ],
                    [
                        'route' => 'admin.jadwal.index',
                        'label' => 'Jadwal Verifikasi',
                        'icon'  => '<rect x="3" y="4" width="18" height="18" rx="2" ry="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/>',
                        'match' => 'admin.jadwal.*',
                    ],
                    [
                        'route' => 'admin.pleno.index',
                        'label' => 'Resume',
                        'icon'  => '<path d="M9 11l3 3L22 4"/><path d="M21 12v7a2 2 0 01-2 2H5a2 2 0 01-2-2V5a2 2 0 012-2h11"/>',
                        'match' => 'admin.pleno.*',
                    ],
                    [
                        'route' => 'admin.akun.index',
                        'label' => 'Kelola Akun',
                        'icon'  => '<path d="M17 21v-2a4 4 0 00-4-4H5a4 4 0 00-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 00-3-3.87"/><path d="M16 3.13a4 4 0 010 7.75"/>',
                        'match' => 'admin.akun.*',
                    ],
                    [
                        'route' => 'admin.prodi.index',
                        'label' => 'Program Studi',
                        'icon'  => '<path d="M22 10v6M2 10l10-5 10 5-10 5z"/><path d="M6 12v5c3 3 9 3 12 0v-5"/>',
                        'match' => 'admin.prodi.*',
                    ],
                    [
                        'route' => 'admin.tahun-ajaran.index',
                        'label' => 'Tahun Ajaran',
                        'icon'  => '<circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/>',
                        'match' => 'admin.tahun-ajaran.*',
                    ],
                ];
            @endphp

            @foreach ($menus as $item)
                @php $active = request()->routeIs($item['match']); @endphp
                <a href="{{ $item['route'] ? route($item['route']) : '#' }}"
                   @click="open = false"
                   class="flex items-center gap-2.5 mx-2 px-3 py-[9px] rounded-md text-[13px] transition-all no-underline
                          {{ $active
                              ? 'bg-white/10 text-white font-medium'
                              : 'text-white/70 hover:bg-white/[0.07] hover:text-white' }}">
                    <svg class="w-4 h-4 shrink-0 {{ $active ? 'opacity-100' : 'opacity-80' }}"
                         viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        {!! $item['icon'] !!}
                    </svg>
                    {{ $item['label'] }}
                </a>
            @endforeach

        </nav>

        {{-- Footer sidebar: user + logout --}}
        <div class="border-t border-white/10 p-3">
            @php
                $user     = auth()->user();
                $initials = collect(explode(' ', $user->nama))->take(2)->map(fn($w) => strtoupper($w[0]))->implode('');
            @endphp
            <div class="flex items-center gap-2.5 px-2 py-2 rounded-md">
                <div class="w-8 h-8 rounded-full bg-white/20 flex items-center justify-center shrink-0 text-[12px] font-semibold text-white">
                    {{ $initials }}
                </div>
                <div class="flex-1 min-w-0">
                    <div class="text-white text-[12px] font-medium truncate">{{ $user->nama }}</div>
                    <div class="text-white/50 text-[10px]">Administrator</div>
                </div>
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" title="Keluar"
                            class="text-white/50 hover:text-white transition-colors flex items-center justify-center p-1">
                        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M9 21H5a2 2 0 01-2-2V5a2 2 0 012-2h4"/>
                            <polyline points="16 17 21 12 16 7"/>
                            <line x1="21" y1="12" x2="9" y2="12"/>
                        </svg>
                    </button>
                </form>
            </div>
        </div>

    </aside>

    {{-- ===================== MAIN AREA ===================== --}}
    <div class="lg:ml-[240px] min-h-screen flex flex-col">

        {{-- Topbar --}}
        <header class="sticky top-0 z-30 h-14 shrink-0 bg-white border-b border-[#E5E8EC] flex items-center justify-between px-4 lg:px-7">
            <div class="flex items-center gap-3 min-w-0">
                <button @click="open = true"
                        class="lg:hidden w-8 h-8 shrink-0 flex items-center justify-center rounded-md hover:bg-[#F4F6F8] transition-colors">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#5f6368" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <line x1="3" y1="6" x2="21" y2="6"/><line x1="3" y1="12" x2="21" y2="12"/><line x1="3" y1="18" x2="21" y2="18"/>
                    </svg>
                </button>
                <div class="min-w-0">
                    <div class="text-[15px] font-semibold text-[#1a2a35] truncate">{{ $title }}</div>
                    @if ($subtitle)
                        <div class="text-[12px] text-[#8a9ba8] mt-px truncate">{!! $subtitle !!}</div>
                    @endif
                </div>
            </div>
            <div class="flex items-center gap-3 shrink-0">
                <button class="relative w-[34px] h-[34px] rounded-md border border-[#E5E8EC] bg-white flex items-center justify-center hover:bg-[#F4F6F8] transition-colors">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#5f6368" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M18 8A6 6 0 006 8c0 7-3 9-3 9h18s-3-2-3-9"/>
                        <path d="M13.73 21a2 2 0 01-3.46 0"/>
                    </svg>
                    <span class="absolute top-1.5 right-1.5 w-[7px] h-[7px] bg-accent rounded-full border-[1.5px] border-white"></span>
                </button>
            </div>
        </header>

        {{-- Konten halaman --}}
        <main class="flex-1 p-4 lg:p-7">
            {{ $slot }}
        </main>

    </div>

</div>

<script>
    window.addEventListener('load', () => NProgress.done());
    document.addEventListener('click', function (e) {
        const a = e.target.closest('a[href]');
        if (!a || a.hasAttribute('wire:navigate') || a.target === '_blank' || e.metaKey || e.ctrlKey || e.shiftKey) return;
        const href = a.getAttribute('href');
        if (!href || href.startsWith('#') || href.startsWith('javascript')) return;
        NProgress.start();
    });
    document.addEventListener('submit', function (e) {
        if (!e.target.hasAttribute('wire:submit') && !e.target.closest('[wire\\:submit]')) NProgress.start();
    });
</script>
</body>
</html>
