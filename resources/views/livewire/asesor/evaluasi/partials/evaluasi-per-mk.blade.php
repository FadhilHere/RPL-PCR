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
    <div class="flex items-center gap-3 px-5 py-4 border-b border-[#F0F2F5] bg-[#FAFBFC]"
         x-data="{ badge: '{{ $mkBadge }}', label: '{{ $mkBadgeLabel }}' }"
         @mk-status-updated.window="if ($event.detail.mkId == {{ $rplMk->id }}) { badge = $event.detail.badge; label = $event.detail.label; }">
        <span class="text-[10px] font-semibold text-primary bg-[#E8F4F8] px-[7px] py-[3px] rounded shrink-0">{{ $mk->kode }}</span>
        <div class="flex-1">
            <div class="text-[13px] font-semibold text-[#1a2a35]">{{ $mk->nama }}</div>
            <div class="text-[11px] text-[#8a9ba8]">{{ $mk->sks }} SKS · Semester {{ $mk->semester }}</div>
        </div>
        <span class="text-[11px] font-semibold px-2.5 py-1 rounded-full" :class="badge" x-text="label"></span>
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

        {{-- Evaluasi VATM 1-5 Asesmen Mandiri (Selalu Dimunculkan) --}}
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
                    <div class="flex items-center gap-1" x-data="{ 
                        nilai: $wire.nilaiAsesor[{{ $asm->id }}],
                        timer: null,
                        updateNilai(n) {
                            this.nilai = n;
                            clearTimeout(this.timer);
                            this.timer = setTimeout(() => {
                                $wire.saveNilaiAsesor({{ $asm->id }}, n);
                            }, 500);
                        }
                    }">
                        <span class="text-[11px] font-medium text-[#5a6a75] mr-1 shrink-0">Asesor:</span>
                        @for ($n = 1; $n <= 5; $n++)
                        <button type="button"
                                @click="updateNilai({{ $n }})"
                                :class="nilai == {{ $n }} ? 'bg-primary text-white border-primary' : 'bg-white text-[#D0D5DD] border-[#D8DDE2] hover:border-primary hover:text-primary'"
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
                    <div class="flex items-center gap-3 ml-auto" x-data="{ 
                        vatm: @js($vatmState),
                        timers: {},
                        toggleVatm(key, field) {
                            this.vatm[key] = !this.vatm[key];
                            clearTimeout(this.timers[key]);
                            this.timers[key] = setTimeout(() => {
                                $wire.saveVatm({{ $asm->id }}, field, this.vatm[key]);
                            }, 500);
                        }
                    }">
                        @foreach (['valid' => 'V', 'autentik' => 'A', 'terkini' => 'T', 'memadai' => 'M'] as $field => $label)
                        @php $key = substr($field, 0, 1); @endphp
                        <button type="button"
                                @click="toggleVatm('{{ $key }}', '{{ $field }}')"
                                :class="vatm['{{ $key }}'] ? 'bg-primary text-white border-primary' : 'bg-white text-[#5a6a75] border-[#D8DDE2] hover:border-primary hover:text-primary'"
                                class="flex items-center gap-1.5 px-2.5 py-1 rounded-lg text-[11px] font-semibold border transition-all"
                                title="{{ ucfirst($field) }}">
                            <span>{{ $label }}</span>
                            <svg x-show="vatm['{{ $key }}']" x-cloak class="w-3 h-3" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round">
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

        {{-- Cek apakah pakai MK Lampau / PT Asal atau Asesmen Mandiri biasa --}}
        @if ($rplMk->has_mk_sejenis && $rplMk->matkulLampau->isNotEmpty())
        <div class="mb-5 border-t border-[#F0F2F5] pt-5">
            <div class="text-[10px] font-semibold text-[#8a9ba8] uppercase tracking-[0.8px] mb-2">MK di PT Asal yang Diajukan Peserta</div>
            <div class="bg-[#F4F6F8] rounded-xl overflow-hidden mb-4 border border-[#E5E8EC]">
                <table class="w-full text-[12px]">
                    <thead>
                        <tr class="border-b border-[#E5E8EC]">
                            <th class="text-left font-semibold text-[#8a9ba8] px-4 py-2.5">Kode MK</th>
                            <th class="text-left font-semibold text-[#8a9ba8] px-4 py-2.5">Nama MK PT Asal</th>
                            <th class="text-center font-semibold text-[#8a9ba8] px-4 py-2.5 w-20">SKS</th>
                            <th class="text-center font-semibold text-[#8a9ba8] px-4 py-2.5 w-28">Nilai Peserta</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($rplMk->matkulLampau as $ml)
                        <tr class="border-b border-[#EFF1F3] last:border-0 bg-white">
                            <td class="px-4 py-3 text-[#5a6a75] font-medium">{{ $ml->kode_mk }}</td>
                            <td class="px-4 py-3 text-[#1a2a35] font-semibold">{{ $ml->nama_mk }}</td>
                            <td class="px-4 py-3 text-center text-[#5a6a75]">{{ $ml->sks }}</td>
                            <td class="px-4 py-3 text-center">
                                <span class="inline-flex items-center justify-center w-7 h-7 rounded-lg bg-[#E8F4F8] text-primary text-[12px] font-bold">{{ $ml->nilai_huruf?->value ?? '-' }}</span>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            {{-- Ringkasan MK Tujuan (PCR) --}}
            <div class="bg-[#E8F4F8] rounded-xl border border-[#BDE0EB] p-4 mb-4 flex items-center gap-4">
                <div class="w-10 h-10 rounded-full bg-white flex items-center justify-center shrink-0 shadow-sm">
                    <svg class="w-5 h-5 text-primary" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M4 19.5v-15A2.5 2.5 0 016.5 2H20v20H6.5a2.5 2.5 0 01-2.5-2.5z"/><path d="M6.5 2H20v20H6.5A2.5 2.5 0 014 19.5v-15A2.5 2.5 0 016.5 2z"/>
                    </svg>
                </div>
                <div>
                    <div class="text-[10px] font-semibold text-primary uppercase tracking-[0.8px] mb-1">Mata Kuliah Tujuan (PCR)</div>
                    <div class="flex items-center gap-2 text-[13px]">
                        <span class="font-bold text-[#1a2a35]">{{ $mk->kode }}</span>
                        <span class="text-[#8a9ba8]">&mdash;</span>
                        <span class="font-semibold text-[#1a2a35]">{{ $mk->nama }}</span>
                        <span class="px-2 py-0.5 rounded-md bg-white text-primary text-[11px] font-bold border border-[#BDE0EB] ml-2">Semester {{ $mk->semester }}</span>
                        <span class="px-2 py-0.5 rounded-md bg-white text-primary text-[11px] font-bold border border-[#BDE0EB]">{{ $mk->sks }} SKS</span>
                    </div>
                </div>
            </div>

            {{-- Input Konversi Asesor --}}
            <div class="bg-[#FAFBFC] rounded-xl border border-[#F0F2F5] p-4 flex flex-wrap items-end gap-4">
                <div>
                    <label class="block text-[11px] font-semibold text-[#5a6a75] uppercase tracking-[0.7px] mb-2">Konversi Nilai Asesor</label>
                    <div class="flex gap-1.5">
                        @foreach ($nilaiHurufOptions as $opt)
                        <button type="button"
                                wire:click="$set('nilaiTransfer.{{ $rplMk->id }}', '{{ $opt->value }}')"
                                class="w-10 h-10 rounded-lg text-[12px] font-bold border transition-all
                                       {{ ($nilaiTransfer[$rplMk->id] ?? '') === $opt->value
                                           ? 'bg-primary border-primary text-white'
                                           : 'bg-white border-[#D0D5DD] text-[#5a6a75] hover:border-primary hover:text-primary' }}">
                            {{ $opt->value }}
                        </button>
                        @endforeach
                    </div>
                    @error("nilaiTransfer.{$rplMk->id}") <p class="mt-1 text-[11px] text-[#c62828]">{{ $message }}</p> @enderror
                </div>
                <div class="flex-1 min-w-[200px]">
                    <label class="block text-[11px] font-semibold text-[#5a6a75] uppercase tracking-[0.7px] mb-2">Catatan Asesor (Opsional)</label>
                    <input wire:model="mkCatatan.{{ $rplMk->id }}" type="text" placeholder="Catatan konversi nilai..."
                           class="w-full h-[40px] px-3 text-[12px] text-[#1a2a35] bg-white border border-[#E0E5EA] rounded-lg outline-none focus:border-primary focus:ring-1 focus:ring-primary/10" />
                </div>
                <button wire:click="simpanNilaiTransfer({{ $rplMk->id }})"
                        class="h-[40px] px-5 bg-primary hover:bg-[#005f78] text-white text-[12px] font-semibold rounded-lg transition-colors shrink-0">
                    Simpan Nilai
                </button>
            </div>
        </div>
        @endif

        {{-- Set status MK / Rekomendasi otomatis --}}
        @if (in_array($permohonan->status, [\App\Enums\StatusPermohonanEnum::DalamReview, \App\Enums\StatusPermohonanEnum::Disetujui]))
            @if (! ($rplMk->has_mk_sejenis && $rplMk->matkulLampau->isNotEmpty()))
                {{-- Hanya tampilkan ringkasan rata-rata VATM jika bukan hybrid MK Lampau --}}
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
                    <span class="text-[10px] text-[#b0bec5]">— (Status final dapat di-override di bawah jika perlu)</span>
                </div>
                @endif
            @endif

            {{-- Set Status Override Asesor --}}
            <div class="border-t border-[#F0F2F5] pt-4 mt-4">
                <div class="text-[10px] font-semibold text-[#8a9ba8] uppercase tracking-[0.8px] mb-3">Timpa Status / Catatan Khusus MK</div>
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
                                  placeholder="Tambahkan catatan jika diperlukan..."
                                  class="w-full px-3 py-2 text-[12px] text-[#1a2a35] bg-white border border-[#E0E5EA] rounded-xl outline-none focus:border-primary focus:ring-2 focus:ring-primary/10 transition resize-none"></textarea>
                    </div>
                    <button wire:click="saveMkStatus({{ $rplMk->id }})"
                            class="shrink-0 h-[42px] px-4 bg-primary hover:bg-[#005f78] text-white text-[12px] font-semibold rounded-xl transition-colors">
                        Simpan Status Override
                    </button>
                </div>
            </div>
        @endif

    </div>
</div>
@endforeach
