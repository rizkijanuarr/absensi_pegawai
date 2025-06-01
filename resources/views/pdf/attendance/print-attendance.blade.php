<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>Laporan Absensi</title>
    <style>
        @page {
            size: A4 landscape;
            margin: 1cm;
        }
        body {
            font-family: 'Segoe UI', Arial, Helvetica, sans-serif;
            font-size: 9pt;
            color: #222;
        }
        .header {
            text-align: center;
            margin-bottom: 0.5cm;
        }
        .header h1 {
            margin: 0;
            font-size: 22pt;
            font-weight: bold;
            letter-spacing: 1px;
        }
        .header p {
            margin: 2px 0 0 0;
            color: #666;
            font-size: 11pt;
        }
        .header .periode {
            margin-top: 6px;
            font-size: 10pt;
            color: #444;
        }
        .line {
            border-bottom: 2px solid #0e7490;
            margin: 0.2cm 0 0.5cm 0;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 0.2cm;
        }
        th, td {
            border: 1px solid #bdbdbd;
            padding: 7px 4px;
            font-size: 8.5pt;
        }
        th {
            background: #0e7490;
            color: #fff;
            font-weight: bold;
            text-align: center;
            letter-spacing: 0.5px;
        }
        tr:nth-child(even) td {
            background: #f3fafd;
        }
        tr:nth-child(odd) td {
            background: #fff;
        }
        .text-center { text-align: center; }
        .text-right { text-align: right; }
        .status-tw { background: #d1fae5 !important; color: #065f46; font-weight: bold; }
        .status-tl { background: #fef9c3 !important; color: #92400e; font-weight: bold; }
        .status-th { background: #fee2e2 !important; color: #991b1b; font-weight: bold; }
        .status-psw { background: #e0e7ff !important; color: #3730a3; font-weight: bold; }
        .footer {
            margin-top: 1cm;
            text-align: right;
            font-size: 8pt;
            color: #888;
        }
    </style>
</head>
<body>
    <div class="header">
        <img src="{{ public_path('logo-app.png') }}" alt="Logo" style="height:55px; margin-bottom:8px; display:block; margin-left:auto; margin-right:auto;">
        <h1>{{ config('app.name') }}</h1>
        <p>Laporan Absensi Pegawai</p>
        <div class="periode">Periode: <b>{{ $periode }}</b></div>
    </div>
    <div class="line"></div>
    @php
        function formatMenit($menit) {
            if (!$menit || $menit == 0) return '0 menit';
            $jam = floor($menit / 60);
            $mnt = $menit % 60;
            if ($jam > 0 && $mnt > 0) return "$jam jam $mnt menit";
            if ($jam > 0) return "$jam jam";
            return "$mnt menit";
        }
    @endphp
    <table>
        <thead>
            <tr>
                <th style="width: 3%">No</th>
                <th style="width: 8%">Tanggal</th>
                <th style="width: 8%">Pegawai</th>
                <th style="width: 7%">Lat Office</th>
                <th style="width: 7%">Long Office</th>
                <th style="width: 7%">Shift Mulai</th>
                <th style="width: 7%">Shift Selesai</th>
                <th style="width: 7%">Lat Datang</th>
                <th style="width: 7%">Long Datang</th>
                <th style="width: 7%">Waktu Datang</th>
                <th style="width: 5%">Status</th>
                <th style="width: 5%">Keterlambatan</th>
                <th style="width: 7%">Lat Pulang</th>
                <th style="width: 7%">Long Pulang</th>
                <th style="width: 7%">Waktu Pulang</th>
                <th style="width: 5%">Status</th>
                <th style="width: 5%">Keterlambatan</th>
                <th style="width: 5%">Durasi</th>
            </tr>
        </thead>
        <tbody>
            @foreach($attendances as $attendance)
            <tr>
                <td class="text-center">{{ $loop->iteration }}</td>
                <td class="text-center">{{ $attendance->created_at->format('d/m/Y') }}</td>
                <td>{{ $attendance->user->name }}</td>
                <td class="text-right">{{ $attendance->schedule_latitude }}</td>
                <td class="text-right">{{ $attendance->schedule_longitude }}</td>
                <td class="text-center">{{ $attendance->schedule_start_time }}</td>
                <td class="text-center">{{ $attendance->schedule_end_time }}</td>
                <td class="text-right">{{ $attendance->start_latitude }}</td>
                <td class="text-right">{{ $attendance->start_longitude }}</td>
                <td class="text-center">{{ $attendance->start_time }}</td>
                <td class="text-center @if($attendance->overdue_status_label=="TW") status-tw @elseif(str_contains($attendance->overdue_status_label,'TL')) status-tl @elseif($attendance->overdue_status_label=="TH") status-th @endif">
                    {{ $attendance->overdue_status_label }}
                </td>
                <td class="text-center">{{ formatMenit($attendance->overdue_minutes) }}</td>
                <td class="text-right">{{ $attendance->end_latitude }}</td>
                <td class="text-right">{{ $attendance->end_longitude }}</td>
                <td class="text-center">{{ $attendance->end_time }}</td>
                <td class="text-center @if($attendance->return_status_label=="TW") status-tw @elseif(str_contains($attendance->return_status_label,'PSW')) status-psw @elseif($attendance->return_status_label=="TH") status-th @endif">
                    {{ $attendance->return_status_label }}
                </td>
                <td class="text-center">{{ formatMenit($attendance->return_minutes) }}</td>
                <td class="text-center">{{ formatMenit($attendance->work_duration) }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>
    <div class="footer">
        Dicetak pada: {{ now()->format('d/m/Y H:i') }}<br>
        {{ config('app.name') }}
    </div>
</body>
</html>