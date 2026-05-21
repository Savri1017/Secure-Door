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
    <style>
        * { box-sizing: border-box; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; margin: 0; padding: 0; }
        body { background-color: #f4f6f9; color: #1e293b; display: flex; min-height: 100vh; }
        
        .sidebar { width: 260px; background-color: #1e40af; padding: 35px 24px; box-shadow: 4px 0 20px rgba(0,0,0,0.05); }
        .sidebar h1 { font-size: 24px; color: #ffffff; margin-bottom: 40px; text-align: center; font-weight: 700; display: flex; align-items: center; justify-content: center; gap: 12px; }
        .sidebar a { display: flex; align-items: center; gap: 12px; color: #93c5fd; padding: 14px 16px; text-decoration: none; border-radius: 12px; margin-bottom: 8px; font-weight: 600; font-size: 14px; transition: all 0.3s ease; }
        .sidebar a:hover { background-color: rgba(255,255,255,0.1); color: #f8fafc; transform: translateX(4px); }
        .sidebar a.active { background-color: #2563eb; color: white; box-shadow: 0 4px 12px rgba(37, 99, 235, 0.3); }
        .sidebar a.active:hover { transform: none; }
        
        .main-content { flex: 1; padding: 40px 50px; background-color: #f8fafc; overflow-y: auto; }
        .page-header-flex { display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px; border-bottom: 2px solid #e2e8f0; padding-bottom: 15px; }
        .page-header-flex h2 { font-size: 24px; font-weight: 700; color: #0f172a; }
        
        .real-clock { background-color: #ffffff; padding: 10px 20px; border-radius: 10px; border: 1px solid #cbd5e1; font-weight: 700; color: #1e40af; font-size: 16px; display: flex; align-items: center; gap: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.02); }
        
        .card { background: #ffffff; padding: 30px; border-radius: 20px; border: 1px solid rgba(226, 232, 240, 0.8); margin-bottom: 30px; box-shadow: 0 4px 24px rgba(0, 0, 0, 0.02); transition: all 0.3s ease; }
        .card:hover { transform: translateY(-3px); box-shadow: 0 8px 30px rgba(0,0,0,0.04); }
        .mode-secure { border-top: 5px solid #10b981; }
        .mode-register { border-top: 5px solid #f59e0b; }
        
        .btn { padding: 12px 24px; border: none; border-radius: 12px; cursor: pointer; font-weight: 700; font-size: 14px; transition: all 0.2s ease; display: inline-flex; align-items: center; gap: 10px; box-shadow: 0 4px 12px rgba(0,0,0,0.05); }
        .btn:hover { transform: translateY(-1px); opacity: 0.95; }
        .btn:active { transform: translateY(1px); }
        .btn-warning { background-color: #f59e0b; color: white; }
        .btn-danger { background-color: #ef4444; color: white; }
        
        .mode-instruction { margin-top: 20px; padding: 15px 20px; border-radius: 10px; font-size: 14px; line-height: 1.6; font-weight: 500; animation: fadeIn 0.5s ease-in-out; }
        .alert-secure { background-color: #ecfdf5; border-left: 4px solid #10b981; color: #065f46; }
        .alert-register { background-color: #fffbeb; border-left: 4px solid #f59e0b; color: #92400e; }
        
        .table-container { background: white; border-radius: 20px; border: 1px solid rgba(226, 232, 240, 0.8); box-shadow: 0 4px 24px rgba(0, 0, 0, 0.02); overflow: hidden; }
        .table-header-flex { padding: 24px 30px; border-bottom: 1px solid #e2e8f0; display: flex; justify-content: space-between; align-items: center; }
        .table-header-flex h3 { font-size: 18px; font-weight: 700; color: #0f172a; }
        
        table { width: 100%; border-collapse: collapse; text-align: left; }
        th, td { padding: 16px 30px; }
        th { background-color: #f8fafc; color: #475569; font-weight: 700; text-transform: uppercase; font-size: 12px; letter-spacing: 0.5px; border-bottom: 1px solid #e2e8f0; }
        td { border-bottom: 1px solid #f1f5f9; color: #334155; font-size: 14px; font-weight: 500; transition: background-color 0.2s ease; }
        tr:last-child td { border-bottom: none; }
        tr:hover td { background-color: #f1f5f9; }
        
        .badge { padding: 6px 14px; border-radius: 6px; font-size: 12px; font-weight: 700; display: inline-flex; align-items: center; gap: 6px; }
        .bg-danger { background-color: #fee2e2; color: #ef4444; border: 1px solid #fca5a5; }
        .bg-warning { background-color: #fef3c7; color: #d97706; border: 1px solid #fde68a; }
        .bg-success { background-color: #d1fae5; color: #10b981; border: 1px solid #a7f3d0; }

        .live-dot { animation: pulseDot 1.8s infinite ease-in-out; }
        @keyframes pulseDot {
            0% { transform: scale(0.9); opacity: 0.6; }
            50% { transform: scale(1.2); opacity: 1; }
            100% { transform: scale(0.9); opacity: 0.6; }
        }
        .spin-icon { animation: slowSpin 3s infinite linear; display: inline-block; }
        @keyframes slowSpin {
            from { transform: rotate(0deg); }
            to { transform: rotate(360deg); }
        }
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(5px); }
            to { opacity: 1; transform: translateY(0); }
        }
    </style>
    <script>
        function loadLogsRealtime() {
            fetch("fetch_logs.php?t=" + new Date().getTime())
                .then(response => response.text())
                .then(data => { document.getElementById("log-table-body").innerHTML = data; })
                .catch(error => console.error("Failed to fetch logs:", error));
        }

        function startClock() {
            const today = new Date();
            let h = today.getHours();
            let m = today.getMinutes();
            let s = today.getSeconds();
            m = checkTime(m);
            s = checkTime(s);
            document.getElementById('clock-display').innerHTML = h + ":" + m + ":" + s + " WIB";
            setTimeout(startClock, 1000);
        }

        function checkTime(i) {
            if (i < 10) {i = "0" + i};
            return i;
        }

        window.onload = function() {
            loadLogsRealtime();
            setInterval(loadLogsRealtime, 1000);
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
        <div class="real-clock">
            <i class="fa-regular fa-clock"></i> <span id="clock-display">00:00:00 WIB</span>
        </div>
    </div>

    <div class="card <?php echo ($secure_mode == 'secure') ? 'mode-secure' : 'mode-register'; ?>">
        <h3 style="font-size: 18px; margin-bottom: 8px; font-weight: 700; color: #0f172a;">System Operation Mode</h3>
        <p style="margin-bottom: 20px; color: #64748b; font-size: 15px;">
            Current Status: <strong style="color: <?php echo ($secure_mode == 'secure') ? '#10b981' : '#f59e0b'; ?>; font-weight: 700;">
                <?php echo ($secure_mode == 'secure') ? '🔒 SECURE MODE' : '⚡ REGISTRATION MODE'; ?>
            </strong>
        </p>
        <form method="POST">
            <input type="hidden" name="current_mode" value="<?php echo $secure_mode; ?>">
            <?php if($secure_mode == 'secure'): ?>
                <button type="submit" name="toggle_mode" class="btn btn-warning"><i class="fa-solid fa-plus"></i> Switch to Registration Mode</button>
                
                <div class="mode-instruction alert-secure">
                    <strong>📌 SECURE MODE:</strong> Home security perimeter is fully active. The ultrasonic sensor constantly monitors structural distance. If any suspicious movement is detected without a verified RFID credential, the system triggers the local physical alarm and instantly broadcasts an emergency report to the Telegram channel.
                </div>
            <?php else: ?>
                <button type="submit" name="toggle_mode" class="btn btn-danger"><i class="fa-solid fa-shield"></i> Restore to Secure Mode</button>
                
                <div class="mode-instruction alert-register">
                    <strong>⚠️ REGISTRATION MODE:</strong> <i class="fa-solid fa-satellite-dish spin-icon"></i> This operation mode is dedicated to enrolling new keyholders. Ultrasonic perimeter scanning is temporarily bypassed. Tapping an unknown RFID card onto the hardware module will automatically register its UID into the authorization database. Ensure you return the system to Secure Mode immediately after enrollment.
                </div>
            <?php endif; ?>
        </form>
    </div>

    <div class="table-container">
        <div class="table-header-flex">
            <h3>Security Activity Log</h3>
            <span style="font-size: 13px; color: #10b981; font-weight: 600; display: flex; align-items: center; gap: 6px;">
                <i class="fa-solid fa-circle live-dot" style="font-size: 8px;"></i> Live Surveillance
            </span>
        </div>
        <table>
            <thead>
                <tr>
                    <th>Timestamp</th>
                    <th>RFID Card UID</th>
                    <th>Status</th>
                    <th>Activity Details</th>
                </tr>
            </thead>
            <tbody id="log-table-body">
            </tbody>
        </table>
    </div>
</div>

</body>
</html>