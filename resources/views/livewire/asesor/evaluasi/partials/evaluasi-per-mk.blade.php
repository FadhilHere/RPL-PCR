{{-- Evaluasi Per Mata Kuliah --}}
@foreach ($permohonan->rplMataKuliah as $rplMk)
@php
    $mk           = $rplMk->mataKuliah;
    $mkStatus     = $rplMk->status ?? \App\Enums\StatusRplMataKuliahEnum::Menunggu;
    $mkBadge      = $mkStatus->badgeClass();
    $mkBadgeLabel = $mkStatus->label();
@endphp
<div class="bg-white rounded-xl border border-[#E5E8EC] overflow-hidden mb-4" wire:key="rplmk-{{ $rplMk->id }}">

    {{-- Header MK --}}
    <div class="flex items-center gap-3 px-5 py-4 border-b border-[#F0F2F5] bg-[#FAFBFC]">
        <span class="text-[10px] font-semibold text-primary bg-[#E8F4F8] px-[7px] py-[3px] rounded shrink-0">{{ $mk->kode }}</span>
        <div class="flex-1">
            <div class="text-[13px] font-semibold text-[#1a2a35]">{{ $mk->nama }}</div>
            <div class="text-[11px] text-[#8a9ba8]">{{ $mk->sks }} SKS · Semester {{ $mk->semester }}</div>
        </div>
        <span class="text-[11px] font-semibold px-2.5 py-1 rounded-full {{ $mkBadge }}">{{ $mkBadgeLabel }}</span>
    </div>

    <div class="px-5 py-4">

        {{-- CPMK referensi --}}
        @if ($mk->cpmk->isNotEmpty())
        <div class="mb-5">
            <div class="text-[10px] font-semibold text-[#8a9ba8] uppercase tracking-[0.8px] mb-2">Capaian Pembelajaran (CPMK)</div>
            <div class="space-y-1.5">
                @foreach ($mk->cpmk as $cpmk)
                <div class="flex items-start gap-2" wire:key="cpmk-{{ $cpmk->id }}">
                    <span class="w-4 h-4 rounded-full bg-[#E8F4F8] text-primary text-[9px] font-semibold flex items-center justify-center shrink-0 mt-0.5">{{ $cpmk->urutan }}</span>
                    <span class="text-[12px] text-[#5a6a75] leading-[1.5]">{{ $cpmk->deskripsi }}</span>
                </div>
                @endforeach
            </div>
        </div>
        @endif

        {{-- Sub CPMK + VATM --}}
        @if ($rplMk->asesmenMandiri->isNotEmpty())
        <div class="mb-5">
            <div class="text-[10px] font-semibold text-[#8a9ba8] uppercase tracking-[0.8px] mb-3">Sub CPMK — Penilaian Diri Peserta & Evaluasi VATM</div>
            @foreach ($rplMk->asesmenMandiri as $asm)
            @php
                $pt   = $asm->pertanyaan;
                $vatm = $asm->evaluasiVatm;
                // skala 1-5 tanpa label, semakin besar semakin memahami
            @endphp
            <div class="py-3 border-b border-[#F6F8FA] last:border-0" wire:key="asm-{{ $asm->id }}">
                <div class="flex items-start gap-2 mb-2">
                    <span class="w-5 h-5 rounded-full bg-[#F0F2F5] text-[#5a6a75] text-[10px] font-semibold flex items-center justify-center shrink-0 mt-0.5">{{ $pt?->urutan ?? '-' }}</span>
                    <span class="flex-1 text-[12px] text-[#1a2a35] leading-[1.5]">{{ $pt?->pertanyaan ?? '—' }}</span>
                </div>
                @if (! empty($asm->referensi_berkas))
                @php $dokByName = $permohonan->peserta->dokumenBukti->keyBy('nama_dokumen'); @endphp
                <div class="ml-7 flex flex-wrap gap-1.5 mb-2">
                    @foreach ($asm->referensi_berkas as $namaRef)
                    @php $refDok = $dokByName[$namaRef] ?? null; @endphp
                    @if ($refDok)
                    @php $refVt = in_array(strtolower(pathinfo($refDok->berkas, PATHINFO_EXTENSION)), ['jpg','jpeg','png']) ? 'image' : 'pdf'; @endphp
                    <button @click="$dispatch('open-berkas-viewer', { url: '{{ route('berkas.view', $refDok) }}', type: '{{ $refVt }}', name: '{{ addslashes($refDok->nama_dokumen) }}' })"
                            class="inline-flex items-center gap-1 text-[10px] font-medium bg-[#E8F4F8] text-primary px-2 py-0.5 rounded-full hover:bg-primary hover:text-white transition-colors">
                        <svg class="w-2.5 h-2.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V8z"/><polyline points="14 2 14 8 20 8"/>
                        </svg>
                        {{ $namaRef }}
                    </button>
                    @else
                    <span class="inline-flex items-center gap-1 text-[10px] font-medium bg-[#E8F4F8] text-primary px-2 py-0.5 rounded-full">
                        <svg class="w-2.5 h-2.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V8z"/>
                            <polyline points="14 2 14 8 20 8"/>
                        </svg>
                        {{ $namaRef }}
                    </span>
                    @endif
                    @endforeach
                </div>
                @endif
                <div class="ml-7 flex items-center gap-4 flex-wrap">
                    {{-- Nilai peserta --}}
                    <span class="text-[11px] font-medium text-[#5a6a75] shrink-0">
                        Peserta: <span class="inline-flex items-center justify-center w-7 h-7 rounded-lg bg-[#E8F4F8] text-primary text-[12px] font-semibold">{{ $asm->penilaian_diri ?? '-' }}</span>
                        <span class="text-[#8a9ba8] text-[10px]">/ 5</span>
                    </span>

                    {{-- Nilai asesor 1-5 --}}
                    <div class="flex items-center gap-1"
                         x-data="{ nilai: {{ $nilaiAsesor[$asm->id] ?? 0 }} }">
                        <span class="text-[11px] font-medium text-[#5a6a75] mr-1 shrink-0">Asesor:</span>
                        @for ($n = 1; $n <= 5; $n++)
                        <button type="button"
                                @click="nilai = {{ $n }}; $wire.saveNilaiAsesor({{ $asm->id }}, {{ $n }})"
                                :class="nilai >= {{ $n }} ? 'bg-primary text-white border-primary' : 'bg-white text-[#D0D5DD] border-[#D8DDE2] hover:border-primary hover:text-primary'"
                                class="w-7 h-7 rounded-lg text-[12px] font-bold border transition-all flex items-center justify-center">
                            {{ $n }}
                        </button>
                        @endfor
                        <span class="text-[#8a9ba8] text-[10px] ml-0.5">/ 5</span>
                    </div>

                    {{-- VATM --}}
                    @php
                        $vatmState = [
                            'v' => $vatm?->valid    ?? false,
                            'a' => $vatm?->autentik ?? false,
                            't' => $vatm?->terkini  ?? false,
                            'm' => $vatm?->memadai  ?? false,
                        ];
                    @endphp
                    <div class="flex items-center gap-3 ml-auto"
                         x-data="{ vatm: @js($vatmState) }">
                        @foreach (['valid' => 'V', 'autentik' => 'A', 'terkini' => 'T', 'memadai' => 'M'] as $field => $label)
                        @php $key = substr($field, 0, 1); @endphp
                        <button type="button"
                                @click="vatm['{{ $key }}'] = !vatm['{{ $key }}']; $wire.saveVatm({{ $asm->id }}, '{{ $field }}', vatm['{{ $key }}'])"
                                :class="vatm['{{ $key }}'] ? 'bg-primary text-white border-primary' : 'bg-white text-[#5a6a75] border-[#D8DDE2] hover:border-primary hover:text-primary'"
                                class="flex items-center gap-1.5 px-2.5 py-1 rounded-lg text-[11px] font-semibold border transition-all"
                                title="{{ ucfirst($field) }}">
                            <span>{{ $label }}</span>
                            <svg x-show="vatm['{{ $key }}']" class="w-3 h-3" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round">
                                <polyline points="20 6 9 17 4 12"/>
                            </svg>
                        </button>
                        @endforeach
                    </div>
                </div>
            </div>
            @endforeach
        </div>
        @else
        <div class="text-[12px] text-[#8a9ba8] mb-5">Peserta belum mengisi asesmen mandiri untuk mata kuliah ini.</div>
        @endif

        {{-- Rekomendasi otomatis berdasarkan rata-rata nilai asesor (Poin 16) --}}
        @php
            $hitungAction = app(\App\Actions\Asesor\HitungKeputusanMkAction::class);
            $rataRata     = $hitungAction->rataRata($rplMk->load('asesmenMandiri.nilaiAsesor'));
            $rekomendasi  = $rataRata !== null ? $hitungAction->execute($rplMk) : null;
        @endphp
        @if ($rataRata !== null)
        <div class="mt-3 mb-1 flex items-center gap-3 px-1">
            <span class="text-[11px] text-[#8a9ba8]">
                Rata-rata nilai asesor:
                <span class="font-semibold text-[#1a2a35]">{{ $rataRata }}</span> / 5
            </span>
            <span class="text-[10px] font-semibold px-2 py-0.5 rounded-full
                {{ $rekomendasi === \App\Enums\StatusRplMataKuliahEnum::Diakui
                    ? 'bg-[#E6F4EA] text-[#1e7e3e]'
                    : 'bg-[#FCE8E6] text-[#c62828]' }}">
                Rekomendasi: {{ $rekomendasi?->label() }}
            </span>
            <span class="text-[10px] text-[#b0bec5]">— asesor dapat override di bawah</span>
        </div>
        @endif

        {{-- Set status MK (hanya saat dalam_review / disetujui) --}}
        @if (in_array($permohonan->status, [\App\Enums\StatusPermohonanEnum::DalamReview, \App\Enums\StatusPermohonanEnum::Disetujui]))
        <div class="border-t border-[#F0F2F5] pt-4">
            <div class="text-[10px] font-semibold text-[#8a9ba8] uppercase tracking-[0.8px] mb-3">Status & Catatan Asesor</div>
            <div class="flex items-start gap-3">
                <div class="w-[200px] shrink-0">
                    <x-form.select
                        wire:model="mkStatus.{{ $rplMk->id }}"
                        :options="collect(\App\Enums\StatusRplMataKuliahEnum::cases())->mapWithKeys(fn($e) => [$e->value => $e->label()])->all()"
                    />
                </div>
                <div class="flex-1">
                    <textarea wire:model="mkCatatan.{{ $rplMk->id }}"
                              rows="2"
                              placeholder="Catatan untuk peserta (opsional)..."
                              class="w-full px-3 py-2 text-[12px] text-[#1a2a35] bg-white border border-[#E0E5EA] rounded-xl outline-none focus:border-primary focus:ring-2 focus:ring-primary/10 transition resize-none"></textarea>
                </div>
                <button wire:click="saveMkStatus({{ $rplMk->id }})"
                        class="shrink-0 h-[42px] px-4 bg-primary hover:bg-[#005f78] text-white text-[12px] font-semibold rounded-xl transition-colors">
                    Simpan
                </button>
            </div>
        </div>
        @endif

    </div>
</div>
@endforeach
