<?php

use App\Actions\Admin\UploadDokumenPesertaAction;
use App\Enums\JenisDokumenEnum;
use App\Models\DokumenBukti;
use App\Models\Peserta;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;
use Livewire\WithFileUploads;

new #[Layout('components.layouts.admin')] class extends Component {
    use WithFileUploads;

    public Peserta $peserta;

    public $berkas      = null;
    public string $nama = '';
    public string $jenis = '';
    public string $ket  = '';

    public function mount(Peserta $peserta): void
    {
        $this->peserta = $peserta->load('user', 'dokumenBukti');
    }

    public function upload(UploadDokumenPesertaAction $action): void
    {
        $this->validate([
            'berkas' => 'required|file|mimes:pdf,jpg,jpeg,png|max:10240',
            'nama'   => 'required|string|max:255',
            'jenis'  => 'required|string',
        ], [
            'berkas.required' => 'Pilih file terlebih dahulu.',
            'berkas.mimes'    => 'Format harus PDF, JPG, atau PNG.',
            'berkas.max'      => 'Ukuran file maksimal 10 MB.',
            'nama.required'   => 'Nama berkas wajib diisi.',
            'jenis.required'  => 'Jenis berkas wajib dipilih.',
        ]);

        $action->execute(
            $this->peserta,
            $this->berkas,
            $this->nama,
            JenisDokumenEnum::from($this->jenis),
            $this->ket ?: null,
            auth()->id(),
        );

        $this->reset('berkas', 'nama', 'jenis', 'ket');
        $this->peserta = $this->peserta->fresh('user', 'dokumenBukti');
    }

    public function hapus(int $dokumenId): void
    {
        $dokumen = DokumenBukti::findOrFail($dokumenId);
        abort_if($dokumen->peserta_id !== $this->peserta->id, 403);

        \Storage::disk('local')->delete($dokumen->berkas);
        $dokumen->delete();
        $this->peserta = $this->peserta->fresh('user', 'dokumenBukti');
    }

    public function with(): array
    {
        return [
            'jenisOptions' => JenisDokumenEnum::options(),
        ];
    }
}; ?>

<x-slot:title>Berkas Peserta</x-slot:title>
<x-slot:subtitle>
    <a href="{{ route('admin.akun.index') }}" class="text-primary hover:underline">Kelola Akun</a>
    &rsaquo; {{ $peserta->user->nama }}
</x-slot:subtitle>

