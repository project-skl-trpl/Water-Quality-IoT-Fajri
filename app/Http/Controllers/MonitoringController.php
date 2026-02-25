<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Carbon\Carbon;

class MonitoringController extends Controller
{
    private $baseUrl;

    public function __construct()
    {
        $this->baseUrl = "https://drone-water-1a638-default-rtdb.asia-southeast1.firebasedatabase.app";
        // $this->baseUrl = "https://drone-under-water-default-rtdb.asia-southeast1.firebasedatabase.app";
        // $this->baseUrl = "https://monitoring-help-trpl-c-default-rtdb.asia-southeast1.firebasedatabase.app";
    }

    /**
     * ==============================
     * 1️⃣ KIRIM DATA SENSOR (SIMULASI)
     * ==============================
     */
    public function sendSensor()
    {
        $data = [
            "humidity" => rand(70, 90),
            "temperature" => rand(25, 35),
            "timestamp" => time()
        ];

        // Update current
        Http::put("{$this->baseUrl}/sensors/device_01/current.json", $data);

        // Simpan logs
        Http::post("{$this->baseUrl}/sensors/device_01/logs.json", $data);

        return response()->json([
            'message' => 'Data sensor berhasil dikirim',
            'data' => $data
        ]);
    }

    /**
     * ==============================
     * 2️⃣ HALAMAN MONITORING
     * ==============================
     */
    public function index()
    {
        return view('monitoring.index');
    }

    /**
     * ==============================
     * 3️⃣ API DATA CURRENT + CHART
     * ==============================
     */
    public function getData()
    {
        $current = Http::get("{$this->baseUrl}/sensors/device_01/current.json")->json();
        $logs = Http::get("{$this->baseUrl}/sensors/device_01/logs.json")->json();

        $labels = [];
        $temperature = [];
        $humidity = [];

        if ($logs && is_array($logs)) {

            // Firebase object → array
            $logs = array_values($logs);

            // Urutkan terbaru
            $logs = array_reverse($logs);

            // Ambil 10 terakhir
            $logs = array_slice($logs, 0, 10);

            foreach ($logs as $log) {

                $labels[] = isset($log['timestamp'])
                    ? Carbon::createFromTimestamp($log['timestamp'])
                        ->timezone('Asia/Jakarta')
                        ->format('H:i:s')
                    : '-';

                $temperature[] = $log['temperature'] ?? 0;
                $humidity[] = $log['humidity'] ?? 0;
            }
        }

        return response()->json([
            'current' => $current,
            'labels' => $labels,
            'temperature' => $temperature,
            'humidity' => $humidity,
        ]);
    }

    /**
     * ==============================
     * 4️⃣ API LOGS PAGINATION
     * ==============================
     */
    public function getLogs(Request $request)
    {
        $logs = Http::get("{$this->baseUrl}/sensors/device_01/logs.json")->json();

        if (!$logs || !is_array($logs)) {
            return response()->json([
                'data' => [],
                'total' => 0,
                'page' => 1,
                'perPage' => 5
            ]);
        }

        // Object → array
        $logs = array_values($logs);

        // Urutkan terbaru
        $logs = array_reverse($logs);

        $page = $request->get('page', 1);
        $perPage = 5;

        $offset = ($page - 1) * $perPage;
        $paginated = array_slice($logs, $offset, $perPage);

        return response()->json([
            'data' => $paginated,
            'total' => count($logs),
            'page' => (int) $page,
            'perPage' => $perPage
        ]);
    }
}