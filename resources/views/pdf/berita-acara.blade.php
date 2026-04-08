<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Berita Acara Asesmen RPL</title>
    <style>
        body { font-family: 'Times New Roman', serif; font-size: 12pt; margin: 0; padding: 20px; color: #000; }
        h2 { text-align: center; font-size: 14pt; margin: 0 0 4px 0; text-transform: uppercase; }
        h3 { text-align: center; font-size: 12pt; margin: 0 0 16px 0; }
        .header-table { width: 100%; margin-bottom: 16px; }
        .header-table td { padding: 2px 4px; vertical-align: top; font-size: 11pt; }
        .label-col { width: 40%; }
        .separator { width: 4%; text-align: center; }
        table.data { width: 100%; border-collapse: collapse; margin-top: 12px; }
        table.data th, table.data td { border: 1px solid #000; padding: 6px 8px; font-size: 11pt; }
        table.data th { background: #e8e8e8; text-align: center; font-weight: bold; }
        table.data td.center { text-align: center; }
        .ttd-section { margin-top: 40px; }
        .ttd-table { width: 100%; border-collapse: collapse; }
        .ttd-table td { text-align: center; padding: 0 20px; vertical-align: top; width: 50%; }
        .ttd-box { min-height: 70px; display: flex; align-items: center; justify-content: center; }
        .ttd-box img { max-height: 65px; max-width: 140px; object-fit: contain; }
        .info-row { display: flex; gap: 40px; margin-bottom: 8px; font-size: 11pt; }
        .stat-box { border: 1px solid #ccc; display: inline-block; padding: 6px 14px; margin-right: 16px; font-size: 11pt; }
    </style>
</head>
<body>

    <h2>Berita Acara Asesmen RPL</h2>
    <h3>Politeknik Caltex Riau</h3>

    <table class="header-table">
        <tr>
            <td class="label-col">Asesor</td>
            <td class="separator">:</td>
            <td>{{ $ba->asesor->user->nama ?? '—' }}</td>
        </tr>
        <tr>
            <td class="label-col">Tanggal Asesmen</td>
            <td class="separator">:</td>
            <td>{{ \Carbon\Carbon::parse($ba->tanggal_asesmen)->locale('id')->isoFormat('D MMMM YYYY') }}</td>
        </tr>
        <tr>
            <td class="label-col">Tahun Ajaran</td>
            <td class="separator">:</td>
            <td>{{ $ba->tahunAjaran->nama ?? '—' }}</td>
        </tr>
    </table>

    <p style="font-size:11pt; margin-bottom:4px;"><strong>Rekapitulasi Kehadiran:</strong></p>
    <span class="stat-box">Jumlah Peserta: <strong>{{ $ba->jumlah_peserta }}</strong></span>
    <span class="stat-box">Hadir: <strong>{{ $ba->jumlah_hadir }}</strong></span>
    <span class="stat-box">Tidak Hadir: <strong>{{ $ba->jumlah_tidak_hadir }}</strong></span>

    <table class="data">
        <thead>
            <tr>
                <th style="width:5%">No</th>
                <th style="width:35%">Nama Peserta</th>
                <th style="width:15%">Total SKS Diperoleh</th>
                <th style="width:25%">Tanggal Asesi</th>
                <th style="width:20%">Keterangan</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($ba->peserta as $i => $bap)
            <tr>
                <td class="center">{{ $i + 1 }}</td>
                <td>{{ $bap->peserta->user->nama ?? '—' }}</td>
                <td class="center">{{ $bap->total_sks_diperoleh }}</td>
                <td class="center">{{ \Carbon\Carbon::parse($ba->tanggal_asesmen)->locale('id')->isoFormat('D MMMM YYYY') }}</td>
                <td class="center">{{ $bap->hadir ? 'Hadir' : 'Tidak Hadir' }}</td>
            </tr>
            @empty
            <tr>
                <td colspan="5" style="text-align:center; color:#888">Tidak ada data peserta</td>
            </tr>
            @endforelse
        </tbody>
    </table>

    <div class="ttd-section">
        <table class="ttd-table">
            <tr>
                {{-- TTD Kiri: Penandatangan (Admin BAAK) --}}
                <td>
                    <p style="margin:0">{{ $ba->penandatanganKiri?->jabatan ?? 'Mengetahui,' }}</p>
                    <div class="ttd-box">
                        @if ($ba->penandatanganKiri?->tanda_tangan && \Illuminate\Support\Facades\Storage::disk('local')->exists($ba->penandatanganKiri->tanda_tangan))
                        <img src="{{ Storage::disk('local')->path($ba->penandatanganKiri->tanda_tangan) }}" alt="TTD">
                        @endif
                    </div>
                    <p style="margin:0; font-weight:bold">{{ $ba->penandatanganKiri?->nama ?? '____________________' }}</p>
                    @if ($ba->penandatanganKiri?->nip)
                    <p style="margin:0; font-size:10pt">NIP. {{ $ba->penandatanganKiri->nip }}</p>
                    @endif
                </td>

                {{-- TTD Kanan: Asesor --}}
                <td>
                    <p style="margin:0">Asesor,</p>
                    <div class="ttd-box">
                        @if ($ba->asesor?->tanda_tangan && \Illuminate\Support\Facades\Storage::disk('local')->exists($ba->asesor->tanda_tangan))
                        <img src="{{ Storage::disk('local')->path($ba->asesor->tanda_tangan) }}" alt="TTD">
                        @endif
                    </div>
                    <p style="margin:0; font-weight:bold">{{ $ba->asesor?->user?->nama ?? '____________________' }}</p>
                    @if ($ba->asesor?->nidn)
                    <p style="margin:0; font-size:10pt">NIDN. {{ $ba->asesor->nidn }}</p>
                    @endif
                </td>
            </tr>
        </table>
    </div>

</body>
</html>
