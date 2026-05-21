<?php
include 'connection.php';

// Toggle Mode Keamanan
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
    <title>SecureDoor - Dashboard</title>
    
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <style>
        * { box-sizing: border-box; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; margin: 0; padding: 0; }
        body { background-color: #f8fafc; color: #1e293b; display: flex; min-height: 100vh; }
        
        /* Sidebar Styling */
        .sidebar { width: 260px; background-color: #1e40af; padding: 30px 20px; box-shadow: 4px 0 10px rgba(0,0,0,0.05); }
        .sidebar h1 { font-size: 24px; color: #ffffff; margin-bottom: 40px; text-align: center; font-weight: 700; display: flex; align-items: center; justify-content: center; gap: 10px; }
        .sidebar a { display: block; color: #93c5fd; padding: 12px 15px; text-decoration: none; border-radius: 8px; margin-bottom: 10px; font-weight: 500; transition: all 0.3s; }
        .sidebar a:hover, .sidebar a.active { background-color: #2563eb; color: white; }
        
        /* Main Content Styling */
        .main-content { flex: 1; padding: 40px; background-color: #f1f5f9; }
        .box { background-color: #ffffff; padding: 25px; border-radius: 12px; border: 1px solid #e2e8f0; margin-bottom: 30px; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05); }
        
        .mode-secure { border-left: 5px solid #10b981; }
        .mode-register { border-left: 5px solid #f59e0b; }
        
        /* Button Styling */
        .btn { padding: 10px 20px; border: none; border-radius: 6px; cursor: pointer; font-weight: 600; font-size: 14px; transition: all 0.2s; display: inline-flex; align-items: center; gap: 8px; }
        .btn-warning { background-color: #f59e0b; color: white; }
        .btn-warning:hover { background-color: #d97706; }
        .btn-danger { background-color: #ef4444; color: white; }
        .btn-danger:hover { background-color: #dc2626; }
        
        /* Table Styling */
        table { width: 100%; border-collapse: collapse; margin-top: 15px; background: white; }
        th, td { padding: 14px; text-align: left; border-bottom: 1px solid #e2e8f0; }
        th { background-color: #f8fafc; color: #2563eb; font-weight: 600; text-transform: uppercase; font-size: 13px; }
        tr:hover { background-color: #f8fafc; }
        
        /* Badges */
        .badge { padding: 6px 12px; border-radius: 20px; font-size: 12px; font-weight: 700; }
        .bg-danger { background-color: #fee2e2; color: #ef4444; }
        .bg-warning { background-color: #fef3c7; color: #d97706; }
        .bg-success { background-color: #d1fae5; color: #10b981; }
    </style>

    <script>
        function loadLogsRealtime() {
            // Fetch API jauh lebih bersih, modern, dan didukung semua browser tanpa memicu warning
            fetch("fetch_logs.php?t=" + new Date().getTime())
                .then(response => response.text())
                .then(data => {
                    document.getElementById("log-table-body").innerHTML = data;
                })
                .catch(error => console.error("Gagal mengambil log:", error));
        }

        // Jalankan otomatis tiap 1 detik
        setInterval(loadLogsRealtime, 1000);
        // Jalankan saat halaman pertama kali dibuka
        window.onload = loadLogsRealtime;
    </script>
</head>
<body>

<div class="sidebar">
    <h1><i class="fa-solid fa-shield-halved"></i> SecureDoor</h1>
    <a href="index.php" class="active"><i class="fa-solid fa-chart-line"></i> Dashboard</a>
    <a href="users.php"><i class="fa-solid fa-users"></i> Kelola Pengguna</a>
</div>

<div class="main-content">
    <div class="box <?php echo ($secure_mode == 'secure') ? 'mode-secure' : 'mode-register'; ?>">
        <h2 style="font-size: 20px; margin-bottom: 10px; color: #1e3a8a;">Status Mode Keamanan</h2>
        <p style="margin-bottom: 15px; color: #64748b;">
            Status Saat Ini: <strong style="color: <?php echo ($secure_mode == 'secure') ? '#10b981' : '#f59e0b'; ?>;">
                <?php echo ($secure_mode == 'secure') ? '🔒 MODE AMAN (Proteksi Aktif)' : '⚡ MODE REGISTRASI (Siap Scan Kartu)'; ?>
            </strong>
        </p>
        <form method="POST">
            <input type="hidden" name="current_mode" value="<?php echo $secure_mode; ?>">
            <?php if($secure_mode == 'secure'): ?>
                <button type="submit" name="toggle_mode" class="btn btn-warning"><i class="fa-solid fa-id-card"></i> Aktifkan Mode Registrasi</button>
            <?php else: ?>
                <button type="submit" name="toggle_mode" class="btn btn-danger"><i class="fa-solid fa-lock"></i> Aktifkan Mode Aman</button>
            <?php endif; ?>
        </form>
    </div>

    <?php if($secure_mode == 'register'): ?>
        <div class="box" style="border: 1px solid #f59e0b; background-color: #fffbeb;">
            <h3 style="color: #b45309; margin-bottom: 5px;"><i class="fa-solid fa-circle-info"></i> Sistem Registrasi Otomatis Jalan</h3>
            <p style="color: #78350f; font-size: 14px;">Tempelkan kartu RFID baru pada alat hardware. Kartu akan langsung terdaftar instan ke database.</p>
        </div>
    <?php endif; ?>

    <div class="box">
        <h2 style="font-size: 20px; margin-bottom: 15px; color: #2563eb;">Log Riwayat Keamanan (Realtime)</h2>
        <table>
            <thead>
                <tr>
                    <th>Waktu Kejadian</th>
                    <th>UID Kartu</th>
                    <th>Kategori Status</th>
                    <th>Detail Kejadian</th>
                </tr>
            </thead>
            <tbody id="log-table-body">
                </tbody>
        </table>
    </div>
</div>

</body>
</html>