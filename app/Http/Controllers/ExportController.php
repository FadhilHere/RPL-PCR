<?php

namespace App\Http\Controllers;

use App\Enums\JenisRplEnum;
use App\Enums\RoleEnum;
use App\Enums\StatusRplMataKuliahEnum;
use App\Exports\PerolehanHasilWordExport;
use App\Exports\ResumeAsesmenExport;
use App\Exports\TransferHasilWordExport;
use App\Models\Penandatangan;
use App\Models\PermohonanRpl;
use App\Models\ProgramStudi;
use App\Services\NilaiKonversiService;
use Barryvdh\DomPDF\Facade\Pdf;
use Maatwebsite\Excel\Facades\Excel;
use PhpOffice\PhpWord\IOFactory;

class ExportController extends Controller
{
    // ======================== Resume Asesmen (Admin) ========================

    public function resumeExcel(?int $prodiId = null)
    {
        $user = auth()->user();
        abort_if(
            !in_array($user->role, [RoleEnum::Admin, RoleEnum::AdminBaak]),
            403
        );

        $prodiId = request('prodi_id');
        $jenisRpl = request('jenis_rpl') ?: null;
        $semester = request('semester') ?: null;
        $tanggalDari = request('tanggal_dari') ?: null;
        $tanggalSampai = request('tanggal_sampai') ?: null;
        $filename = 'Resume_Asesmen' . ($prodiId ? '_' . ProgramStudi::find($prodiId)?->kode : '') . '_' . now()->format('Ymd') . '.xlsx';

        return Excel::download(new ResumeAsesmenExport(
            prodiId: $prodiId,
            jenisRpl: $jenisRpl,
            semester: $semester,
            tanggalDari: $tanggalDari,
            tanggalSampai: $tanggalSampai,
        ), $filename);
    }

    public function resumePdf()
    {
        $user = auth()->user();
        abort_if(
            !in_array($user->role, [RoleEnum::Admin, RoleEnum::AdminBaak]),
            403
        );

        $prodiId = request('prodi_id');
        $jenisRpl = request('jenis_rpl') ?: null;
        $semester = request('semester') ?: null;
        $tanggalDari = request('tanggal_dari') ?: null;
        $tanggalSampai = request('tanggal_sampai') ?: null;
        $prodi = $prodiId ? ProgramStudi::find($prodiId) : null;

        $list = PermohonanRpl::with([
            'peserta.user',
            'programStudi',
            'tahunAjaran',
            'asesor.user',
            'rplMataKuliah.mataKuliah',
        ])->whereNotIn('status', ['draf', 'diajukan'])
            ->when($prodiId, fn($q) => $q->where('program_studi_id', $prodiId))
            ->when($jenisRpl, fn($q) => $q->where('jenis_rpl', $jenisRpl))
            ->when($semester, fn($q) => $q->where('semester', $semester))
            ->when($tanggalDari, fn($q) => $q->whereDate('tanggal_pengajuan', '>=', $tanggalDari))
            ->when($tanggalSampai, fn($q) => $q->whereDate('tanggal_pengajuan', '<=', $tanggalSampai))
            ->latest('tanggal_pengajuan')
            ->get();

        $permohonanList = $list->map(function ($p) {
            $sksDiakui = $p->rplMataKuliah
                ->where('status', StatusRplMataKuliahEnum::Diakui)
                ->sum(fn($m) => $m->mataKuliah->sks ?? 0);
            $totalSksProdi = (int) ($p->programStudi?->total_sks ?? 0);

            return [
                'permohonan' => $p,
                'sksDiakui' => $sksDiakui,
                'sksTidakDiakui' => $totalSksProdi - $sksDiakui,
                'mkDiakui' => $p->rplMataKuliah->where('status', StatusRplMataKuliahEnum::Diakui)->count(),
                'mkTidakDiakui' => $p->rplMataKuliah->where('status', StatusRplMataKuliahEnum::TidakDiakui)->count(),
            ];
        });

        $pdf = Pdf::loadView('pdf.resume-asesmen', [
            'permohonanList' => $permohonanList,
            'prodiNama' => $prodi?->nama,
        ])->setPaper('A4', 'landscape');

        $filename = 'Resume_Asesmen' . ($prodi ? '_' . $prodi->kode : '') . '_' . now()->format('Ymd') . '.pdf';

        return $pdf->download($filename);
    }

    // ======================== Resume per Asesor ========================

