<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class OutlookController extends Controller
{
    /**
     * Konvertiert eine IANA-Zeitzonen-ID in eine Windows-Zeitzonen-ID.
     * Dies ist notwendig, da EWS Windows-Zeitzonen-IDs erwartet.
     *
     * @param string $ianaTimeZoneId Die IANA-Zeitzonen-ID (z.B. 'Europe/Berlin').
     * @return string Die entsprechende Windows-Zeitzonen-ID.
     */
    private function convertIanaToWindowsTimeZone(string $ianaTimeZoneId): string
    {
        // Eine einfache Zuordnung für die häufigsten Fälle.
        switch ($ianaTimeZoneId) {
            case 'Europe/Berlin':
            case 'Europe/Vienna':
            case 'Europe/Zurich':
            case 'Europe/Rome':
            case 'Europe/Paris':
                return 'Central Europe Standard Time';
            case 'America/New_York':
                return 'Eastern Standard Time';
            case 'America/Los_Angeles':
                return 'Pacific Standard Time';
            case 'Asia/Tokyo':
                return 'Tokyo Standard Time';
            case 'Europe/London':
                return 'GMT Standard Time';
            default:
                Log::warning("Unknown IANA timezone ID: $ianaTimeZoneId. Falling back to direct ID or 'UTC'.");
                return $ianaTimeZoneId;
        }
    }

    /**
     * Sendet eine EWS SOAP-Anfrage über cURL.
     *
     * @param string $xmlPayload Die vollständige SOAP-XML-Payload.
     * @param string $impersonateEmail Die E-Mail-Adresse des zu imitierenden Benutzers.
     * @return array Ein Array mit 'success' (bool) und 'response' (string) oder 'error' (string).
     */
    private function sendEwsRequest(string $xmlPayload, string $impersonateEmail): array
    {
        $config = config('services.ews');

        $ewsUrl = $config['host'];
        $username = $config['username'];
        $password = $config['password'];

        if (!$ewsUrl || !$username || !$password) {
            return ["success" => false, "error" => "EWS configuration missing (host, username, or password)."];
        }

        Log::debug('Sending EWS request to: ' . $ewsUrl);
        Log::debug('Impersonating user: ' . $impersonateEmail);
        Log::debug('EWS XML Payload: ' . $xmlPayload);

        $ch = curl_init($ewsUrl);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
        curl_setopt($ch, CURLOPT_USERPWD, "{$username}:{$password}");
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $xmlPayload);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            "Content-Type: text/xml; charset=utf-8",
            "Accept: text/xml"
        ]);
        curl_setopt($ch, CURLOPT_VERBOSE, true);

        $verifySSL = env('EWS_VERIFY_SSL', true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST,  0);

        if (!$verifySSL) {
            Log::warning('EWS SSL verification is disabled. This should only be used in development!');
        }

        $response = curl_exec($ch);

        if (curl_errno($ch)) {
            $error = curl_error($ch);
            curl_close($ch);
            Log::error('cURL Error during EWS request: ' . $error);
            return ["success" => false, "error" => "cURL error: " . $error];
        }

        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode >= 400) {
            Log::error("EWS HTTP Error ($httpCode): " . $response);
            return ["success" => false, "error" => "EWS HTTP Error ($httpCode): " . $response];
        }

        Log::debug('EWS Response: ' . $response);
        return ["success" => true, "response" => $response];
    }

    /**
     * Erstellt ein Ereignis im persönlichen Outlook-Kalender des Benutzers via EWS.
     *
     * @param \App\Models\Event $event Das lokale Ereignis.
     * @param \App\Models\User $user Der Benutzer.
     * @return string|null Die EWS-Event-ID bei Erfolg, sonst null.
     */
    public function createEwsEvent(\App\Models\Event $event, \App\Models\User $user): ?string
    {
        $targetUser = $user->email;
        $subject = htmlspecialchars($event->title);
        $body = htmlspecialchars($event->description ?? '');
        $location = '';

        $isAllDayEventXml = $event->is_all_day ? 'true' : 'false';
        $start = '';
        $end = '';
        $timeZoneXml = '';

        if ($event->is_all_day) {
            $start = Carbon::parse($event->start_date)->startOfDay()->format('Y-m-d\TH:i:s');
            $end = Carbon::parse($event->end_date)->addDay()->startOfDay()->format('Y-m-d\TH:i:s');
        } else {
            $startDateTime = Carbon::parse($event->start_date);
            $endDateTime = Carbon::parse($event->end_date);

            if ($event->start_time) {
                $timeParts = explode(':', $event->start_time);
                $startDateTime->setTime((int)$timeParts[0], (int)$timeParts[1], 0);
            }

            if ($event->end_time) {
                $timeParts = explode(':', $event->end_time);
                $endDateTime->setTime((int)$timeParts[0], (int)$timeParts[1], 0);
            }

            $start = $startDateTime->toIso8601String();
            $end = $endDateTime->toIso8601String();

            $appTimeZone = config('app.timezone');
            $windowsTimeZoneId = $this->convertIanaToWindowsTimeZone($appTimeZone);
            $timeZoneXml = <<<XML
<t:StartTimeZone Id="$windowsTimeZoneId" />
<t:EndTimeZone Id="$windowsTimeZoneId" />
XML;

            Log::info('Creating EWS event with times', [
                'start_date' => $event->start_date,
                'start_time' => $event->start_time,
                'end_date' => $event->end_date,
                'end_time' => $event->end_time,
                'start_iso' => $start,
                'end_iso' => $end,
                'timezone' => $windowsTimeZoneId,
                'is_all_day' => false
            ]);
        }

        $xmlPayload = <<<XML
<?xml version="1.0" encoding="utf-8"?>
<soap:Envelope xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
xmlns:m="http://schemas.microsoft.com/exchange/services/2006/messages"
xmlns:t="http://schemas.microsoft.com/exchange/services/2006/types"
xmlns:soap="http://schemas.xmlsoap.org/soap/envelope/">
<soap:Header>
<t:RequestServerVersion Version="Exchange2016" />
<t:ExchangeImpersonation>
<t:ConnectingSID>
<t:PrimarySmtpAddress>$targetUser</t:PrimarySmtpAddress>
</t:ConnectingSID>
</t:ExchangeImpersonation>
</soap:Header>
<soap:Body>
<m:CreateItem SendMeetingInvitations="SendToNone">
<m:SavedItemFolderId>
<t:DistinguishedFolderId Id="calendar"/>
</m:SavedItemFolderId>
<m:Items>
<t:CalendarItem>
<t:Subject>$subject</t:Subject>
<t:Body BodyType="Text">$body</t:Body>
<t:Start>$start</t:Start>
<t:End>$end</t:End>
<t:Location>$location</t:Location>
<t:IsAllDayEvent>$isAllDayEventXml</t:IsAllDayEvent>
$timeZoneXml
</t:CalendarItem>
</m:Items>
</m:CreateItem>
</soap:Body>
</soap:Envelope>
XML;

        $response = $this->sendEwsRequest($xmlPayload, $targetUser);

        if ($response['success']) {
            try {
                $xml = simplexml_load_string($response['response']);
                $xml->registerXPathNamespace('m', 'http://schemas.microsoft.com/exchange/services/2006/messages');
                $xml->registerXPathNamespace('t', 'http://schemas.microsoft.com/exchange/services/2006/types');

                $responseClassNodes = $xml->xpath('//m:CreateItemResponseMessage/@ResponseClass');
                $responseClass = count($responseClassNodes) > 0 ? (string)$responseClassNodes[0] : null;
                if ($responseClass === 'Success') {
                    $itemIdNodes = $xml->xpath('//t:ItemId/@Id');
                    $itemId = count($itemIdNodes) > 0 ? (string)$itemIdNodes[0] : null;
                    $changeKeyNodes = $xml->xpath('//t:ItemId/@ChangeKey');
                    $changeKey = count($changeKeyNodes) > 0 ? (string)$changeKeyNodes[0] : null;

                    if ($itemId) {
                        $event->outlook_event_id = $itemId;
                        $event->outlook_change_key = $changeKey;
                        $event->save();
                        Log::info('EWS event created for user ' . $user->id . ': ' . $itemId . ' with ChangeKey: ' . $changeKey);
                        return $itemId;
                    }
                } else {
                    $messageTextNodes = $xml->xpath('//m:MessageText');
                    $messageText = count($messageTextNodes) > 0 ? (string)$messageTextNodes[0] : 'Unknown EWS error';
                    Log::error('EWS CreateItem failed for user ' . $user->id . ': ' . $messageText);
                }
            } catch (\Exception $e) {
                Log::error('Error parsing EWS CreateItem response for user ' . $user->id . ': ' . $e->getMessage());
            }
        } else {
            Log::error('EWS CreateItem request failed for user ' . $user->id . ': ' . $response['error']);
        }
        return null;
    }

    /**
     * Aktualisiert ein Ereignis im persönlichen Outlook-Kalender des Benutzers via EWS.
     *
     * @param \App\Models\Event $event Das lokale Ereignis.
     * @param \App\Models\User $user Der Benutzer.
     * @return bool True bei Erfolg, sonst false.
     */
    public function updateEwsEvent(\App\Models\Event $event, \App\Models\User $user): bool
    {
        if (!$event->outlook_event_id || !$event->outlook_change_key) {
            Log::warning('No EWS event ID or ChangeKey found for local event ' . $event->id . '. Update skipped.');
            return false;
        }

        $outlookItemId = $event->outlook_event_id;
        $outlookChangeKey = $event->outlook_change_key;

        $targetUser = $user->email;
        $subject = htmlspecialchars($event->title);
        $body = htmlspecialchars($event->description ?? '');
        $location = '';

        $isAllDayEventXml = $event->is_all_day ? 'true' : 'false';
        $start = '';
        $end = '';
        $timeZoneUpdateXml = '';

        if ($event->is_all_day) {
            $start = Carbon::parse($event->start_date)->startOfDay()->format('Y-m-d\TH:i:s');
            $end = Carbon::parse($event->end_date)->addDay()->startOfDay()->format('Y-m-d\TH:i:s');
            $timeZoneUpdateXml = <<<XML
<t:DeleteItemField>
    <t:FieldURI FieldURI="calendar:StartTimeZone" />
</t:DeleteItemField>
<t:DeleteItemField>
    <t:FieldURI FieldURI="calendar:EndTimeZone" />
</t:DeleteItemField>
XML;
        } else {
            $startDateTime = Carbon::parse($event->start_date);
            $endDateTime = Carbon::parse($event->end_date);

            if ($event->start_time) {
                $timeParts = explode(':', $event->start_time);
                $startDateTime->setTime((int)$timeParts[0], (int)$timeParts[1], 0);
            }

            if ($event->end_time) {
                $timeParts = explode(':', $event->end_time);
                $endDateTime->setTime((int)$timeParts[0], (int)$timeParts[1], 0);
            }

            $start = $startDateTime->toIso8601String();
            $end = $endDateTime->toIso8601String();

            $appTimeZone = config('app.timezone');
            $windowsTimeZoneId = $this->convertIanaToWindowsTimeZone($appTimeZone);
            $timeZoneUpdateXml = <<<XML
<t:SetItemField>
    <t:FieldURI FieldURI="calendar:StartTimeZone" />
    <t:CalendarItem>
        <t:StartTimeZone Id="$windowsTimeZoneId" />
    </t:CalendarItem>
</t:SetItemField>
<t:SetItemField>
    <t:FieldURI FieldURI="calendar:EndTimeZone" />
    <t:CalendarItem>
        <t:EndTimeZone Id="$windowsTimeZoneId" />
    </t:CalendarItem>
</t:SetItemField>
XML;

            Log::info('Updating EWS event with times', [
                'start_date' => $event->start_date,
                'start_time' => $event->start_time,
                'end_date' => $event->end_date,
                'end_time' => $event->end_time,
                'start_iso' => $start,
                'end_iso' => $end,
                'timezone' => $windowsTimeZoneId,
                'is_all_day' => false
            ]);
        }

        $xmlPayload = <<<XML
<?xml version="1.0" encoding="utf-8"?>
<soap:Envelope xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
xmlns:m="http://schemas.microsoft.com/exchange/services/2006/messages"
xmlns:t="http://schemas.microsoft.com/exchange/services/2006/types"
xmlns:soap="http://schemas.xmlsoap.org/soap/envelope/">
<soap:Header>
<t:RequestServerVersion Version="Exchange2016" />
<t:ExchangeImpersonation>
<t:ConnectingSID>
<t:PrimarySmtpAddress>$targetUser</t:PrimarySmtpAddress>
</t:ConnectingSID>
</t:ExchangeImpersonation>
</soap:Header>
<soap:Body>
<m:UpdateItem ConflictResolution="AutoResolve" MessageDisposition="SendAndSaveCopy" SendMeetingInvitationsOrCancellations="SendToNone">
<m:ItemChanges>
<t:ItemChange>
<t:ItemId Id="$outlookItemId" ChangeKey="$outlookChangeKey" />
<t:Updates>
<t:SetItemField>
<t:FieldURI FieldURI="item:Subject" />
<t:CalendarItem>
<t:Subject>$subject</t:Subject>
</t:CalendarItem>
</t:SetItemField>
<t:SetItemField>
<t:FieldURI FieldURI="item:Body" />
<t:CalendarItem>
<t:Body BodyType="Text">$body</t:Body>
</t:CalendarItem>
</t:SetItemField>
<t:SetItemField>
<t:FieldURI FieldURI="calendar:Start" />
<t:CalendarItem>
<t:Start>$start</t:Start>
</t:CalendarItem>
</t:SetItemField>
<t:SetItemField>
<t:FieldURI FieldURI="calendar:End" />
<t:CalendarItem>
<t:End>$end</t:End>
</t:CalendarItem>
</t:SetItemField>
<t:SetItemField>
<t:FieldURI FieldURI="calendar:Location" />
<t:CalendarItem>
<t:Location>$location</t:Location>
</t:CalendarItem>
</t:SetItemField>
<t:SetItemField>
<t:FieldURI FieldURI="calendar:IsAllDayEvent" />
<t:CalendarItem>
<t:IsAllDayEvent>$isAllDayEventXml</t:IsAllDayEvent>
</t:CalendarItem>
</t:SetItemField>
$timeZoneUpdateXml
</t:Updates>
</t:ItemChange>
</m:ItemChanges>
</m:UpdateItem>
</soap:Body>
</soap:Envelope>
XML;

        $response = $this->sendEwsRequest($xmlPayload, $targetUser);

        if ($response['success']) {
            try {
                $xml = simplexml_load_string($response['response']);
                $xml->registerXPathNamespace('m', 'http://schemas.microsoft.com/exchange/services/2006/messages');
                $xml->registerXPathNamespace('t', 'http://schemas.microsoft.com/exchange/services/2006/types');

                $responseClassNodes = $xml->xpath('//m:UpdateItemResponseMessage/@ResponseClass');
                $responseClass = count($responseClassNodes) > 0 ? (string)$responseClassNodes[0] : null;

                if ($responseClass === 'Success') {
                    $updatedChangeKeyNodes = $xml->xpath('//t:ItemId/@ChangeKey');
                    $updatedChangeKey = count($updatedChangeKeyNodes) > 0 ? (string)$updatedChangeKeyNodes[0] : null;

                    if ($updatedChangeKey) {
                        $event->outlook_change_key = $updatedChangeKey;
                        $event->save();
                    }
                    Log::info('EWS event updated for user ' . $user->id . ': ' . $event->outlook_event_id . ' with new ChangeKey: ' . $updatedChangeKey);
                    return true;
                } else {
                    $responseCodeNodes = $xml->xpath('//m:ResponseCode');
                    $responseCode = count($responseCodeNodes) > 0 ? (string)$responseCodeNodes[0] : null;

                    if ($responseCode === 'ErrorIrresolvableConflict') {
                        Log::warning('ChangeKey conflict detected. Attempting to refresh ChangeKey and retry update.');

                        // Aktuellen ChangeKey aus Outlook holen
                        $currentChangeKey = $this->getEwsEventChangeKey($outlookItemId, $targetUser);

                        if ($currentChangeKey) {
                            // ChangeKey in der Datenbank aktualisieren
                            $event->outlook_change_key = $currentChangeKey;
                            $event->save();

                            Log::info('ChangeKey refreshed. Retrying update with new ChangeKey: ' . $currentChangeKey);

                            // Update erneut versuchen (rekursiver Aufruf, aber nur einmal)
                            return $this->updateEwsEvent($event, $user);
                        } else {
                            Log::error('Failed to retrieve current ChangeKey from Outlook.');
                        }
                    }

                    $messageTextNodes = $xml->xpath('//m:MessageText');
                    $messageText = count($messageTextNodes) > 0 ? (string)$messageTextNodes[0] : 'Unknown EWS error';
                    Log::error('EWS UpdateItem failed for user ' . $user->id . ': ' . $messageText);
                }
            } catch (\Exception $e) {
                Log::error('Error parsing EWS UpdateItem response for user ' . $user->id . ': ' . $e->getMessage());
            }
        } else {
            Log::error('EWS UpdateItem request failed for user ' . $user->id . ': ' . $response['error']);
        }
        return false;
    }

    /**
     * Löscht ein Ereignis aus dem persönlichen Outlook-Kalender des Benutzers via EWS.
     *
     * @param \App\Models\Event $event Das lokale Ereignis.
     * @param \App\Models\User $user Der Benutzer.
     * @return bool True bei Erfolg, sonst false.
     */
    public function deleteEwsEvent(\App\Models\Event $event, \App\Models\User $user): bool
    {
        if (!$event->outlook_event_id || !$event->outlook_change_key) {
            Log::warning('No EWS event ID or ChangeKey found for local event ' . $event->id . '. Delete skipped.');
            return false;
        }

        $outlookItemId = $event->outlook_event_id;
        $outlookChangeKey = $event->outlook_change_key;

        $targetUser = $user->email;

        $xmlPayload = <<<XML
<?xml version="1.0" encoding="utf-8"?>
<soap:Envelope xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
xmlns:m="http://schemas.microsoft.com/exchange/services/2006/messages"
xmlns:t="http://schemas.microsoft.com/exchange/services/2006/types"
xmlns:soap="http://schemas.xmlsoap.org/soap/envelope/">
<soap:Header>
<t:RequestServerVersion Version="Exchange2016" />
<t:ExchangeImpersonation>
<t:ConnectingSID>
<t:PrimarySmtpAddress>$targetUser</t:PrimarySmtpAddress>
</t:ConnectingSID>
</t:ExchangeImpersonation>
</soap:Header>
<soap:Body>
<m:DeleteItem DeleteType="SoftDelete" SendMeetingCancellations="SendToNone">
<m:ItemIds>
<t:ItemId Id="$outlookItemId" ChangeKey="$outlookChangeKey" />
</m:ItemIds>
</m:DeleteItem>
</soap:Body>
</soap:Envelope>
XML;

        $response = $this->sendEwsRequest($xmlPayload, $targetUser);

        if ($response['success']) {
            try {
                $xml = simplexml_load_string($response['response']);
                $xml->registerXPathNamespace('m', 'http://schemas.microsoft.com/exchange/services/2006/messages');
                $xml->registerXPathNamespace('t', 'http://schemas.microsoft.com/exchange/services/2006/types');

                $responseClassNodes = $xml->xpath('//m:DeleteItemResponseMessage/@ResponseClass');
                $responseClass = count($responseClassNodes) > 0 ? (string)$responseClassNodes[0] : null;
                if ($responseClass === 'Success') {
                    Log::info('EWS event deleted for user ' . $user->id . ': ' . $event->outlook_event_id);
                    return true;
                } else {
                    $messageTextNodes = $xml->xpath('//m:MessageText');
                    $messageText = count($messageTextNodes) > 0 ? (string)$messageTextNodes[0] : 'Unknown EWS error';
                    Log::error('EWS DeleteItem failed for user ' . $user->id . ': ' . $messageText);
                }
            } catch (\Exception $e) {
                Log::error('Error parsing EWS DeleteItem response for user ' . $user->id . ': ' . $e->getMessage());
            }
        } else {
            Log::error('EWS DeleteItem request failed for user ' . $user->id . ': ' . $response['error']);
        }
        return false;
    }

    /**
     * Holt den aktuellen ChangeKey eines Events aus Outlook via EWS.
     *
     * @param string $outlookItemId Die Outlook Event ID.
     * @param string $userEmail Die E-Mail-Adresse des Benutzers.
     * @return string|null Der aktuelle ChangeKey bei Erfolg, sonst null.
     */
    private function getEwsEventChangeKey(string $outlookItemId, string $userEmail): ?string
    {
        $xmlPayload = <<<XML
<?xml version="1.0" encoding="utf-8"?>
<soap:Envelope xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
xmlns:m="http://schemas.microsoft.com/exchange/services/2006/messages"
xmlns:t="http://schemas.microsoft.com/exchange/services/2006/types"
xmlns:soap="http://schemas.xmlsoap.org/soap/envelope/">
<soap:Header>
<t:RequestServerVersion Version="Exchange2016" />
<t:ExchangeImpersonation>
<t:ConnectingSID>
<t:PrimarySmtpAddress>$userEmail</t:PrimarySmtpAddress>
</t:ConnectingSID>
</t:ExchangeImpersonation>
</soap:Header>
<soap:Body>
<m:GetItem>
<m:ItemShape>
<t:BaseShape>IdOnly</t:BaseShape>
</m:ItemShape>
<m:ItemIds>
<t:ItemId Id="$outlookItemId" />
</m:ItemIds>
</m:GetItem>
</soap:Body>
</soap:Envelope>
XML;

        $response = $this->sendEwsRequest($xmlPayload, $userEmail);

        if ($response['success']) {
            try {
                $xml = simplexml_load_string($response['response']);
                $xml->registerXPathNamespace('m', 'http://schemas.microsoft.com/exchange/services/2006/messages');
                $xml->registerXPathNamespace('t', 'http://schemas.microsoft.com/exchange/services/2006/types');

                $responseClassNodes = $xml->xpath('//m:GetItemResponseMessage/@ResponseClass');
                $responseClass = count($responseClassNodes) > 0 ? (string)$responseClassNodes[0] : null;

                if ($responseClass === 'Success') {
                    $changeKeyNodes = $xml->xpath('//t:ItemId/@ChangeKey');
                    $changeKey = count($changeKeyNodes) > 0 ? (string)$changeKeyNodes[0] : null;

                    if ($changeKey) {
                        Log::info('Retrieved current ChangeKey from Outlook: ' . $changeKey);
                        return $changeKey;
                    }
                }
            } catch (\Exception $e) {
                Log::error('Error parsing EWS GetItem response: ' . $e->getMessage());
            }
        }

        return null;
    }
}
