<?php

namespace App\Livewire\Forms;

use Livewire\Attributes\Validate;
use Livewire\Form;

class RegisterForm extends Form
{
    // --- Data Akun ---

    #[Validate('required|string|max:255')]
    public string $nama = '';

    #[Validate('required|email|max:255|unique:users,email')]
    public string $email = '';

    #[Validate('required|string|min:8|confirmed')]
    public string $password = '';

    #[Validate('required|string')]
    public string $password_confirmation = '';

    // --- Data Pribadi ---

    #[Validate('required|in:L,P')]
    public string $jenisKelamin = '';

    #[Validate('required|date|before:today')]
    public string $tanggalLahir = '';

    #[Validate('required|string|max:500')]
    public string $alamat = '';

    #[Validate('required|string|max:100')]
    public string $kota = '';

    #[Validate('required|string|max:100')]
    public string $provinsi = '';

    #[Validate('nullable|string|max:10')]
    public ?string $kodePos = null;

    #[Validate('required|string|max:20')]
    public string $telepon = '';

    // --- Alumni PCR & Periode ---

    public bool $isDoPcr = false;

    #[Validate('required|in:ganjil,genap')]
    public string $semester = '';

    // --- Pernyataan ---

    #[Validate('accepted')]
    public bool $setuju = false;
}
