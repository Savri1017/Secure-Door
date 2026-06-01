<?php
include 'connection.php';

if (isset($_GET['id'])) {
    $id = intval($_GET['id']);
    
    $query = "DELETE FROM access_log WHERE id = $id";
    
    if (mysqli_query($conn, $query)) {
        header("Location: index.php");
        exit();
    } else {
        echo "Gagal menghapus log: " . mysqli_error($conn);
    }
} else {
    echo "ID tidak ditemukan.";
}
?>