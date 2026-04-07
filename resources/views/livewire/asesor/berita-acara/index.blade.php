<?php

use Livewire\Attributes\Layout;
use Livewire\Volt\Component;
use App\Actions\Asesor\GenerateBeritaAcaraAction;
use App\Enums\PosisiPenandatanganEnum;
use App\Models\Asesor;
use App\Models\BeritaAcara;
use App\Models\Penandatangan;
use App\Models\TahunAjaran;

new #[Layout('components.layouts.asesor')] class extends Component {
    public string $tanggalAsesmen = '';

    public function generate(GenerateBeritaAcaraAction $action): void
    {
        $this->validate(['tanggalAsesmen' => 'required|date']);

        $asesor = auth()->user()->asesor;
        $tahunAjaran = TahunAjaran::aktif()->firstOrFail();

        $action->execute($asesor, $this->tanggalAsesmen, $tahunAjaran);

        $this->tanggalAsesmen = '';
        $this->dispatch('ba-generated');
    }

    public function toggleHadir(int $bapId): void
    {
        $bap = \App\Models\BeritaAcaraPeserta::findOrFail($bapId);
        abort_if($bap->beritaAcara->is_locked, 403, 'Berita acara sudah dikunci.');
        abort_if($bap->beritaAcara->asesor_id !== auth()->user()->asesor?->id, 403);

        $bap->update(['hadir' => ! $bap->hadir]);

        // Update counter
        $ba = $bap->beritaAcara;
        $hadir = $ba->peserta()->where('hadir', true)->count();
        $ba->update([
            'jumlah_hadir'        => $hadir,
            'jumlah_tidak_hadir'  => $ba->jumlah_peserta - $hadir,
        ]);
    }

    public function lock(int $baId): void
    {
        $ba = BeritaAcara::findOrFail($baId);
        abort_if($ba->asesor_id !== auth()->user()->asesor?->id, 403);
        $ba->update(['is_locked' => true]);
    }

    public function with(): array
    {
        $asesor = auth()->user()->asesor;
        $beritaAcaraList = BeritaAcara::with(['tahunAjaran', 'peserta.peserta.user', 'penandatanganKiri', 'penandatanganKanan'])
            ->where('asesor_id', $asesor?->id)
            ->orderByDesc('tanggal_asesmen')
            ->get();

        $tahunAjaranAktif = TahunAjaran::aktif()->first();

        return compact('beritaAcaraList', 'tahunAjaranAktif');
    }
}; ?>

<x-slot:title>Berita Acara</x-slot:title>
<x-slot:subtitle>Generate dan kelola berita acara asesmen RPL Anda</x-slot:subtitle>

