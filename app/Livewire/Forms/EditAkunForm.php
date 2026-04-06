<?php

namespace App\Livewire\Forms;

use Livewire\Form;

class EditAkunForm extends Form
{
    public string $nama           = '';
    public string $email          = '';
    public string $newPassword    = '';
    public string $nidn           = '';
    public string $bidangKeahlian = '';
    public bool   $sudahPelatihan = false;
    public array  $prodiIds       = [];
    public string $nik            = '';
    public string $telepon        = '';
    public string $institusiAsal  = '';
}
