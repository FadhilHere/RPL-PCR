<?php

namespace App\Enums;

enum NilaiTranskripEnum: string
{
    case APlus = 'A+';
    case A = 'A';
    case AMinus = 'A-';
    case ABPlus = 'AB+';
    case AB = 'AB';
    case ABMinus = 'AB-';
    case BPlus = 'B+';
    case B = 'B';
    case BMinus = 'B-';
    case BCPlus = 'BC+';
    case BC = 'BC';
    case BCMinus = 'BC-';
    case CPlus = 'C+';
    case C = 'C';
    case CMinus = 'C-';
    case DPlus = 'D+';
    case D = 'D';
    case DMinus = 'D-';
    case EPlus = 'E+';
    case E = 'E';

    public function label(): string
    {
        return $this->value;
    }
}
