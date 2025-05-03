<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Urlaubsantrag abgelehnt</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            color: #333;
            max-width: 600px;
            margin: 0 auto;
        }
        .header {
            background-color: #F44336;
            color: white;
            padding: 20px;
            text-align: center;
        }
        .content {
            padding: 20px;
            border: 1px solid #ddd;
        }
        .footer {
            margin-top: 20px;
            font-size: 12px;
            color: #777;
            text-align: center;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin: 20px 0;
        }
        table, th, td {
            border: 1px solid #ddd;
        }
        th, td {
            padding: 10px;
            text-align: left;
        }
        th {
            background-color: #f2f2f2;
        }
        .btn {
            display: inline-block;
            padding: 10px 20px;
            margin: 10px 0;
            color: white;
            text-decoration: none;
            border-radius: 4px;
            font-weight: bold;
            background-color: #4a6fdc;
        }
    </style>
</head>
<body>
<div class="header">
    <h1>Urlaubsantrag abgelehnt</h1>
</div>

<div class="content">
    <p>Hallo {{ $employee->full_name }},</p>

    <p>Ihr Urlaubsantrag wurde <strong>abgelehnt</strong>.</p>

    <h2>Details zum Antrag:</h2>

    <table>
        <tr>
            <th>Zeitraum:</th>
            <td>Von {{ $vacationRequest->start_date->format('d.m.Y') }} bis {{ $vacationRequest->end_date->format('d.m.Y') }}</td>
        </tr>
        <tr>
            <th>Anzahl der Tage:</th>
            <td>{{ $vacationRequest->days }} Arbeitstage</td>
        </tr>
        @if ($vacationRequest->notes)
            <tr>
                <th>Anmerkungen:</th>
                <td>{{ $vacationRequest->notes }}</td>
            </tr>
        @endif
        @if ($rejectionReason)
            <tr>
                <th>Ablehnungsgrund:</th>
                <td>{{ $rejectionReason }}</td>
            </tr>
        @endif
        @if ($approver)
            <tr>
                <th>Abgelehnt von:</th>
                <td>{{ $approver->full_name }}</td>
            </tr>
        @endif
    </table>

    <p>
        Bei Fragen wenden Sie sich bitte an Ihren Vorgesetzten.
    </p>

    <p>
        <a href="{{ url('/vacation') }}" class="btn">Zur Urlaubsübersicht</a>
    </p>
</div>

<div class="footer">
    <p>Dies ist eine automatisch generierte E-Mail. Bitte antworten Sie nicht auf diese Nachricht.</p>
</div>
</body>
</html>
