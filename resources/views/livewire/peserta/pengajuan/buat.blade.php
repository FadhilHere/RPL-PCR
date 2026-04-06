<?php

use Livewire\Attributes\Layout;
use Livewire\Volt\Component;
use App\Actions\Peserta\BuatPermohonanAction;
use App\Enums\SemesterEnum;
use App\Models\ProgramStudi;
use App\Models\TahunAjaran;

new #[Layout('components.layouts.peserta')] class extends Component {
    public ?int    $selectedProdiId = null;
    public ?int    $tahunAjaranId   = null;
    public ?string $semester        = null;

    public function mount(): void
    {
        $ta = TahunAjaran::aktif()->first();
        if ($ta) {
            $this->tahunAjaranId = $ta->id;
        }

        $peserta = auth()->user()->peserta;
        if ($peserta?->semester) {
            $this->semester = $peserta->semester->value;
        }
    }

    public function submit(BuatPermohonanAction $action): void
    {
        $this->validate([
            'selectedProdiId' => 'required|exists:program_studi,id',
            'semester'        => 'required|in:' . implode(',', array_column(SemesterEnum::cases(), 'value')),
        ], [
            'selectedProdiId.required' => 'Pilih program studi terlebih dahulu.',
            'semester.required'        => 'Pilih semester terlebih dahulu.',
        ]);

        $peserta = auth()->user()->peserta;
        abort_if(! $peserta, 403);

        $prodi   = ProgramStudi::findOrFail($this->selectedProdiId);
        $semEnum = SemesterEnum::from($this->semester);

        $action->execute($peserta, $prodi, $this->tahunAjaranId, $semEnum);

        $this->redirect(route('peserta.pengajuan.index'), navigate: true);
    }

    public function with(): array
    {
        return [
            'prodis'           => ProgramStudi::where('aktif', true)->orderBy('nama')->get(),
            'tahunAjaranAktif' => TahunAjaran::aktif()->first(),
        ];
    }
}; ?>

<x-slot:title>Buat Pengajuan RPL</x-slot:title>
<x-slot:subtitle><a href="{{ route('peserta.pengajuan.index') }}" class="text-primary hover:underline">Pengajuan RPL</a> &rsaquo; Buat Baru</x-slot:subtitle>

<div class="max-w-2xl">

    {{-- Info box --}}
    <div class="flex gap-3 bg-[#F0F7FA] border border-[#C5DDE5] rounded-xl px-4 py-3.5 mb-6">
        <svg class="w-4 h-4 text-primary shrink-0 mt-0.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/>
        </svg>
        <p class="text-[12px] text-[#1a2a35] leading-[1.6]">
            Setelah pengajuan dikirim, admin akan menentukan daftar mata kuliah yang akan Anda nilai.
            Anda akan diberi tahu saat proses pengisian asesmen mandiri sudah bisa dimulai.
        </p>
    </div>

    {{-- Periode --}}
    <div class="bg-white rounded-xl border border-[#E5E8EC] p-5 mb-5">
        <h3 class="text-[13px] font-semibold text-[#1a2a35] mb-4">Periode Pengajuan</h3>
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div>
                <label class="block text-[11px] font-semibold text-[#5a6a75] uppercase tracking-[0.7px] mb-1.5">Tahun Ajaran</label>
                @if ($tahunAjaranAktif)
                <div class="h-[42px] px-3 flex items-center text-[13px] font-medium text-[#1a2a35] bg-[#F4F6F8] border border-[#E0E5EA] rounded-xl">
                    {{ $tahunAjaranAktif->nama }}
                    <span class="ml-2 text-[10px] font-semibold text-[#1e7e3e] bg-[#E6F4EA] px-1.5 py-0.5 rounded-full">Aktif</span>
                </div>
                @else
                <div class="h-[42px] px-3 flex items-center text-[12px] text-[#b45309] bg-[#FFF3E0] border border-[#FFCC80] rounded-xl">
                    Belum ada tahun ajaran aktif. Hubungi admin.
                </div>
                @endif
            </div>
            <div>
                <label class="block text-[11px] font-semibold text-[#5a6a75] uppercase tracking-[0.7px] mb-1.5">Semester</label>
                @php $peserta = auth()->user()->peserta; @endphp
                @if ($peserta?->semester)
                <div class="h-[42px] px-3 flex items-center text-[13px] font-medium text-[#1a2a35] bg-[#F4F6F8] border border-[#E0E5EA] rounded-xl">
                    {{ $peserta->semester->label() }}
                </div>
                @else
                <div class="h-[42px] px-3 flex items-center text-[12px] text-[#b45309] bg-[#FFF3E0] border border-[#FFCC80] rounded-xl">
                    Semester belum diatur. Hubungi admin.
                </div>
                @endif
                @error('semester') <p class="mt-1 text-[11px] text-[#c62828]">{{ $message }}</p> @enderror
            </div>
        </div>
    </div>

    {{-- Pilih Prodi --}}
    <div class="mb-6">
        <h3 class="text-[13px] font-semibold text-[#1a2a35] mb-1">Pilih Program Studi</h3>
        <p class="text-[12px] text-[#8a9ba8] mb-3">Pilih satu program studi yang ingin Anda ajukan rekognisi.</p>

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-2.5">
            @foreach ($prodis as $prodi)
            <button wire:click="$set('selectedProdiId', {{ $prodi->id }})"
                    class="text-left px-4 py-3 rounded-xl border transition-all
                           {{ $selectedProdiId === $prodi->id
                               ? 'border-primary bg-[#E8F4F8] ring-2 ring-primary/20'
                               : 'border-[#E0E5EA] bg-white hover:border-primary/50' }}">
                <div class="flex items-center justify-between">
                    <span class="text-[13px] font-medium text-[#1a2a35]">{{ $prodi->nama }}</span>
                    <span class="text-[10px] font-semibold px-1.5 py-0.5 rounded
                        {{ $prodi->jenjang === 'S2' ? 'bg-[#E8F0FE] text-[#1557b0]' : 'bg-[#E6F4EA] text-[#1e7e3e]' }}">
                        {{ $prodi->jenjang }}
                    </span>
                </div>
                <div class="text-[11px] text-[#8a9ba8] mt-0.5">{{ $prodi->kode }}</div>
            </button>
            @endforeach
        </div>
        @error('selectedProdiId') <p class="mt-1.5 text-[11px] text-[#c62828]">{{ $message }}</p> @enderror
    </div>

    {{-- Tombol --}}
    <div class="flex gap-3">
        <a href="{{ route('peserta.pengajuan.index') }}"
           class="h-[44px] px-5 flex items-center justify-center bg-white border border-[#D8DDE2] text-[#1a2a35] text-[13px] font-semibold rounded-xl hover:bg-[#F4F6F8] transition-colors no-underline">
            Batal
        </a>
        <button wire:click="submit"
                wire:loading.attr="disabled"
                @disabled(! $selectedProdiId || ! $semester)
                class="h-[44px] px-6 bg-primary hover:bg-[#005f78] text-white text-[13px] font-semibold rounded-xl transition-colors disabled:opacity-50">
            <span wire:loading.remove>Kirim Pengajuan</span>
            <span wire:loading>Mengirim...</span>
        </button>
    </div>

</div>