<div>
    {{-- Generate BA --}}
    <div class="bg-white rounded-[10px] border border-[#E5E8EC] p-5 mb-5">
        <div class="text-[13px] font-semibold text-[#1a2a35] mb-4">Generate Berita Acara Baru</div>
        <div class="flex items-end gap-3">
            <div class="flex-1 max-w-xs">
                <label class="block text-[11px] font-semibold text-[#5a6a75] uppercase tracking-[0.7px] mb-1.5">Tanggal Asesmen</label>
                <input wire:model="tanggalAsesmen" type="date"
                       class="w-full h-[42px] px-3.5 text-[13px] text-[#1a2a35] bg-white border border-[#E0E5EA] rounded-xl outline-none focus:border-primary focus:ring-2 focus:ring-primary/10" />
                @error('tanggalAsesmen') <p class="mt-1 text-[11px] text-[#c62828]">{{ $message }}</p> @enderror
            </div>
            <button wire:click="generate" wire:loading.attr="disabled"
                    class="h-[42px] px-5 bg-primary hover:bg-[#005f78] text-white text-[13px] font-semibold rounded-xl transition-colors disabled:opacity-60">
                <span wire:loading.remove wire:target="generate">Generate BA</span>
                <span wire:loading wire:target="generate">Generating...</span>
            </button>
        </div>
    </div>

    {{-- Daftar BA --}}
    @forelse ($beritaAcaraList as $ba)
    <div class="bg-white rounded-[10px] border border-[#E5E8EC] overflow-hidden mb-4" wire:key="ba-{{ $ba->id }}">
        <div class="flex items-center justify-between px-5 py-3.5 border-b border-[#F0F2F5]">
            <div>
                <div class="text-[13px] font-semibold text-[#1a2a35]">
                    Berita Acara — {{ \Carbon\Carbon::parse($ba->tanggal_asesmen)->locale('id')->isoFormat('D MMMM YYYY') }}
                </div>
                <div class="text-[11px] text-[#8a9ba8] mt-0.5">
                    {{ $ba->tahunAjaran->nama ?? '—' }} &nbsp;·&nbsp;
                    {{ $ba->jumlah_peserta }} peserta &nbsp;·&nbsp;
                    {{ $ba->jumlah_hadir }} hadir &nbsp;·&nbsp;
                    {{ $ba->jumlah_tidak_hadir }} tidak hadir
                </div>
            </div>
            <div class="flex items-center gap-2">
                @if ($ba->is_locked)
                <span class="text-[10px] font-semibold px-2 py-0.5 rounded-full bg-[#FCE8E6] text-[#c62828]">Terkunci</span>
                @else
                <span class="text-[10px] font-semibold px-2 py-0.5 rounded-full bg-[#E6F4EA] text-[#1e7e3e]">Dapat Diedit</span>
                <button wire:click="lock({{ $ba->id }})"
                        wire:confirm="Kunci berita acara ini? Data tidak dapat diubah setelah dikunci."
                        class="text-[11px] font-semibold text-[#b45309] hover:text-[#92400e] border border-[#FCD34D] rounded-lg px-2.5 py-1 transition-colors">
                    Kunci BA
                </button>
                @endif
                <a href="{{ route('asesor.berita-acara.download', $ba) }}"
                   class="flex items-center gap-1.5 text-[11px] font-semibold text-primary hover:text-[#005f78] border border-[#D0D5DD] rounded-lg px-2.5 py-1 transition-colors no-underline">
                    <svg class="w-3.5 h-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M21 15v4a2 2 0 01-2 2H5a2 2 0 01-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/>
                    </svg>
                    Download PDF
                </a>
            </div>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-[12px]">
                <thead>
                    <tr class="border-b border-[#F0F2F5]">
                        <th class="text-left font-semibold text-[#8a9ba8] px-5 py-2.5">No</th>
                        <th class="text-left font-semibold text-[#8a9ba8] px-3 py-2.5">Nama Peserta</th>
                        <th class="text-center font-semibold text-[#8a9ba8] px-3 py-2.5">Total SKS Diperoleh</th>
                        <th class="text-center font-semibold text-[#8a9ba8] px-3 py-2.5">Hadir</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($ba->peserta as $i => $bap)
                    <tr class="border-b border-[#F6F8FA] last:border-0 hover:bg-[#FAFBFC]" wire:key="bap-{{ $bap->id }}">
                        <td class="px-5 py-3 text-[#8a9ba8]">{{ $i + 1 }}</td>
                        <td class="px-3 py-3 font-medium text-[#1a2a35]">{{ $bap->peserta?->user?->nama ?? '—' }}</td>
                        <td class="px-3 py-3 text-center text-[#1a2a35]">{{ $bap->total_sks_diperoleh }} SKS</td>
                        <td class="px-3 py-3 text-center">
                            @if (!$ba->is_locked)
                            <button wire:click="toggleHadir({{ $bap->id }})"
                                    class="relative inline-flex h-[22px] w-[40px] items-center rounded-full transition-colors
                                           {{ $bap->hadir ? 'bg-[#1e7e3e]' : 'bg-[#D0D5DD]' }}">
                                <span class="inline-block h-[16px] w-[16px] transform rounded-full bg-white shadow transition-transform
                                             {{ $bap->hadir ? 'translate-x-[21px]' : 'translate-x-[3px]' }}"></span>
                            </button>
                            @else
                            <span class="text-[10px] font-semibold px-2 py-0.5 rounded-full {{ $bap->hadir ? 'bg-[#E6F4EA] text-[#1e7e3e]' : 'bg-[#FCE8E6] text-[#c62828]' }}">
                                {{ $bap->hadir ? 'Hadir' : 'Tidak Hadir' }}
                            </span>
                            @endif
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
    @empty
    <div class="bg-white rounded-[10px] border border-[#E5E8EC] px-5 py-10 text-center text-[13px] text-[#8a9ba8]">
        Belum ada berita acara. Generate berita acara pertama Anda di atas.
    </div>
    @endforelse
</div>
