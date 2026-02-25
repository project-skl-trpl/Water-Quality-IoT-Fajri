<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Advanced IoT Dashboard</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600;700&display=swap" rel="stylesheet">
    <script src="https://unpkg.com/lucide@latest"></script>
    <style>
        body { font-family: 'Inter', sans-serif; }
        .glass {
            background: rgba(255, 255, 255, 0.8);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.3);
        }
        .chart-container { position: relative; height: 400px; width: 100%; }
    </style>
</head>
<body class="bg-slate-50 text-slate-900 min-h-screen">

<div class="max-w-7xl mx-auto px-4 py-8">
    
    <div class="flex flex-col md:flex-row justify-between items-center mb-10 gap-4">
        <div>
            <h1 class="text-4xl font-extrabold tracking-tight text-slate-800">IoT <span class="text-blue-600">Live</span> Monitoring</h1>
            <p class="text-slate-500 mt-1">Real-time data synchronization with Laravel backend</p>
        </div>
        <div class="flex items-center gap-2 bg-white px-4 py-2 rounded-full shadow-sm border">
            <span class="relative flex h-3 w-3">
                <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-green-400 opacity-75"></span>
                <span class="relative inline-flex rounded-full h-3 w-3 bg-green-500"></span>
            </span>
            <span class="text-sm font-medium text-slate-600" id="status-text">System Online</span>
        </div>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
        <div class="glass p-6 rounded-3xl shadow-xl transition-all hover:scale-[1.02]">
            <div class="flex justify-between items-start">
                <div>
                    <p class="text-sm font-semibold text-blue-600 uppercase tracking-wider">Temperature</p>
                    <h3 class="text-5xl font-bold mt-2" id="temp-val">--</h3>
                </div>
                <div class="bg-blue-100 p-3 rounded-2xl text-blue-600">
                    <i data-lucide="thermometer"></i>
                </div>
            </div>
            <p class="text-slate-400 text-xs mt-4">Unit: Celsius (°C)</p>
        </div>

        <div class="glass p-6 rounded-3xl shadow-xl transition-all hover:scale-[1.02]">
            <div class="flex justify-between items-start">
                <div>
                    <p class="text-sm font-semibold text-emerald-600 uppercase tracking-wider">Humidity</p>
                    <h3 class="text-5xl font-bold mt-2" id="hum-val">--</h3>
                </div>
                <div class="bg-emerald-100 p-3 rounded-2xl text-emerald-600">
                    <i data-lucide="droplets"></i>
                </div>
            </div>
            <p class="text-slate-400 text-xs mt-4">Unit: Percentage (%)</p>
        </div>

        <div class="glass p-6 rounded-3xl shadow-xl transition-all hover:scale-[1.02]">
            <div class="flex justify-between items-start">
                <div>
                    <p class="text-sm font-semibold text-amber-600 uppercase tracking-wider">Last Sync</p>
                    <h3 class="text-3xl font-bold mt-4" id="time-val">--:--:--</h3>
                </div>
                <div class="bg-amber-100 p-3 rounded-2xl text-amber-600">
                    <i data-lucide="refresh-cw"></i>
                </div>
            </div>
            <p class="text-slate-400 text-xs mt-6">Updated every 1s</p>
        </div>
    </div>

    <div class="glass p-8 rounded-[2rem] shadow-2xl mb-8">
        <div class="flex items-center justify-between mb-6">
            <h2 class="text-xl font-bold flex items-center gap-2">
                <i data-lucide="activity" class="text-blue-500"></i> Data Stream
            </h2>
        </div>
        <div class="chart-container">
            <canvas id="sensorChart"></canvas>
        </div>
    </div>

    <div class="bg-white rounded-[2rem] shadow-xl overflow-hidden border border-slate-100">
        <div class="p-6 border-b border-slate-50 flex justify-between items-center">
            <h2 class="text-xl font-bold">Historical Logs</h2>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-left">
                <thead>
                    <tr class="bg-slate-50 text-slate-500 text-sm uppercase">
                        <th class="px-8 py-4 font-semibold">Sequence</th>
                        <th class="px-8 py-4 font-semibold">Temperature</th>
                        <th class="px-8 py-4 font-semibold">Humidity</th>
                        <th class="px-8 py-4 font-semibold">Status</th>
                    </tr>
                </thead>
                <tbody id="logsTable" class="divide-y divide-slate-100">
                    </tbody>
            </table>
        </div>
        <div id="pagination" class="p-4 bg-slate-50 flex justify-center gap-2"></div>
    </div>
</div>

<script>
let chart;
let currentPage = 1;

