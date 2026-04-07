<?php

namespace App\Http\Controllers;

use App\Enums\JenisRplEnum;
use App\Enums\RoleEnum;
use App\Enums\StatusRplMataKuliahEnum;
use App\Exports\ResumeAsesmenExport;
use App\Exports\TransferHasilWordExport;
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
            ! in_array($user->role, [RoleEnum::Admin, RoleEnum::AdminBaak]),
            403
        );

        $prodiId = request('prodi_id');
        $filename = 'Resume_Asesmen' . ($prodiId ? '_' . ProgramStudi::find($prodiId)?->kode : '') . '_' . now()->format('Ymd') . '.xlsx';

        return Excel::download(new ResumeAsesmenExport(prodiId: $prodiId), $filename);
    }

    public function resumePdf()
    {
        $user = auth()->user();
        abort_if(
            ! in_array($user->role, [RoleEnum::Admin, RoleEnum::AdminBaak]),
            403
        );

        $prodiId = request('prodi_id');
        $prodi   = $prodiId ? ProgramStudi::find($prodiId) : null;

        $list = PermohonanRpl::with([
            'peserta.user',
            'programStudi',
            'tahunAjaran',
            'rplMataKuliah.mataKuliah',
        ])->whereNotIn('status', ['draf', 'diajukan'])
          ->when($prodiId, fn($q) => $q->where('program_studi_id', $prodiId))
          ->latest('tanggal_pengajuan')
          ->get();

        $permohonanList = $list->map(fn($p) => [
            'permohonan'     => $p,
            'sksDiakui'      => $p->rplMataKuliah->where('status', StatusRplMataKuliahEnum::Diakui)->sum(fn($m) => $m->mataKuliah->sks ?? 0),
            'mkDiakui'       => $p->rplMataKuliah->where('status', StatusRplMataKuliahEnum::Diakui)->count(),
            'mkTidakDiakui'  => $p->rplMataKuliah->where('status', StatusRplMataKuliahEnum::TidakDiakui)->count(),
        ]);

        $pdf = Pdf::loadView('pdf.resume-asesmen', [
            'permohonanList' => $permohonanList,
            'prodiNama'      => $prodi?->nama,
        ])->setPaper('A4', 'landscape');

        $filename = 'Resume_Asesmen' . ($prodi ? '_' . $prodi->kode : '') . '_' . now()->format('Ymd') . '.pdf';

        return $pdf->download($filename);
    }

    // ======================== Resume per Asesor ========================

    public function resumeAsesorExcel()
    {
        $user   = auth()->user();
        $asesor = $user->asesor;
        abort_if(! $asesor, 403);

        $filename = 'Resume_Asesmen_' . now()->format('Ymd') . '.xlsx';

        return Excel::download(new ResumeAsesmenExport(asesorId: $asesor->id), $filename);
    }

    public function resumeAsesorPdf()
    {
        $user   = auth()->user();
        $asesor = $user->asesor;
        abort_if(! $asesor, 403);

        $list = PermohonanRpl::with([
            'peserta.user',
            'programStudi',
            'tahunAjaran',
            'rplMataKuliah.mataKuliah',
        ])->whereNotIn('status', ['draf', 'diajukan'])
          ->whereHas('asesor', fn($q) => $q->where('asesor_id', $asesor->id))
          ->latest('tanggal_pengajuan')
          ->get();

        $permohonanList = $list->map(fn($p) => [
            'permohonan'     => $p,
            'sksDiakui'      => $p->rplMataKuliah->where('status', StatusRplMataKuliahEnum::Diakui)->sum(fn($m) => $m->mataKuliah->sks ?? 0),
            'mkDiakui'       => $p->rplMataKuliah->where('status', StatusRplMataKuliahEnum::Diakui)->count(),
            'mkTidakDiakui'  => $p->rplMataKuliah->where('status', StatusRplMataKuliahEnum::TidakDiakui)->count(),
        ]);

        $pdf = Pdf::loadView('pdf.resume-asesmen', [
            'permohonanList' => $permohonanList,
            'asesorNama'     => $asesor->user?->nama,
        ])->setPaper('A4', 'landscape');

        $filename = 'Resume_Asesmen_' . now()->format('Ymd') . '.pdf';

        return $pdf->download($filename);
    }

    // ======================== Word Export Transfer Kredit ========================

    public function transferWord(PermohonanRpl $permohonan)
    {
        $user = auth()->user();

        $isAsesor = $user->asesor && $permohonan->asesor()->where('asesor_id', $user->asesor->id)->exists();
        abort_if(
            ! in_array($user->role, [RoleEnum::Admin, RoleEnum::AdminBaak]) && ! $isAsesor,
            403
        );

        abort_if($permohonan->jenis_rpl !== JenisRplEnum::RplI, 404);

        $export   = new TransferHasilWordExport(app(NilaiKonversiService::class));
        $phpWord  = $export->generate($permohonan);

        $filename = 'Hasil_Transfer_' . $permohonan->nomor_permohonan . '_' . now()->format('Ymd') . '.docx';
        $tmpPath  = sys_get_temp_dir() . '/' . $filename;

        $writer = IOFactory::createWriter($phpWord, 'Word2007');
        $writer->save($tmpPath);

        return response()->download($tmpPath, $filename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        ])->deleteFileAfterSend(true);
    }
}
