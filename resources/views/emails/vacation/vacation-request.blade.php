<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Neuer Urlaubsantrag</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            color: #333;
            max-width: 600px;
            margin: 0 auto;
        }
        .header {
            background-color: #4a6fdc;
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
            margin: 10px 5px;
            color: white;
            text-decoration: none;
            border-radius: 4px;
            font-weight: bold;
        }
        .btn-approve {
            background-color: #4CAF50;
        }
        .btn-reject {
            background-color: #F44336;
        }
        .action-buttons {
            text-align: center;
            margin: 20px 0;
        }
    </style>
</head>
<body>
<div class="header">
    <h1>Neuer Urlaubsantrag</h1>
</div>

<div class="content">
    <p>Hallo,</p>

    <p>ein neuer Urlaubsantrag wurde eingereicht und erfordert Ihre Genehmigung.</p>

    <h2>Details zum Antrag:</h2>

    <table>
        <tr>
            <th>Mitarbeiter:</th>
            <td>{{ $employee->full_name }}</td>
        </tr>
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
        @if ($substitute)
            <tr>
                <th>Vertretung:</th>
                <td>{{ $substitute->full_name }}</td>
            </tr>
        @endif
    </table>

    @if ($overlappingRequests->isNotEmpty())
        <h3>Überlappende Urlaubsanträge:</h3>
        <table>
            <tr>
                <th>Mitarbeiter</th>
                <th>Zeitraum</th>
            </tr>
            @foreach ($overlappingRequests as $request)
                <tr>
                    <td>{{ $request['employee_name'] }}</td>
                    <td>Von {{ $request['start_date'] }} bis {{ $request['end_date'] }}</td>
                </tr>
            @endforeach
        </table>
    @endif

    <div class="action-buttons">
        <p>Bitte entscheiden Sie über diesen Urlaubsantrag:</p>
        <a href="{{ url('/vacation/management?action=approve&id=' . $vacationRequest->id) }}" class="btn btn-approve">Genehmigen</a>
        <a href="{{ url('/vacation/management?action=reject&id=' . $vacationRequest->id) }}" class="btn btn-reject">Ablehnen</a>
    </div>

    <p>
        Alternativ können Sie sich auch direkt in das System einloggen, um den Antrag zu bearbeiten:
        <a href="{{ url('/vacation/management') }}">Zur Urlaubsverwaltung</a>
    </p>
</div>

<div class="footer">
    <p>Dies ist eine automatisch generierte E-Mail. Bitte antworten Sie nicht auf diese Nachricht.</p>
</div>
</body>
</html>