/* Initialize Icons */
lucide.createIcons();

/* =========================
   UI UPDATER
========================= */
function updateUI(data) {
    const temp = data.current?.temperature ?? 0;
    const hum = data.current?.humidity ?? 0;
    
    document.getElementById('temp-val').innerText = `${temp}°`;
    document.getElementById('hum-val').innerText = `${hum}%`;
    document.getElementById('time-val').innerText = new Date().toLocaleTimeString('id-ID');
}

/* =========================
   CHART SETUP (Smooth)
========================= */
function initChart(labels, tempData, humData) {
    const ctx = document.getElementById('sensorChart').getContext('2d');
    
    const gradientTemp = ctx.createLinearGradient(0, 0, 0, 400);
    gradientTemp.addColorStop(0, 'rgba(59, 130, 246, 0.5)');
    gradientTemp.addColorStop(1, 'rgba(59, 130, 246, 0)');

    chart = new Chart(ctx, {
        type: 'line',
        data: {
            labels: labels,
            datasets: [
                {
                    label: 'Temperature',
                    data: tempData,
                    borderColor: '#3b82f6',
                    backgroundColor: gradientTemp,
                    fill: true,
                    tension: 0.4,
                    pointRadius: 4,
                    pointBackgroundColor: '#fff',
                    pointBorderWidth: 2
                },
                {
                    label: 'Humidity',
                    data: humData,
                    borderColor: '#10b981',
                    fill: false,
                    tension: 0.4,
                    pointRadius: 0
                }
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            interaction: { intersect: false, mode: 'index' },
            animation: {
                duration: 1000,
                easing: 'easeInOutQuart'
            },
            scales: {
                y: { 
                    grid: { color: '#f1f5f9' },
                    ticks: { font: { family: 'Inter' } }
                },
                x: { grid: { display: false } }
            },
            plugins: {
                legend: { position: 'top', align: 'end' }
            }
        }
    });
}

/* =========================
   CORE FUNCTIONS
========================= */
async function fetchData() {
    try {
        // Simulasi send & fetch
        await fetch('/monitoring/send');
        const response = await fetch('/monitoring/data');
        const data = await response.json();

        updateUI(data);

        if (!chart) {
            initChart(data.labels, data.temperature, data.humidity);
        } else if (data.labels.length > 0) {
            const lastIdx = data.labels.length - 1;
            
            chart.data.labels.push(data.labels[lastIdx]);
            chart.data.datasets[0].data.push(data.temperature[lastIdx]);
            chart.data.datasets[1].data.push(data.humidity[lastIdx]);

            if (chart.data.labels.length > 12) {
                chart.data.labels.shift();
                chart.data.datasets.forEach(ds => ds.data.shift());
            }
            
            // 'none' animation untuk update posisi, tapi tension tetap smooth
            chart.update(); 
        }
    } catch (e) {
        console.error("Data fetch error", e);
    }
}

async function loadLogs(page = currentPage) {
    currentPage = page;
    const response = await fetch(`/monitoring/logs?page=${page}`);
    const result = await response.json();

    let html = '';
    result.data.forEach((log, i) => {
        html += `
            <tr class="hover:bg-blue-50/50 transition-colors">
                <td class="px-8 py-4 font-medium text-slate-400">#${log.id || i+1}</td>
                <td class="px-8 py-4 font-bold text-blue-600">${log.temperature}°C</td>
                <td class="px-8 py-4 font-bold text-emerald-600">${log.humidity}%</td>
                <td class="px-8 py-4">
                    <span class="px-3 py-1 bg-green-100 text-green-700 rounded-full text-xs font-bold">SUCCESS</span>
                </td>
            </tr>
        `;
    });
    document.getElementById('logsTable').innerHTML = html;

    // Minimalist Pagination
    let totalPages = Math.ceil(result.total / result.perPage);
    let pgHtml = '';
    for (let i = 1; i <= Math.min(totalPages, 5); i++) {
        pgHtml += `
            <button onclick="loadLogs(${i})" 
                class="w-10 h-10 rounded-xl border transition-all ${i === currentPage ? 'bg-blue-600 text-white shadow-lg shadow-blue-200' : 'bg-white hover:bg-slate-100'}">
                ${i}
            </button>
        `;
    }
    document.getElementById('pagination').innerHTML = pgHtml;
}

/* =========================
   RUN SYSTEM
========================= */
async function start() {
    await fetchData();
    await loadLogs();
    
    setInterval(async () => {
        await fetchData();
        if (currentPage === 1) loadLogs(1);
    }, 2000);
}

start();
</script>
</body>
</html>