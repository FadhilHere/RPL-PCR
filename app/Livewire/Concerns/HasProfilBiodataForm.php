<?php

namespace App\Livewire\Concerns;

use App\Models\Peserta;

trait HasProfilBiodataForm
{
    public string $nama = '';
    public string $email = '';
    public string $nik = '';
    public string $telepon = '';
    public string $teleponFaks = '';
    public string $alamat = '';
    public string $kota = '';
    public string $provinsi = '';
    public string $kodePos = '';
    public string $tempatLahir = '';
    public string $tanggalLahir = '';
    public string $jenisKelamin = '';
    public string $agama = '';
    public string $golonganPangkat = '';
    public string $instansi = '';
    public string $pekerjaan = '';
    public $foto = null;

    protected function fillBiodataForm(Peserta $peserta): void
    {
        $this->nama = $peserta->user->nama;
        $this->email = $peserta->user->email;
        $this->nik = $peserta->nik ?? '';
        $this->telepon = $peserta->telepon ?? '';
        $this->teleponFaks = $peserta->telepon_faks ?? '';
        $this->alamat = $peserta->alamat ?? '';
        $this->kota = $peserta->kota ?? '';
        $this->provinsi = $peserta->provinsi ?? '';
        $this->kodePos = $peserta->kode_pos ?? '';
        $this->tempatLahir = $peserta->tempat_lahir ?? '';
        $tanggalLahir = $peserta->tanggal_lahir;

        if ($tanggalLahir instanceof \DateTimeInterface) {
            $this->tanggalLahir = $tanggalLahir->format('Y-m-d');
        } elseif (is_string($tanggalLahir) && trim($tanggalLahir) !== '') {
            $timestamp = strtotime($tanggalLahir);
            $this->tanggalLahir = $timestamp !== false ? date('Y-m-d', $timestamp) : '';
        } else {
            $this->tanggalLahir = '';
        }
        $this->jenisKelamin = $peserta->jenis_kelamin ?? '';
        $this->agama = $peserta->agama ?? '';
        $this->golonganPangkat = $peserta->golongan_pangkat ?? '';
        $this->instansi = $peserta->instansi ?? '';
        $this->pekerjaan = $peserta->pekerjaan ?? '';
    }

    /**
     * @return array<string, string>
     */
    protected function biodataRules(int $ignoreUserId): array
    {
        return [
            'nama' => 'required|string|max:255',
            'email' => 'required|email|max:255|unique:users,email,' . $ignoreUserId,
            'nik' => 'nullable|string|max:20',
            'telepon' => 'nullable|string|max:20',
            'teleponFaks' => 'nullable|string|max:20',
            'alamat' => 'nullable|string|max:500',
            'kota' => 'nullable|string|max:100',
            'provinsi' => 'nullable|string|max:100',
            'kodePos' => 'nullable|string|max:10',
            'tempatLahir' => 'nullable|string|max:100',
            'tanggalLahir' => 'nullable|date|before:today',
            'jenisKelamin' => 'nullable|in:L,P',
            'agama' => 'nullable|string|max:50',
            'golonganPangkat' => 'nullable|string|max:50',
            'instansi' => 'nullable|string|max:255',
            'pekerjaan' => 'nullable|string|max:255',
            'foto' => 'nullable|image|mimes:jpg,jpeg,png|max:2048',
        ];
    }

    /**
     * @return array<string, string|null>
     */
    protected function biodataPayload(): array
    {
        return [
            'nik' => $this->toNull($this->nik),
            'telepon' => $this->toNull($this->telepon),
            'telepon_faks' => $this->toNull($this->teleponFaks),
            'alamat' => $this->toNull($this->alamat),
            'kota' => $this->toNull($this->kota),
            'provinsi' => $this->toNull($this->provinsi),
            'kode_pos' => $this->toNull($this->kodePos),
            'tempat_lahir' => $this->toNull($this->tempatLahir),
            'tanggal_lahir' => $this->toNull($this->tanggalLahir),
            'jenis_kelamin' => $this->toNull($this->jenisKelamin),
            'agama' => $this->toNull($this->agama),
            'golongan_pangkat' => $this->toNull($this->golonganPangkat),
            'instansi' => $this->toNull($this->instansi),
            'pekerjaan' => $this->toNull($this->pekerjaan),
        ];
    }

    protected function toNull(?string $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $trimmed = trim($value);

        return $trimmed === '' ? null : $trimmed;
    }
}
