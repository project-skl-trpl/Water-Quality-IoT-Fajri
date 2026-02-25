<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Water Quality Monitoring</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="https://unpkg.com/lucide@latest"></script>
    <style>
        .glass { background: rgba(255, 255, 255, 0.9); backdrop-filter: blur(10px); border: 1px solid rgba(255, 255, 255, 0.3); }
    </style>
</head>
<body class="bg-slate-100 text-slate-900 min-h-screen">

<div class="max-w-7xl mx-auto px-4 py-8">
    
    <div class="mb-10">
        <h1 class="text-3xl font-bold text-slate-800">Water Quality <span class="text-blue-600">Dashboard</span></h1>
        <p class="text-slate-500">Monitoring pH, TDS, and Turbidity in Real-time</p>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
        <div class="glass p-6 rounded-3xl shadow-lg border-l-8 border-purple-500">
            <div class="flex justify-between items-center">
                <div>
                    <p class="text-xs font-bold text-slate-400 uppercase">pH Level</p>
                    <h3 class="text-4xl font-bold mt-1 text-purple-600" id="ph-val">--</h3>
                </div>
                <div class="bg-purple-100 p-3 rounded-2xl text-purple-600"><i data-lucide="droplet"></i></div>
            </div>
        </div>

        <div class="glass p-6 rounded-3xl shadow-lg border-l-8 border-blue-500">
            <div class="flex justify-between items-center">
                <div>
                    <p class="text-xs font-bold text-slate-400 uppercase">TDS (PPM)</p>
                    <h3 class="text-4xl font-bold mt-1 text-blue-600" id="tds-val">--</h3>
                </div>
                <div class="bg-blue-100 p-3 rounded-2xl text-blue-600"><i data-lucide="waves"></i></div>
            </div>
        </div>

        <div class="glass p-6 rounded-3xl shadow-lg border-l-8 border-amber-500">
            <div class="flex justify-between items-center">
                <div>
                    <p class="text-xs font-bold text-slate-400 uppercase">Turbidity</p>
                    <h3 class="text-4xl font-bold mt-1 text-amber-600" id="turb-val">--</h3>
                </div>
                <div class="bg-amber-100 p-3 rounded-2xl text-amber-600"><i data-lucide="eye"></i></div>
            </div>
        </div>
    </div>

    <div class="bg-white p-6 rounded-3xl shadow-xl mb-8">
        <h2 class="text-lg font-bold mb-4 flex items-center gap-2">
            <i data-lucide="activity" class="text-blue-500"></i> Sensor Graphics
        </h2>
        <div class="h-[350px]">
            <canvas id="waterChart"></canvas>
        </div>
    </div>

    <div class="bg-white rounded-3xl shadow-xl overflow-hidden border border-slate-200">
        <div class="p-6 border-b flex justify-between items-center">
            <h2 class="text-lg font-bold">Historical Logs</h2>
            <span id="sync-status" class="text-xs text-green-500 font-medium">● Connected</span>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-left">
                <thead class="bg-slate-50 text-slate-500 text-xs uppercase">
                    <tr>
                        <th class="px-6 py-4">Timestamp</th>
                        <th class="px-6 py-4">pH</th>
                        <th class="px-6 py-4">TDS</th>
                        <th class="px-6 py-4">Turbidity</th>
                    </tr>
                </thead>
                <tbody id="logsTable" class="divide-y divide-slate-100 text-sm"></tbody>
            </table>
        </div>
        <div id="pagination" class="p-4 bg-slate-50 flex justify-center gap-2"></div>
    </div>
</div>

<script>
let chart;
let currentPage = 1;
lucide.createIcons();

function initChart(labels, ph, turb, tds) {
    const ctx = document.getElementById('waterChart').getContext('2d');
    chart = new Chart(ctx, {
        type: 'line',
        data: {
            labels: labels,
            datasets: [
                { label: 'pH', data: ph, borderColor: '#a855f7', tension: 0.4, fill: false },
                { label: 'Turbidity', data: turb, borderColor: '#f59e0b', tension: 0.4, fill: false },
                { label: 'TDS', data: tds, borderColor: '#3b82f6', tension: 0.4, fill: false }
            ]
        },
        options: { responsive: true, maintainAspectRatio: false }
    });
}

async function refreshData() {
    try {
        const res = await fetch('/monitoring/data');
        const data = await res.json();

        // Update Cards
        if (data.current) {
            document.getElementById('ph-val').innerText = data.current.ph.toFixed(2);
            document.getElementById('tds-val').innerText = data.current.tds;
            document.getElementById('turb-val').innerText = data.current.turbidity.toFixed(2);
        }

        // Update Chart
        if (!chart) {
            initChart(data.labels, data.ph, data.turbidity, data.tds);
        } else {
            chart.data.labels = data.labels;
            chart.data.datasets[0].data = data.ph;
            chart.data.datasets[1].data = data.turbidity;
            chart.data.datasets[2].data = data.tds;
            chart.update('none');
        }
    } catch (e) { console.error(e); }
}

async function loadLogs(page = currentPage) {
    currentPage = page;
    const res = await fetch(`/monitoring/logs?page=${page}`);
    const result = await res.json();

    let html = '';
    result.data.forEach(log => {
        html += `
            <tr class="hover:bg-slate-50">
                <td class="px-6 py-4 text-slate-500">${log.timestamp}s</td>
                <td class="px-6 py-4 font-bold text-purple-600">${log.ph.toFixed(2)}</td>
                <td class="px-6 py-4 font-bold text-blue-600">${log.tds}</td>
                <td class="px-6 py-4 font-bold text-amber-600">${log.turbidity.toFixed(2)}</td>
            </tr>`;
    });
    document.getElementById('logsTable').innerHTML = html || '<tr><td colspan="4" class="text-center py-4">No data</td></tr>';

    // Basic Pagination
    let pgHtml = '';
    for (let i = 1; i <= Math.ceil(result.total / 5); i++) {
        if(i > 5) break; // Limit buttons
        pgHtml += `<button onclick="loadLogs(${i})" class="px-3 py-1 rounded-lg border ${i === currentPage ? 'bg-blue-600 text-white' : 'bg-white'}">${i}</button>`;
    }
    document.getElementById('pagination').innerHTML = pgHtml;
}

// Start auto refresh
refreshData();
loadLogs();
setInterval(refreshData, 3000);
setInterval(() => { if(currentPage === 1) loadLogs(1); }, 5000);
</script>
</body>
</html>