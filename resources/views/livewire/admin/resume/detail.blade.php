<?php

use Livewire\Attributes\Layout;
use Livewire\Volt\Component;
use App\Actions\Asesor\HitungKeputusanMkAction;
use App\Enums\StatusRplMataKuliahEnum;
use App\Models\PermohonanRpl;

new #[Layout('components.layouts.admin')] class extends Component {

    public PermohonanRpl $permohonan;

    public function mount(PermohonanRpl $permohonan): void
    {
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
                'rplMk'       => $rplMk,
                'mk'          => $rplMk->mataKuliah,
                'rataRata'    => $rataRata,
                'rataPeserta' => $rataPeserta ? round($rataPeserta, 2) : null,
                'rekomendasi' => $rekomendasi,
                'statusAkhir' => $rplMk->status,
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
    <a href="{{ route('admin.resume.index') }}" class="text-primary hover:underline">Resume</a>
    &rsaquo; {{ $permohonan->nomor_permohonan }}
</x-slot:subtitle>

<div>

    {{-- Resume Card --}}
    <div class="bg-white rounded-xl border border-[#E5E8EC] p-6">

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
                <div>
                    <span class="text-[#8a9ba8]">Status Permohonan:</span>
                    <span class="ml-2">
                        <span class="text-[11px] font-semibold px-2 py-0.5 rounded-full {{ $permohonan->status->badgeClass() }}">
                            {{ $permohonan->status->label() }}
                        </span>
                    </span>
                </div>
            </div>
        </div>

        {{-- Tabel per MK --}}
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
                        @forelse ($mkData as $item)
                        @php
                            $efektifStatus = $item['statusAkhir'] ?? $item['rekomendasi'];
                            $isDiakui      = $efektifStatus === \App\Enums\StatusRplMataKuliahEnum::Diakui;
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
                        @empty
                        <tr>
                            <td colspan="6" class="py-8 text-center text-[12px] text-[#8a9ba8]">Belum ada mata kuliah yang di-assign.</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        {{-- Summary --}}
        @if ($mkData->isNotEmpty())
        <div class="bg-[#F4F6F8] rounded-xl p-5 flex items-center gap-8">
            <div class="text-center">
                <div class="text-[24px] font-bold text-primary">{{ $totalSksDiakui }}</div>
                <div class="text-[11px] text-[#8a9ba8]">SKS Diakui</div>
            </div>
            <div class="text-[#D0D5DD] text-xl">/</div>
            <div class="text-center">
                <div class="text-[24px] font-bold text-[#5a6a75]">{{ $totalSks }}</div>
                <div class="text-[11px] text-[#8a9ba8]">Total SKS</div>
            </div>
            @if ($mkDiakui->isNotEmpty())
            <div class="flex-1 border-l border-[#E5E8EC] pl-8">
                <div class="text-[11px] font-semibold text-[#8a9ba8] uppercase tracking-[0.5px] mb-1.5">Mata Kuliah Diakui</div>
                <div class="text-[12px] text-[#1a2a35] leading-[1.7]">
                    @foreach ($mkDiakui->values() as $item)
                    <span>{{ $item['mk']->nama }} <span class="text-[#8a9ba8]">({{ $item['mk']->sks }} SKS)</span>{{ ! $loop->last ? ',' : '' }}</span>
                    @endforeach
                </div>
            </div>
            @endif
        </div>
        @endif

    </div>

    {{-- Back --}}
    <div class="mt-4">
        <a href="{{ route('admin.resume.index') }}"
           class="text-[13px] text-[#5a6a75] hover:text-primary transition-colors no-underline">
            ← Kembali ke Resume
        </a>
    </div>

</div>
