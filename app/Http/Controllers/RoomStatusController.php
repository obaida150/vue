<?php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class RoomStatusController extends Controller
{
    public function api(Request $request)
    {
        $token14 = (string) env('HA_TOKEN_14', '');
        $token23 = (string) env('HA_TOKEN_23', '');

        if ($token14 === '' || $token23 === '') {
            return response()->json([
                'results14' => array_fill(0, 6, ['state' => 'Token fehlt', 'color' => '#7f8c8d']),
                'results23' => array_fill(0, 6, ['state' => 'Token fehlt', 'color' => '#7f8c8d']),
            ])->header('Cache-Control', 'no-cache, no-store, must-revalidate, private')
                ->header('Pragma', 'no-cache')
                ->header('Expires', '0');
        }

        $sensors14 = [
            'http://10.11.0.56:8123/api/states/binary_sensor.0x54ef441000c402c0_occupancy',
            'http://10.11.0.56:8123/api/states/binary_sensor.0x54ef44100132c13c_occupancy',
            'http://10.11.0.56:8123/api/states/binary_sensor.0x54ef44100132c59e_occupancy',
            'http://10.11.0.56:8123/api/states/binary_sensor.0x54ef44100132b9df_occupancy',
            'http://10.11.0.56:8123/api/states/binary_sensor.0x54ef44100132b7f3_occupancy',
            'http://10.11.0.56:8123/api/states/binary_sensor.0x54ef44100132cd34_occupancy',
        ];
        $sensors23 = [
            'http://10.10.11.24:8123/api/states/binary_sensor.0x54ef44100132c46e_occupancy',
            'http://10.10.11.24:8123/api/states/binary_sensor.0x54ef44100132c8ba_occupancy',
            'http://10.10.11.24:8123/api/states/binary_sensor.0x54ef44100132bb78_occupancy',
            'http://10.10.11.24:8123/api/states/binary_sensor.0x54ef44100132bb56_occupancy',
            'http://10.10.11.24:8123/api/states/binary_sensor.0x54ef44100132c332_occupancy',
            'http://10.10.11.24:8123/api/states/binary_sensor.0x54ef44100132d022_occupancy',
        ];

        $results14 = $this->fetchParallel($sensors14, $token14, 'KS14');
        $results23 = $this->fetchParallel($sensors23, $token23, 'KS23');

        return response()->json(compact('results14', 'results23'))
            ->header('Cache-Control', 'no-cache, no-store, must-revalidate, private')
            ->header('Pragma', 'no-cache')
            ->header('Expires', '0')
            ->header('X-Accel-Buffering', 'no');
    }
    public function __invoke(Request $request)
    {
        $token14 = (string) env('HA_TOKEN_14', '');
        $token23 = (string) env('HA_TOKEN_23', '');

        if ($token14 === '' || $token23 === '') {
            return response()->view('room-status', [
                'results14' => array_fill(0, 6, ['state' => 'Token fehlt', 'color' => '#7f8c8d']),
                'results23' => array_fill(0, 6, ['state' => 'Token fehlt', 'color' => '#7f8c8d']),
            ])->header('Cache-Control', 'no-store');
        }

        $sensors14 = [
            'http://10.11.0.56:8123/api/states/binary_sensor.0x54ef441000c402c0_occupancy',
            'http://10.11.0.56:8123/api/states/binary_sensor.0x54ef44100132c13c_occupancy',
            'http://10.11.0.56:8123/api/states/binary_sensor.0x54ef44100132c59e_occupancy',
            'http://10.11.0.56:8123/api/states/binary_sensor.0x54ef44100132b9df_occupancy',
            'http://10.11.0.56:8123/api/states/binary_sensor.0x54ef44100132b7f3_occupancy',
            'http://10.11.0.56:8123/api/states/binary_sensor.0x54ef44100132cd34_occupancy',
        ];
        $sensors23 = [
            'http://10.10.11.24:8123/api/states/binary_sensor.0x54ef44100132c46e_occupancy',
            'http://10.10.11.24:8123/api/states/binary_sensor.0x54ef44100132c8ba_occupancy',
            'http://10.10.11.24:8123/api/states/binary_sensor.0x54ef44100132bb78_occupancy',
            'http://10.10.11.24:8123/api/states/binary_sensor.0x54ef44100132bb56_occupancy',
            'http://10.10.11.24:8123/api/states/binary_sensor.0x54ef44100132c332_occupancy',
            'http://10.10.11.24:8123/api/states/binary_sensor.0x54ef44100132d022_occupancy',
        ];

        $results14 = $this->fetchParallel($sensors14, $token14, 'KS14');
        $results23 = $this->fetchParallel($sensors23, $token23, 'KS23');

        return response()
            ->view('room-status', compact('results14', 'results23'))
            ->header('Cache-Control', 'no-store');
    }

    private function fetchParallel(array $urls, string $token, string $tag): array
    {
        $results = array_fill(0, count($urls), ['state' => 'Lädt...', 'color' => '#7f8c8d']);
        $promises = [];

        foreach ($urls as $i => $url) {
            $promises[$i] = Http::timeout(2)
                ->connectTimeout(1)
                ->withToken($token)
                ->acceptJson()
                ->async()
                ->get($url);
        }

        foreach ($promises as $i => $promise) {
            try {
                $resp = $promise->wait();

                // Prüfe ob $resp wirklich eine Response ist
                if (!($resp instanceof \Illuminate\Http\Client\Response)) {
                    $results[$i] = ['state' => 'Fehler', 'color' => '#7f8c8d'];
                    Log::error("[$tag] Keine Response", ['url' => $urls[$i], 'type' => get_class($resp)]);
                    continue;
                }

                if ($resp->successful()) {
                    $isOn = ($resp->json('state') === 'on');
                    $results[$i] = [
                        'state' => $isOn ? 'Belegt' : 'Frei',
                        'color' => $isOn ? '#c0392b' : '#27ae60',
                    ];
                } else {
                    $status = $resp->status();
                    if (in_array($status, [401, 403])) {
                        $results[$i] = ['state' => 'Token ungültig', 'color' => '#7f8c8d'];
                    } else {
                        $results[$i] = ['state' => 'HTTP '.$status, 'color' => '#7f8c8d'];
                    }
                    Log::warning("[$tag] HTTP $status", ['url' => $urls[$i]]);
                }

            } catch (\Illuminate\Http\Client\ConnectionException $e) {
                $results[$i] = ['state' => 'Timeout', 'color' => '#7f8c8d'];
                Log::error("[$tag] ConnectionException", [
                    'url' => $urls[$i],
                    'message' => $e->getMessage(),
                ]);
            } catch (\Throwable $e) {
                $results[$i] = ['state' => 'Fehler', 'color' => '#7f8c8d'];
                Log::error("[$tag] Throwable", [
                    'url' => $urls[$i],
                    'type' => get_class($e),
                    'message' => $e->getMessage(),
                ]);
            }
        }

        return $results;
    }
}
