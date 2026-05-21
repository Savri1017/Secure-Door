<?php
include 'connection.php';

// Ambil 25 log aktivitas keamanan terbaru dari database
$log_query = mysqli_query($conn, "SELECT * FROM access_log ORDER BY timestamp DESC LIMIT 25");

if(mysqli_num_rows($log_query) > 0){
    while($row = mysqli_fetch_assoc($log_query)){
        $badge = "";
        
        // Menentukan warna badge sesuai dengan status log (Tema Terang)
        if($row['status'] == 'Intruder!') {
            $badge = "<span class='badge bg-danger'><i class='fa-solid fa-triangle-exclamation'></i> Maling!</span>";
        } elseif ($row['status'] == 'Access Denied') {
            $badge = "<span class='badge bg-warning'><i class='fa-solid fa-circle-xmark'></i> Ditolak</span>";
        } else {
            $badge = "<span class='badge bg-success'><i class='fa-solid fa-circle-check'></i> Pemilik</span>";
        }
        
        echo "<tr>";
        echo "<td><i class='fa-regular fa-clock' style='color: #64748b; margin-right: 5px;'></i> ".$row['timestamp']."</td>";
        echo "<td><code style='color: #0f172a; font-weight: 600; background: #e2e8f0; padding: 3px 6px; border-radius: 4px;'>".$row['card_uid']."</code></td>";
        echo "<td>".$badge."</td>";
        echo "<td><span style='color: #334155; font-weight: 500;'>".$row['details']."</span></td>";
        echo "</tr>";
    }
} else {
    // Tampilan jika database masih kosong
    echo "<tr><td colspan='4' style='text-align:center; color: #94a3b8; padding: 30px;'><i class='fa-solid fa-folder-open' style='font-size: 24px; display:block; margin-bottom:10px;'></i> Belum ada riwayat aktivitas terekam.</td></tr>";
}
?>