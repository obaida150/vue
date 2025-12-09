<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Urlaub eingetragen</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            color: #333;
            max-width: 600px;
            margin: 0 auto;
            padding: 20px;
        }
        .header {
            background-color: #3b82f6;
            color: white;
            padding: 20px;
            text-align: center;
            border-radius: 5px 5px 0 0;
        }
        .content {
            background-color: #f9fafb;
            padding: 30px;
            border: 1px solid #e5e7eb;
            border-radius: 0 0 5px 5px;
        }
        .info-box {
            background-color: white;
            border: 1px solid #e5e7eb;
            border-radius: 5px;
            padding: 15px;
            margin: 15px 0;
        }
        .info-row {
            display: flex;
            justify-content: space-between;
            padding: 8px 0;
            border-bottom: 1px solid #f3f4f6;
        }
        .info-row:last-child {
            border-bottom: none;
        }
        .info-label {
            font-weight: bold;
            color: #6b7280;
        }
        .info-value {
            color: #1f2937;
        }
        .reason-box {
            background-color: #fef3c7;
            border-left: 4px solid #f59e0b;
            padding: 15px;
            margin: 15px 0;
            border-radius: 3px;
        }
        .footer {
            text-align: center;
            margin-top: 30px;
            padding-top: 20px;
            border-top: 1px solid #e5e7eb;
            color: #6b7280;
            font-size: 12px;
        }
        .periods-list {
            margin-top: 10px;
        }
        .period-item {
            background-color: #f3f4f6;
            padding: 10px;
            margin: 5px 0;
            border-radius: 3px;
            border-left: 3px solid #3b82f6;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1 style="margin: 0;">Urlaub wurde eingetragen</h1>
    </div>
    
    <div class="content">
        <p>Hallo {{ $employeeName }},</p>
        
        <p>die Personalabteilung hat für Sie Urlaub eingetragen.</p>
        
        <div class="info-box">
            <h3 style="margin-top: 0;">Urlaubsdetails</h3>
            
            @if(count($allRequests) > 1)
                <div class="periods-list">
                    @foreach($allRequests as $index => $request)
                        <div class="period-item">
                            <strong>Zeitraum {{ $index + 1 }}:</strong><br>
                            Von: {{ $request->start_date->format('d.m.Y') }}<br>
                            Bis: {{ $request->end_date->format('d.m.Y') }}<br>
                            Tage: {{ $request->days }}<br>
                            Art: {{ match($request->day_type ?? 'full_day') {
                                'morning' => 'Vormittag',
                                'afternoon' => 'Nachmittag',
                                default => 'Ganztägig'
                            } }}
                        </div>
                    @endforeach
                </div>
            @else
                <div class="info-row">
                    <span class="info-label">Von:</span>
                    <span class="info-value">{{ $startDate }}</span>
                </div>
                <div class="info-row">
                    <span class="info-label">Bis:</span>
                    <span class="info-value">{{ $endDate }}</span>
                </div>
                <div class="info-row">
                    <span class="info-label">Urlaubstage:</span>
                    <span class="info-value">{{ $days }}</span>
                </div>
                <div class="info-row">
                    <span class="info-label">Art:</span>
                    <span class="info-value">{{ $dayType }}</span>
                </div>
            @endif
            
            <div class="info-row">
                <span class="info-label">Eingetragen von:</span>
                <span class="info-value">{{ $hrUserName }}</span>
            </div>
        </div>
        
        <div class="reason-box">
            <strong>Grund für den Eintrag:</strong><br>
            {{ $reason }}
        </div>
        
        @if($notes)
            <div class="info-box">
                <strong>Zusätzliche Anmerkungen:</strong><br>
                {{ $notes }}
            </div>
        @endif
        
        <p>Dieser Urlaub wurde direkt genehmigt und ist nun in Ihrem Urlaubskonto verbucht.</p>
        
        <p>Bei Fragen wenden Sie sich bitte an die Personalabteilung.</p>
        
        <p>Mit freundlichen Grüßen,<br>
        Ihre Personalabteilung</p>
    </div>
    
    <div class="footer">
        <p>Diese E-Mail wurde automatisch generiert. Bitte antworten Sie nicht auf diese E-Mail.</p>
    </div>
</body>
</html>
