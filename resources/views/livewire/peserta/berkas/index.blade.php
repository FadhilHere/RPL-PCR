<?php

use Livewire\Attributes\Layout;
use Livewire\Volt\Component;
use Livewire\WithFileUploads;
use App\Enums\JenisDokumenEnum;
use App\Livewire\Forms\UploadDokumenForm;
use App\Models\DokumenBukti;

new #[Layout('components.layouts.peserta')] class extends Component {
    use WithFileUploads;

    public UploadDokumenForm $form;
    public function with(): array
    {
        $peserta = auth()->user()->peserta;

        $dokumenList = $peserta
            ? DokumenBukti::where('peserta_id', $peserta->id)->latest()->get()
            : collect();

        // Jenis yang sudah diupload
        $uploadedJenis = $dokumenList->pluck('jenis_dokumen')->map(fn($j) => $j->value)->unique()->values();

        return [
            'dokumenList'   => $dokumenList,
            'isDoPcr'       => (bool) $peserta?->is_do_pcr,
            'jenisOptions'  => JenisDokumenEnum::options(),
            'uploadedJenis' => $uploadedJenis,
            'semuaJenis'    => JenisDokumenEnum::cases(),
        ];
    }

    public function uploadDokumen(): void
    {
        $peserta = auth()->user()->peserta;

        abort_if(! $peserta, 403);

        $this->form->validate();
        $this->form->store($peserta);
        $this->form->reset();
    }

    public function hapusDokumen(int $id): void
    {
        $peserta = auth()->user()->peserta;
        $dokumen = DokumenBukti::findOrFail($id);

        abort_if($dokumen->peserta_id !== $peserta?->id, 403);

        \Storage::disk('local')->delete($dokumen->berkas);
        $dokumen->delete();
    }

}; ?>

<x-slot:title>Berkas Pendukung</x-slot:title>
<x-slot:subtitle>Kelola berkas bukti pendukung RPL Anda</x-slot:subtitle>

