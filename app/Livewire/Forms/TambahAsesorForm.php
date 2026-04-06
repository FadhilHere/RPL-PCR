<?php

namespace App\Livewire\Forms;

use App\Actions\Admin\TambahAsesorAction;
use Livewire\Attributes\Validate;
use Livewire\Form;

class TambahAsesorForm extends Form
{
    #[Validate('required|string|max:255')]
    public string $nama = '';

    #[Validate('required|email|unique:users,email')]
    public string $email = '';

    #[Validate('required|string|min:8')]
    public string $password = '';

    #[Validate('nullable|string|max:20')]
    public string $nidn = '';

    #[Validate('required|string|max:255')]
    public string $bidangKeahlian = '';

    public bool $sudahPelatihan = false;

    public function store(): void
    {
        (new TambahAsesorAction)->execute(
            $this->nama,
            $this->email,
            $this->password,
            $this->nidn ?: null,
            $this->bidangKeahlian,
            $this->sudahPelatihan,
        );
    }
}
