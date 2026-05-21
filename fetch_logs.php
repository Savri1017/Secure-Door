<?php
include 'connection.php';

$log_query = mysqli_query($conn, "SELECT * FROM access_log ORDER BY timestamp DESC LIMIT 25");

if(mysqli_num_rows($log_query) > 0){
    while($row = mysqli_fetch_assoc($log_query)){
        $badge = "";
        if($row['status'] == 'Intruder!') {
            $badge = "<span class='badge bg-danger'><i class='fa-solid fa-shield-virus'></i> Penyusup</span>";
        } elseif ($row['status'] == 'Access Denied') {
            $badge = "<span class='badge bg-warning'><i class='fa-solid fa-ban'></i> Ditolak</span>";
        } else {
            $badge = "<span class='badge bg-success'><i class='fa-solid fa-user-shield'></i> Terverifikasi</span>";
        }
        
        echo "<tr>";
        echo "<td style='color: #64748b; font-weight: 600;'><i class='fa-regular fa-clock' style='margin-right: 6px; opacity:0.7;'></i> ".$row['timestamp']."</td>";
        echo "<td><code style='color: #0f172a; font-weight: 700; background: #f1f5f9; padding: 4px 10px; border-radius: 8px; border: 1px solid #e2e8f0; font-size: 13px;'>".$row['card_uid']."</code></td>";
        echo "<td>".$badge."</td>";
        echo "<td><span style='color: #1e293b; font-weight: 600;'>".$row['details']."</span></td>";
        echo "</tr>";
    }
} else {
    echo "<tr><td colspan='4' style='text-align:center; color: #94a3b8; padding: 40px;'><i class='fa-solid fa-database' style='font-size: 20px; display:block; margin-bottom:10px; opacity:0.5;'></i> Kamar log kosong. Tidak ada data terdeteksi.</td></tr>";
}
?>