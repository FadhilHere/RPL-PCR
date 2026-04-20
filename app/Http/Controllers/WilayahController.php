<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

class WilayahController extends Controller
{
    private const CACHE_TTL_SECONDS = 604800;
    private const REQUEST_TIMEOUT_SECONDS = 10;

    public function provinces(): JsonResponse
    {
        return $this->respondWithWilayahData(
            cacheKey: 'wilayah.provinces',
            url: 'https://wilayah.id/api/provinces.json',
        );
    }

    public function regencies(string $provinceCode): JsonResponse
    {
        if (!preg_match('/^\d{2}$/', $provinceCode)) {
            return response()->json([
                'message' => 'Kode provinsi tidak valid.',
                'source' => 'validation',
                'data' => [],
            ], 422);
        }

        return $this->respondWithWilayahData(
            cacheKey: "wilayah.regencies.{$provinceCode}",
            url: "https://wilayah.id/api/regencies/{$provinceCode}.json",
        );
    }

    private function respondWithWilayahData(string $cacheKey, string $url): JsonResponse
    {
        try {
            $payload = Http::acceptJson()
                ->timeout(self::REQUEST_TIMEOUT_SECONDS)
                ->retry(1, 300)
                ->get($url)
                ->throw()
                ->json();

            $data = is_array($payload['data'] ?? null) ? $payload['data'] : [];

            Cache::put($cacheKey, $data, now()->addSeconds(self::CACHE_TTL_SECONDS));

            return response()->json([
                'message' => 'ok',
                'source' => 'upstream',
                'data' => $data,
            ]);
        } catch (\Throwable $exception) {
            $cached = Cache::get($cacheKey);

            if (is_array($cached) && $cached !== []) {
                return response()->json([
                    'message' => 'Menggunakan cache wilayah terakhir.',
                    'source' => 'cache',
                    'data' => $cached,
                ]);
            }

            report($exception);

            return response()->json([
                'message' => 'Data wilayah sedang tidak tersedia. Silakan isi manual.',
                'source' => 'unavailable',
                'data' => [],
            ], 503);
        }
    }
}