<div x-data="{ showViewer: false, viewUrl: '', viewType: '', viewName: '', confirmModal: { show: false, id: 0, name: '' } }">

    {{-- Info --}}
    <div class="bg-[#E8F4F8] border border-[#C5DDE5] rounded-xl px-4 py-3 mb-5 flex items-center gap-3">
        <svg class="w-4 h-4 text-primary shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/>
        </svg>
        <p class="text-[12px] text-[#1a2a35] leading-[1.5]">
            Unggah semua berkas bukti di sini. Saat mengisi asesmen mandiri, Anda dapat memilih berkas yang relevan untuk setiap pertanyaan.
        </p>
    </div>

    {{-- Checklist Persyaratan --}}
    <div class="bg-white rounded-xl border border-[#E5E8EC] p-5 mb-5">
        <div class="flex items-center justify-between mb-3">
            <h3 class="text-[13px] font-semibold text-[#1a2a35]">Persyaratan Berkas</h3>
            @if ($isDoPcr)
            <span class="text-[11px] text-[#5a6a75] px-2.5 py-1 bg-[#E8F4F8] rounded-full">Alumni PCR</span>
            @endif
        </div>
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-2">
            @foreach ($semuaJenis as $jenis)
            @php
                $isUploaded = $uploadedJenis->contains($jenis->value);
                $isWajib    = $jenis->wajib()
                    || (! $isDoPcr && in_array($jenis, [
                        \App\Enums\JenisDokumenEnum::Transkrip,
                        \App\Enums\JenisDokumenEnum::KeteranganMataKuliah,
                    ]));
            @endphp
            <div class="flex items-center gap-2.5 px-3 py-2 rounded-lg border
                        {{ $isUploaded ? 'border-[#A8D5B5] bg-[#F0FAF3]' : ($isWajib ? 'border-[#FFCC80] bg-[#FFF9F0]' : 'border-[#E5E8EC] bg-[#FAFBFC]') }}">
                <div class="shrink-0">
                    @if ($isUploaded)
                    <svg class="w-4 h-4 text-[#1e7e3e]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                        <polyline points="20 6 9 17 4 12"/>
                    </svg>
                    @elseif ($isWajib)
                    <svg class="w-4 h-4 text-[#b45309]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/>
                    </svg>
                    @else
                    <svg class="w-4 h-4 text-[#D0D5DD]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <circle cx="12" cy="12" r="10"/>
                    </svg>
                    @endif
                </div>
                <div class="min-w-0 flex-1">
                    <div class="text-[11px] font-medium text-[#1a2a35] truncate">{{ $jenis->label() }}</div>
                    <div class="text-[10px] {{ $isUploaded ? 'text-[#1e7e3e]' : ($isWajib ? 'text-[#b45309]' : 'text-[#b0bec5]') }}">
                        {{ $isUploaded ? 'Sudah diunggah' : ($isWajib ? 'Wajib' : 'Opsional') }}
                    </div>
                </div>
            </div>
            @endforeach
        </div>
    </div>

    <div class="flex flex-col lg:flex-row gap-5 items-start">

        {{-- Form Upload --}}
        <div class="w-full lg:w-[340px] lg:shrink-0">
            <div class="bg-white rounded-xl border border-[#E5E8EC] p-5">
                <h3 class="text-[13px] font-semibold text-[#1a2a35] mb-4">Tambah Berkas</h3>

                <div class="space-y-4">
                    <div>
                        <label class="block text-[11px] font-semibold text-[#5a6a75] uppercase tracking-[0.7px] mb-1.5">Nama Berkas</label>
                        <input wire:model="form.namaDokumen" type="text" placeholder="cth: CV Terbaru 2026"
                               class="w-full h-[42px] px-3 text-[13px] text-[#1a2a35] bg-white border border-[#E0E5EA] rounded-xl outline-none focus:border-primary focus:ring-2 focus:ring-primary/10 placeholder:text-[#b0bec5]" />
                        @error('form.namaDokumen') <p class="mt-1 text-[10px] text-[#c62828]">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label class="block text-[11px] font-semibold text-[#5a6a75] uppercase tracking-[0.7px] mb-1.5">Jenis Berkas</label>
                        <x-form.select wire:model="form.jenisDokumen" :options="$jenisOptions" />
                    </div>

                    <div>
                        <label class="block text-[11px] font-semibold text-[#5a6a75] uppercase tracking-[0.7px] mb-1.5">File</label>
                        <div class="border-2 border-dashed border-[#D8DDE2] rounded-xl p-4 text-center hover:border-primary transition-colors cursor-pointer">
                            <input wire:model="form.berkas" type="file" accept=".pdf,.jpg,.jpeg,.png"
                                   class="hidden" id="fileInput" />
                            <label for="fileInput" class="cursor-pointer block">
                                @if ($form->berkas)
                                    <div class="text-[12px] font-medium text-primary">{{ $form->berkas->getClientOriginalName() }}</div>
                                    <div class="text-[11px] text-[#8a9ba8] mt-0.5">{{ round($form->berkas->getSize() / 1024) }} KB</div>
                                @else
                                    <svg class="mx-auto mb-2 text-[#b0bec5]" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
                                        <path d="M21 15v4a2 2 0 01-2 2H5a2 2 0 01-2-2v-4"/>
                                        <polyline points="17 8 12 3 7 8"/>
                                        <line x1="12" y1="3" x2="12" y2="15"/>
                                    </svg>
                                    <div class="text-[12px] text-[#8a9ba8]">Klik untuk pilih file</div>
                                    <div class="text-[10px] text-[#b0bec5] mt-0.5">PDF, JPG, PNG · Max 5 MB</div>
                                @endif
                            </label>
                        </div>
                        @error('form.berkas') <p class="mt-1 text-[10px] text-[#c62828]">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label class="block text-[11px] font-semibold text-[#5a6a75] uppercase tracking-[0.7px] mb-1.5">Keterangan <span class="normal-case font-normal text-[#b0bec5]">(opsional)</span></label>
                        <input wire:model="form.keterangan" type="text" placeholder="Keterangan singkat..."
                               class="w-full h-[42px] px-3 text-[13px] text-[#1a2a35] bg-white border border-[#E0E5EA] rounded-xl outline-none focus:border-primary placeholder:text-[#b0bec5]" />
                    </div>

                    <button wire:click="uploadDokumen"
                            wire:loading.attr="disabled"
                            class="w-full h-[42px] bg-primary hover:bg-[#005f78] text-white text-[13px] font-semibold rounded-xl transition-colors disabled:opacity-60">
                        <span wire:loading.remove wire:target="uploadDokumen">Unggah Berkas</span>
                        <span wire:loading wire:target="uploadDokumen">Mengunggah...</span>
                    </button>
                </div>
            </div>
        </div>

        {{-- Daftar Berkas --}}
        <div class="flex-1">
            <div class="bg-white rounded-xl border border-[#E5E8EC] overflow-hidden">
                <div class="flex items-center justify-between px-5 py-3.5 border-b border-[#F0F2F5]">
                    <div class="text-[13px] font-semibold text-[#1a2a35]">Berkas Terunggah</div>
                    <span class="text-[11px] text-[#8a9ba8]">{{ count($dokumenList) }} berkas</span>
                </div>

                @forelse ($dokumenList as $dok)
                @php
                    $ext      = strtolower(pathinfo($dok->berkas, PATHINFO_EXTENSION));
                    $viewType = in_array($ext, ['jpg', 'jpeg', 'png']) ? 'image' : 'pdf';
                @endphp
                <div class="flex items-center gap-3.5 px-5 py-3.5 border-b border-[#F6F8FA] last:border-0" wire:key="dok-{{ $dok->id }}">
                    <div class="w-9 h-9 rounded-lg bg-[#E8F4F8] flex items-center justify-center shrink-0">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#004B5F" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V8z"/>
                            <polyline points="14 2 14 8 20 8"/>
                        </svg>
                    </div>
                    <div class="flex-1 min-w-0">
                        <div class="text-[12px] font-medium text-[#1a2a35] truncate">{{ $dok->nama_dokumen }}</div>
                        <div class="text-[11px] text-[#8a9ba8]">{{ $dok->jenis_dokumen->label() }}</div>
                        @if ($dok->keterangan)
                        <div class="text-[11px] text-[#b0bec5] truncate">{{ $dok->keterangan }}</div>
                        @endif
                    </div>
                    <div class="flex items-center gap-1 shrink-0">
                        <button @click="viewUrl = '{{ route('berkas.view', $dok->id) }}'; viewType = '{{ $viewType }}'; viewName = '{{ addslashes($dok->nama_dokumen) }}'; showViewer = true"
                                class="w-[28px] h-[28px] rounded-md border border-[#D0D5DD] text-[#5a6a75] hover:border-primary hover:text-primary hover:bg-[#E8F4F8] transition-colors flex items-center justify-center">
                            <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
                        </button>
                        <a href="{{ route('berkas.download', $dok->id) }}"
                           class="w-[28px] h-[28px] rounded-md border border-[#D0D5DD] text-[#5a6a75] hover:border-primary hover:text-primary hover:bg-[#E8F4F8] transition-colors flex items-center justify-center no-underline">
                            <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15v4a2 2 0 01-2 2H5a2 2 0 01-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
                        </a>
                        <button @click="confirmModal = { show: true, id: {{ $dok->id }}, name: '{{ addslashes($dok->nama_dokumen) }}' }"
                                class="w-[28px] h-[28px] rounded-md border border-[#D0D5DD] text-[#5a6a75] hover:border-[#c62828] hover:text-[#c62828] hover:bg-[#FCE8E6] transition-colors flex items-center justify-center">
                            <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="3 6 5 6 21 6"/><path d="M19 6l-1 14a2 2 0 01-2 2H8a2 2 0 01-2-2L5 6"/><path d="M10 11v6M14 11v6"/></svg>
                        </button>
                    </div>
                </div>
                @empty
                <div class="py-10 text-center text-[13px] text-[#8a9ba8]">
                    Belum ada berkas yang diunggah.
                </div>
                @endforelse
            </div>
        </div>

    </div>

    {{-- Modal Lihat Berkas (Alpine) --}}
    <div x-show="showViewer"
         x-cloak
         x-transition.opacity
         class="fixed inset-0 z-[60] flex items-center justify-center bg-black/60"
         @click.self="showViewer = false">
        <div class="bg-white rounded-2xl shadow-2xl w-full max-w-3xl mx-4 flex flex-col overflow-hidden"
             style="max-height: 90vh">

            {{-- Header --}}
            <div class="flex items-center justify-between px-5 py-3.5 border-b border-[#F0F2F5] shrink-0">
                <span x-text="viewName" class="text-[13px] font-semibold text-[#1a2a35] truncate max-w-[60%]"></span>
                <div class="flex items-center gap-3">
                    <a :href="viewUrl.replace('/view', '/download')"
                       class="inline-flex items-center gap-1.5 text-[11px] font-semibold text-primary hover:text-[#005f78] transition-colors no-underline">
                        <svg class="w-3.5 h-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M21 15v4a2 2 0 01-2 2H5a2 2 0 01-2-2v-4"/>
                            <polyline points="7 10 12 15 17 10"/>
                            <line x1="12" y1="15" x2="12" y2="3"/>
                        </svg>
                        Unduh
                    </a>
                    <button @click="showViewer = false"
                            class="text-[#8a9ba8] hover:text-[#1a2a35] transition-colors p-1">
                        <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/>
                        </svg>
                    </button>
                </div>
            </div>

            {{-- Body --}}
            <div class="flex-1 min-h-0 overflow-auto bg-[#F0F2F5]" style="height: 75vh">
                <template x-if="viewType === 'pdf'">
                    <iframe :src="viewUrl" class="w-full border-0" style="height: 75vh"></iframe>
                </template>
                <template x-if="viewType === 'image'">
                    <div class="flex items-center justify-center p-6 min-h-full">
                        <img :src="viewUrl" class="max-w-full max-h-full object-contain rounded-lg shadow" />
                    </div>
                </template>
            </div>

        </div>
    </div>

    {{-- Modal Konfirmasi Hapus (Alpine) --}}
    <div x-show="confirmModal.show"
         x-cloak
         x-transition.opacity
         class="fixed inset-0 z-50 flex items-center justify-center bg-black/40"
         @click.self="confirmModal.show = false">
        <div class="bg-white rounded-2xl shadow-xl w-full max-w-sm p-6 mx-4">
            <div class="w-10 h-10 rounded-full bg-[#FCE8E6] flex items-center justify-center mb-4 mx-auto">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#c62828" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <polyline points="3 6 5 6 21 6"/>
                    <path d="M19 6l-1 14a2 2 0 01-2 2H8a2 2 0 01-2-2L5 6"/>
                    <path d="M10 11v6"/><path d="M14 11v6"/>
                    <path d="M9 6V4a1 1 0 011-1h4a1 1 0 011 1v2"/>
                </svg>
            </div>
            <p class="text-[14px] text-[#1a2a35] text-center mb-6 leading-relaxed">
                Hapus berkas "<span x-text="confirmModal.name" class="font-semibold"></span>"?
            </p>
            <div class="flex gap-3">
                <button @click="confirmModal.show = false"
                    class="flex-1 h-[42px] bg-white border border-[#D8DDE2] text-[13px] font-semibold text-[#5a6a75] rounded-xl hover:bg-[#F8FAFB] transition-colors">
                    Batal
                </button>
                <button @click="$wire.hapusDokumen(confirmModal.id); confirmModal.show = false"
                    class="flex-1 h-[42px] bg-[#c62828] hover:bg-[#a02020] text-white text-[13px] font-semibold rounded-xl transition-colors">
                    Hapus
                </button>
            </div>
        </div>
    </div>

</div>