    public function resumeAsesorExcel()
    {
        $user = auth()->user();
        $asesor = $user->asesor;
        abort_if(!$asesor, 403);

        $jenisRpl = request('jenis_rpl') ?: null;
        $semester = request('semester') ?: null;
        $tanggalDari = request('tanggal_dari') ?: null;
        $tanggalSampai = request('tanggal_sampai') ?: null;
        $filename = 'Resume_Asesmen_' . now()->format('Ymd') . '.xlsx';

        return Excel::download(new ResumeAsesmenExport(
            asesorId: $asesor->id,
            jenisRpl: $jenisRpl,
            semester: $semester,
            tanggalDari: $tanggalDari,
            tanggalSampai: $tanggalSampai,
        ), $filename);
    }

    public function resumeAsesorPdf()
    {
        $user = auth()->user();
        $asesor = $user->asesor;
        abort_if(!$asesor, 403);

        $jenisRpl = request('jenis_rpl') ?: null;
        $semester = request('semester') ?: null;
        $tanggalDari = request('tanggal_dari') ?: null;
        $tanggalSampai = request('tanggal_sampai') ?: null;

        $list = PermohonanRpl::with([
            'peserta.user',
            'programStudi',
            'tahunAjaran',
            'asesor.user',
            'rplMataKuliah.mataKuliah',
        ])->whereNotIn('status', ['draf', 'diajukan'])
            ->whereHas('asesor', fn($q) => $q->where('asesor_id', $asesor->id))
            ->when($jenisRpl, fn($q) => $q->where('jenis_rpl', $jenisRpl))
            ->when($semester, fn($q) => $q->where('semester', $semester))
            ->when($tanggalDari, fn($q) => $q->whereDate('tanggal_pengajuan', '>=', $tanggalDari))
            ->when($tanggalSampai, fn($q) => $q->whereDate('tanggal_pengajuan', '<=', $tanggalSampai))
            ->latest('tanggal_pengajuan')
            ->get();

        $permohonanList = $list->map(function ($p) {
            $sksDiakui = $p->rplMataKuliah
                ->where('status', StatusRplMataKuliahEnum::Diakui)
                ->sum(fn($m) => $m->mataKuliah->sks ?? 0);
            $totalSksProdi = (int) ($p->programStudi?->total_sks ?? 0);

            return [
                'permohonan' => $p,
                'sksDiakui' => $sksDiakui,
                'sksTidakDiakui' => $totalSksProdi - $sksDiakui,
                'mkDiakui' => $p->rplMataKuliah->where('status', StatusRplMataKuliahEnum::Diakui)->count(),
                'mkTidakDiakui' => $p->rplMataKuliah->where('status', StatusRplMataKuliahEnum::TidakDiakui)->count(),
            ];
        });

        $pdf = Pdf::loadView('pdf.resume-asesmen', [
            'permohonanList' => $permohonanList,
            'asesorNama' => $asesor->user?->nama,
        ])->setPaper('A4', 'landscape');

        $filename = 'Resume_Asesmen_' . now()->format('Ymd') . '.pdf';

        return $pdf->download($filename);
    }

    // ======================== Word Export (Transfer & Perolehan) ========================

    public function hasilWord(PermohonanRpl $permohonan)
    {
        $user = auth()->user();

        $isAsesor = $user->asesor && $permohonan->asesor()->where('asesor_id', $user->asesor->id)->exists();
        abort_if(
            !in_array($user->role, [RoleEnum::Admin, RoleEnum::AdminBaak]) && !$isAsesor,
            403
        );

        $nilaiKonversi = app(NilaiKonversiService::class);
        $ttdWadir = Penandatangan::where('posisi', 'wadir')->where('aktif', true)->orderBy('urutan')->first();
        $prodiKetua = $permohonan->programStudi;

        if ($permohonan->jenis_rpl === JenisRplEnum::RplI) {
            $export = new TransferHasilWordExport($nilaiKonversi, $ttdWadir, $prodiKetua);
            $jenisStr = 'Transfer';
        } else {
            $export = new PerolehanHasilWordExport($nilaiKonversi, $ttdWadir, $prodiKetua);
            $jenisStr = 'Perolehan';
        }

        $phpWord = $export->generate($permohonan);
        $filename = 'Hasil_' . $jenisStr . '_' . $permohonan->nomor_permohonan . '_' . now()->format('Ymd') . '.docx';
        $tmpPath = sys_get_temp_dir() . '/' . $filename;

        $writer = IOFactory::createWriter($phpWord, 'Word2007');
        $writer->save($tmpPath);

        return response()->download($tmpPath, $filename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        ])->deleteFileAfterSend(true);
    }
}
