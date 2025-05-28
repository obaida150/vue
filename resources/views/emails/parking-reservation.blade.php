<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Parkplatz-Reservierung</title>
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
            border-radius: 8px 8px 0 0;
        }
        .content {
            background-color: #f8fafc;
            padding: 30px;
            border: 1px solid #e2e8f0;
        }
        .footer {
            background-color: #64748b;
            color: white;
            padding: 15px;
            text-align: center;
            border-radius: 0 0 8px 8px;
            font-size: 14px;
        }
        .reservation-details {
            background-color: white;
            padding: 20px;
            border-radius: 8px;
            margin: 20px 0;
            border-left: 4px solid #3b82f6;
        }
        .status-badge {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: bold;
            text-transform: uppercase;
        }
        .status-created { background-color: #dcfce7; color: #16a34a; }
        .status-cancelled { background-color: #fee2e2; color: #dc2626; }
        .button {
            display: inline-block;
            background-color: #3b82f6;
            color: white;
            padding: 12px 24px;
            text-decoration: none;
            border-radius: 6px;
            margin: 10px 0;
        }
        .icon {
            font-size: 18px;
            margin-right: 8px;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>
            @if($type === 'created')
                🅿️ Parkplatz reserviert
            @elseif($type === 'cancelled')
                ❌ Reservierung storniert
            @endif
        </h1>
    </div>

    <div class="content">
        <p>Hallo {{ $user->name }},</p>

        @if($type === 'created')
            <p>Ihre Parkplatz-Reservierung wurde erfolgreich erstellt und ist sofort gültig!</p>
        @elseif($type === 'cancelled')
            <p>Ihre Parkplatz-Reservierung wurde storniert.</p>
        @endif

        <div class="reservation-details">
            <h3>📋 Reservierungsdetails</h3>
            
            <p><strong><span class="icon">🅿️</span>Parkplatz:</strong> {{ $reservation->parkingSpot->name ?? $reservation->parkingSpot->identifier }}</p>
            <p><strong><span class="icon">📍</span>Standort:</strong> {{ $reservation->parkingSpot->parkingLocation->name }}</p>
            <p><strong><span class="icon">📅</span>Datum:</strong> {{ \Carbon\Carbon::parse($reservation->reservation_date)->format('d.m.Y') }}</p>
            <p><strong><span class="icon">🕐</span>Zeit:</strong> {{ $reservation->start_time }} - {{ $reservation->end_time }}</p>
            
            @if($reservation->vehicle_info)
                <p><strong><span class="icon">🚗</span>Fahrzeug:</strong> {{ $reservation->vehicle_info }}</p>
            @endif
            
            <p><strong><span class="icon">📊</span>Status:</strong> 
                <span class="status-badge status-{{ $type }}">
                    @if($type === 'created')
                        Bestätigt
                    @elseif($type === 'cancelled')
                        Storniert
                    @endif
                </span>
            </p>

            @if($reservation->notes)
                <p><strong><span class="icon">📝</span>Notizen:</strong> {{ $reservation->notes }}</p>
            @endif
        </div>

        @if($type !== 'cancelled')
            <p style="text-align: center;">
                <a href="{{ url('/parking') }}" class="button">
                    Zur Parkplatzverwaltung
                </a>
            </p>
        @endif

        <hr style="margin: 30px 0; border: none; border-top: 1px solid #e2e8f0;">

        <p style="font-size: 14px; color: #64748b;">
            @if($type === 'created')
                Ihre Reservierung ist sofort gültig. Sie können zukünftige Reservierungen in der Parkplatzverwaltung einsehen oder stornieren.
            @elseif($type === 'cancelled')
                Sie können jederzeit eine neue Reservierung erstellen.
            @endif
        </p>
    </div>

    <div class="footer">
        <p>Diese E-Mail wurde automatisch generiert. Bitte antworten Sie nicht auf diese E-Mail.</p>
        <p>{{ config('app.name') }} - Parkplatzverwaltung</p>
    </div>
</body>
</html>