<div x-data="{
    confirm: { open: false, id: null },
    openConfirm(id) { this.confirm = { open: true, id }; },
    doHapus() { $wire.hapus(this.confirm.id); this.confirm.open = false; },
    viewer: { open: false, url: '', type: '', name: '' },
    openViewer(url, type, name) { this.viewer = { open: true, url, type, name }; },
}">

    {{-- Info peserta --}}
    <div class="bg-white rounded-[10px] border border-[#E5E8EC] px-5 py-4 mb-5 flex items-center gap-6 flex-wrap">
        <div>
            <div class="text-[11px] text-[#8a9ba8] mb-0.5">Nama</div>
            <div class="text-[13px] font-semibold text-[#1a2a35]">{{ $peserta->user->nama }}</div>
        </div>
        <div>
            <div class="text-[11px] text-[#8a9ba8] mb-0.5">Email</div>
            <div class="text-[13px] text-[#5a6a75]">{{ $peserta->user->email }}</div>
        </div>
        @if ($peserta->telepon)
        <div>
            <div class="text-[11px] text-[#8a9ba8] mb-0.5">Telepon</div>
            <div class="text-[13px] text-[#5a6a75]">{{ $peserta->telepon }}</div>
        </div>
        @endif
        <div class="ml-auto">
            <span class="text-[10px] font-semibold px-2.5 py-1 rounded-full {{ $peserta->user->aktif ? 'bg-[#E6F4EA] text-[#1e7e3e]' : 'bg-[#FCE8E6] text-[#c62828]' }}">
                {{ $peserta->user->aktif ? 'Aktif' : 'Nonaktif' }}
            </span>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-5">

        {{-- Upload Form --}}
        <div class="bg-white rounded-[10px] border border-[#E5E8EC] p-5">
            <div class="text-[13px] font-semibold text-[#1a2a35] mb-4">Upload Berkas Baru</div>

            <div class="space-y-3">
                <div>
                    <label class="block text-[11px] font-semibold text-[#5a6a75] uppercase tracking-[0.7px] mb-1.5">Nama Berkas *</label>
                    <input wire:model="nama" type="text" placeholder="Nama dokumen..."
                        class="w-full h-[40px] px-3.5 text-[13px] text-[#1a2a35] bg-white border border-[#E0E5EA] rounded-xl outline-none transition-all focus:border-primary focus:ring-2 focus:ring-primary/10 placeholder:text-[#b0bec5]" />
                    @error('nama') <p class="mt-1 text-[11px] text-[#c62828]">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label class="block text-[11px] font-semibold text-[#5a6a75] uppercase tracking-[0.7px] mb-1.5">Jenis Berkas *</label>
                    <x-form.select wire:model="jenis" placeholder="— Pilih jenis —" :options="$jenisOptions" />
                    @error('jenis') <p class="mt-1 text-[11px] text-[#c62828]">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label class="block text-[11px] font-semibold text-[#5a6a75] uppercase tracking-[0.7px] mb-1.5">
                        Keterangan <span class="normal-case font-normal text-[#b0bec5]">(opsional)</span>
                    </label>
                    <input wire:model="ket" type="text" placeholder="Keterangan tambahan..."
                        class="w-full h-[40px] px-3.5 text-[13px] text-[#1a2a35] bg-white border border-[#E0E5EA] rounded-xl outline-none transition-all focus:border-primary focus:ring-2 focus:ring-primary/10 placeholder:text-[#b0bec5]" />
                </div>

                <div>
                    <label class="block text-[11px] font-semibold text-[#5a6a75] uppercase tracking-[0.7px] mb-1.5">File * <span class="normal-case font-normal text-[#b0bec5]">PDF/JPG/PNG, maks 10 MB</span></label>
                    <label class="flex items-center gap-3 h-[56px] px-4 border-2 border-dashed border-[#D0D5DD] rounded-xl bg-[#FAFBFC] hover:border-primary hover:bg-[#F0F7FA] transition-all cursor-pointer group">
                        <input type="file" wire:model="berkas" accept=".pdf,.jpg,.jpeg,.png" class="hidden" />
                        <svg class="w-5 h-5 text-[#8a9ba8] group-hover:text-primary transition-colors shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <polyline points="16 16 12 12 8 16"/><line x1="12" y1="12" x2="12" y2="21"/>
                            <path d="M20.39 18.39A5 5 0 0018 9h-1.26A8 8 0 103 16.3"/>
                        </svg>
                        <span class="text-[12px] text-[#5a6a75] group-hover:text-primary transition-colors">
                            @if ($berkas) {{ $berkas->getClientOriginalName() }} @else Klik untuk pilih file @endif
                        </span>
                    </label>
                    <div wire:loading wire:target="berkas" class="mt-1 text-[11px] text-[#8a9ba8]">Mengupload...</div>
                    @error('berkas') <p class="mt-1 text-[11px] text-[#c62828]">{{ $message }}</p> @enderror
                </div>
            </div>

            <button wire:click="upload" wire:loading.attr="disabled" wire:target="upload"
                class="mt-4 w-full h-[42px] bg-primary hover:bg-[#005f78] text-white text-[13px] font-semibold rounded-xl transition-colors disabled:opacity-60">
                <span wire:loading.remove wire:target="upload">Upload Berkas</span>
                <span wire:loading wire:target="upload">Mengupload...</span>
            </button>
        </div>

        {{-- Daftar berkas --}}
        <div class="bg-white rounded-[10px] border border-[#E5E8EC] overflow-hidden">
            <div class="px-5 py-3.5 border-b border-[#F0F2F5] flex items-center justify-between">
                <div class="text-[13px] font-semibold text-[#1a2a35]">Berkas Tersimpan</div>
                <span class="text-[11px] text-[#8a9ba8]">{{ $peserta->dokumenBukti->count() }} berkas</span>
            </div>

            @if ($peserta->dokumenBukti->isEmpty())
            <div class="px-5 py-8 text-center text-[12px] text-[#8a9ba8]">Belum ada berkas.</div>
            @else
            <div class="divide-y divide-[#F6F8FA]">
                @foreach ($peserta->dokumenBukti as $dok)
                @php $ext = strtolower(pathinfo($dok->berkas, PATHINFO_EXTENSION)); @endphp
                <div class="px-5 py-3 flex items-start gap-3" wire:key="dok-{{ $dok->id }}">
                    <div class="w-8 h-8 rounded-lg flex items-center justify-center shrink-0 mt-0.5
                        {{ in_array($ext, ['jpg','jpeg','png']) ? 'bg-[#E8F4F8] text-primary' : 'bg-[#FCE8E6] text-[#c62828]' }}">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            @if (in_array($ext, ['jpg','jpeg','png']))
                            <rect x="3" y="3" width="18" height="18" rx="2"/><circle cx="8.5" cy="8.5" r="1.5"/><polyline points="21 15 16 10 5 21"/>
                            @else
                            <path d="M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V8z"/><polyline points="14 2 14 8 20 8"/>
                            @endif
                        </svg>
                    </div>
                    <div class="flex-1 min-w-0">
                        <div class="text-[12px] font-medium text-[#1a2a35] truncate">{{ $dok->nama_dokumen }}</div>
                        <div class="text-[11px] text-[#8a9ba8]">{{ $dok->jenis_dokumen?->label() }}</div>
                        @if ($dok->keterangan)
                        <div class="text-[11px] text-[#5a6a75] mt-0.5">{{ $dok->keterangan }}</div>
                        @endif
                    </div>
                    @php $viewType = in_array($ext, ['jpg','jpeg','png']) ? 'image' : 'pdf'; @endphp
                    <div class="flex items-center gap-1 shrink-0">
                        <button @click="openViewer('{{ route('berkas.view', $dok) }}', '{{ $viewType }}', '{{ addslashes($dok->nama_dokumen) }}')"
                           class="w-[28px] h-[28px] rounded-md border border-[#D0D5DD] text-[#5a6a75] hover:border-primary hover:text-primary hover:bg-[#E8F4F8] transition-colors flex items-center justify-center">
                            <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
                        </button>
                        <a href="{{ route('berkas.download', $dok) }}"
                           class="w-[28px] h-[28px] rounded-md border border-[#D0D5DD] text-[#5a6a75] hover:border-primary hover:text-primary hover:bg-[#E8F4F8] transition-colors flex items-center justify-center no-underline">
                            <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15v4a2 2 0 01-2 2H5a2 2 0 01-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
                        </a>
                        <button @click="openConfirm({{ $dok->id }})"
                                class="w-[28px] h-[28px] rounded-md border border-[#D0D5DD] text-[#5a6a75] hover:border-[#c62828] hover:text-[#c62828] hover:bg-[#FCE8E6] transition-colors flex items-center justify-center">
                            <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="3 6 5 6 21 6"/><path d="M19 6l-1 14a2 2 0 01-2 2H8a2 2 0 01-2-2L5 6"/><path d="M10 11v6M14 11v6"/></svg>
                        </button>
                    </div>
                </div>
                @endforeach
            </div>
            @endif
        </div>

    </div>

    {{-- Inline Viewer --}}
    <div x-show="viewer.open" x-cloak x-transition.opacity
         class="fixed inset-0 z-50 flex items-center justify-center bg-black/60"
         @click.self="viewer.open = false; viewer.url = ''">
        <div class="bg-white rounded-2xl shadow-2xl w-full max-w-3xl mx-4 flex flex-col overflow-hidden" style="max-height: 90vh">
            <div class="flex items-center justify-between px-5 py-3.5 border-b border-[#F0F2F5] shrink-0">
                <span x-text="viewer.name" class="text-[13px] font-semibold text-[#1a2a35] truncate max-w-[60%]"></span>
                <div class="flex items-center gap-3">
                    <a :href="viewer.url.replace('/view', '/download')"
                       class="inline-flex items-center gap-1.5 text-[11px] font-semibold text-primary hover:text-[#005f78] transition-colors no-underline">
                        <svg class="w-3.5 h-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M21 15v4a2 2 0 01-2 2H5a2 2 0 01-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/>
                        </svg>
                        Unduh
                    </a>
                    <button @click="viewer.open = false; viewer.url = ''" class="text-[#8a9ba8] hover:text-[#1a2a35] transition-colors p-1">
                        <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
                    </button>
                </div>
            </div>
            <div class="flex-1 min-h-0 overflow-auto bg-[#F0F2F5]" style="height: 75vh">
                <template x-if="viewer.type === 'pdf'">
                    <iframe :src="viewer.url" class="w-full border-0" style="height: 75vh"></iframe>
                </template>
                <template x-if="viewer.type === 'image'">
                    <div class="flex items-center justify-center p-6 min-h-full">
                        <img :src="viewer.url" class="max-w-full max-h-full object-contain rounded-lg shadow" />
                    </div>
                </template>
            </div>
        </div>
    </div>

    {{-- Konfirmasi hapus --}}
    <div x-show="confirm.open" x-cloak class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 p-4">
        <div @click.outside="confirm.open = false" class="bg-white rounded-2xl shadow-xl w-full max-w-sm p-6">
            <div class="flex items-center gap-3 mb-4">
                <div class="w-10 h-10 rounded-full bg-[#FCE8E6] flex items-center justify-center shrink-0">
                    <svg class="w-5 h-5 text-[#c62828]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <polyline points="3 6 5 6 21 6"/><path d="M19 6l-1 14a2 2 0 01-2 2H8a2 2 0 01-2-2L5 6"/>
                    </svg>
                </div>
                <div>
                    <div class="text-[14px] font-semibold text-[#1a2a35]">Hapus Berkas?</div>
                    <div class="text-[12px] text-[#8a9ba8]">Berkas akan dihapus permanen.</div>
                </div>
            </div>
            <div class="flex gap-3">
                <button @click="confirm.open = false" class="flex-1 h-[40px] bg-white border border-[#D8DDE2] text-[#1a2a35] text-[13px] font-semibold rounded-xl hover:bg-[#F4F6F8] transition-colors">Batal</button>
                <button @click="doHapus()" class="flex-1 h-[40px] bg-[#c62828] hover:bg-[#b71c1c] text-white text-[13px] font-semibold rounded-xl transition-colors">Ya, Hapus</button>
            </div>
        </div>
    </div>

</div>
