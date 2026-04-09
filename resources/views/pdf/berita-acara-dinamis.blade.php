<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Berita Acara Asesmen RPL</title>
    <style>
        body {
            font-family: "Times New Roman", serif;
            font-size: 11pt;
            margin: 0;
            padding: 24px;
            color: #000;
        }
        .kop-table {
            width: 100%;
            border-collapse: collapse;
        }
        .kop-table td {
            vertical-align: middle;
            text-align: center;
        }
        .logo-cell {
            width: 100%;
        }
        .logo {
            width: 150px;
            height: auto;
        }
        .divider {
            border-top: 2px solid #000;
            margin-top: 10px;
            margin-bottom: 16px;
        }
        h2 {
            text-align: center;
            font-size: 13pt;
            margin: 0 0 12px;
            text-transform: uppercase;
            letter-spacing: 0.3px;
        }
        .meta-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 10px;
        }
        .meta-table td {
            padding: 2px 4px;
            vertical-align: top;
            font-size: 10.5pt;
        }
        .meta-label {
            width: 28%;
        }
        .meta-sep {
            width: 3%;
            text-align: center;
        }
        .summary {
            margin-top: 4px;
            margin-bottom: 12px;
            font-size: 10.5pt;
        }
        .table-data {
            width: 100%;
            border-collapse: collapse;
        }
        .table-data th,
        .table-data td {
            border: 1px solid #000;
            padding: 6px 7px;
            font-size: 10.5pt;
        }
        .table-data th {
            text-align: center;
            background: #f0f0f0;
            font-weight: bold;
        }
        .center {
            text-align: center;
        }
        .ttd-section {
            margin-top: 34px;
        }
        .ttd-table {
            width: 100%;
            border-collapse: collapse;
        }
        .ttd-table td {
            width: 50%;
            vertical-align: top;
            text-align: center;
            padding: 0 16px;
        }
        .ttd-box {
            min-height: 74px;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .ttd-box img {
            max-height: 68px;
            max-width: 160px;
            object-fit: contain;
        }
        .nama-ttd {
            font-weight: bold;
        }
        .nip {
            font-size: 10pt;
        }
        .footer-last {
            margin-top: 22px;
            border-top: 1px solid #000;
            padding-top: 7px;
            text-align: center;
            font-size: 9.5pt;
            line-height: 1.45;
            page-break-inside: avoid;
        }
    </style>
</head>
<body>

    @php
        $logoPath = public_path('img/logo_pcr.png');
        $periodeTanggal = 'Semua tanggal asesi';

        if ($tanggalDari && $tanggalSampai) {
            $periodeTanggal = $tanggalDari->locale('id')->isoFormat('D MMMM YYYY') . ' s.d. ' . $tanggalSampai->locale('id')->isoFormat('D MMMM YYYY');
        } elseif ($tanggalDari) {
            $periodeTanggal = 'Mulai ' . $tanggalDari->locale('id')->isoFormat('D MMMM YYYY');
        } elseif ($tanggalSampai) {
            $periodeTanggal = 'Sampai ' . $tanggalSampai->locale('id')->isoFormat('D MMMM YYYY');
        }
    @endphp

    <table class="kop-table">
        <tr>
            <td class="logo-cell">
                @if (file_exists($logoPath))
                <img src="{{ $logoPath }}" alt="Logo PCR" class="logo">
                @endif
            </td>
        </tr>
    </table>
    <div class="divider"></div>

    <h2>Berita Acara Asesmen RPL</h2>

    <table class="meta-table">
        <tr>
            <td class="meta-label">Nama Asesor</td>
            <td class="meta-sep">:</td>
            <td>{{ $asesor->user?->nama ?? '—' }}</td>
        </tr>
        <tr>
            <td class="meta-label">Tahun Ajaran</td>
            <td class="meta-sep">:</td>
            <td>{{ $tahunAjaran->nama ?? '—' }}</td>
        </tr>
        <tr>
            <td class="meta-label">Periode Tanggal Asesi</td>
            <td class="meta-sep">:</td>
            <td>{{ $periodeTanggal }}</td>
        </tr>
        <tr>
            <td class="meta-label">Tanggal Cetak</td>
            <td class="meta-sep">:</td>
            <td>{{ now()->locale('id')->isoFormat('D MMMM YYYY, HH:mm') }} WIB</td>
        </tr>
    </table>

    <div class="summary">
        Total peserta terjadwal: <strong>{{ $rows->count() }}</strong>
    </div>

    <table class="table-data">
        <thead>
            <tr>
                <th style="width: 6%;">No</th>
                <th style="width: 23%;">Nama Peserta</th>
                <th style="width: 16%;">Jenis RPL</th>
                <th style="width: 15%;">Total SKS Diperoleh</th>
                <th style="width: 20%;">Tanggal Asesi</th>
                <th style="width: 20%;">Keterangan Hadir</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($rows as $idx => $row)
            <tr>
                <td class="center">{{ $idx + 1 }}</td>
                <td>{{ $row['nama_peserta'] }}</td>
                <td class="center">{{ $row['jenis_rpl'] }}</td>
                <td class="center">{{ $row['total_sks_diperoleh'] }}</td>
                <td class="center">{{ $row['tanggal_asesi']->locale('id')->isoFormat('D MMMM YYYY') }}</td>
                <td class="center">{{ $row['keterangan_hadir'] }}</td>
            </tr>
            @empty
            <tr>
                <td colspan="6" class="center" style="color: #666;">Tidak ada data peserta untuk filter yang dipilih.</td>
            </tr>
            @endforelse
        </tbody>
    </table>

    <div class="ttd-section">
        <table class="ttd-table">
            <tr>
                <td>
                    <p style="margin: 0;">{{ $penandatanganKiri?->jabatan ?? 'Mengetahui,' }}</p>
                    <div class="ttd-box">
                        @if ($penandatanganKiri?->tanda_tangan && \Illuminate\Support\Facades\Storage::disk('local')->exists($penandatanganKiri->tanda_tangan))
                        <img src="{{ \Illuminate\Support\Facades\Storage::disk('local')->path($penandatanganKiri->tanda_tangan) }}" alt="TTD Kiri">
                        @endif
                    </div>
                    <p class="nama-ttd" style="margin: 0;">{{ $penandatanganKiri?->nama ?? '____________________' }}</p>
                    @if ($penandatanganKiri?->nip)
                    <p class="nip" style="margin: 0;">NIP. {{ $penandatanganKiri->nip }}</p>
                    @endif
                </td>
                <td>
                    <p style="margin: 0;">Asesor,</p>
                    <div class="ttd-box">
                        @if ($asesor?->tanda_tangan && \Illuminate\Support\Facades\Storage::disk('local')->exists($asesor->tanda_tangan))
                        <img src="{{ \Illuminate\Support\Facades\Storage::disk('local')->path($asesor->tanda_tangan) }}" alt="TTD Asesor">
                        @endif
                    </div>
                    <p class="nama-ttd" style="margin: 0;">{{ $asesor?->user?->nama ?? '____________________' }}</p>
                    @if ($asesor?->nidn)
                    <p class="nip" style="margin: 0;">NIDN. {{ $asesor->nidn }}</p>
                    @endif
                </td>
            </tr>
        </table>
    </div>

    <div class="footer-last">
        <div>Jl. Umban Sari No.1, Umban Sari, Kec. Rumbai, Kota Pekanbaru, Riau 28265</div>
        <div>(0761) 53939 | pcr.ac.id</div>
    </div>

</body>
</html>
