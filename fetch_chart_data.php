<?php
header('Content-Type: application/json');
include 'connection.php';

$secure_count = 0;
$denied_count = 0;
$intruder_count = 0;

$query = mysqli_query($conn, "SELECT status, COUNT(*) as total FROM access_log GROUP BY status");

while ($row = mysqli_fetch_assoc($query)) {
    if ($row['status'] == 'Intruder!') {
        $intruder_count = (int)$row['total'];
    } elseif ($row['status'] == 'Access Denied') {
        $denied_count = (int)$row['total'];
    } else {
        $secure_count += (int)$row['total'];
    }
}

echo json_encode([
    'labels' => ['Terverifikasi', 'Ditolak', 'Penyusup'],
    'data' => [$secure_count, $denied_count, $intruder_count]
]);
?>