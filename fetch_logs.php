<?php
include 'connection.php';

$query = "SELECT * FROM access_log ORDER BY timestamp DESC"; 
$result = mysqli_query($conn, $query);

if ($result && mysqli_num_rows($result) > 0) {
    while ($row = mysqli_fetch_assoc($result)) {
        $status = $row['status'];
        $badge_class = 'badge-secondary';
        
        if (strpos(strtolower($status), 'verifikasi') !== false) { 
            $badge_class = 'badge-success'; 
        } elseif (strpos(strtolower($status), 'tolak') !== false) { 
            $badge_class = 'badge-warning'; 
        } elseif (strpos(strtolower($status), 'susup') !== false || strpos(strtolower($status), 'nyusup') !== false) { 
            $badge_class = 'badge-danger'; 
        }

        echo "<tr>";
        echo "<td>" . $row['timestamp'] . "</td>";
        
        echo "<td><span class='uid-tag'>" . $row['card_uid'] . "</span></td>";
        
        echo "<td><span class='badge " . $badge_class . "'>" . $status . "</span></td>";
        echo "<td>" . $row['details'] . "</td>";
        echo "<td style='text-align:center;'>";
        echo "<button class='btn-delete' onclick='confirmDelete(" . $row['id'] . ")'>";
        echo "<i class='fa-solid fa-trash'></i> Hapus";
        echo "</button>";
        echo "</td>";
        echo "</tr>";
    }
} else {
    echo "<tr><td colspan='5' style='text-align:center; color:var(--text-card-p);'>Belum ada data log aktivitas.</td></tr>";
}
?>