<?php
include 'connection.php';

if (isset($_POST['toggle_mode'])) {
    $current_mode = $_POST['current_mode'];
    $new_mode = ($current_mode == 'secure') ? 'register' : 'secure';
    mysqli_query($conn, "UPDATE settings SET secure_mode = '$new_mode' WHERE id = 1");
    header("Location: index.php");
    exit();
}

$settings_res = mysqli_query($conn, "SELECT secure_mode FROM settings WHERE id = 1");
$settings_row = mysqli_fetch_assoc($settings_res);
$secure_mode = $settings_row['secure_mode'];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SecureDoor - Monitoring Dashboard</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chartjs-plugin-datalabels@2.2.0"></script>
    <style>
        * { box-sizing: border-box; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; margin: 0; padding: 0; }
        body { display: flex; min-height: 100vh; transition: background-color 0.3s, color 0.3s; }
        
        :root {
            --bg-body: #f4f6f9;
            --bg-sidebar: #1e40af;
            --text-sidebar-h1: #ffffff;
            --text-sidebar-menu: #93c5fd;
            --bg-sidebar-hover: rgba(255,255,255,0.1);
            --text-sidebar-hover: #f8fafc;
            --bg-sidebar-active: #2563eb;
            --text-sidebar-active: white;
            --box-sidebar-active: rgba(37, 99, 235, 0.3);
            --bg-main: #f8fafc;
            --text-main-h2: #0f172a;
            --border-header: #e2e8f0;
            --bg-card: #ffffff;
            --border-card: rgba(226, 232, 240, 0.8);
            --text-card-h3: #0f172a;
            --text-card-p: #64748b;
            --bg-instruction-secure: #ecfdf5;
            --text-instruction-secure: #065f46;
            --border-instruction-secure: #10b981;
            --bg-instruction-register: #fffbeb;
            --text-instruction-register: #92400e;
            --border-instruction-register: #f59e0b;
            --bg-table-th: #f8fafc;
            --text-table-th: #475569;
            --border-table-th: #e2e8f0;
            --border-table-td: #f1f5f9;
            --text-table-td: #334155;
            --bg-table-row-hover: #f1f5f9;
            --input-bg: #ffffff;
            --input-border: #cbd5e1;
            --input-text: #0f172a;
        }

        [data-theme="dark"] {
            --bg-body: #0b0f19;
            --bg-sidebar: #111827;
            --text-sidebar-h1: #eab308;
            --text-sidebar-menu: #9ca3af;
            --bg-sidebar-hover: rgba(234, 179, 8, 0.1);
            --text-sidebar-hover: #eab308;
            --bg-sidebar-active: #eab308;
            --text-sidebar-active: #0b0f19;
            --box-sidebar-active: rgba(234, 179, 8, 0.3);
            --bg-main: #0b0f19;
            --text-main-h2: #ffffff;
            --border-header: #1f2937;
            --bg-card: #111827;
            --border-card: #1f2937;
            --text-card-h3: #ffffff;
            --text-card-p: #9ca3af;
            --bg-instruction-secure: rgba(16, 185, 129, 0.1);
            --text-instruction-secure: #a7f3d0;
            --border-instruction-secure: #10b981;
            --bg-instruction-register: rgba(234, 179, 8, 0.1);
            --text-instruction-register: #fde68a;
            --border-instruction-register: #eab308;
            --bg-table-th: #1f2937;
            --text-table-th: #9ca3af;
            --border-table-th: #374151;
            --border-table-td: #1f2937;
            --text-table-td: #e5e7eb;
            --bg-table-row-hover: #1f2937;
            --input-bg: #1f2937;
            --input-border: #374151;
            --input-text: #ffffff;
        }

        body { background-color: var(--bg-body); }
        .sidebar { width: 260px; background-color: var(--bg-sidebar); padding: 35px 24px; box-shadow: 4px 0 20px rgba(0,0,0,0.05); transition: all 0.3s; }
        .sidebar h1 { font-size: 24px; color: var(--text-sidebar-h1); margin-bottom: 40px; text-align: center; font-weight: 700; display: flex; align-items: center; justify-content: center; gap: 12px; }
        .sidebar a { display: flex; align-items: center; gap: 12px; color: var(--text-sidebar-menu); padding: 14px 16px; text-decoration: none; border-radius: 12px; margin-bottom: 8px; font-weight: 600; font-size: 14px; transition: all 0.3s ease; }
        .sidebar a:hover { background-color: var(--bg-sidebar-hover); color: var(--text-sidebar-hover); transform: translateX(4px); }
        .sidebar a.active { background-color: var(--bg-sidebar-active); color: var(--text-sidebar-active); box-shadow: 0 4px 12px var(--box-sidebar-active); }
        .sidebar a.active:hover { transform: none; color: var(--text-sidebar-active); }
        
        .main-content { flex: 1; padding: 40px 50px; background-color: var(--bg-main); overflow-y: auto; transition: all 0.3s; }
        .page-header-flex { display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px; border-bottom: 2px solid var(--border-header); padding-bottom: 15px; }
        .page-header-flex h2 { font-size: 24px; font-weight: 700; color: var(--text-main-h2); }
        
        .header-actions { display: flex; align-items: center; gap: 15px; }
        .real-clock { background-color: var(--bg-card); padding: 10px 20px; border-radius: 10px; border: 1px solid var(--border-card); font-weight: 700; color: #eab308; font-size: 16px; display: flex; align-items: center; gap: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.02); }
        
        .btn-theme-toggle { background-color: var(--bg-card); border: 1px solid var(--border-card); color: var(--text-main-h2); padding: 10px 14px; border-radius: 10px; cursor: pointer; font-size: 16px; transition: all 0.2s; display: flex; align-items: center; justify-content: center; }
        .btn-theme-toggle:hover { transform: scale(1.05); }

        .card { background: var(--bg-card); padding: 30px; border-radius: 20px; border: 1px solid var(--border-card); margin-bottom: 30px; box-shadow: 0 4px 24px rgba(0, 0, 0, 0.02); transition: all 0.3s ease; }
        .card:hover { transform: translateY(-3px); }
        .mode-secure { border-top: 5px solid #10b981; }
        .mode-register { border-top: 5px solid #eab308; }
        
        .btn { padding: 12px 24px; border: none; border-radius: 12px; cursor: pointer; font-weight: 700; font-size: 14px; transition: all 0.2s ease; display: inline-flex; align-items: center; gap: 10px; box-shadow: 0 4px 12px rgba(0,0,0,0.05); }
        .btn:hover { transform: translateY(-1px); opacity: 0.95; }
        .btn-warning { background-color: #eab308; color: #0b0f19; }
        .btn-danger { background-color: #ef4444; color: white; }
        
        .mode-instruction { margin-top: 20px; padding: 15px 20px; border-radius: 10px; font-size: 14px; line-height: 1.6; font-weight: 500; animation: fadeIn 0.5s ease-in-out; }
        .alert-secure { background-color: var(--bg-instruction-secure); border-left: 4px solid var(--border-instruction-secure); color: var(--text-instruction-secure); }
        .alert-register { background-color: var(--bg-instruction-register); border-left: 4px solid var(--border-instruction-register); color: var(--text-instruction-register); }
        
        .chart-container { background: var(--bg-card); padding: 30px 60px; border-radius: 20px; border: 1px solid var(--border-card); margin-bottom: 30px; box-shadow: 0 4px 24px rgba(0,0,0,0.02); display: flex; align-items: center; justify-content: space-around; gap: 40px; }
        .chart-wrapper-donut { flex: 1; height: 250px; display: flex; justify-content: center; }
        
        .chart-side-table { width: 340px; border-collapse: collapse; }
        .chart-side-table tr.clickable-row { cursor: pointer; transition: background-color 0.2s; }
        .chart-side-table tr.clickable-row:hover { background-color: var(--bg-table-row-hover); }
        .chart-side-table tr.row-hidden { text-decoration: line-through; opacity: 0.4; }
        .chart-side-table td { padding: 14px 18px; border-bottom: 1px solid var(--border-table-td); font-size: 15px; font-weight: 600; color: var(--text-main-h2); transition: color 0.3s; }
        .chart-side-table tr:last-child td { border-bottom: none; font-weight: 700; }
        .chart-side-table .count-val { text-align: right; font-family: monospace; font-size: 16px; }

        .table-controls { display: flex; gap: 15px; align-items: center; }
        .search-input { background-color: var(--input-bg); border: 1px solid var(--input-border); color: var(--input-text); padding: 10px 16px; border-radius: 10px; font-size: 14px; outline: none; width: 220px; transition: all 0.3s; }
        .search-input:focus { border-color: #2563eb; box-shadow: 0 0 0 3px rgba(37,99,235,0.15); }
        .filter-select { background-color: var(--input-bg); border: 1px solid var(--input-border); color: var(--input-text); padding: 10px 14px; border-radius: 10px; font-size: 14px; outline: none; cursor: pointer; }

        .table-container { background: var(--bg-card); border-radius: 20px; border: 1px solid var(--border-card); box-shadow: 0 4px 24px rgba(0, 0, 0, 0.02); overflow: hidden; }
        .table-container .table-header-flex { padding: 24px 30px; border-bottom: 1px solid var(--border-card); display: flex; justify-content: space-between; align-items: center; }
        .table-header-flex h3 { font-size: 18px; font-weight: 700; color: var(--text-main-h2); }
        
        table { width: 100%; border-collapse: collapse; text-align: left; }
        th, td { padding: 16px 30px; vertical-align: middle; }
        th { background-color: var(--bg-table-th); color: var(--text-table-th); font-weight: 700; text-transform: uppercase; font-size: 12px; letter-spacing: 0.5px; border-bottom: 1px solid var(--border-table-th); }
        td { border-bottom: 1px solid var(--border-table-td); color: var(--text-table-td); font-size: 14px; font-weight: 500; transition: background-color 0.2s ease; }
        tr:last-child td { border-bottom: none; }
        tr:hover td { background-color: var(--bg-table-row-hover); }
        
        /* TOMBOL HAPUS MERAH SOLID */
        .btn-delete {
            background-color: #ef4444;
            border: none;
            color: white;
            cursor: pointer;
            font-size: 12px;
            font-weight: 700;
            padding: 8px 14px;
            border-radius: 8px;
            transition: all 0.2s ease;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            box-shadow: 0 2px 6px rgba(239, 68, 68, 0.2);
        }
        .btn-delete:hover {
            background-color: #dc2626;
            transform: scale(1.05);
        }

        .badge { padding: 6px 14px; border-radius: 6px; font-size: 12px; font-weight: 700; display: inline-flex; align-items: center; gap: 6px; }
        .badge-success { background-color: rgba(16, 185, 129, 0.2); color: #10b981; }
        .badge-warning { background-color: rgba(234, 179, 8, 0.2); color: #eab308; }
        .badge-danger { background-color: rgba(239, 68, 68, 0.2); color: #ef4444; }
        .badge-secondary { background-color: rgba(100, 116, 139, 0.2); color: #64748b; }

        .live-dot { animation: pulseDot 1.8s infinite ease-in-out; }
        @keyframes pulseDot { 0% { transform: scale(0.9); opacity: 0.6; } 50% { transform: scale(1.2); opacity: 1; } 100% { transform: scale(0.9); opacity: 0.6; } }
        .spin-icon { animation: slowSpin 3s infinite linear; display: inline-block; }
        @keyframes slowSpin { from { transform: rotate(0deg); } to { transform: rotate(360deg); } }
        @keyframes fadeIn { from { opacity: 0; transform: translateY(5px); } to { opacity: 1; transform: translateY(0); } }

        .table-header-flex span, .table-header-flex i.live-dot { color: #10b981 !important; }
        [data-theme="dark"] .table-header-flex span, [data-theme="dark"] .table-header-flex i.live-dot { color: #10b981 !important; }
    </style>
    <script>
        let securityChart;
        let isUserSearchingOrFiltering = false;

        function loadLogsRealtime() {
            if (isUserSearchingOrFiltering) return;

            fetch("fetch_logs.php?t=" + new Date().getTime())
                .then(response => response.text())
                .then(data => { 
                    document.getElementById("log-table-body").innerHTML = data;
                    applyFilterAndSearch();
                })
                .catch(error => console.error("Failed to fetch logs:", error));
        }

        // FUNGSI FILTER LOG YANG SUDAH DIKALIBRASI (owner, denied, intruder)
        function applyFilterAndSearch() {
            const searchValue = document.getElementById("searchLog").value.toLowerCase().trim();
            const filterValue = document.getElementById("filterStatus").value.toLowerCase().trim();
            const rows = document.querySelectorAll("#log-table-body tr");

            isUserSearchingOrFiltering = (searchValue !== "" || filterValue !== "all");

            rows.forEach(row => {
                const textContent = row.innerText.toLowerCase();
                const statusCell = row.cells[2] ? row.cells[2].innerText.toLowerCase().trim() : "";
                
                const matchesSearch = textContent.includes(searchValue);
                
                let matchesFilter = false;
                if (filterValue === "all") {
                    matchesFilter = true;
                } else {
                    matchesFilter = statusCell.includes(filterValue);
                }

                if (matchesSearch && matchesFilter) {
                    row.style.display = "";
                } else {
                    row.style.display = "none";
                }
            });
        }

        function confirmDelete(id) {
            if (confirm("Apakah Anda yakin ingin menghapus data log aktivitas ini secara permanen?")) {
                window.location.href = "delete_log.php?id=" + id;
            }
        }

        function loadChartData() {
            fetch("fetch_chart_data.php?t=" + new Date().getTime())
                .then(response => response.json())
                .then(jsonData => {
                    let v = jsonData.data[0] || 0;
                    let d = jsonData.data[1] || 0;
                    let p = jsonData.data[2] || 0;
                    let total = v + d + p;
                    
                    let pVerif = total > 0 ? ((v / total) * 100).toFixed(0) : 0;
                    let pTolak = total > 0 ? ((d / total) * 100).toFixed(0) : 0;
                    let pSuspect = total > 0 ? ((p / total) * 100).toFixed(0) : 0;

                    document.getElementById("text-verif").innerHTML = `<i class="fa-solid fa-circle" style="color: #10b981; font-size: 10px; margin-right: 8px;"></i> Owner (${pVerif}%)`;
                    document.getElementById("text-tolak").innerHTML = `<i class="fa-solid fa-circle" style="color: #eab308; font-size: 10px; margin-right: 8px;"></i> Denied (${pTolak}%)`;
                    document.getElementById("text-suspect").innerHTML = `<i class="fa-solid fa-circle" style="color: #ef4444; font-size: 10px; margin-right: 8px;"></i> Intruder (${pSuspect}%)`;

                    document.getElementById("count-verif").innerText = v;
                    document.getElementById("count-tolak").innerText = d;
                    document.getElementById("count-suspect").innerText = p;
                    document.getElementById("count-total").innerText = total;

                    if (securityChart) {
                        securityChart.data.datasets[0].data = jsonData.data;
                        securityChart.update();
                    } else {
                        initChart(jsonData.labels, jsonData.data);
                    }
                })
                .catch(error => console.error("Failed to fetch chart data:", error));
        }

        function toggleDataVisibility(index, rowId) {
            if (!securityChart) return;
            const isVisible = securityChart.isDatasetVisible(0) && !securityChart.getDataVisibility(index);
            securityChart.toggleDataVisibility(index);
            securityChart.update();

            const row = document.getElementById(rowId);
            if (isVisible) { row.classList.remove('row-hidden'); } 
            else { row.classList.add('row-hidden'); }
        }

        function initChart(labels, data) {
            const ctx = document.getElementById('securityChart').getContext('2d');
            securityChart = new Chart(ctx, {
                type: 'doughnut',
                data: {
                    labels: labels,
                    datasets: [{
                        data: data,
                        backgroundColor: ['#10b981', '#eab308', '#ef4444'],
                        borderWidth: 0
                    }]
                },
                plugins: [ChartDataLabels],
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: { display: false },
                        datalabels: {
                            color: '#ffffff',
                            font: { weight: 'bold', size: 13, family: 'Segoe UI' },
                            formatter: (value, context) => {
                                let total = context.dataset.data.reduce((a, b) => a + b, 0);
                                if (value === 0 || total === 0) return '';
                                return ((value / total) * 100).toFixed(0) + '%';
                            }
                        }
                    }
                }
            });
        }

        function startClock() {
            const today = new Date();
            let h = today.getHours();
            let m = today.getMinutes();
            let s = today.getSeconds();
            m = checkTime(m); s = checkTime(s);
            document.getElementById('clock-display').innerHTML = h + ":" + m + ":" + s + " WIB";
            setTimeout(startClock, 1000);
        }

        function checkTime(i) { if (i < 10) {i = "0" + i}; return i; }

        function toggleTheme() {
            const currentTheme = document.documentElement.getAttribute('data-theme');
            let newTheme = currentTheme === 'dark' ? 'light' : 'dark';
            document.documentElement.setAttribute('data-theme', newTheme);
            localStorage.setItem('theme', newTheme);
            updateThemeButtonIcon(newTheme);
            if (securityChart) securityChart.update();
        }

        function updateThemeButtonIcon(theme) {
            document.getElementById('theme-icon').className = theme === 'dark' ? 'fa-solid fa-sun' : 'fa-solid fa-moon';
        }

        const savedTheme = localStorage.getItem('theme') || 'light';
        document.documentElement.setAttribute('data-theme', savedTheme);

        window.onload = function() {
            updateThemeButtonIcon(savedTheme);
            loadLogsRealtime();
            loadChartData();
            setInterval(loadLogsRealtime, 1000);
            setInterval(loadChartData, 5000);
            startClock();
        }
    </script>
</head>
<body>

<div class="sidebar">
    <h1><i class="fa-solid fa-shield-halved"></i> SecureDoor</h1>
    <a href="index.php" class="active"><i class="fa-solid fa-chart-line"></i> Dashboard</a>
    <a href="users.php"><i class="fa-solid fa-users"></i> Manage Users</a>
</div>

<div class="main-content">
    
    <div class="page-header-flex">
        <h2>Dashboard</h2>
        <div class="header-actions">
            <button class="btn-theme-toggle" onclick="toggleTheme()">
                <i id="theme-icon" class="fa-solid fa-moon"></i>
            </button>
            <div class="real-clock">
                <i class="fa-regular fa-clock"></i> <span id="clock-display">00:00:00 WIB</span>
            </div>
        </div>
    </div>

    <div class="card <?php echo ($secure_mode == 'secure') ? 'mode-secure' : 'mode-register'; ?>">
        <h3 style="font-size: 18px; margin-bottom: 8px; font-weight: 700; color: var(--text-card-h3);">System Operation Mode</h3>
        <p style="margin-bottom: 20px; color: var(--text-card-p); font-size: 15px;">
            Current Status: <strong style="color: <?php echo ($secure_mode == 'secure') ? '#10b981' : '#eab308'; ?>; font-weight: 700;">
                <?php echo ($secure_mode == 'secure') ? ' SECURE MODE' : ' REGISTRATION MODE'; ?>
            </strong>
        </p>
        <form method="POST">
            <input type="hidden" name="current_mode" value="<?php echo $secure_mode; ?>">
            <?php if($secure_mode == 'secure'): ?>
                <button type="submit" name="toggle_mode" class="btn btn-warning"><i class="fa-solid fa-plus"></i> Switch to Registration Mode</button>
                <div class="mode-instruction alert-secure">
                    <strong> SECURE MODE:</strong> Home security perimeter is fully active. The ultrasonic sensor constantly monitors structural distance. If any suspicious movement is detected without a verified RFID credential, the system triggers the local physical alarm and instantly broadcasts an emergency report to the Telegram channel.
                </div>
            <?php else: ?>
                <button type="submit" name="toggle_mode" class="btn btn-danger"><i class="fa-solid fa-shield"></i> Restore to Secure Mode</button>
                <div class="mode-instruction alert-register">
                    <strong> REGISTRATION MODE:</strong> <i class="fa-solid fa-satellite-dish spin-icon"></i> This operation mode is dedicated to enrolling new keyholders. Ultrasonic perimeter scanning is temporarily bypassed. Tapping an unknown RFID card onto the hardware module will automatically register its UID into the authorization database. Ensure you return the system to Secure Mode immediately after enrollment.
                </div>
            <?php endif; ?>
        </form>
    </div>

    <div class="chart-container">
        <table class="chart-side-table">
            <tr id="row-verif" class="clickable-row" onclick="toggleDataVisibility(0, 'row-verif')">
                <td id="text-verif"><i class="fa-solid fa-circle" style="color: #10b981; font-size: 10px; margin-right: 8px;"></i> Owner</td>
                <td id="count-verif" class="count-val">0</td>
            </tr>
            <tr id="row-tolak" class="clickable-row" onclick="toggleDataVisibility(1, 'row-tolak')">
                <td id="text-tolak"><i class="fa-solid fa-circle" style="color: #eab308; font-size: 10px; margin-right: 8px;"></i> Denied</td>
                <td id="count-tolak" class="count-val">0</td>
            </tr>
            <tr id="row-suspect" class="clickable-row" onclick="toggleDataVisibility(2, 'row-suspect')">
                <td id="text-suspect"><i class="fa-solid fa-circle" style="color: #ef4444; font-size: 10px; margin-right: 8px;"></i> Intruder</td>
                <td id="count-suspect" class="count-val">0</td>
            </tr>
            <tr>
                <td><strong>Total Log Aktif</strong></td>
                <td id="count-total" class="count-val" style="font-weight: 700; border-top: 1px dashed var(--border-table-th);">0</td>
            </tr>
        </table>

        <div class="chart-wrapper-donut">
            <canvas id="securityChart"></canvas>
        </div>
    </div>

    <div class="table-container">
        <div class="table-header-flex">
            <h3>Security Activity Log</h3>
            <div class="table-controls">
                <input type="text" id="searchLog" class="search-input" placeholder="Cari UID / detail..." onkeyup="applyFilterAndSearch()">
                <select id="filterStatus" class="filter-select" onchange="applyFilterAndSearch()">
                    <option value="all">Semua Status</option>
                    <option value="owner">Owner</option>
                    <option value="denied">Denied</option>
                    <option value="intruder">Intruder!</option>
                </select>
            </div>
        </div>
        <table>
            <thead>
                <tr>
                    <th>Timestamp</th>
                    <th>RFID Card UID</th>
                    <th>Status</th>
                    <th>Activity Details</th>
                    <th style="width: 120px; text-align: center;">Aksi</th>
                </tr>
            </thead>
            <tbody id="log-table-body">
            </tbody>
        </table>
    </div>
</div>

</body>
</html>