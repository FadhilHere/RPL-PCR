{{-- Evaluasi Per Mata Kuliah --}}
@foreach ($permohonan->rplMataKuliah as $rplMk)
@php
    $mk           = $rplMk->mataKuliah;
    $mkStatus     = $rplMk->status ?? \App\Enums\StatusRplMataKuliahEnum::Menunggu;
    $mkBadge      = $mkStatus->badgeClass();
    $mkBadgeLabel = $mkStatus->label();
@endphp
<div class="bg-white rounded-xl border border-[#E5E8EC] overflow-hidden mb-4" wire:key="rplmk-{{ $rplMk->id }}" x-data="{ open: false }">

    {{-- Header MK --}}
    <div class="flex items-center gap-3 px-5 py-4 border-b border-[#F0F2F5] bg-[#FAFBFC] cursor-pointer select-none"
         x-data="{ badge: '{{ $mkBadge }}', label: '{{ $mkBadgeLabel }}' }"
         @click="open = !open"
         @mk-status-updated.window="if ($event.detail.mkId == {{ $rplMk->id }}) { badge = $event.detail.badge; label = $event.detail.label; }">
        <span class="text-[10px] font-semibold text-primary bg-[#E8F4F8] px-[7px] py-[3px] rounded shrink-0">{{ $mk->kode }}</span>
        <div class="flex-1">
            <div class="text-[13px] font-semibold text-[#1a2a35]">{{ $mk->nama }}</div>
            <div class="text-[11px] text-[#8a9ba8]">{{ $mk->sks }} SKS · Semester {{ $mk->semester }}</div>
        </div>
        @if ($rplMk->matkulLampau->isNotEmpty())
        <span class="text-[10px] font-semibold px-2 py-0.5 rounded-full bg-[#E8F4F8] text-primary shrink-0">Ada MK Lampau</span>
        @endif
        <span class="text-[11px] font-semibold px-2.5 py-1 rounded-full shrink-0" :class="badge" x-text="label"></span>
        <svg :class="open ? 'rotate-180' : ''" class="w-4 h-4 text-[#8a9ba8] shrink-0 transition-transform duration-200" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <polyline points="6 9 12 15 18 9"/>
        </svg>
    </div>

    <div class="px-5 py-4"
         x-show="open"
         x-transition:enter="transition ease-out duration-200"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition ease-in duration-150"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0">

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
                        pending: false,
                        queued: null,
                        lastSent: $wire.nilaiAsesor[{{ $asm->id }}] ?? null,
                        updateNilai(n) {
                            this.nilai = n;
                            this.$dispatch('nilai-asesor-updated', {
                                mkId: {{ $rplMk->id }},
                                asmId: {{ $asm->id }},
                                nilai: n
                            });
                            this.queued = n;
                            clearTimeout(this.timer);
                            this.timer = setTimeout(() => this.flushQueue(), 1000);
                        },
                        flushQueue() {
                            if (this.pending) return;
                            if (this.queued === null || this.queued === this.lastSent) return;
                            this.pending = true;
                            const val = this.queued;
                            this.queued = null;
                            $wire.saveNilaiAsesor({{ $asm->id }}, val).then(() => {
                                this.lastSent = val;
                                this.pending = false;
                                if (this.queued !== null && this.queued !== this.lastSent) {
                                    this.flushQueue();
                                }
                            });
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

        {{-- MK Lampau (selalu tampil, editable) --}}
        <div class="mb-5 border-t border-[#F0F2F5] pt-5" x-data="{ showTambah: false, confirmModal: { show: false, id: 0 } }" @notify-saved.window="showTambah = false">
            <div class="text-[10px] font-semibold text-[#8a9ba8] uppercase tracking-[0.8px] mb-2">MK di PT Asal yang Diajukan Peserta</div>
            <div class="bg-[#F4F6F8] rounded-xl overflow-hidden mb-3 border border-[#E5E8EC]">
                <table class="w-full text-[12px]">
                    <thead>
                        <tr class="border-b border-[#E5E8EC]">
                            <th class="text-left font-semibold text-[#8a9ba8] px-4 py-2.5 w-[110px]">Kode MK</th>
                            <th class="text-left font-semibold text-[#8a9ba8] px-4 py-2.5">Nama MK PT Asal</th>
                            <th class="text-center font-semibold text-[#8a9ba8] px-4 py-2.5 w-[90px]">SKS</th>
                            <th class="text-center font-semibold text-[#8a9ba8] px-4 py-2.5 w-[160px]">Nilai</th>
                            <th class="text-center font-semibold text-[#8a9ba8] px-4 py-2.5 w-[150px]">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($rplMk->matkulLampau as $ml)
                        <tr x-data="{ editing: false }"
                            @ml-saved.window="if ($event.detail.mlId === {{ $ml->id }}) editing = false"
                            wire:key="ml-{{ $ml->id }}"
                            class="border-b border-[#EFF1F3] last:border-0 bg-white">
                            <template x-if="!editing">
                                <td class="px-4 py-3 text-[#5a6a75] font-medium">
                                    @if ($ml->isOverridden('kode_mk'))
                                    <span class="inline-block w-1 h-4 bg-amber-400 mr-1.5 rounded-sm align-middle" title="Diedit asesor"></span>
                                    @endif
                                    {{ $ml->kode_mk_final ?? '—' }}
                                </td>
                            </template>
                            <template x-if="editing">
                                <td class="px-3 py-2">
                                    <input type="text" wire:model.defer="editForm.{{ $ml->id }}.kode_mk_asesor" placeholder="Kode MK"
                                           class="w-full h-[38px] rounded-xl border border-[#E0E5EA] px-3 text-[12px] text-[#1a2a35] focus:outline-none focus:border-primary focus:ring-2 focus:ring-primary/10">
                                    @error("editForm.{$ml->id}.kode_mk_asesor") <p class="text-[10px] text-red-500 mt-0.5">{{ $message }}</p> @enderror
                                </td>
                            </template>

                            <template x-if="!editing">
                                <td class="px-4 py-3 text-[#1a2a35] font-semibold">
                                    @if ($ml->isOverridden('nama_mk'))
                                    <span class="inline-block w-1 h-4 bg-amber-400 mr-1.5 rounded-sm align-middle" title="Diedit asesor"></span>
                                    @endif
                                    {{ $ml->nama_mk_final ?? '—' }}
                                </td>
                            </template>
                            <template x-if="editing">
                                <td class="px-3 py-2">
                                    <input type="text" wire:model.defer="editForm.{{ $ml->id }}.nama_mk_asesor" placeholder="Nama MK"
                                           class="w-full h-[38px] rounded-xl border border-[#E0E5EA] px-3 text-[12px] text-[#1a2a35] focus:outline-none focus:border-primary focus:ring-2 focus:ring-primary/10">
                                    @error("editForm.{$ml->id}.nama_mk_asesor") <p class="text-[10px] text-red-500 mt-0.5">{{ $message }}</p> @enderror
                                </td>
                            </template>

                            <template x-if="!editing">
                                <td class="px-4 py-3 text-center text-[#5a6a75]">
                                    @if ($ml->isOverridden('sks'))
                                    <span class="inline-block w-1 h-4 bg-amber-400 mr-1.5 rounded-sm align-middle" title="Diedit asesor"></span>
                                    @endif
                                    {{ $ml->sks_final ?? '—' }}
                                </td>
                            </template>
                            <template x-if="editing">
                                <td class="px-3 py-2">
                                    <input type="number" wire:model.defer="editForm.{{ $ml->id }}.sks_asesor" placeholder="SKS" min="1" max="20"
                                           class="w-full h-[38px] rounded-xl border border-[#E0E5EA] px-3 text-[12px] text-[#1a2a35] text-center focus:outline-none focus:border-primary focus:ring-2 focus:ring-primary/10">
                                    @error("editForm.{$ml->id}.sks_asesor") <p class="text-[10px] text-red-500 mt-0.5">{{ $message }}</p> @enderror
                                </td>
                            </template>

                            <template x-if="!editing">
                                <td class="px-4 py-3 text-center">
                                    @if ($ml->isOverridden('nilai_huruf'))
                                    <span class="inline-block w-1 h-4 bg-amber-400 mr-1.5 rounded-sm align-middle" title="Diedit asesor"></span>
                                    @endif
                                    <span class="inline-flex items-center justify-center w-7 h-7 rounded-lg bg-[#E8F4F8] text-primary text-[12px] font-bold">{{ $ml->nilai_huruf_final?->value ?? '-' }}</span>
                                </td>
                            </template>
                            <template x-if="editing">
                                <td class="px-3 py-2">
                                    <input type="text" wire:model.defer="editForm.{{ $ml->id }}.nilai_huruf_asesor" placeholder="mis. A, AB, B+"
                                           class="w-full h-[38px] rounded-xl border border-[#E0E5EA] px-3 text-[12px] text-[#1a2a35] focus:outline-none focus:border-primary focus:ring-2 focus:ring-primary/10">
                                    @error("editForm.{$ml->id}.nilai_huruf_asesor") <p class="text-[10px] text-red-500 mt-0.5">{{ $message }}</p> @enderror
                                </td>
                            </template>

                            <template x-if="!editing">
                                <td class="px-4 py-3 text-center">
                                    <div class="flex items-center justify-center gap-1.5">
                                        <button type="button"
                                                @click="editing = true"
                                                class="text-[#5a6a75] hover:text-primary transition-colors p-1.5"
                                                title="Edit"
                                                aria-label="Edit">
                                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                                <path d="M11 4H4a2 2 0 00-2 2v14a2 2 0 002 2h14a2 2 0 002-2v-7"/>
                                                <path d="M18.5 2.5a2.121 2.121 0 013 3L12 15l-4 1 1-4 9.5-9.5z"/>
                                            </svg>
                                        </button>
                                        <button type="button"
                                                @click="confirmModal = { show: true, id: {{ $ml->id }} }"
                                                class="text-[#c62828] hover:text-[#a02020] transition-colors p-1.5"
                                                title="Hapus"
                                                aria-label="Hapus">
                                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                                <polyline points="3 6 5 6 21 6"/><path d="M19 6l-1 14a2 2 0 01-2 2H8a2 2 0 01-2-2L5 6"/>
                                                <path d="M10 11v6"/><path d="M14 11v6"/>
                                                <path d="M9 6V4a1 1 0 011-1h4a1 1 0 011 1v2"/>
                                            </svg>
                                        </button>
                                    </div>
                                </td>
                            </template>
                            <template x-if="editing">
                                <td class="px-3 py-2 text-center">
                                    <div class="flex items-center justify-center gap-1.5">
                                        <button type="button"
                                                @click="editing = false; $wire.simpanEditMatkulLampau({{ $ml->id }})"
                                                class="h-[34px] px-4 rounded-xl bg-primary text-white text-[12px] font-semibold hover:bg-[#005f78] transition-colors">
                                            Simpan
                                        </button>
                                        <button type="button"
                                                @click="editing = false"
                                                class="h-[34px] px-4 rounded-xl border border-[#D0D5DD] text-[#5a6a75] text-[12px] font-medium hover:border-[#8a9ba8] hover:text-[#1a2a35] transition-colors">
                                            Batal
                                        </button>
                                    </div>
                                </td>
                            </template>
                        </tr>
                        @empty
                        <tr class="bg-white">
                            <td colspan="5" class="px-4 py-5 text-center text-[12px] text-[#8a9ba8] italic">Belum ada MK Lampau yang diinput.</td>
                        </tr>
                        @endforelse

                        {{-- Form Tambah MK Lampau --}}
                        <tr x-show="showTambah" wire:key="tambah-form-{{ $rplMk->id }}" class="bg-white border-t-2 border-[#E0E5EA]">
                            <td class="px-3 py-3">
                                <input type="text" wire:model.defer="tambahForm.kode_mk_asesor" placeholder="MK001"
                                       class="w-full h-[42px] rounded-xl border border-[#E0E5EA] px-3 text-[12px] text-[#1a2a35] focus:outline-none focus:border-primary focus:ring-2 focus:ring-primary/10">
                                @error('tambahForm.kode_mk_asesor') <p class="text-[10px] text-red-500 mt-0.5">{{ $message }}</p> @enderror
                            </td>
                            <td class="px-3 py-3">
                                <input type="text" wire:model.defer="tambahForm.nama_mk_asesor" placeholder="Nama mata kuliah di PT Asal"
                                       class="w-full h-[42px] rounded-xl border border-[#E0E5EA] px-3 text-[12px] text-[#1a2a35] focus:outline-none focus:border-primary focus:ring-2 focus:ring-primary/10">
                                @error('tambahForm.nama_mk_asesor') <p class="text-[10px] text-red-500 mt-0.5">{{ $message }}</p> @enderror
                            </td>
                            <td class="px-3 py-3">
                                <input type="number" wire:model.defer="tambahForm.sks_asesor" placeholder="SKS" min="1" max="20"
                                       class="w-full h-[42px] rounded-xl border border-[#E0E5EA] px-3 text-[12px] text-[#1a2a35] text-center focus:outline-none focus:border-primary focus:ring-2 focus:ring-primary/10">
                                @error('tambahForm.sks_asesor') <p class="text-[10px] text-red-500 mt-0.5">{{ $message }}</p> @enderror
                            </td>
                            <td class="px-3 py-3">
                                <input type="text" wire:model.defer="tambahForm.nilai_huruf_asesor" placeholder="mis. A, AB, B+"
                                       class="w-full h-[42px] rounded-xl border border-[#E0E5EA] px-3 text-[12px] text-[#1a2a35] focus:outline-none focus:border-primary focus:ring-2 focus:ring-primary/10">
                                @error('tambahForm.nilai_huruf_asesor') <p class="text-[10px] text-red-500 mt-0.5">{{ $message }}</p> @enderror
                            </td>
                            <td class="px-3 py-3">
                                <div class="flex items-center justify-center gap-2">
                                    <button type="button"
                                            wire:click="tambahMatkulLampau({{ $rplMk->id }})"
                                            class="h-[42px] px-5 rounded-xl bg-primary text-white text-[12px] font-semibold hover:bg-[#005f78] transition-colors whitespace-nowrap">
                                        Tambah
                                    </button>
                                    <button type="button"
                                            @click="showTambah = false"
                                            class="h-[42px] px-4 rounded-xl border border-[#D0D5DD] text-[#5a6a75] text-[12px] font-medium hover:border-[#8a9ba8] hover:text-[#1a2a35] transition-colors whitespace-nowrap">
                                        Batal
                                    </button>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
            <button type="button"
                    @click="showTambah = !showTambah"
                    x-show="!showTambah"
                    class="flex items-center gap-1.5 text-[12px] font-medium text-primary hover:underline mb-4">
                <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/>
                </svg>
                Tambah MK Lampau Manual
            </button>

            <div x-show="confirmModal.show"
                 x-cloak
                 class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 p-4"
                 x-transition:enter="transition ease-out duration-150" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
                 x-transition:leave="transition ease-in duration-100" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0">
                <div @click.outside="confirmModal.show = false" @keydown.escape.window="confirmModal.show = false"
                     class="bg-white rounded-2xl shadow-xl w-full max-w-sm p-6"
                     x-transition:enter="transition ease-out duration-150" x-transition:enter-start="opacity-0 scale-95" x-transition:enter-end="opacity-100 scale-100">
                    <div class="flex items-center gap-3 mb-4">
                        <div class="w-10 h-10 rounded-full bg-[#FCE8E6] flex items-center justify-center shrink-0">
                            <svg class="w-5 h-5 text-[#c62828]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <polyline points="3 6 5 6 21 6"/><path d="M19 6l-1 14a2 2 0 01-2 2H8a2 2 0 01-2-2L5 6"/>
                            </svg>
                        </div>
                        <div>
                            <div class="text-[14px] font-semibold text-[#1a2a35]">Hapus MK Lampau?</div>
                            <div class="text-[12px] text-[#8a9ba8]">Data akan dihapus permanen.</div>
                        </div>
                    </div>
                    <div class="flex gap-3">
                        <button @click="confirmModal.show = false"
                                class="flex-1 h-[40px] border border-[#D8DDE2] text-[#1a2a35] text-[13px] font-semibold rounded-xl hover:bg-[#F4F6F8] transition-colors">
                            Batal
                        </button>
                        <button @click="$wire.hapusMatkulLampau(confirmModal.id); confirmModal.show = false"
                                class="flex-1 h-[40px] bg-[#c62828] hover:bg-[#b71c1c] text-white text-[13px] font-semibold rounded-xl transition-colors">
                            Ya, Hapus
                        </button>
                    </div>
                </div>
            </div>

            {{-- MK Tujuan + Konversi Nilai — hanya jika ada MK Lampau --}}
            @if ($rplMk->matkulLampau->isNotEmpty())
            {{-- MK Tujuan (Header) --}}
            <div class="rounded-xl border-2 border-[#BDE0EB] overflow-hidden mb-4">
                <div class="bg-[#E8F4F8] px-5 py-4 flex items-center gap-4">
                    <div class="w-11 h-11 rounded-full bg-white flex items-center justify-center shrink-0 shadow-sm">
                        <svg class="w-5 h-5 text-primary" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M4 19.5v-15A2.5 2.5 0 016.5 2H20v20H6.5a2.5 2.5 0 01-2.5-2.5z"/><path d="M6.5 2H20v20H6.5A2.5 2.5 0 014 19.5v-15A2.5 2.5 0 016.5 2z"/>
                        </svg>
                    </div>
                    <div class="flex-1">
                        <div class="text-[10px] font-semibold text-primary uppercase tracking-[0.8px] mb-1">Mata Kuliah Tujuan (PCR)</div>
                        <div class="text-[15px] font-bold text-[#1a2a35]">{{ $mk->kode }} — {{ $mk->nama }}</div>
                    </div>
                    <div class="flex gap-2 shrink-0">
                        <span class="px-3 py-1.5 rounded-lg bg-white text-primary text-[12px] font-bold border border-[#BDE0EB]">Semester {{ $mk->semester }}</span>
                        <span class="px-3 py-1.5 rounded-lg bg-white text-primary text-[12px] font-bold border border-[#BDE0EB]">{{ $mk->sks }} SKS</span>
                    </div>
                </div>
            </div>

            {{-- Konversi Nilai + Catatan Lampau --}}
            <div class="bg-white rounded-xl border border-[#E5E8EC] px-5 py-5 shadow-sm">
                <div class="flex flex-col lg:flex-row gap-8 mb-2">
                    {{-- Kiri: Konversi Nilai --}}
                    <div class="shrink-0 w-[420px]">
                        <label class="block text-[12px] font-semibold text-[#1a2a35] mb-3">Konversi Nilai Asesor</label>
                        <div class="flex gap-2 flex-wrap mb-1">
                            @foreach ($nilaiHurufOptions as $opt)
                            <button type="button"
                                    wire:click="$set('nilaiTransfer.{{ $rplMk->id }}', '{{ $opt->value }}')"
                                    @click="$dispatch('mk-status-predicted', { mkId: {{ $rplMk->id }}, status: '{{ $opt->diakui() ? \App\Enums\StatusRplMataKuliahEnum::Diakui->value : \App\Enums\StatusRplMataKuliahEnum::TidakDiakui->value }}', sks: {{ $mk->sks ?? 0 }} })"
                                    class="w-12 h-12 rounded-xl text-[14px] font-bold border-2 transition-all
                                           {{ ($nilaiTransfer[$rplMk->id] ?? '') === $opt->value
                                               ? 'bg-primary border-primary text-white shadow-md shadow-primary/20'
                                               : 'bg-white border-[#D0D5DD] text-[#5a6a75] hover:border-primary hover:text-primary' }}">
                                {{ $opt->value }}
                            </button>
                            @endforeach
                        </div>
                        @error("nilaiTransfer.{$rplMk->id}") <p class="mt-1.5 text-[12px] text-[#c62828]">{{ $message }}</p> @enderror
                    </div>

                    {{-- Kanan: Catatan Lampau --}}
                    <div class="flex-1 space-y-4">
                        @foreach ($rplMk->matkulLampau as $ml)
                        <div wire:key="cat-lampau-ui-{{ $ml->id }}">
                            <label class="block text-[12px] font-semibold text-[#1a2a35] mb-2">
                                Catatan Asesor untuk <span class="text-primary">{{ $ml->kode_mk_final ?? '—' }} — {{ $ml->nama_mk_final ?? '—' }}</span>
                            </label>
                            {{-- Quill di-instantiate hanya saat asesor klik area edit (lazy init) --}}
                            <div wire:ignore
                                 x-data="{
                                    initialized: false,
                                    content: @entangle('catatanLampau.'.$ml->id),
                                    quill: null,
                                    initQuill() {
                                        if (this.initialized) return;
                                        this.initialized = true;
                                        this.$nextTick(() => {
                                            this.quill = new Quill(this.$refs.quillLampau{{ $ml->id }}, {
                                                theme: 'snow',
                                                placeholder: 'Tulis catatan asesor terkait matkul PT Asal ini...',
                                                modules: { toolbar: [['bold', 'italic', 'underline'], [{ 'list': 'ordered'}, { 'list': 'bullet' }]] }
                                            });
                                            if (this.content) this.quill.root.innerHTML = this.content;
                                            this.quill.on('text-change', () => {
                                                this.content = this.quill.root.innerHTML === '<p><br></p>' ? '' : this.quill.root.innerHTML;
                                            });
                                            this.quill.focus();
                                        });
                                    }
                                 }">
                                <div x-show="!initialized"
                                     @click="initQuill()"
                                     class="border border-[#D8DDE2] rounded-lg p-3 min-h-[80px] cursor-text hover:border-primary transition-colors text-[12px] text-[#1a2a35] prose prose-sm max-w-none"
                                     x-html="content || '<span class=\'text-[#8a9ba8]\'>Klik untuk menambahkan catatan...</span>'">
                                </div>
                                <div x-show="initialized">
                                    <div x-ref="quillLampau{{ $ml->id }}"></div>
                                </div>
                            </div>
                        </div>
                        @endforeach
                    </div>
                </div>

                {{-- Simpan --}}
                <div class="flex justify-end pt-5 mt-4 border-t border-[#F0F2F5]">
                    <button wire:click="simpanNilaiTransfer({{ $rplMk->id }})"
                            class="h-[46px] px-7 bg-primary hover:bg-[#005f78] text-white text-[13px] font-semibold rounded-xl transition-colors">
                        Simpan Nilai
                    </button>
                </div>
            </div>
            @endif
        </div>

        {{-- Set status MK / Rekomendasi otomatis --}}
        @if (in_array($permohonan->status, [\App\Enums\StatusPermohonanEnum::Asesmen, \App\Enums\StatusPermohonanEnum::Disetujui, \App\Enums\StatusPermohonanEnum::Ditolak]))
            @if (! $rplMk->matkulLampau->isNotEmpty())
                {{-- Hanya tampilkan ringkasan rata-rata VATM jika bukan hybrid MK Lampau --}}
                @php
                    $asmNilaiMap = $rplMk->asesmenMandiri
                        ->mapWithKeys(fn($asm) => [$asm->id => ($this->nilaiAsesor[$asm->id] ?? 0) ?: null]);
                    $labelDiakui = \App\Enums\StatusRplMataKuliahEnum::Diakui->label();
                    $labelTidak  = \App\Enums\StatusRplMataKuliahEnum::TidakDiakui->label();
                @endphp
                <div class="mt-3 mb-1 flex items-center gap-3 px-1"
                     wire:ignore
                     x-data="{
                        nilaiMap: @js($asmNilaiMap),
                        labelDiakui: @js($labelDiakui),
                        labelTidak: @js($labelTidak),
                        init() {
                            this.dispatchPrediksi();
                        },
                        rataRataRaw() {
                            const vals = Object.values(this.nilaiMap);
                            if (vals.length === 0) return null;
                            if (vals.some((v) => v === null || v === 0)) return null;
                            return vals.reduce((sum, v) => sum + Number(v), 0) / vals.length;
                        },
                        rataRataDisplay() {
                            const raw = this.rataRataRaw();
                            return raw === null ? null : Math.round(raw * 100) / 100;
                        },
                        diakui() {
                            const raw = this.rataRataRaw();
                            return raw !== null && raw >= 3;
                        },
                        rekomendasiLabel() {
                            const raw = this.rataRataRaw();
                            if (raw === null) return '';
                            return raw >= 3 ? this.labelDiakui : this.labelTidak;
                        },
                        nilaiHurufFromRata(raw) {
                            if (raw === null) return '';
                            if (raw >= 5.0) return 'A';
                            if (raw >= 4.5) return 'AB';
                            if (raw >= 4.0) return 'B';
                            if (raw >= 3.5) return 'BC';
                            if (raw >= 3.0) return 'C';
                            if (raw >= 2.0) return 'D';
                            return 'E';
                        },
                        dispatchPrediksi() {
                            const raw = this.rataRataRaw();
                            const status = raw === null
                                ? '{{ \App\Enums\StatusRplMataKuliahEnum::Menunggu->value }}'
                                : (raw >= 3
                                    ? '{{ \App\Enums\StatusRplMataKuliahEnum::Diakui->value }}'
                                    : '{{ \App\Enums\StatusRplMataKuliahEnum::TidakDiakui->value }}');
                            this.$dispatch('mk-status-predicted', {
                                mkId: {{ $rplMk->id }},
                                status: status,
                                sks: {{ $mk->sks ?? 0 }}
                            });
                        }
                     }"
                     @nilai-asesor-updated.window="if ($event.detail.mkId === {{ $rplMk->id }}) { nilaiMap[$event.detail.asmId] = $event.detail.nilai; dispatchPrediksi(); }"
                     x-show="rataRataRaw() !== null" x-cloak>
                    <span class="text-[11px] text-[#8a9ba8]">
                        Rata-rata nilai asesor:
                        <span class="font-semibold text-[#1a2a35]" x-text="rataRataDisplay()"></span>
                        <span class="text-[#8a9ba8]" x-text="'(' + nilaiHurufFromRata(rataRataRaw()) + ')'"></span> / 5
                    </span>
                    <span class="text-[10px] font-semibold px-2 py-0.5 rounded-full"
                          :class="diakui() ? 'bg-[#E6F4EA] text-[#1e7e3e]' : 'bg-[#FCE8E6] text-[#c62828]'">
                        Rekomendasi: <span x-text="rekomendasiLabel()"></span>
                    </span>
                    <span class="text-[10px] text-[#b0bec5]">— (Status final dapat di-override di bawah jika perlu)</span>
                </div>
            @endif

            {{-- Set Status Override Asesor --}}
            <div class="border-t border-[#F0F2F5] pt-4 mt-4">
                <div class="text-[10px] font-semibold text-[#8a9ba8] uppercase tracking-[0.8px] mb-3">Timpa Status / Catatan Khusus MK</div>
                <p class="mb-3 text-[11px] text-[#8a9ba8]">
                    Status ditentukan otomatis dari nilai huruf. Nilai di bawah C akan tidak diakui.
                </p>
                <div class="flex items-start gap-3">
                    <div class="w-[200px] shrink-0">
                        <x-form.select
                            wire:model="mkStatus.{{ $rplMk->id }}"
                            :options="collect(\App\Enums\StatusRplMataKuliahEnum::cases())->mapWithKeys(fn($e) => [$e->value => $e->label()])->all()"
                        />
                    </div>
                    {{-- Quill override catatan MK di-instantiate hanya saat asesor klik area edit (lazy init) --}}
                    <div class="flex-1" wire:ignore
                         x-data="{
                            initialized: false,
                            content: @entangle('mkCatatan.'.$rplMk->id),
                            quill: null,
                            initQuill() {
                                if (this.initialized) return;
                                this.initialized = true;
                                this.$nextTick(() => {
                                    this.quill = new Quill(this.$refs.quillContainerOverride, {
                                        theme: 'snow',
                                        placeholder: 'Tambahkan catatan khusus...',
                                        modules: { toolbar: [['bold', 'italic', 'underline'], [{ 'list': 'ordered'}, { 'list': 'bullet' }]] }
                                    });
                                    if (this.content) this.quill.root.innerHTML = this.content;
                                    this.quill.on('text-change', () => {
                                        this.content = this.quill.root.innerHTML === '<p><br></p>' ? '' : this.quill.root.innerHTML;
                                    });
                                    this.quill.focus();
                                });
                            }
                         }">
                        {{-- Preview mode: tampil saat belum diedit, klik untuk init Quill --}}
                        <div x-show="!initialized"
                             @click="initQuill()"
                             class="border border-[#D8DDE2] rounded-lg p-3 min-h-[42px] cursor-text hover:border-primary transition-colors text-[12px] text-[#1a2a35] prose prose-sm max-w-none"
                             x-html="content || '<span class=\'text-[#8a9ba8]\'>Klik untuk menambahkan catatan khusus...</span>'">
                        </div>
                        {{-- Editor mode: muncul setelah diklik --}}
                        <div x-show="initialized">
                            <div x-ref="quillContainerOverride"></div>
                        </div>
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
