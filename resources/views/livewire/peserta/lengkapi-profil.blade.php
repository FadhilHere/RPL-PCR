<?php

use App\Actions\Peserta\LengkapiProfilAction;
use App\Models\Peserta;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;

new #[Layout('components.layouts.peserta')] class extends Component {

    public function simpan(array $biodata, array $seksi): void
    {
        $peserta = auth()->user()->peserta;
        abort_if(! $peserta, 403);

        // Validasi: riwayat pendidikan minimal 1 entry
        $this->validate([
            'biodata.nik'       => ['nullable', 'string', 'max:20'],
            'biodata.telepon'   => ['nullable', 'string', 'max:20'],
            'biodata.alamat'    => ['nullable', 'string'],
            'biodata.agama'     => ['nullable', 'string', 'max:50'],
        ]);

        // Validasi riwayat pendidikan minimal 1 via validator helper
        $errors = [];
        $pendidikan = $seksi['riwayatPendidikan'] ?? [];
        $valid = collect($pendidikan)->filter(fn($r) => ! empty($r['namaSekolah']))->count();
        if ($valid === 0) {
            $errors['riwayat_pendidikan'] = 'Riwayat pendidikan minimal 1 entry harus diisi.';
        }

        if (! empty($errors)) {
            $this->dispatch('validation-errors', errors: $errors);
            return;
        }

        (new LengkapiProfilAction)->execute($peserta, $biodata, $seksi);

        $this->redirect(route('peserta.dashboard'), navigate: true);
    }
}; ?>

<x-slot:title>Lengkapi Profil</x-slot:title>

<div
    x-data="{
        tab: 'biodata',
        tabs: ['biodata','pendidikan','pelatihan','konferensi','penghargaan','organisasi'],
        tabLabels: {
            biodata:     'Biodata',
            pendidikan:  'Riwayat Pendidikan',
            pelatihan:   'Pelatihan',
            konferensi:  'Konferensi / Seminar',
            penghargaan: 'Penghargaan',
            organisasi:  'Organisasi Profesi',
        },
        errors: {},

        biodata: {
            nik: '', telepon: '', teleponFaks: '', alamat: '', kota: '',
            provinsi: '', kodePos: '', tempatLahir: '', tanggalLahir: '',
            jenisKelamin: '', agama: '', golonganPangkat: '', instansi: '', pekerjaan: '',
        },

        seksi: {
            riwayatPendidikan: [{ namaSekolah: '', tahunLulus: '', jurusan: '' }],
            pelatihan:         [],
            konferensi:        [],
            penghargaan:       [],
            organisasi:        [],
        },

        addRow(key, template) { this.seksi[key].push({ ...template }); },
        removeRow(key, idx)   { this.seksi[key].splice(idx, 1); },

        async submit() {
            this.errors = {};
            await $wire.simpan(this.biodata, this.seksi);
        }
    }"
    @validation-errors.window="errors = $event.detail.errors"
