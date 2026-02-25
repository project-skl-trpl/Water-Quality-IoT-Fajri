<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class MonitoringController2 extends Controller
{
    private $baseUrl = "https://monitoring-help-trpl-c-default-rtdb.asia-southeast1.firebasedatabase.app";

    public function index()
    {
        return view('monitoring.index2');
    }

    public function getData()
    {
        // Mengambil data langsung dari node device_01
        $response = Http::get("{$this->baseUrl}/sensors/device_01.json");
        $data = $response->json();

        $labels = [];
        $phData = [];
        $turbidityData = [];
        $tdsData = [];

        if (isset($data['logs']) && is_array($data['logs'])) {
            $logs = array_values($data['logs']);
            // Ambil 10 data terakhir untuk chart
            $latestLogs = array_slice($logs, -10);

            foreach ($latestLogs as $log) {
                $labels[] = isset($log['timestamp']) ? $log['timestamp'] . "s" : "-";
                $phData[] = $log['ph'] ?? 0;
                $turbidityData[] = $log['turbidity'] ?? 0;
                $tdsData[] = $log['tds'] ?? 0;
            }
        }

        return response()->json([
            'current' => $data['current'] ?? null,
            'labels' => $labels,
            'ph' => $phData,
            'turbidity' => $turbidityData,
            'tds' => $tdsData,
        ]);
    }

    public function getLogs(Request $request)
    {
        $response = Http::get("{$this->baseUrl}/sensors/device_01/logs.json");
        $logsRaw = $response->json();

        if (!$logsRaw) {
            return response()->json(['data' => [], 'total' => 0]);
        }

        // Konversi object Firebase ke array dan urutkan dari yang terbaru
        $logs = array_reverse(array_values($logsRaw));

        $page = $request->get('page', 1);
        $perPage = 5;
        $offset = ($page - 1) * $perPage;
        $paginated = array_slice($logs, $offset, $perPage);

        return response()->json([
            'data' => $paginated,
            'total' => count($logs),
            'page' => (int)$page,
            'perPage' => $perPage
        ]);
    }
}
