<?php

use Livewire\Attributes\Layout;
use Livewire\Volt\Component;
use App\Actions\Asesor\HitungKeputusanMkAction;
use App\Enums\StatusRplMataKuliahEnum;
use App\Models\Asesor;
use App\Models\PermohonanRpl;

new #[Layout('components.layouts.asesor')] class extends Component {

    public PermohonanRpl $permohonan;

    public function mount(PermohonanRpl $permohonan): void
    {
        $asesorId   = auth()->user()->asesor?->id;
        $isAssigned = $asesorId && Asesor::find($asesorId)
            ->programStudi()->where('program_studi_id', $permohonan->program_studi_id)->exists();

        if (! $isAssigned) {
            abort(403, 'Anda tidak ditugaskan ke prodi dari permohonan ini.');
        }

        $this->permohonan = $permohonan->load([
            'peserta.user',
            'programStudi',
            'tahunAjaran',
            'rplMataKuliah.mataKuliah',
            'rplMataKuliah.asesmenMandiri.nilaiAsesor',
            'rplMataKuliah.asesmenMandiri.pertanyaan',
        ]);
    }

    public function with(): array
    {
        $hitungAction = app(HitungKeputusanMkAction::class);

        $mkData = $this->permohonan->rplMataKuliah->map(function ($rplMk) use ($hitungAction) {
            $rataRata    = $hitungAction->rataRata($rplMk);
            $rekomendasi = $rataRata !== null ? $hitungAction->execute($rplMk) : null;
            $rataPeserta = $rplMk->asesmenMandiri->avg('penilaian_diri');

            return [
                'rplMk'        => $rplMk,
                'mk'           => $rplMk->mataKuliah,
                'rataRata'     => $rataRata,
                'rataPeserta'  => $rataPeserta ? round($rataPeserta, 2) : null,
                'rekomendasi'  => $rekomendasi,
                'statusAkhir'  => $rplMk->status,
            ];
        });

        $mkDiakui = $mkData->filter(fn($d) =>
            ($d['statusAkhir'] ?? $d['rekomendasi']) === StatusRplMataKuliahEnum::Diakui
        );

        $totalSks       = $this->permohonan->rplMataKuliah->sum(fn($m) => $m->mataKuliah->sks ?? 0);
        $totalSksDiakui = $mkDiakui->sum(fn($d) => $d['mk']->sks ?? 0);

        return compact('mkData', 'mkDiakui', 'totalSks', 'totalSksDiakui');
    }
}; ?>

<x-slot:title>Detail Resume</x-slot:title>
<x-slot:subtitle>
    <a href="{{ route('asesor.pengajuan.index') }}" class="text-primary hover:underline">Pengajuan RPL</a>
    &rsaquo; <a href="{{ route('asesor.evaluasi.index', $permohonan) }}" class="text-primary hover:underline">{{ $permohonan->nomor_permohonan }}</a>
    &rsaquo; Resume
</x-slot:subtitle>

