<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<style>
    * { margin: 0; padding: 0; box-sizing: border-box; }
    body {
        font-family: 'Times New Roman', Times, serif;
        font-size: 10pt;
        color: #1a2a35;
        padding: 20px 30px;
    }
    h1 {
        font-size: 14pt;
        font-weight: bold;
        text-align: center;
        margin-bottom: 4px;
    }
    .subtitle {
        font-size: 10pt;
        text-align: center;
        color: #555;
        margin-bottom: 16px;
    }
    .divider {
        border-top: 2px solid #004B5F;
        margin-bottom: 16px;
    }
    table {
        width: 100%;
        border-collapse: collapse;
        font-size: 9pt;
    }
    th {
        background: #004B5F;
        color: white;
        padding: 6px 8px;
        text-align: left;
        font-weight: bold;
        border: 1px solid #004B5F;
    }
    td {
        padding: 5px 8px;
        border: 1px solid #ccc;
        vertical-align: top;
    }
    tr:nth-child(even) td { background: #f5f8fa; }
    .badge-diakui    { color: #1e7e3e; font-weight: bold; }
    .badge-tidak     { color: #c62828; font-weight: bold; }
    .badge-pending   { color: #b45309; }
    .meta {
        display: flex;
        justify-content: space-between;
        font-size: 9pt;
        color: #555;
        margin-bottom: 12px;
    }
    .summary {
        margin-top: 14px;
        font-size: 9pt;
        color: #444;
    }
</style>
</head>
<body>

<h1>Resume Asesmen RPL</h1>
<div class="subtitle">
    Politeknik Caltex Riau
    @if (isset($prodiNama)) — {{ $prodiNama }} @endif
    @if (isset($asesorNama)) — Asesor: {{ $asesorNama }} @endif
</div>
<div class="divider"></div>

<div class="meta">
    <span>Dicetak: {{ now()->locale('id')->isoFormat('D MMMM YYYY, HH:mm') }} WIB</span>
    <span>Total: {{ count($permohonanList) }} pengajuan</span>
</div>

<table>
    <thead>
        <tr>
            <th style="width:28px">No</th>
            <th>Nomor Permohonan</th>
            <th>Nama Peserta</th>
            <th>Program Studi</th>
            <th>Semester</th>
            <th>Jenis RPL</th>
            <th>Nama Asesor</th>
            <th>Status</th>
            <th style="width:55px;text-align:center">SKS Diakui</th>
            <th style="width:70px;text-align:center">SKS Tidak Diakui</th>
            <th style="width:35px;text-align:center">MK ✓</th>
            <th style="width:35px;text-align:center">MK ✗</th>
        </tr>
    </thead>
    <tbody>
        @foreach ($permohonanList as $i => $item)
        @php
            $p           = $item['permohonan'];
            $sksDiakui   = $item['sksDiakui'];
            $sksTidak    = $item['sksTidakDiakui'] ?? 0;
            $mkDiakui    = $item['mkDiakui'];
            $mkTidak     = $item['mkTidakDiakui'];
            $asesorNames = $p->asesor->pluck('user.nama')->filter()->implode(', ');
        @endphp
        <tr>
            <td style="text-align:center">{{ $i + 1 }}</td>
            <td>{{ $p->nomor_permohonan }}</td>
            <td>{{ $p->peserta?->user?->nama ?? '—' }}</td>
            <td>{{ $p->programStudi?->nama ?? '—' }}</td>
            <td>{{ $p->semester?->label() ?? '—' }}</td>
            <td>{{ $p->jenis_rpl?->label() ?? '—' }}</td>
            <td>{{ $asesorNames ?: '—' }}</td>
            <td>
                @if ($p->status->value === 'disetujui')
                    <span class="badge-diakui">{{ $p->status->label() }}</span>
                @elseif ($p->status->value === 'ditolak')
                    <span class="badge-tidak">{{ $p->status->label() }}</span>
                @else
                    <span class="badge-pending">{{ $p->status->label() }}</span>
                @endif
            </td>
            <td style="text-align:center">{{ $sksDiakui }}</td>
            <td style="text-align:center">{{ $sksTidak }}</td>
            <td style="text-align:center" class="badge-diakui">{{ $mkDiakui }}</td>
            <td style="text-align:center" class="badge-tidak">{{ $mkTidak }}</td>
        </tr>
        @endforeach
    </tbody>
</table>

<div class="summary">
    <strong>Total SKS Diakui:</strong>
    {{ collect($permohonanList)->sum('sksDiakui') }} SKS,
    <strong>Total SKS Tidak Diakui:</strong>
    {{ collect($permohonanList)->sum('sksTidakDiakui') }} SKS dari
    {{ collect($permohonanList)->count() }} pengajuan.
</div>

</body>
</html>
