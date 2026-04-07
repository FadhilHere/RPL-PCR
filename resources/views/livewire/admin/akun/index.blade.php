<?php

use Livewire\Attributes\Layout;
use Livewire\Volt\Component;
use Livewire\WithPagination;
use App\Actions\Admin\TambahAdminAction;
use App\Actions\Admin\TambahAsesorAction;
use App\Actions\Admin\TambahPesertaAction;
use App\Actions\Admin\EditAkunAction;
use App\Enums\RoleEnum;
use App\Models\User;
use App\Models\ProgramStudi;
use App\Livewire\Forms\TambahAkunForm;
use App\Livewire\Forms\EditAkunForm;

new #[Layout('components.layouts.admin')] class extends Component {
    use WithPagination;

    public string $search     = '';
    public string $filterRole = '';

    public bool $showForm   = false;
    public TambahAkunForm $create;
    public ?int $createProdiId = null;

    public ?int       $editUserId = null;
    public EditAkunForm $edit;
    public ?int $editProdiId = null;

    public function updatedSearch(): void { $this->resetPage(); }
    public function updatedFilterRole(): void { $this->resetPage(); }

    // ── Tambah ────────────────────────────────────────────────

    public function openForm(): void
    {
        $this->create->reset();
        $this->createProdiId = null;
        $this->showForm = true;
    }

    public function save(string $role, TambahAdminAction $tambahAdmin, TambahAsesorAction $tambahAsesor, TambahPesertaAction $tambahPeserta): void
    {
        $this->create->roleForm = in_array($role, ['asesor', 'peserta', 'admin', 'admin_pmb', 'admin_baak']) ? $role : 'asesor';

        $rules = [
            'create.nama'     => 'required|string|max:255',
            'create.email'    => 'required|email|unique:users,email',
            'create.password' => 'required|string|min:8',
        ];

        if ($this->create->roleForm === 'asesor') {
            $rules['create.bidangKeahlian'] = 'required|string|max:255';
        }

        $this->validate($rules);

        if ($this->create->roleForm === 'asesor') {
            $tambahAsesor->execute(
                $this->create->nama, $this->create->email, $this->create->password,
                $this->create->nidn ?: null, $this->create->bidangKeahlian,
                $this->create->sudahPelatihan,
                $this->createProdiId ? [$this->createProdiId] : [],
            );
        } elseif ($this->create->roleForm === 'peserta') {
            $tambahPeserta->execute(
                $this->create->nama, $this->create->email, $this->create->password,
                null, null, null,
            );
        } elseif ($this->create->roleForm === 'admin') {
            $tambahAdmin->execute(
                $this->create->nama, $this->create->email, $this->create->password,
            );
        } elseif ($this->create->roleForm === 'admin_pmb') {
            $tambahAdmin->execute(
                $this->create->nama, $this->create->email, $this->create->password,
                \App\Enums\RoleEnum::AdminPmb,
            );
        } elseif ($this->create->roleForm === 'admin_baak') {
            $tambahAdmin->execute(
                $this->create->nama, $this->create->email, $this->create->password,
                \App\Enums\RoleEnum::AdminBaak,
            );
        }

        $this->create->reset();
        $this->showForm = false;
    }

    // ── Edit ──────────────────────────────────────────────────

    public function openEdit(int $userId): void
    {
        $user = User::with(['asesor.programStudi', 'peserta'])->findOrFail($userId);

        $this->editUserId        = $userId;
        $this->edit->nama        = $user->nama;
        $this->edit->email       = $user->email;
        $this->edit->newPassword = '';
        $this->resetValidation();

        if ($user->role === RoleEnum::Asesor && $user->asesor) {
            $this->edit->nidn           = $user->asesor->nidn ?? '';
            $this->edit->bidangKeahlian = $user->asesor->bidang_keahlian ?? '';
            $this->edit->sudahPelatihan = (bool) $user->asesor->sudah_pelatihan_rpl;
            $this->editProdiId          = $user->asesor->programStudi->first()?->id;
        }
    }

    public function saveEdit(EditAkunAction $action): void
    {
        $user = User::findOrFail($this->editUserId);

        $rules = [
            'edit.nama'  => 'required|string|max:255',
            'edit.email' => 'required|email|unique:users,email,' . $this->editUserId,
        ];

        if ($this->edit->newPassword !== '') {
            $rules['edit.newPassword'] = 'min:8';
        }

        if ($user->role === RoleEnum::Asesor) {
            $rules['edit.bidangKeahlian'] = 'required|string|max:255';
        }

        $this->validate($rules, [
            'edit.nama.required'           => 'Nama wajib diisi.',
            'edit.email.required'          => 'Email wajib diisi.',
            'edit.email.unique'            => 'Email sudah digunakan akun lain.',
            'edit.newPassword.min'         => 'Password minimal 8 karakter.',
            'edit.bidangKeahlian.required' => 'Bidang keahlian wajib diisi.',
        ]);

        $action->execute(
            $this->editUserId,
            $this->edit->nama,
            $this->edit->email,
            $this->edit->newPassword,
            $this->edit->nidn ?: null,
            $this->edit->bidangKeahlian ?: null,
            $this->edit->sudahPelatihan,
            $this->editProdiId ? [$this->editProdiId] : [],
        );

        $this->editUserId = null;
    }

    // ── Toggle Aktif ──────────────────────────────────────────

    public function toggleAktif(int $userId): void
    {
        User::findOrFail($userId)->update(['aktif' => ! User::findOrFail($userId)->aktif]);
    }

    // ── Toggle Pembayaran ─────────────────────────────────────

    public function togglePembayaran(int $permohonanId): void
    {
        $permohonan = \App\Models\PermohonanRpl::findOrFail($permohonanId);
        $verified   = ! $permohonan->pembayaran_terverifikasi;

        $permohonan->update([
            'pembayaran_terverifikasi'       => $verified,
            'tanggal_verifikasi_pembayaran'  => $verified ? now() : null,
            'admin_verifikator_id'           => $verified ? auth()->id() : null,
        ]);
    }

    // ── Hapus ─────────────────────────────────────────────────

    public function deleteUser(int $userId): void
    {
        User::findOrFail($userId)->delete();
    }

    public function with(): array
    {
        $users = User::with(['asesor.programStudi', 'peserta.latestPermohonan'])
            ->when($this->search, fn($q) => $q->where(function ($q) {
                $q->where('nama', 'like', "%{$this->search}%")
                  ->orWhere('email', 'like', "%{$this->search}%");
            }))
            ->when($this->filterRole, fn($q) => $q->where('role', $this->filterRole))
            ->orderByDesc('created_at')
            ->paginate(15);

        $editUser = $this->editUserId
            ? $users->firstWhere('id', $this->editUserId) ?? User::with(['asesor.programStudi', 'peserta'])->find($this->editUserId)
            : null;

        return [
            'users'        => $users,
            'prodiOptions' => ProgramStudi::where('aktif', true)->orderBy('nama')->get(),
            'editUser'     => $editUser,
        ];
    }
}; ?>