<div>

    {{-- Print button (commented out) --}}
    <!-- <div class="flex justify-end mb-4 print:hidden">
        <button onclick="window.print()"
                class="inline-flex items-center gap-2 h-[38px] px-4 bg-primary hover:bg-[#005f78] text-white text-[13px] font-semibold rounded-xl transition-colors">
            <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <polyline points="6 9 6 2 18 2 18 9"/><path d="M6 18H4a2 2 0 01-2-2v-5a2 2 0 012-2h16a2 2 0 012 2v5a2 2 0 01-2 2h-2"/>
                <rect x="6" y="14" width="12" height="8"/>
            </svg>
            Cetak
        </button>
    </div> -->

    {{-- Resume Card --}}
    <div class="bg-white rounded-xl border border-[#E5E8EC] p-6 print:shadow-none print:border-0">

        {{-- Header --}}
        <div class="border-b border-[#F0F2F5] pb-5 mb-5">
            <h2 class="text-[16px] font-bold text-[#1a2a35] mb-1">Resume Asesmen RPL</h2>
            <div class="grid grid-cols-2 gap-x-8 gap-y-2 mt-4 text-[12px]">
                <div>
                    <span class="text-[#8a9ba8]">Peserta:</span>
                    <span class="ml-2 font-semibold text-[#1a2a35]">{{ $permohonan->peserta->user->nama ?? '-' }}</span>
                </div>
                <div>
                    <span class="text-[#8a9ba8]">No. Permohonan:</span>
                    <span class="ml-2 font-semibold text-[#1a2a35]">{{ $permohonan->nomor_permohonan }}</span>
                </div>
                <div>
                    <span class="text-[#8a9ba8]">Program Studi:</span>
                    <span class="ml-2 font-semibold text-[#1a2a35]">{{ $permohonan->programStudi->nama ?? '-' }}</span>
                </div>
                <div>
                    <span class="text-[#8a9ba8]">Tahun Ajaran:</span>
                    <span class="ml-2 font-semibold text-[#1a2a35]">
                        {{ $permohonan->tahunAjaran?->nama ?? '-' }}
                        @if ($permohonan->semester)
                        — Semester {{ $permohonan->semester->label() }}
                        @endif
                    </span>
                </div>
            </div>
        </div>

        {{-- Tabel perbandingan per MK --}}
        <div class="mb-6">
            <h3 class="text-[13px] font-semibold text-[#1a2a35] mb-3">Perbandingan Nilai per Mata Kuliah</h3>
            <div class="overflow-x-auto">
                <table class="w-full text-[12px]">
                    <thead>
                        <tr class="border-b border-[#E5E8EC]">
                            <th class="text-left py-2 pr-4 text-[10px] font-semibold text-[#8a9ba8] uppercase tracking-[0.5px]">Mata Kuliah</th>
                            <th class="text-center py-2 px-3 text-[10px] font-semibold text-[#8a9ba8] uppercase tracking-[0.5px]">SKS</th>
                            <th class="text-center py-2 px-3 text-[10px] font-semibold text-[#8a9ba8] uppercase tracking-[0.5px]">Rata-rata Peserta</th>
                            <th class="text-center py-2 px-3 text-[10px] font-semibold text-[#8a9ba8] uppercase tracking-[0.5px]">Rata-rata Asesor</th>
                            <th class="text-center py-2 px-3 text-[10px] font-semibold text-[#8a9ba8] uppercase tracking-[0.5px]">Rekomendasi</th>
                            <th class="text-center py-2 pl-3 text-[10px] font-semibold text-[#8a9ba8] uppercase tracking-[0.5px]">Status Akhir</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($mkData as $item)
                        @php
                            $efektifStatus = $item['statusAkhir'] ?? $item['rekomendasi'];
                            $isDiakui = $efektifStatus === \App\Enums\StatusRplMataKuliahEnum::Diakui;
                        @endphp
                        <tr class="border-b border-[#F6F8FA] last:border-0">
                            <td class="py-2.5 pr-4">
                                <div class="font-medium text-[#1a2a35]">{{ $item['mk']->nama }}</div>
                                <div class="text-[10px] text-[#8a9ba8]">{{ $item['mk']->kode }}</div>
                            </td>
                            <td class="py-2.5 px-3 text-center text-[#5a6a75]">{{ $item['mk']->sks }}</td>
                            <td class="py-2.5 px-3 text-center">
                                <span class="font-semibold {{ $item['rataPeserta'] !== null ? 'text-[#1a2a35]' : 'text-[#b0bec5]' }}">
                                    {{ $item['rataPeserta'] ?? '—' }}
                                </span>
                            </td>
                            <td class="py-2.5 px-3 text-center">
                                <span class="font-semibold {{ $item['rataRata'] !== null ? 'text-primary' : 'text-[#b0bec5]' }}">
                                    {{ $item['rataRata'] ?? '—' }}
                                </span>
                            </td>
                            <td class="py-2.5 px-3 text-center">
                                @if ($item['rekomendasi'])
                                <span class="text-[10px] font-semibold px-2 py-0.5 rounded-full
                                    {{ $item['rekomendasi'] === \App\Enums\StatusRplMataKuliahEnum::Diakui ? 'bg-[#E6F4EA] text-[#1e7e3e]' : 'bg-[#FCE8E6] text-[#c62828]' }}">
                                    {{ $item['rekomendasi']->label() }}
                                </span>
                                @else
                                <span class="text-[#b0bec5]">—</span>
                                @endif
                            </td>
                            <td class="py-2.5 pl-3 text-center">
                                @if ($item['statusAkhir'] && $item['statusAkhir'] !== \App\Enums\StatusRplMataKuliahEnum::Menunggu)
                                <span class="text-[10px] font-semibold px-2 py-0.5 rounded-full {{ $item['statusAkhir']->badgeClass() }}">
                                    {{ $item['statusAkhir']->label() }}
                                </span>
                                @else
                                <span class="text-[10px] text-[#b0bec5]">Belum diset</span>
                                @endif
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>

        {{-- Summary --}}
        <div class="bg-[#F4F6F8] rounded-xl p-5">
             <h3 class="text-[13px] font-semibold text-[#1a2a35] mb-3">Ringkasan Keputusan</h3>
            <div class="flex items-center gap-6 mb-4">
                <div class="text-center">
                    <div class="text-[24px] font-bold text-primary">{{ $totalSksDiakui }}</div>
                    <div class="text-[11px] text-[#8a9ba8]">SKS Diakui</div>
                </div>
                <div class="text-[#D0D5DD] text-xl">/</div>
                <div class="text-center">
                    <div class="text-[24px] font-bold text-[#5a6a75]">{{ $totalSks }}</div>
                    <div class="text-[11px] text-[#8a9ba8]">Total SKS</div>
                </div>
            </div>
            @if ($mkDiakui->isNotEmpty())
            <div class="text-[12px] text-[#1a2a35] leading-[1.8]">
                <span class="font-semibold">LULUS {{ $permohonan->programStudi->nama ?? '' }};</span>
                <span> {{ $totalSksDiakui }} SKS:</span>
                @foreach ($mkDiakui->values() as $i => $item)
                <span>{{ $i + 1 }}. {{ $item['mk']->nama }} ({{ $item['mk']->sks }} SKS){{ ! $loop->last ? ',' : '.' }}</span>
                @endforeach
            </div>
            @else
            <p class="text-[12px] text-[#8a9ba8]">Belum ada mata kuliah yang diakui.</p>
            @endif
        </div>

    </div>

    {{-- Back --}}
    <div class="mt-4 print:hidden">
        <a href="{{ route('asesor.evaluasi.index', $permohonan) }}"
           class="text-[13px] text-[#5a6a75] hover:text-primary transition-colors no-underline">
            ← Kembali ke Evaluasi
        </a>
    </div>

    <style>
    @media print {
        body { background: white; }
        .print\:hidden { display: none !important; }
    }
    </style>

</div>
