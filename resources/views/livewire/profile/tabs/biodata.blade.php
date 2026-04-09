<?php

use App\Actions\Profil\SimpanBiodataPesertaAction;
use App\Livewire\Concerns\HasProfilBiodataForm;
use App\Models\Peserta;
use Livewire\Attributes\Locked;
use Livewire\Volt\Component;
use Livewire\WithFileUploads;

new class extends Component {
    use WithFileUploads;
    use HasProfilBiodataForm;

    #[Locked]
    public int $pesertaId;
    public bool $enforceOwnership = true;

    public ?string $fotoUrl = null;

    public function mount(int $pesertaId, bool $enforceOwnership = true): void
    {
        $this->pesertaId = $pesertaId;
        $this->enforceOwnership = $enforceOwnership;

        if ($this->enforceOwnership) {
            $this->guardPesertaScope();
        }

        $peserta = $this->resolvePeserta();

        $this->fillBiodataForm($peserta);
        $this->fotoUrl = $peserta->foto ? route('peserta.foto', $peserta) : null;
    }

    protected function guardPesertaScope(): void
    {
        $authPesertaId = auth()->user()?->peserta?->id;

        if ($authPesertaId !== null) {
            abort_if((int) $authPesertaId !== $this->pesertaId, 403);
        }
    }

    protected function resolvePeserta(): Peserta
    {
        return Peserta::query()
            ->with('user')
            ->findOrFail($this->pesertaId);
    }

    public function simpanBiodata(SimpanBiodataPesertaAction $action): void
    {
        $peserta = $this->resolvePeserta();

        $this->validate($this->biodataRules($peserta->user_id));

        $action->execute(
            $peserta,
            $this->nama,
            $this->email,
            $this->biodataPayload(),
            $this->foto,
        );

        $peserta->refresh();

        $this->fillBiodataForm($peserta);
        $this->fotoUrl = $peserta->foto ? route('peserta.foto', $peserta) : null;

        $this->reset('foto');
        $this->dispatch('biodata-saved');
    }
};
?>

