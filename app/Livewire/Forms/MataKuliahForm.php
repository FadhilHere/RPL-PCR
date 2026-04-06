<?php

namespace App\Livewire\Forms;

use Livewire\Form;

class MataKuliahForm extends Form
{
    public ?int   $editId    = null;
    public string $kode      = '';
    public string $nama      = '';
    public int    $sks       = 2;
    public int    $semester  = 1;
    public string $deskripsi = '';
    public bool   $bisaRpl   = true;
}
