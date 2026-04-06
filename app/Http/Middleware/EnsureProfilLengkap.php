<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Symfony\Component\HttpFoundation\Response;

class EnsureProfilLengkap
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user || ! $user->hasRole('peserta')) {
            return $next($request);
        }

        // 1. Password masih default (= nama) → wajib ganti password dulu
        if (Hash::check($user->nama, $user->password)) {
            return redirect()->route('peserta.ganti-password');
        }

        // 2. Profil belum lengkap → wajib lengkapi profil
        if (! $user->peserta?->profil_lengkap) {
            return redirect()->route('peserta.lengkapi-profil');
        }

        return $next($request);
    }
}
