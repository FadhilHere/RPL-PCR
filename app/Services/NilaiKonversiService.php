<?php

namespace App\Services;

use App\Enums\NilaiHurufEnum;

class NilaiKonversiService
{
    /**
     * Konversi rata-rata nilai asesor (skala 1–5) ke nilai huruf.
     * 1.0–1.9 → E
     * 2.0–2.9 → D
     * 3.0–3.4 → C
     * 3.5–3.9 → BC
     * 4.0–4.4 → B
     * 4.5–4.9 → AB
     * 5.0     → A
     */
    public function toHuruf(float $rataRata): NilaiHurufEnum
    {
        return NilaiHurufEnum::fromRataRata($rataRata);
    }
}
