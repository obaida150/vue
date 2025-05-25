<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Berichtsheft - Bericht Nr. {{ $report->berichtsnummer }}</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            font-size: 12px;
            line-height: 1.4;
            color: #333;
            margin: 0;
            padding: 20px;
        }

        .header {
            text-align: center;
            margin-bottom: 30px;
            border-bottom: 2px solid #333;
            padding-bottom: 20px;
        }

        .document-title {
            font-size: 16px;
            font-weight: bold;
            margin-top: 15px;
        }

        .info-grid {
            display: table;
            width: 100%;
            margin-bottom: 25px;
        }

        .info-row {
            display: table-row;
        }

        .info-label {
            display: table-cell;
            width: 25%;
            font-weight: bold;
            padding: 8px 10px 8px 0;
            border-bottom: 1px solid #ddd;
        }

        .info-value {
            display: table-cell;
            width: 25%;
            padding: 8px 10px 8px 0;
            border-bottom: 1px solid #ddd;
        }

        .section {
            margin-bottom: 25px;
            page-break-inside: avoid;
        }

        .section-title {
            font-size: 14px;
            font-weight: bold;
            background-color: #f5f5f5;
            padding: 10px;
            margin-bottom: 15px;
            border-left: 4px solid #007bff;
        }

        .content-box {
            border: 1px solid #ddd;
            padding: 15px;
            min-height: 100px;
            background-color: #fafafa;
        }

        .subjects-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
        }

        .subjects-table th,
        .subjects-table td {
            border: 1px solid #ddd;
            padding: 8px;
            text-align: left;
        }

        .subjects-table th {
            background-color: #f5f5f5;
            font-weight: bold;
        }

        .signature-section {
            margin-top: 40px;
            display: table;
            width: 100%;
        }

        .signature-box {
            display: table-cell;
            width: 50%;
            padding: 20px;
            text-align: center;
        }

        .signature-line {
            border-top: 1px solid #333;
            margin-top: 60px;
            padding-top: 5px;
            font-size: 10px;
        }

        .footer {
            position: fixed;
            bottom: 20px;
            left: 20px;
            right: 20px;
            text-align: center;
            font-size: 10px;
            color: #666;
            border-top: 1px solid #ddd;
            padding-top: 10px;
        }

        @media print {
            body {
                margin: 0;
                padding: 15px;
            }
        }
    </style>
</head>
<body>
<!-- Header -->
<div class="header">
    <div class="document-title">Berichtsheft für Auszubildende</div>
</div>

<!-- Grunddaten -->
<div class="section">
    <div class="section-title">Grunddaten</div>
    <div class="info-grid">
        <div class="info-row">
            <div class="info-label">Berichtsnummer:</div>
            <div class="info-value">{{ $report->berichtsnummer }}</div>
            <div class="info-label">Berichtart:</div>
            <div class="info-value">{{ $report->type }}</div>
        </div>
        <div class="info-row">
            <div class="info-label">Lehrjahr:</div>
            <div class="info-value">{{ $report->year }}. Lehrjahr</div>
            <div class="info-label">Ausbilder:</div>
            <div class="info-value">{{ $report->instructor ? $report->instructor->name : 'Nicht zugewiesen' }}</div>
        </div>
        <div class="info-row">
            <div class="info-label">Zeitraum von:</div>
            <div class="info-value">{{ $report->date_from ? $report->date_from->format('d.m.Y') : 'Nicht angegeben' }}</div>
            <div class="info-label">Zeitraum bis:</div>
            <div class="info-value">{{ $report->date_to ? $report->date_to->format('d.m.Y') : 'Nicht angegeben' }}</div>
        </div>
        <div class="info-row">
            <div class="info-label">Erstellt am:</div>
            <div class="info-value">{{ $report->created_at->format('d.m.Y H:i') }}</div>
            <div class="info-label">Auszubildende/r:</div>
            <div class="info-value">{{ $report->user ? $report->user->name : 'Unbekannt' }}</div>
        </div>
    </div>
</div>

@if($report->type === 'Betrieb')
    <!-- Betriebliche Tätigkeiten -->
    <div class="section">
        <div class="section-title">Betriebliche Tätigkeiten</div>
        <div class="content-box">
            {!! nl2br(e($report->activities ?? 'Keine Tätigkeiten angegeben.')) !!}
        </div>
    </div>

    <!-- Unterweisungen -->
    <div class="section">
        <div class="section-title">Unterweisungen, betrieblicher Unterricht, sonstige Schulungen</div>
        <div class="content-box">
            {!! nl2br(e($report->unterweisungen ?? 'Keine Unterweisungen angegeben.')) !!}
        </div>
    </div>
@endif

@if($report->type === 'Berufsschule')
    <!-- Berufsschule Fächer -->
    <div class="section">
        <div class="section-title">Berufsschule - Unterrichtsfächer</div>

        @if($report->subjects_data && is_array($report->subjects_data) && count($report->subjects_data) > 0)
            <table class="subjects-table">
                <thead>
                <tr>
                    <th>Fach</th>
                    <th>Inhalte</th>
                </tr>
                </thead>
                <tbody>
                @foreach($report->subjects_data as $subject)
                    <tr>
                        <td>{{ $subject['name'] ?? 'Unbekanntes Fach' }}</td>
                        <td>{{ $subject['content'] ?? 'Keine Inhalte angegeben' }}</td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        @else
            <div class="content-box">
                Keine Fächer angegeben.
            </div>
        @endif
    </div>

    <!-- Berufsschule Unterricht -->
    @if($report->unterricht)
        <div class="section">
            <div class="section-title">Berufsschule - Unterrichtsinhalte</div>
            <div class="content-box">
                {!! nl2br(e($report->unterricht)) !!}
            </div>
        </div>
    @endif
@endif

<!-- Unterschriften -->
<div class="signature-section">
    <div class="signature-box">
        <div class="signature-line">
            Datum, Unterschrift Auszubildende/r
        </div>
    </div>
    <div class="signature-box">
        <div class="signature-line">
            Datum, Unterschrift Ausbilder/in
        </div>
    </div>
</div>

<!-- Footer -->
<div class="footer">
    Berichtsheft - Bericht Nr. {{ $report->berichtsnummer }} |
    Erstellt am {{ $report->created_at->format('d.m.Y H:i') }} |
    Seite 1
</div>
</body>
</html>