>

    {{-- Banner info --}}
    <div class="bg-[#F0F7FA] border border-[#C5DDE5] rounded-xl px-4 py-3.5 mb-6 flex gap-3">
        <svg class="w-4 h-4 text-primary shrink-0 mt-0.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/>
        </svg>
        <p class="text-[12px] text-[#1a2a35] leading-[1.6]">
            Lengkapi riwayat hidup Anda sebelum memulai proses RPL. <strong>Riwayat Pendidikan wajib diisi</strong> minimal satu entry. Bagian lainnya bersifat opsional.
        </p>
    </div>

    {{-- Tab navigasi --}}
    <div class="bg-white rounded-xl border border-[#E5E8EC] overflow-hidden mb-1">
        <div class="flex overflow-x-auto border-b border-[#F0F2F5]">
            <template x-for="t in tabs" :key="t">
                <button
                    @click="tab = t"
                    :class="tab === t
                        ? 'border-b-2 border-primary text-primary font-semibold'
                        : 'text-[#8a9ba8] hover:text-[#1a2a35]'"
                    class="px-4 py-3 text-[12px] whitespace-nowrap transition-colors shrink-0">
                    <span x-text="tabLabels[t]"></span>
                    <span x-show="t === 'pendidikan'" class="ml-1 text-[#c62828] font-bold">*</span>
                </button>
            </template>
        </div>
    </div>

    {{-- ============ TAB: BIODATA ============ --}}
    <div x-show="tab === 'biodata'" class="bg-white rounded-xl border border-[#E5E8EC] p-6 mb-4">
        <h3 class="text-[13px] font-semibold text-[#1a2a35] mb-4">Biodata Diri</h3>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">

            {{-- Nama (readonly dari user) --}}
            <div>
                <label class="block text-[11px] font-semibold text-[#5a6a75] uppercase tracking-[0.7px] mb-1.5">Nama Lengkap</label>
                <input type="text" value="{{ auth()->user()->nama }}" disabled
                       class="w-full h-[44px] px-3 text-[13px] text-[#8a9ba8] bg-[#F8FAFB] border border-[#E0E5EA] rounded-xl cursor-not-allowed" />
            </div>

            {{-- NIK --}}
            <div>
                <label class="block text-[11px] font-semibold text-[#5a6a75] uppercase tracking-[0.7px] mb-1.5">NIP / NIK</label>
                <input type="text" x-model="biodata.nik" placeholder="Nomor identitas"
                       class="w-full h-[44px] px-3 text-[13px] text-[#1a2a35] bg-white border border-[#E0E5EA] rounded-xl outline-none focus:border-primary focus:ring-2 focus:ring-primary/10 placeholder:text-[#b0bec5]" />
            </div>

            {{-- Tempat Lahir --}}
            <div>
                <label class="block text-[11px] font-semibold text-[#5a6a75] uppercase tracking-[0.7px] mb-1.5">Tempat Lahir</label>
                <input type="text" x-model="biodata.tempatLahir" placeholder="Kota tempat lahir"
                       class="w-full h-[44px] px-3 text-[13px] text-[#1a2a35] bg-white border border-[#E0E5EA] rounded-xl outline-none focus:border-primary focus:ring-2 focus:ring-primary/10 placeholder:text-[#b0bec5]" />
            </div>

            {{-- Tanggal Lahir --}}
            <div>
                <label class="block text-[11px] font-semibold text-[#5a6a75] uppercase tracking-[0.7px] mb-1.5">Tanggal Lahir</label>
                <input type="date" x-model="biodata.tanggalLahir"
                       class="w-full h-[44px] px-3 text-[13px] text-[#1a2a35] bg-white border border-[#E0E5EA] rounded-xl outline-none focus:border-primary focus:ring-2 focus:ring-primary/10" />
            </div>

            {{-- Jenis Kelamin --}}
            <div>
                <label class="block text-[11px] font-semibold text-[#5a6a75] uppercase tracking-[0.7px] mb-1.5">Jenis Kelamin</label>
                <div class="flex gap-3 mt-1">
                    <label class="flex items-center gap-2 cursor-pointer">
                        <input type="radio" x-model="biodata.jenisKelamin" value="L" class="accent-primary" />
                        <span class="text-[13px] text-[#1a2a35]">Laki-laki</span>
                    </label>
                    <label class="flex items-center gap-2 cursor-pointer">
                        <input type="radio" x-model="biodata.jenisKelamin" value="P" class="accent-primary" />
                        <span class="text-[13px] text-[#1a2a35]">Perempuan</span>
                    </label>
                </div>
            </div>

            {{-- Agama --}}
            <div>
                <label class="block text-[11px] font-semibold text-[#5a6a75] uppercase tracking-[0.7px] mb-1.5">Agama</label>
                <input type="text" x-model="biodata.agama" placeholder="Islam, Kristen, dll."
                       class="w-full h-[44px] px-3 text-[13px] text-[#1a2a35] bg-white border border-[#E0E5EA] rounded-xl outline-none focus:border-primary focus:ring-2 focus:ring-primary/10 placeholder:text-[#b0bec5]" />
            </div>

            {{-- Golongan/Pangkat --}}
            <div>
                <label class="block text-[11px] font-semibold text-[#5a6a75] uppercase tracking-[0.7px] mb-1.5">Golongan / Pangkat <span class="text-[#b0bec5] font-normal normal-case">(opsional)</span></label>
                <input type="text" x-model="biodata.golonganPangkat" placeholder="Contoh: III/A"
                       class="w-full h-[44px] px-3 text-[13px] text-[#1a2a35] bg-white border border-[#E0E5EA] rounded-xl outline-none focus:border-primary focus:ring-2 focus:ring-primary/10 placeholder:text-[#b0bec5]" />
            </div>

            {{-- Instansi --}}
            <div>
                <label class="block text-[11px] font-semibold text-[#5a6a75] uppercase tracking-[0.7px] mb-1.5">Instansi <span class="text-[#b0bec5] font-normal normal-case">(opsional)</span></label>
                <input type="text" x-model="biodata.instansi" placeholder="Nama perusahaan/instansi"
                       class="w-full h-[44px] px-3 text-[13px] text-[#1a2a35] bg-white border border-[#E0E5EA] rounded-xl outline-none focus:border-primary focus:ring-2 focus:ring-primary/10 placeholder:text-[#b0bec5]" />
            </div>

            {{-- Pekerjaan --}}
            <div>
                <label class="block text-[11px] font-semibold text-[#5a6a75] uppercase tracking-[0.7px] mb-1.5">Pekerjaan / Jabatan <span class="text-[#b0bec5] font-normal normal-case">(opsional)</span></label>
                <input type="text" x-model="biodata.pekerjaan" placeholder="Contoh: Teknisi, Supervisor"
                       class="w-full h-[44px] px-3 text-[13px] text-[#1a2a35] bg-white border border-[#E0E5EA] rounded-xl outline-none focus:border-primary focus:ring-2 focus:ring-primary/10 placeholder:text-[#b0bec5]" />
            </div>

            {{-- Telepon --}}
            <div>
                <label class="block text-[11px] font-semibold text-[#5a6a75] uppercase tracking-[0.7px] mb-1.5">Telepon / HP</label>
                <input type="text" x-model="biodata.telepon" placeholder="08xxxxxxxxxx"
                       class="w-full h-[44px] px-3 text-[13px] text-[#1a2a35] bg-white border border-[#E0E5EA] rounded-xl outline-none focus:border-primary focus:ring-2 focus:ring-primary/10 placeholder:text-[#b0bec5]" />
            </div>

            {{-- Telepon Faks --}}
            <div>
                <label class="block text-[11px] font-semibold text-[#5a6a75] uppercase tracking-[0.7px] mb-1.5">Telepon / Faks <span class="text-[#b0bec5] font-normal normal-case">(opsional)</span></label>
                <input type="text" x-model="biodata.teleponFaks" placeholder="Nomor faks jika ada"
                       class="w-full h-[44px] px-3 text-[13px] text-[#1a2a35] bg-white border border-[#E0E5EA] rounded-xl outline-none focus:border-primary focus:ring-2 focus:ring-primary/10 placeholder:text-[#b0bec5]" />
            </div>

            {{-- Alamat (full width) --}}
            <div class="md:col-span-2">
                <label class="block text-[11px] font-semibold text-[#5a6a75] uppercase tracking-[0.7px] mb-1.5">Alamat</label>
                <textarea x-model="biodata.alamat" rows="2" placeholder="Jalan, RT/RW, Kelurahan, Kecamatan"
                          class="w-full px-3 py-2.5 text-[13px] text-[#1a2a35] bg-white border border-[#E0E5EA] rounded-xl outline-none focus:border-primary focus:ring-2 focus:ring-primary/10 placeholder:text-[#b0bec5] resize-none"></textarea>
            </div>

            {{-- Kota --}}
            <div>
                <label class="block text-[11px] font-semibold text-[#5a6a75] uppercase tracking-[0.7px] mb-1.5">Kota <span class="text-[#b0bec5] font-normal normal-case">(opsional)</span></label>
                <input type="text" x-model="biodata.kota" placeholder="Nama kota"
                       class="w-full h-[44px] px-3 text-[13px] text-[#1a2a35] bg-white border border-[#E0E5EA] rounded-xl outline-none focus:border-primary focus:ring-2 focus:ring-primary/10 placeholder:text-[#b0bec5]" />
            </div>

            {{-- Provinsi --}}
            <div>
                <label class="block text-[11px] font-semibold text-[#5a6a75] uppercase tracking-[0.7px] mb-1.5">Provinsi <span class="text-[#b0bec5] font-normal normal-case">(opsional)</span></label>
                <input type="text" x-model="biodata.provinsi" placeholder="Nama provinsi"
                       class="w-full h-[44px] px-3 text-[13px] text-[#1a2a35] bg-white border border-[#E0E5EA] rounded-xl outline-none focus:border-primary focus:ring-2 focus:ring-primary/10 placeholder:text-[#b0bec5]" />
            </div>

            {{-- Kode Pos --}}
            <div>
                <label class="block text-[11px] font-semibold text-[#5a6a75] uppercase tracking-[0.7px] mb-1.5">Kode Pos <span class="text-[#b0bec5] font-normal normal-case">(opsional)</span></label>
                <input type="text" x-model="biodata.kodePos" placeholder="12345"
                       class="w-full h-[44px] px-3 text-[13px] text-[#1a2a35] bg-white border border-[#E0E5EA] rounded-xl outline-none focus:border-primary focus:ring-2 focus:ring-primary/10 placeholder:text-[#b0bec5]" />
            </div>

        </div>
    </div>

    {{-- ============ TAB: RIWAYAT PENDIDIKAN ============ --}}
    <div x-show="tab === 'pendidikan'" class="bg-white rounded-xl border border-[#E5E8EC] p-6 mb-4">
        <div class="flex items-center justify-between mb-4">
            <div>
                <h3 class="text-[13px] font-semibold text-[#1a2a35]">Riwayat Pendidikan <span class="text-[#c62828]">*</span></h3>
                <p class="text-[11px] text-[#8a9ba8] mt-0.5">Minimal 1 entry wajib diisi.</p>
            </div>
            <button @click="addRow('riwayatPendidikan', { namaSekolah: '', tahunLulus: '', jurusan: '' })"
                    class="flex items-center gap-1.5 text-[12px] font-semibold text-primary hover:text-[#005f78] transition-colors">
                <svg class="w-3.5 h-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
                Tambah
            </button>
        </div>

        <p x-show="errors.riwayat_pendidikan" x-text="errors.riwayat_pendidikan" class="text-[11px] text-[#c62828] mb-3"></p>

        <template x-for="(row, idx) in seksi.riwayatPendidikan" :key="idx">
            <div class="grid grid-cols-1 md:grid-cols-3 gap-3 mb-3 pb-3 border-b border-[#F6F8FA] last:border-0">
                <div class="md:col-span-3 flex items-center gap-2">
                    <span class="w-5 h-5 rounded-full bg-[#E8F4F8] text-primary text-[10px] font-semibold flex items-center justify-center shrink-0" x-text="idx + 1"></span>
                    <span class="text-[11px] font-semibold text-[#5a6a75]">Entry Pendidikan</span>
                    <button x-show="seksi.riwayatPendidikan.length > 1" @click="removeRow('riwayatPendidikan', idx)"
                            class="ml-auto text-[#c62828] hover:text-[#a02020] transition-colors">
                        <svg class="w-3.5 h-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
                    </button>
                </div>
                <div class="md:col-span-3">
                    <label class="block text-[11px] font-semibold text-[#5a6a75] uppercase tracking-[0.7px] mb-1.5">Nama Sekolah / Perguruan Tinggi</label>
                    <input type="text" x-model="row.namaSekolah" placeholder="Nama institusi pendidikan"
                           class="w-full h-[44px] px-3 text-[13px] text-[#1a2a35] bg-white border border-[#E0E5EA] rounded-xl outline-none focus:border-primary focus:ring-2 focus:ring-primary/10 placeholder:text-[#b0bec5]" />
                </div>
                <div>
                    <label class="block text-[11px] font-semibold text-[#5a6a75] uppercase tracking-[0.7px] mb-1.5">Tahun Lulus</label>
                    <input type="text" x-model="row.tahunLulus" placeholder="2020" maxlength="4"
                           class="w-full h-[44px] px-3 text-[13px] text-[#1a2a35] bg-white border border-[#E0E5EA] rounded-xl outline-none focus:border-primary focus:ring-2 focus:ring-primary/10 placeholder:text-[#b0bec5]" />
                </div>
                <div class="md:col-span-2">
                    <label class="block text-[11px] font-semibold text-[#5a6a75] uppercase tracking-[0.7px] mb-1.5">Jurusan / Program Studi</label>
                    <input type="text" x-model="row.jurusan" placeholder="Nama jurusan"
                           class="w-full h-[44px] px-3 text-[13px] text-[#1a2a35] bg-white border border-[#E0E5EA] rounded-xl outline-none focus:border-primary focus:ring-2 focus:ring-primary/10 placeholder:text-[#b0bec5]" />
                </div>
            </div>
        </template>
    </div>

    {{-- ============ TAB: PELATIHAN ============ --}}
    <div x-show="tab === 'pelatihan'" class="bg-white rounded-xl border border-[#E5E8EC] p-6 mb-4">
        <div class="flex items-center justify-between mb-4">
            <div>
                <h3 class="text-[13px] font-semibold text-[#1a2a35]">Pelatihan Profesional</h3>
                <p class="text-[11px] text-[#8a9ba8] mt-0.5">Opsional. Tambahkan pelatihan yang pernah Anda ikuti.</p>
            </div>
            <button @click="addRow('pelatihan', { tahun: '', jenisPelatihan: '', penyelenggara: '', jangkaWaktu: '' })"
                    class="flex items-center gap-1.5 text-[12px] font-semibold text-primary hover:text-[#005f78] transition-colors">
                <svg class="w-3.5 h-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
                Tambah
            </button>
        </div>

        <div x-show="seksi.pelatihan.length === 0" class="py-8 text-center text-[12px] text-[#8a9ba8]">
            Belum ada data pelatihan. Klik "Tambah" untuk menambahkan.
        </div>

        <template x-for="(row, idx) in seksi.pelatihan" :key="idx">
            <div class="grid grid-cols-2 md:grid-cols-4 gap-3 mb-3 pb-3 border-b border-[#F6F8FA] last:border-0">
                <div class="col-span-2 md:col-span-4 flex items-center gap-2">
                    <span class="w-5 h-5 rounded-full bg-[#E8F4F8] text-primary text-[10px] font-semibold flex items-center justify-center shrink-0" x-text="idx + 1"></span>
                    <button @click="removeRow('pelatihan', idx)" class="ml-auto text-[#c62828] hover:text-[#a02020] transition-colors">
                        <svg class="w-3.5 h-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
                    </button>
                </div>
                <div>
                    <label class="block text-[11px] font-semibold text-[#5a6a75] uppercase tracking-[0.7px] mb-1.5">Tahun</label>
                    <input type="text" x-model="row.tahun" placeholder="2022" maxlength="4"
                           class="w-full h-[44px] px-3 text-[13px] text-[#1a2a35] bg-white border border-[#E0E5EA] rounded-xl outline-none focus:border-primary focus:ring-2 focus:ring-primary/10 placeholder:text-[#b0bec5]" />
                </div>
                <div>
                    <label class="block text-[11px] font-semibold text-[#5a6a75] uppercase tracking-[0.7px] mb-1.5">Jenis Pelatihan</label>
                    <input type="text" x-model="row.jenisPelatihan" placeholder="Dalam / Luar Negeri"
                           class="w-full h-[44px] px-3 text-[13px] text-[#1a2a35] bg-white border border-[#E0E5EA] rounded-xl outline-none focus:border-primary focus:ring-2 focus:ring-primary/10 placeholder:text-[#b0bec5]" />
                </div>
                <div>
                    <label class="block text-[11px] font-semibold text-[#5a6a75] uppercase tracking-[0.7px] mb-1.5">Penyelenggara</label>
                    <input type="text" x-model="row.penyelenggara" placeholder="Nama lembaga"
                           class="w-full h-[44px] px-3 text-[13px] text-[#1a2a35] bg-white border border-[#E0E5EA] rounded-xl outline-none focus:border-primary focus:ring-2 focus:ring-primary/10 placeholder:text-[#b0bec5]" />
                </div>
                <div>
                    <label class="block text-[11px] font-semibold text-[#5a6a75] uppercase tracking-[0.7px] mb-1.5">Jangka Waktu</label>
                    <input type="text" x-model="row.jangkaWaktu" placeholder="3 hari / 40 jam"
                           class="w-full h-[44px] px-3 text-[13px] text-[#1a2a35] bg-white border border-[#E0E5EA] rounded-xl outline-none focus:border-primary focus:ring-2 focus:ring-primary/10 placeholder:text-[#b0bec5]" />
                </div>
            </div>
        </template>
    </div>

    {{-- ============ TAB: KONFERENSI / SEMINAR ============ --}}
    <div x-show="tab === 'konferensi'" class="bg-white rounded-xl border border-[#E5E8EC] p-6 mb-4">
        <div class="flex items-center justify-between mb-4">
            <div>
                <h3 class="text-[13px] font-semibold text-[#1a2a35]">Konferensi / Seminar / Simposium</h3>
                <p class="text-[11px] text-[#8a9ba8] mt-0.5">Opsional.</p>
            </div>
            <button @click="addRow('konferensi', { tahun: '', judulKegiatan: '', penyelenggara: '', peran: '' })"
                    class="flex items-center gap-1.5 text-[12px] font-semibold text-primary hover:text-[#005f78] transition-colors">
                <svg class="w-3.5 h-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
                Tambah
            </button>
        </div>

        <div x-show="seksi.konferensi.length === 0" class="py-8 text-center text-[12px] text-[#8a9ba8]">
            Belum ada data. Klik "Tambah" untuk menambahkan.
        </div>

        <template x-for="(row, idx) in seksi.konferensi" :key="idx">
            <div class="grid grid-cols-2 md:grid-cols-4 gap-3 mb-3 pb-3 border-b border-[#F6F8FA] last:border-0">
                <div class="col-span-2 md:col-span-4 flex items-center gap-2">
                    <span class="w-5 h-5 rounded-full bg-[#E8F4F8] text-primary text-[10px] font-semibold flex items-center justify-center shrink-0" x-text="idx + 1"></span>
                    <button @click="removeRow('konferensi', idx)" class="ml-auto text-[#c62828] hover:text-[#a02020] transition-colors">
                        <svg class="w-3.5 h-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
                    </button>
                </div>
                <div>
                    <label class="block text-[11px] font-semibold text-[#5a6a75] uppercase tracking-[0.7px] mb-1.5">Tahun</label>
                    <input type="text" x-model="row.tahun" placeholder="2023" maxlength="4"
                           class="w-full h-[44px] px-3 text-[13px] text-[#1a2a35] bg-white border border-[#E0E5EA] rounded-xl outline-none focus:border-primary focus:ring-2 focus:ring-primary/10 placeholder:text-[#b0bec5]" />
                </div>
                <div class="md:col-span-3">
                    <label class="block text-[11px] font-semibold text-[#5a6a75] uppercase tracking-[0.7px] mb-1.5">Judul Kegiatan</label>
                    <input type="text" x-model="row.judulKegiatan" placeholder="Nama seminar / konferensi"
                           class="w-full h-[44px] px-3 text-[13px] text-[#1a2a35] bg-white border border-[#E0E5EA] rounded-xl outline-none focus:border-primary focus:ring-2 focus:ring-primary/10 placeholder:text-[#b0bec5]" />
                </div>
                <div class="md:col-span-2">
                    <label class="block text-[11px] font-semibold text-[#5a6a75] uppercase tracking-[0.7px] mb-1.5">Penyelenggara</label>
                    <input type="text" x-model="row.penyelenggara" placeholder="Nama institusi"
                           class="w-full h-[44px] px-3 text-[13px] text-[#1a2a35] bg-white border border-[#E0E5EA] rounded-xl outline-none focus:border-primary focus:ring-2 focus:ring-primary/10 placeholder:text-[#b0bec5]" />
                </div>
                <div class="md:col-span-2">
                    <label class="block text-[11px] font-semibold text-[#5a6a75] uppercase tracking-[0.7px] mb-1.5">Peran</label>
                    <input type="text" x-model="row.peran" placeholder="Panitia / Pemohon / Pembicara"
                           class="w-full h-[44px] px-3 text-[13px] text-[#1a2a35] bg-white border border-[#E0E5EA] rounded-xl outline-none focus:border-primary focus:ring-2 focus:ring-primary/10 placeholder:text-[#b0bec5]" />
                </div>
            </div>
        </template>
    </div>

    {{-- ============ TAB: PENGHARGAAN ============ --}}
    <div x-show="tab === 'penghargaan'" class="bg-white rounded-xl border border-[#E5E8EC] p-6 mb-4">
        <div class="flex items-center justify-between mb-4">
            <div>
                <h3 class="text-[13px] font-semibold text-[#1a2a35]">Penghargaan / Piagam</h3>
                <p class="text-[11px] text-[#8a9ba8] mt-0.5">Opsional.</p>
            </div>
            <button @click="addRow('penghargaan', { tahun: '', bentukPenghargaan: '', pemberi: '' })"
                    class="flex items-center gap-1.5 text-[12px] font-semibold text-primary hover:text-[#005f78] transition-colors">
                <svg class="w-3.5 h-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
                Tambah
            </button>
        </div>

        <div x-show="seksi.penghargaan.length === 0" class="py-8 text-center text-[12px] text-[#8a9ba8]">
            Belum ada data. Klik "Tambah" untuk menambahkan.
        </div>

        <template x-for="(row, idx) in seksi.penghargaan" :key="idx">
            <div class="grid grid-cols-3 gap-3 mb-3 pb-3 border-b border-[#F6F8FA] last:border-0">
                <div class="col-span-3 flex items-center gap-2">
                    <span class="w-5 h-5 rounded-full bg-[#E8F4F8] text-primary text-[10px] font-semibold flex items-center justify-center shrink-0" x-text="idx + 1"></span>
                    <button @click="removeRow('penghargaan', idx)" class="ml-auto text-[#c62828] hover:text-[#a02020] transition-colors">
                        <svg class="w-3.5 h-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
                    </button>
                </div>
                <div>
                    <label class="block text-[11px] font-semibold text-[#5a6a75] uppercase tracking-[0.7px] mb-1.5">Tahun</label>
                    <input type="text" x-model="row.tahun" placeholder="2021" maxlength="4"
                           class="w-full h-[44px] px-3 text-[13px] text-[#1a2a35] bg-white border border-[#E0E5EA] rounded-xl outline-none focus:border-primary focus:ring-2 focus:ring-primary/10 placeholder:text-[#b0bec5]" />
                </div>
                <div>
                    <label class="block text-[11px] font-semibold text-[#5a6a75] uppercase tracking-[0.7px] mb-1.5">Bentuk Penghargaan</label>
                    <input type="text" x-model="row.bentukPenghargaan" placeholder="Piagam, Trofi, dll."
                           class="w-full h-[44px] px-3 text-[13px] text-[#1a2a35] bg-white border border-[#E0E5EA] rounded-xl outline-none focus:border-primary focus:ring-2 focus:ring-primary/10 placeholder:text-[#b0bec5]" />
                </div>
                <div>
                    <label class="block text-[11px] font-semibold text-[#5a6a75] uppercase tracking-[0.7px] mb-1.5">Pemberi</label>
                    <input type="text" x-model="row.pemberi" placeholder="Institusi pemberi"
                           class="w-full h-[44px] px-3 text-[13px] text-[#1a2a35] bg-white border border-[#E0E5EA] rounded-xl outline-none focus:border-primary focus:ring-2 focus:ring-primary/10 placeholder:text-[#b0bec5]" />
                </div>
            </div>
        </template>
    </div>

    {{-- ============ TAB: ORGANISASI PROFESI ============ --}}
    <div x-show="tab === 'organisasi'" class="bg-white rounded-xl border border-[#E5E8EC] p-6 mb-4">
        <div class="flex items-center justify-between mb-4">
            <div>
                <h3 class="text-[13px] font-semibold text-[#1a2a35]">Organisasi Profesi / Ilmiah</h3>
                <p class="text-[11px] text-[#8a9ba8] mt-0.5">Opsional.</p>
            </div>
            <button @click="addRow('organisasi', { tahun: '', namaOrganisasi: '', jabatan: '' })"
                    class="flex items-center gap-1.5 text-[12px] font-semibold text-primary hover:text-[#005f78] transition-colors">
                <svg class="w-3.5 h-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
                Tambah
            </button>
        </div>

        <div x-show="seksi.organisasi.length === 0" class="py-8 text-center text-[12px] text-[#8a9ba8]">
            Belum ada data. Klik "Tambah" untuk menambahkan.
        </div>

        <template x-for="(row, idx) in seksi.organisasi" :key="idx">
            <div class="grid grid-cols-3 gap-3 mb-3 pb-3 border-b border-[#F6F8FA] last:border-0">
                <div class="col-span-3 flex items-center gap-2">
                    <span class="w-5 h-5 rounded-full bg-[#E8F4F8] text-primary text-[10px] font-semibold flex items-center justify-center shrink-0" x-text="idx + 1"></span>
                    <button @click="removeRow('organisasi', idx)" class="ml-auto text-[#c62828] hover:text-[#a02020] transition-colors">
                        <svg class="w-3.5 h-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
                    </button>
                </div>
                <div>
                    <label class="block text-[11px] font-semibold text-[#5a6a75] uppercase tracking-[0.7px] mb-1.5">Tahun</label>
                    <input type="text" x-model="row.tahun" placeholder="2020" maxlength="4"
                           class="w-full h-[44px] px-3 text-[13px] text-[#1a2a35] bg-white border border-[#E0E5EA] rounded-xl outline-none focus:border-primary focus:ring-2 focus:ring-primary/10 placeholder:text-[#b0bec5]" />
                </div>
                <div>
                    <label class="block text-[11px] font-semibold text-[#5a6a75] uppercase tracking-[0.7px] mb-1.5">Nama Organisasi</label>
                    <input type="text" x-model="row.namaOrganisasi" placeholder="Nama himpunan / asosiasi"
                           class="w-full h-[44px] px-3 text-[13px] text-[#1a2a35] bg-white border border-[#E0E5EA] rounded-xl outline-none focus:border-primary focus:ring-2 focus:ring-primary/10 placeholder:text-[#b0bec5]" />
                </div>
                <div>
                    <label class="block text-[11px] font-semibold text-[#5a6a75] uppercase tracking-[0.7px] mb-1.5">Jabatan / Keanggotaan</label>
                    <input type="text" x-model="row.jabatan" placeholder="Anggota, Ketua, dll."
                           class="w-full h-[44px] px-3 text-[13px] text-[#1a2a35] bg-white border border-[#E0E5EA] rounded-xl outline-none focus:border-primary focus:ring-2 focus:ring-primary/10 placeholder:text-[#b0bec5]" />
                </div>
            </div>
        </template>
    </div>

    {{-- Tombol Submit --}}
    <div class="flex items-center justify-between mt-2">
        <p class="text-[11px] text-[#8a9ba8]"><span class="text-[#c62828]">*</span> Riwayat Pendidikan wajib diisi</p>
        <button @click="submit()"
                wire:loading.attr="disabled"
                class="bg-primary hover:bg-[#005f78] text-white text-[13px] font-semibold px-6 py-2.5 rounded-xl transition-colors disabled:opacity-60 flex items-center gap-2">
            <span wire:loading.remove wire:target="simpan">Simpan & Mulai RPL</span>
            <span wire:loading wire:target="simpan">Menyimpan...</span>
        </button>
    </div>

</div>
