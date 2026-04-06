<?php

namespace App\Livewire\Forms;

use Livewire\Form;

class TambahAkunForm extends Form
{
    public string $roleForm       = 'asesor';
    public string $nama           = '';
    public string $email          = '';
    public string $password       = '';
    public string $nidn           = '';
    public string $bidangKeahlian = '';
    public bool   $sudahPelatihan = false;
    public array  $prodiIds       = [];
    public string $nik            = '';
    public string $telepon        = '';
    public string $institusiAsal  = '';
}
