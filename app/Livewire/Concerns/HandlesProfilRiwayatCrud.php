<?php

namespace App\Livewire\Concerns;

use Illuminate\Database\Eloquent\Model;

trait HandlesProfilRiwayatCrud
{
    abstract protected function currentProfilPesertaId(): ?int;

    /**
     * @param class-string<Model> $modelClass
     * @param array<string, mixed> $data
     */
    protected function simpanRiwayat(string $modelClass, ?int $id, array $data): void
    {
        $pesertaId = $this->requireProfilPesertaId();
        $payload = ['peserta_id' => $pesertaId] + $data;

        if ($id) {
            $record = $modelClass::query()->findOrFail($id);
            abort_if((int) data_get($record, 'peserta_id') !== $pesertaId, 403);
            $record->update($payload);

            return;
        }

        $modelClass::query()->create($payload);
    }

    /**
     * @param class-string<Model> $modelClass
     */
    protected function hapusRiwayat(string $modelClass, int $id): void
    {
        $pesertaId = $this->requireProfilPesertaId();

        $record = $modelClass::query()->findOrFail($id);
        abort_if((int) data_get($record, 'peserta_id') !== $pesertaId, 403);
        $record->delete();
    }

    protected function requireProfilPesertaId(): int
    {
        $pesertaId = $this->currentProfilPesertaId();
        abort_if(! $pesertaId, 403);

        return $pesertaId;
    }
}