<x-slot:title>Kelola Akun</x-slot:title>
<x-slot:subtitle>Manajemen seluruh akun pengguna sistem RPL</x-slot:subtitle>

<div x-data="{
    confirm: { open: false, userId: null, userName: '' },
    openConfirm(id, name) { this.confirm = { open: true, userId: id, userName: name }; },
    doDelete() { $wire.deleteUser(this.confirm.userId); this.confirm.open = false; },
}">

    {{-- Toolbar --}}
    <div class="flex items-center gap-3 mb-5">
        <div class="relative flex-1 max-w-xs">
            <input wire:model.live.debounce.300ms="search"
                   type="text" placeholder="Cari nama atau email..."
                   class="w-full h-[38px] pl-9 pr-3 text-[13px] text-[#1a2a35] bg-white border border-[#E0E5EA] rounded-lg outline-none focus:border-primary focus:ring-2 focus:ring-primary/10 placeholder:text-[#b0bec5]" />
            <div class="absolute left-3 top-1/2 -translate-y-1/2 text-[#b0bec5] pointer-events-none">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/>
                </svg>
            </div>
        </div>
        <x-form.select wire:model.live="filterRole"
            placeholder="Semua Role"
            :options="['admin' => 'Admin', 'admin_pmb' => 'Admin PMB', 'admin_baak' => 'Admin BAAK', 'asesor' => 'Asesor', 'peserta' => 'Peserta']"
            class="w-[180px]" />
        <div class="flex-1"></div>
        <button wire:click="openForm"
                class="bg-primary hover:bg-[#005f78] text-white text-[13px] font-semibold px-4 py-2 rounded-lg transition-colors flex items-center gap-2">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                <line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/>
            </svg>
            Tambah Akun
        </button>
    </div>

    {{-- Tabel --}}
    <div class="bg-white rounded-[10px] border border-[#E5E8EC] overflow-hidden">
        <table class="w-full">
            <thead>
                <tr class="border-b border-[#F0F2F5]">
                    <th class="text-left px-5 py-3 text-[11px] font-semibold text-[#8a9ba8] uppercase tracking-[0.5px]">Nama</th>
                    <th class="text-left px-4 py-3 text-[11px] font-semibold text-[#8a9ba8] uppercase tracking-[0.5px]">Email</th>
                    <th class="text-left px-4 py-3 text-[11px] font-semibold text-[#8a9ba8] uppercase tracking-[0.5px]">Role</th>
                    <th class="text-left px-4 py-3 text-[11px] font-semibold text-[#8a9ba8] uppercase tracking-[0.5px]">Info</th>
                    <th class="text-center px-4 py-3 text-[11px] font-semibold text-[#8a9ba8] uppercase tracking-[0.5px]">Aktif</th>
                    <th class="text-center px-4 py-3 text-[11px] font-semibold text-[#8a9ba8] uppercase tracking-[0.5px]">Pembayaran</th>
                    <th class="text-center px-4 py-3 text-[11px] font-semibold text-[#8a9ba8] uppercase tracking-[0.5px]">Aksi</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($users as $user)
                @php
                    $roleBadge = $user->role->badgeClass();
                    $info = match($user->role) {
                        RoleEnum::Asesor    => $user->asesor?->bidang_keahlian ?? '—',
                        RoleEnum::Peserta   => $user->peserta?->institusi_asal ?? '—',
                        RoleEnum::Admin     => 'Super Admin',
                        RoleEnum::AdminPmb  => 'Kelola Akun & Verifikasi',
                        RoleEnum::AdminBaak => 'Jadwal, Pleno & Resume',
                    };
                    $prodiList = $user->role === RoleEnum::Asesor
                        ? $user->asesor?->programStudi->pluck('kode')->implode(', ')
                        : null;
                @endphp
                <tr class="border-b border-[#F6F8FA] last:border-0 hover:bg-[#FAFBFC] transition-colors" wire:key="user-{{ $user->id }}">
                    <td class="px-5 py-3.5">
                        <div class="flex items-center gap-2.5">
                            <div class="w-7 h-7 rounded-full bg-[#D0E4ED] flex items-center justify-center text-[10px] font-semibold text-primary shrink-0">
                                {{ strtoupper(substr($user->nama, 0, 1)) }}
                            </div>
                            <span class="text-[13px] font-medium text-[#1a2a35]">{{ $user->nama }}</span>
                        </div>
                    </td>
                    <td class="px-4 py-3.5 text-[12px] text-[#5a6a75]">{{ $user->email }}</td>
                    <td class="px-4 py-3.5">
                        <span class="text-[10px] font-semibold px-2 py-0.5 rounded-full {{ $roleBadge }}">
                            {{ $user->role->label() }}
                        </span>
                    </td>
                    <td class="px-4 py-3.5 max-w-[180px]">
                        <div class="text-[12px] text-[#8a9ba8] truncate">{{ $info }}</div>
                        @if ($prodiList !== null)
                            <div class="text-[11px] text-[#5a6a75] mt-0.5 truncate">
                                {{ $prodiList ?: 'Belum ada prodi' }}
                            </div>
                        @endif
                    </td>
                    {{-- Toggle Aktif --}}
                    <td class="px-4 py-3.5 text-center">
                        <button wire:click="toggleAktif({{ $user->id }})"
                                title="{{ $user->aktif ? 'Nonaktifkan' : 'Aktifkan' }}"
                                class="relative inline-flex h-[22px] w-[40px] items-center rounded-full transition-colors focus:outline-none
                                       {{ $user->aktif ? 'bg-[#1e7e3e]' : 'bg-[#D0D5DD]' }}">
                            <span class="inline-block h-[16px] w-[16px] transform rounded-full bg-white shadow transition-transform
                                         {{ $user->aktif ? 'translate-x-[21px]' : 'translate-x-[3px]' }}"></span>
                        </button>
                    </td>
                    {{-- Pembayaran toggle (peserta saja) --}}
                    <td class="px-4 py-3.5 text-center">
                        @if ($user->role === RoleEnum::Peserta)
                            @php $latestP = $user->peserta?->latestPermohonan; @endphp
                            @if ($latestP)
                                <button wire:click="togglePembayaran({{ $latestP->id }})"
                                        title="{{ $latestP->pembayaran_terverifikasi ? 'Batalkan verifikasi pembayaran' : 'Verifikasi pembayaran' }}"
                                        class="relative inline-flex h-[22px] w-[40px] items-center rounded-full transition-colors focus:outline-none
                                               {{ $latestP->pembayaran_terverifikasi ? 'bg-[#1e7e3e]' : 'bg-[#D0D5DD]' }}">
                                    <span class="inline-block h-[16px] w-[16px] transform rounded-full bg-white shadow transition-transform
                                                 {{ $latestP->pembayaran_terverifikasi ? 'translate-x-[21px]' : 'translate-x-[3px]' }}"></span>
                                </button>
                            @else
                                <div class="relative inline-flex h-[22px] w-[40px] items-center rounded-full bg-[#D0D5DD] transition-colors focus:outline-none"
                                     title="Peserta belum mengajukan pengajuan">
                                    <span class="inline-block h-[16px] w-[16px] transform rounded-full bg-white shadow transition-transform translate-x-[3px]"></span>
                                </div>
                            @endif
                        @else
                            <span class="text-[11px] text-[#D0D5DD]">—</span>
                        @endif
                    </td>
                    {{-- Aksi --}}
                    <td class="px-4 py-3.5 text-center">
                        <div class="flex items-center justify-center gap-1.5">
                            @if ($user->role === RoleEnum::Peserta && $user->peserta)
                            <a href="{{ route('admin.akun.profil', $user->peserta) }}"
                               title="Lihat profil peserta"
                               class="w-[32px] h-[32px] rounded-md border border-[#D0D5DD] text-[#5a6a75] hover:border-primary hover:text-primary hover:bg-[#E8F4F8] transition-colors flex items-center justify-center no-underline">
                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M20 21v-2a4 4 0 00-4-4H8a4 4 0 00-4 4v2"/><circle cx="12" cy="7" r="4"/>
                                </svg>
                            </a>
                            <a href="{{ route('admin.akun.berkas', $user->peserta) }}"
                               title="Kelola berkas"
                               class="w-[32px] h-[32px] rounded-md border border-[#D0D5DD] text-[#5a6a75] hover:border-primary hover:text-primary hover:bg-[#E8F4F8] transition-colors flex items-center justify-center no-underline">
                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M22 19a2 2 0 01-2 2H4a2 2 0 01-2-2V5a2 2 0 012-2h5l2 3h9a2 2 0 012 2z"/>
                                </svg>
                            </a>
                            @endif
                            <button wire:click="openEdit({{ $user->id }})"
                                    title="Edit akun"
                                    class="w-[32px] h-[32px] rounded-md border border-[#D0D5DD] text-[#5a6a75] hover:border-primary hover:text-primary hover:bg-[#E8F4F8] transition-colors flex items-center justify-center">
                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M11 4H4a2 2 0 00-2 2v14a2 2 0 002 2h14a2 2 0 002-2v-7"/>
                                    <path d="M18.5 2.5a2.121 2.121 0 013 3L12 15l-4 1 1-4 9.5-9.5z"/>
                                </svg>
                            </button>
                            <button @click="openConfirm({{ $user->id }}, @js($user->nama))"
                                    title="Hapus akun"
                                    class="w-[32px] h-[32px] rounded-md border border-[#D0D5DD] text-[#5a6a75] hover:border-[#c62828] hover:text-[#c62828] hover:bg-[#FCE8E6] transition-colors flex items-center justify-center">
                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <polyline points="3 6 5 6 21 6"/>
                                    <path d="M19 6l-1 14a2 2 0 01-2 2H8a2 2 0 01-2-2L5 6"/>
                                    <path d="M10 11v6M14 11v6"/>
                                    <path d="M9 6V4a1 1 0 011-1h4a1 1 0 011 1v2"/>
                                </svg>
                            </button>
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="7" class="px-5 py-10 text-center text-[13px] text-[#8a9ba8]">
                        Tidak ada akun ditemukan.
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{-- Pagination --}}
    @if ($users->hasPages())
    <div class="mt-4">
        {{ $users->links() }}
    </div>
    @endif

    @include('livewire.admin.akun.partials.modal-tambah-akun')
    @include('livewire.admin.akun.partials.modal-edit-akun', compact('editUser', 'prodiOptions'))
    @include('livewire.admin.akun.partials.modal-konfirmasi-hapus')

</div>