<div>
    <div class="bg-white rounded-xl border border-[#E5E8EC] p-5 mb-4">
        <div class="text-[13px] font-semibold text-[#1a2a35] mb-4">Data Akun &amp; Identitas</div>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">

            <div>
                <label class="block text-[11px] font-semibold text-[#5a6a75] uppercase tracking-[0.7px] mb-1.5">Nama Lengkap <span class="text-[#D2092F]">*</span></label>
                <input wire:model="nama" type="text" placeholder="Nama sesuai KTP"
                    class="w-full h-[40px] px-3.5 text-[13px] text-[#1a2a35] bg-white border border-[#E0E5EA] rounded-xl outline-none focus:border-primary focus:ring-2 focus:ring-primary/10 placeholder:text-[#b0bec5]" />
                @error('nama') <p class="mt-1 text-[11px] text-[#c62828]">{{ $message }}</p> @enderror
            </div>

            <div>
                <label class="block text-[11px] font-semibold text-[#5a6a75] uppercase tracking-[0.7px] mb-1.5">Email <span class="text-[#D2092F]">*</span></label>
                <input wire:model="email" type="email" placeholder="nama@email.com"
                    class="w-full h-[40px] px-3.5 text-[13px] text-[#1a2a35] bg-white border border-[#E0E5EA] rounded-xl outline-none focus:border-primary focus:ring-2 focus:ring-primary/10 placeholder:text-[#b0bec5]" />
                @error('email') <p class="mt-1 text-[11px] text-[#c62828]">{{ $message }}</p> @enderror
            </div>

            <div>
                <label class="block text-[11px] font-semibold text-[#5a6a75] uppercase tracking-[0.7px] mb-1.5">NIP / NIK</label>
                <input wire:model="nik" type="text" placeholder="Nomor identitas"
                    class="w-full h-[40px] px-3.5 text-[13px] text-[#1a2a35] bg-white border border-[#E0E5EA] rounded-xl outline-none focus:border-primary focus:ring-2 focus:ring-primary/10 placeholder:text-[#b0bec5]" />
            </div>

            <div>
                <label class="block text-[11px] font-semibold text-[#5a6a75] uppercase tracking-[0.7px] mb-1.5">No. HP / WA</label>
                <input wire:model="telepon" type="text" placeholder="08xxxxxxxxxx"
                    class="w-full h-[40px] px-3.5 text-[13px] text-[#1a2a35] bg-white border border-[#E0E5EA] rounded-xl outline-none focus:border-primary focus:ring-2 focus:ring-primary/10 placeholder:text-[#b0bec5]" />
            </div>

            <div>
                <label class="block text-[11px] font-semibold text-[#5a6a75] uppercase tracking-[0.7px] mb-1.5">Telepon / Faks <span class="font-normal text-[#b0bec5] normal-case">(opsional)</span></label>
                <input wire:model="teleponFaks" type="text" placeholder="Nomor faks jika ada"
                    class="w-full h-[40px] px-3.5 text-[13px] text-[#1a2a35] bg-white border border-[#E0E5EA] rounded-xl outline-none focus:border-primary focus:ring-2 focus:ring-primary/10 placeholder:text-[#b0bec5]" />
            </div>

            <div x-data="{
                open: false,
                val: @entangle('jenisKelamin').live,
                opts: [{v:'L',l:'Laki-laki'},{v:'P',l:'Perempuan'}],
                get label() { return this.opts.find(o=>o.v===this.val)?.l ?? 'Pilih jenis kelamin'; }
            }">
                <label class="block text-[11px] font-semibold text-[#5a6a75] uppercase tracking-[0.7px] mb-1.5">Jenis Kelamin</label>
                <div class="relative">
                    <button type="button" @click="open=!open"
                        :class="open ? 'border-primary ring-2 ring-primary/10' : 'border-[#E0E5EA] hover:border-[#C5CDD5]'"
                        class="w-full h-[40px] px-3.5 flex items-center justify-between bg-white border rounded-xl text-[13px] transition-all">
                        <span :class="val ? 'text-[#1a2a35]' : 'text-[#b0bec5]'" x-text="label"></span>
                        <svg class="w-4 h-4 text-[#8a9ba8] shrink-0" :class="open && 'rotate-180'" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="6 9 12 15 18 9"/></svg>
                    </button>
                    <div x-show="open" @click.outside="open=false" x-cloak
                        x-transition:enter="transition ease-out duration-100" x-transition:enter-start="opacity-0 -translate-y-1" x-transition:enter-end="opacity-100 translate-y-0"
                        class="absolute z-20 mt-1 w-full bg-white border border-[#E0E5EA] rounded-xl shadow-lg overflow-hidden">
                        <template x-for="o in opts" :key="o.v">
                            <button type="button" @click="val=o.v; open=false"
                                :class="val===o.v ? 'bg-[#E8F4F8] text-primary font-semibold' : 'hover:bg-[#F4F6F8] text-[#1a2a35]'"
                                class="w-full px-3.5 py-2 text-left text-[13px] transition-colors" x-text="o.l"></button>
                        </template>
                    </div>
                </div>
            </div>

            <div>
                <label class="block text-[11px] font-semibold text-[#5a6a75] uppercase tracking-[0.7px] mb-1.5">Tempat Lahir</label>
                <input wire:model="tempatLahir" type="text" placeholder="Kota tempat lahir"
                    class="w-full h-[40px] px-3.5 text-[13px] text-[#1a2a35] bg-white border border-[#E0E5EA] rounded-xl outline-none focus:border-primary focus:ring-2 focus:ring-primary/10 placeholder:text-[#b0bec5]" />
            </div>

            <div x-data="{ val: @entangle('tanggalLahir').live }">
                <label class="block text-[11px] font-semibold text-[#5a6a75] uppercase tracking-[0.7px] mb-1.5">Tanggal Lahir</label>
                <x-form.date-picker x-model="val" :enable-time="false" placeholder="Pilih tanggal lahir" class="w-full" />
                @error('tanggalLahir') <p class="mt-1 text-[11px] text-[#c62828]">{{ $message }}</p> @enderror
            </div>

            <div>
                <label class="block text-[11px] font-semibold text-[#5a6a75] uppercase tracking-[0.7px] mb-1.5">Agama</label>
                <input wire:model="agama" type="text" placeholder="Islam, Kristen, dll."
                    class="w-full h-[40px] px-3.5 text-[13px] text-[#1a2a35] bg-white border border-[#E0E5EA] rounded-xl outline-none focus:border-primary focus:ring-2 focus:ring-primary/10 placeholder:text-[#b0bec5]" />
            </div>

            <div>
                <label class="block text-[11px] font-semibold text-[#5a6a75] uppercase tracking-[0.7px] mb-1.5">Golongan / Pangkat <span class="font-normal text-[#b0bec5] normal-case">(opsional)</span></label>
                <input wire:model="golonganPangkat" type="text" placeholder="Contoh: III/A"
                    class="w-full h-[40px] px-3.5 text-[13px] text-[#1a2a35] bg-white border border-[#E0E5EA] rounded-xl outline-none focus:border-primary focus:ring-2 focus:ring-primary/10 placeholder:text-[#b0bec5]" />
            </div>

            <div>
                <label class="block text-[11px] font-semibold text-[#5a6a75] uppercase tracking-[0.7px] mb-1.5">Instansi <span class="font-normal text-[#b0bec5] normal-case">(opsional)</span></label>
                <input wire:model="instansi" type="text" placeholder="Nama perusahaan / instansi"
                    class="w-full h-[40px] px-3.5 text-[13px] text-[#1a2a35] bg-white border border-[#E0E5EA] rounded-xl outline-none focus:border-primary focus:ring-2 focus:ring-primary/10 placeholder:text-[#b0bec5]" />
            </div>

            <div>
                <label class="block text-[11px] font-semibold text-[#5a6a75] uppercase tracking-[0.7px] mb-1.5">Pekerjaan / Jabatan <span class="font-normal text-[#b0bec5] normal-case">(opsional)</span></label>
                <input wire:model="pekerjaan" type="text" placeholder="Teknisi, Supervisor, dll."
                    class="w-full h-[40px] px-3.5 text-[13px] text-[#1a2a35] bg-white border border-[#E0E5EA] rounded-xl outline-none focus:border-primary focus:ring-2 focus:ring-primary/10 placeholder:text-[#b0bec5]" />
            </div>

            <div class="md:col-span-2">
                <label class="block text-[11px] font-semibold text-[#5a6a75] uppercase tracking-[0.7px] mb-1.5">Alamat</label>
                <textarea wire:model="alamat" rows="2" placeholder="Alamat lengkap"
                    class="w-full px-3.5 py-2 text-[13px] text-[#1a2a35] bg-white border border-[#E0E5EA] rounded-xl outline-none focus:border-primary focus:ring-2 focus:ring-primary/10 placeholder:text-[#b0bec5] resize-none"></textarea>
            </div>

            <div class="md:col-span-2" x-data="{
                provOpen: false, kotaOpen: false,
                provinces: [], regencies: [],
                loadingProv: true, loadingKota: false,
                provName: @entangle('provinsi').live,
                kotaName: @entangle('kota').live,
                provCode: null,
                provSearch: '', kotaSearch: '',
                get filteredProv() { return this.provinces.filter(p => p.name.toLowerCase().includes(this.provSearch.toLowerCase())); },
                get filteredKota() { return this.regencies.filter(r => r.name.toLowerCase().includes(this.kotaSearch.toLowerCase())); },
                async init() {
                    const r = await fetch('https://wilayah.id/api/provinces.json');
                    const d = await r.json();
                    this.provinces = d.data ?? [];
                    this.loadingProv = false;
                    if (this.provName) {
                        const found = this.provinces.find(p => p.name === this.provName);
                        if (found) {
                            this.provCode = found.code;
                            const r2 = await fetch('https://wilayah.id/api/regencies/' + found.code + '.json');
                            const d2 = await r2.json();
                            this.regencies = d2.data ?? [];
                        }
                    }
                },
                async selectProv(code, name) {
                    this.provName = name; this.provCode = code; this.provOpen = false; this.provSearch = '';
                    this.kotaName = ''; this.regencies = []; this.loadingKota = true;
                    const r = await fetch('https://wilayah.id/api/regencies/' + code + '.json');
                    const d = await r.json();
                    this.regencies = d.data ?? []; this.loadingKota = false;
                },
                selectKota(name) { this.kotaName = name; this.kotaOpen = false; this.kotaSearch = ''; }
            }">
                <div class="grid grid-cols-3 gap-x-3">
                    <div>
                        <label class="block text-[11px] font-semibold text-[#5a6a75] uppercase tracking-[0.7px] mb-1.5">Provinsi</label>
                        <div class="relative">
                            <button type="button" @click="provOpen=!provOpen; if(provOpen) $nextTick(()=>$refs.provSearch.focus())"
                                :class="provOpen ? 'border-primary ring-2 ring-primary/10' : 'border-[#E0E5EA] hover:border-[#C5CDD5]'"
                                class="w-full h-[40px] px-3.5 flex items-center justify-between bg-white border rounded-xl text-[13px] transition-all">
                                <span :class="provName ? 'text-[#1a2a35]' : 'text-[#b0bec5]'" x-text="provName || 'Pilih provinsi'" class="truncate mr-1"></span>
                                <svg class="w-4 h-4 text-[#8a9ba8] shrink-0" :class="provOpen && 'rotate-180'" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="6 9 12 15 18 9"/></svg>
                            </button>
                            <div x-show="provOpen" @click.outside="provOpen=false" x-cloak
                                x-transition:enter="transition ease-out duration-100" x-transition:enter-start="opacity-0 -translate-y-1" x-transition:enter-end="opacity-100 translate-y-0"
                                class="absolute z-30 mt-1 w-full bg-white border border-[#E0E5EA] rounded-xl shadow-lg overflow-hidden">
                                <div class="p-2 border-b border-[#F0F2F5]">
                                    <input x-ref="provSearch" x-model="provSearch" type="text" placeholder="Cari provinsi..." class="w-full h-[32px] px-2.5 text-[12px] bg-[#F4F6F8] border border-transparent rounded-lg outline-none focus:border-primary placeholder:text-[#b0bec5]" />
                                </div>
                                <div class="max-h-[180px] overflow-y-auto">
                                    <template x-if="loadingProv"><div class="py-3 text-center text-[12px] text-[#8a9ba8]">Memuat...</div></template>
                                    <template x-if="!loadingProv && filteredProv.length === 0"><div class="py-3 text-center text-[12px] text-[#8a9ba8]">Tidak ditemukan</div></template>
                                    <template x-for="p in filteredProv" :key="p.code">
                                        <button type="button" @click="selectProv(p.code, p.name)"
                                            :class="provName===p.name ? 'bg-[#E8F4F8] text-primary font-semibold' : 'hover:bg-[#F4F6F8] text-[#1a2a35]'"
                                            class="w-full px-3.5 py-2 text-left text-[12px] transition-colors" x-text="p.name"></button>
                                    </template>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div>
                        <label class="block text-[11px] font-semibold text-[#5a6a75] uppercase tracking-[0.7px] mb-1.5">Kota / Kabupaten</label>
                        <div class="relative">
                            <button type="button"
                                @click="if(provCode) { kotaOpen=!kotaOpen; if(kotaOpen) $nextTick(()=>$refs.kotaSearch.focus()); }"
                                :disabled="!provCode"
                                :class="kotaOpen ? 'border-primary ring-2 ring-primary/10' : (provCode ? 'border-[#E0E5EA] hover:border-[#C5CDD5]' : 'border-[#E0E5EA] opacity-50 cursor-not-allowed')"
                                class="w-full h-[40px] px-3.5 flex items-center justify-between bg-white border rounded-xl text-[13px] transition-all">
                                <span :class="kotaName ? 'text-[#1a2a35]' : 'text-[#b0bec5]'" x-text="loadingKota ? 'Memuat...' : (kotaName || (provCode ? 'Pilih kota' : 'Pilih provinsi dulu'))" class="truncate mr-1"></span>
                                <svg class="w-4 h-4 text-[#8a9ba8] shrink-0" :class="kotaOpen && 'rotate-180'" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="6 9 12 15 18 9"/></svg>
                            </button>
                            <div x-show="kotaOpen" @click.outside="kotaOpen=false" x-cloak
                                x-transition:enter="transition ease-out duration-100" x-transition:enter-start="opacity-0 -translate-y-1" x-transition:enter-end="opacity-100 translate-y-0"
                                class="absolute z-30 mt-1 w-full bg-white border border-[#E0E5EA] rounded-xl shadow-lg overflow-hidden">
                                <div class="p-2 border-b border-[#F0F2F5]">
                                    <input x-ref="kotaSearch" x-model="kotaSearch" type="text" placeholder="Cari kota..." class="w-full h-[32px] px-2.5 text-[12px] bg-[#F4F6F8] border border-transparent rounded-lg outline-none focus:border-primary placeholder:text-[#b0bec5]" />
                                </div>
                                <div class="max-h-[180px] overflow-y-auto">
                                    <template x-if="filteredKota.length === 0"><div class="py-3 text-center text-[12px] text-[#8a9ba8]">Tidak ditemukan</div></template>
                                    <template x-for="k in filteredKota" :key="k.code">
                                        <button type="button" @click="selectKota(k.name)"
                                            :class="kotaName===k.name ? 'bg-[#E8F4F8] text-primary font-semibold' : 'hover:bg-[#F4F6F8] text-[#1a2a35]'"
                                            class="w-full px-3.5 py-2 text-left text-[12px] transition-colors" x-text="k.name"></button>
                                    </template>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div>
                        <label class="block text-[11px] font-semibold text-[#5a6a75] uppercase tracking-[0.7px] mb-1.5">Kode Pos</label>
                        <input wire:model="kodePos" type="text" placeholder="Kode pos" maxlength="10"
                            class="w-full h-[40px] px-3.5 text-[13px] text-[#1a2a35] bg-white border border-[#E0E5EA] rounded-xl outline-none focus:border-primary focus:ring-2 focus:ring-primary/10 placeholder:text-[#b0bec5]" />
                    </div>
                </div>
            </div>

        </div>
    </div>

    <div class="bg-white rounded-xl border border-[#E5E8EC] p-5 mb-4">
        <div class="text-[13px] font-semibold text-[#1a2a35] mb-4">Pas Foto</div>
        <div x-data="{ preview: null }" class="flex items-start gap-4">
            <div class="shrink-0 w-[48px] h-[64px] rounded-lg border-2 border-dashed border-[#D0D5DD] bg-[#F4F6F8] overflow-hidden flex items-center justify-center">
                <template x-if="preview">
                    <img :src="preview" class="w-full h-full object-cover" />
                </template>
                <template x-if="!preview">
                    @if ($fotoUrl)
                    <img src="{{ $fotoUrl }}" class="w-full h-full object-cover" />
                    @else
                    <svg class="w-4 h-4 text-[#b0bec5]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><rect x="3" y="3" width="18" height="18" rx="2"/><circle cx="8.5" cy="8.5" r="1.5"/><polyline points="21 15 16 10 5 21"/></svg>
                    @endif
                </template>
            </div>
            <div class="flex-1">
                <label class="flex items-center gap-3 h-[64px] px-4 border-2 border-dashed border-[#D0D5DD] rounded-xl bg-white hover:border-primary hover:bg-[#F0F7FA] transition-all cursor-pointer group">
                    <input type="file" wire:model="foto" accept="image/jpg,image/jpeg,image/png" class="hidden"
                        @change="const f=$event.target.files[0]; if(f){const r=new FileReader();r.onload=e=>preview=e.target.result;r.readAsDataURL(f);}" />
                    <svg class="w-5 h-5 text-[#8a9ba8] group-hover:text-primary transition-colors shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="16 16 12 12 8 16"/><line x1="12" y1="12" x2="12" y2="21"/><path d="M20.39 18.39A5 5 0 0018 9h-1.26A8 8 0 103 16.3"/></svg>
                    <div>
                        <div class="text-[12px] font-medium text-[#5a6a75] group-hover:text-primary transition-colors">Latar merah, ukuran 3x4</div>
                        <div class="text-[11px] text-[#b0bec5] mt-0.5">JPG / PNG, maks 2 MB</div>
                    </div>
                </label>
                <div wire:loading wire:target="foto" class="mt-1 text-[11px] text-[#8a9ba8]">Mengupload...</div>
                @error('foto') <p class="mt-1 text-[11px] text-[#c62828]">{{ $message }}</p> @enderror
            </div>
        </div>
    </div>

    <div class="flex justify-end">
        <button wire:click="simpanBiodata" wire:loading.attr="disabled"
            class="h-[42px] px-6 bg-primary hover:bg-[#005f78] text-white text-[13px] font-semibold rounded-xl transition-colors disabled:opacity-60">
            <span wire:loading.remove wire:target="simpanBiodata">Simpan Biodata</span>
            <span wire:loading wire:target="simpanBiodata">Menyimpan...</span>
        </button>
    </div>
</div>
