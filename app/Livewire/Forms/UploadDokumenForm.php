<?php

namespace App\Livewire\Forms;

use App\Enums\JenisDokumenEnum;
use App\Models\DokumenBukti;
use App\Models\Peserta;
use Livewire\Attributes\Validate;
use Livewire\Form;

class UploadDokumenForm extends Form
{
    #[Validate('required|string|max:255')]
    public string $namaDokumen = '';

    #[Validate('required|string')]
    public string $jenisDokumen = JenisDokumenEnum::Cv->value;

    #[Validate('nullable|string')]
    public string $keterangan = '';

    #[Validate(['required', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:5120'])]
    public $berkas = null;

    public function messages(): array
    {
        return [
            'berkas.max'   => 'Ukuran file maksimal 5 MB.',
            'berkas.mimes' => 'Format file harus PDF, JPG, atau PNG.',
        ];
    }

    public function store(Peserta $peserta): void
    {
        $ext  = strtolower($this->berkas->getClientOriginalExtension());
        $path = $this->berkas->storeAs(
            "dokumen/peserta_{$peserta->id}",
            uniqid() . '.' . $ext,
            'local'
        );

        DokumenBukti::create([
            'peserta_id'    => $peserta->id,
            'jenis_dokumen' => $this->jenisDokumen,
            'nama_dokumen'  => $this->namaDokumen,
            'berkas'        => $path,
            'keterangan'    => $this->keterangan ?: null,
        ]);
    }
}
