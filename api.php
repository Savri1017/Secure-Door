<?php
header("Content-Type: application/json");
require_once 'config.php';

$action = isset($_GET['action']) ? $_GET['action'] : '';

if ($_SERVER['REQUEST_METHOD'] === 'GET' && $action === 'status') {
    $result = $conn->query("SELECT mode_siaga FROM system_status WHERE id = 1");
    $row = $result->fetch_assoc();
    echo json_encode(["mode_siaga" => (bool)$row['mode_siaga']]);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $action === 'check-card') {
    $data = json_decode(file_get_contents("php://input"), true);
    $uid = isset($data['uid']) ? $conn->real_escape_string(strtoupper(trim($data['uid']))) : '';

    if (empty($uid)) {
        echo json_encode(["status" => "ERROR", "message" => "UID Kosong"]);
        exit();
    }

    $statusCheck = $conn->query("SELECT registration_mode FROM system_status WHERE id = 1");
    $statusRow = $statusCheck->fetch_assoc();

    if ($statusRow['registration_mode'] == 1) {
        $cardCheck = $conn->query("SELECT id FROM cards WHERE uid = '$uid'");
        if ($cardCheck->num_rows == 0) {
            $randomName = "User Baru " . rand(100, 999);
            $conn->query("INSERT INTO cards (uid, owner_name) VALUES ('$uid', '$randomName')");
            $conn->query("UPDATE system_status SET registration_mode = 0 WHERE id = 1");
            echo json_encode(["status" => "REGISTERED", "message" => "Kartu baru didaftarkan!"]);
        } else {
            echo json_encode(["status" => "ALREADY_EXISTS", "message" => "Kartu sudah terdaftar."]);
        }
        exit();
    }

    $cardQuery = $conn->query("SELECT owner_name FROM cards WHERE uid = '$uid'");
    if ($cardQuery->num_rows > 0) {
        $card = $cardQuery->fetch_assoc();
        $owner = $card['owner_name'];
        $desc = "Pemilik rumah atas nama [" . $owner . "] berhasil masuk rumah.";
        $conn->query("INSERT INTO security_logs (type, uid, description) VALUES ('PEMILIK', '$uid', '$desc')");
        echo json_encode(["status" => "VALID"]);
    } else {
        $desc = "Peringatan! Seseorang mencoba menempelkan kartu asing tidak terdaftar (UID: " . $uid . ").";
        $conn->query("INSERT INTO security_logs (type, uid, description) VALUES ('KARTU_SALAH', '$uid', '$desc')");
        echo json_encode(["status" => "INVALID"]);
    }
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $action === 'log-maling') {
    $desc = "🚨 Bahaya! Sensor ultrasonik mendeteksi adanya pergerakan asing melewati pintu tanpa pemindaian kartu RFID!";
    $conn->query("INSERT INTO security_logs (type, description) VALUES ('MALING', '$desc')");
    echo json_encode(["status" => "SUCCESS"]);
    exit();
}

echo json_encode(["status" => "INVALID_ACTION"]);
?>