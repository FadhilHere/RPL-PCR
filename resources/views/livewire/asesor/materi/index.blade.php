<?php

use Livewire\Attributes\Layout;
use Livewire\Volt\Component;
use App\Models\ProgramStudi;

new #[Layout('components.layouts.asesor')] class extends Component {
    public function with(): array
    {
        $asesorId = auth()->user()->asesor?->id;

        return [
            'prodis' => ProgramStudi::withCount('mataKuliah')
                ->whereHas('asesors', fn($q) => $q->where('asesor_id', $asesorId))
                ->where('aktif', true)
                ->orderBy('nama')
                ->get(),
        ];
    }
}; ?>

<x-slot:title>Materi Asesmen</x-slot:title>
<x-slot:subtitle>Kelola mata kuliah dan CPK per program studi</x-slot:subtitle>

<div>
    @if ($prodis->isEmpty())
    <div class="bg-white rounded-xl border border-[#E5E8EC] px-5 py-12 text-center text-[13px] text-[#8a9ba8]">
        Anda belum ditugaskan ke program studi manapun. Hubungi admin untuk pengaturan.
    </div>
    @else
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
        @foreach ($prodis as $prodi)
        <a href="{{ route('asesor.materi.prodi', $prodi->id) }}"
           class="group bg-white border border-[#E5E8EC] hover:border-primary rounded-xl p-5 transition-all no-underline hover:shadow-sm">
            <div class="flex items-start justify-between mb-3">
                <div class="w-10 h-10 rounded-lg bg-[#E8F4F8] flex items-center justify-center shrink-0">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#004B5F" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M4 19.5A2.5 2.5 0 016.5 17H20"/>
                        <path d="M6.5 2H20v20H6.5A2.5 2.5 0 014 19.5v-15A2.5 2.5 0 016.5 2z"/>
                    </svg>
                </div>
                <span class="text-[10px] font-semibold px-2 py-0.5 rounded-full
                    {{ $prodi->jenjang === 'S2' ? 'bg-[#E8F0FE] text-[#1557b0]' : 'bg-[#E6F4EA] text-[#1e7e3e]' }}">
                    {{ $prodi->jenjang }}
                </span>
            </div>
            <div class="text-[13px] font-semibold text-[#1a2a35] leading-[1.4] mb-1 group-hover:text-primary transition-colors">
                {{ $prodi->nama }}
            </div>
            <div class="text-[11px] text-[#8a9ba8] mb-3">Kode: {{ $prodi->kode }} · {{ $prodi->total_sks }} SKS</div>
            <div class="flex items-center justify-between">
                <span class="text-[12px] text-[#5a6a75]">
                    <span class="font-semibold text-[#1a2a35]">{{ $prodi->mata_kuliah_count }}</span> mata kuliah
                </span>
                <span class="text-[12px] text-primary font-medium group-hover:underline">Kelola →</span>
            </div>
        </a>
        @endforeach
    </div>
    @endif
</div>
