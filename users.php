<?php
include 'connection.php';

// Proses Update Nama Pengguna
if (isset($_POST['update_user'])) {
    $id = $_POST['id'];
    $new_name = mysqli_real_escape_string($conn, $_POST['owner_name']);
    mysqli_query($conn, "UPDATE card_table SET owner_name = '$new_name' WHERE id = $id");
    header("Location: users.php");
    exit();
}

// Proses Hapus Kartu dari Daftar
if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    mysqli_query($conn, "DELETE FROM card_table WHERE id = $id");
    header("Location: users.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>SecureDoor - Kelola Pengguna</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * { box-sizing: border-box; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; margin: 0; padding: 0; }
        body { background-color: #f8fafc; color: #1e293b; display: flex; min-height: 100vh; }
        
        .sidebar { width: 260px; background-color: #1e40af; padding: 30px 20px; box-shadow: 4px 0 10px rgba(0,0,0,0.05); }
        .sidebar h1 { font-size: 24px; color: #ffffff; margin-bottom: 40px; text-align: center; font-weight: 700; display: flex; align-items: center; justify-content: center; gap: 10px; }
        .sidebar a { display: block; color: #93c5fd; padding: 12px 15px; text-decoration: none; border-radius: 8px; margin-bottom: 10px; font-weight: 500; transition: all 0.3s; }
        .sidebar a:hover, .sidebar a.active { background-color: #2563eb; color: white; }
        
        .main-content { flex: 1; padding: 40px; background-color: #f1f5f9; }
        .box { background-color: #ffffff; padding: 25px; border-radius: 12px; border: 1px solid #e2e8f0; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05); }
        
        table { width: 100%; border-collapse: collapse; margin-top: 15px; background: white; }
        th, td { padding: 14px; text-align: left; border-bottom: 1px solid #e2e8f0; vertical-align: middle; }
        th { background-color: #f8fafc; color: #2563eb; font-weight: 600; text-transform: uppercase; font-size: 13px; }
        tr:hover { background-color: #f8fafc; }
        
        input[type="text"] { background-color: #ffffff; border: 1px solid #cbd5e1; color: #1e293b; padding: 8px 12px; border-radius: 6px; width: 250px; font-size: 14px; }
        
        .btn-action { padding: 6px 12px; border: none; border-radius: 4px; cursor: pointer; font-weight: 600; font-size: 13px; text-decoration: none; display: inline-flex; align-items: center; gap: 4px; }
        .btn-edit { background-color: #dfe7ff; color: #4338ca; }
        .btn-edit:hover { background-color: #c7d2fe; }
        .btn-save { background-color: #d1fae5; color: #065f46; }
        .btn-save:hover { background-color: #a7f3d0; }
        .btn-delete { background-color: #fee2e2; color: #991b1b; margin-left: 5px; }
        .btn-delete:hover { background-color: #fca5a5; }
    </style>
    <script>
        // Fungsi memunculkan form input edit saat tombol diklik
        function aktifkanEditForm(id) {
            document.getElementById('text-nama-' + id).style.display = 'none';
            document.getElementById('form-edit-' + id).style.display = 'inline-block';
            document.getElementById('btn-edit-trigger-' + id).style.display = 'none';
        }
    </script>
</head>
<body>

<div class="sidebar">
    <h1><i class="fa-solid fa-shield-halved"></i> SecureDoor</h1>
    <a href="index.php"><i class="fa-solid fa-chart-line"></i> Dashboard</a>
    <a href="users.php" class="active"><i class="fa-solid fa-users"></i> Kelola Pengguna</a>
</div>

<div class="main-content">
    <div class="box">
        <h2 style="font-size: 20px; margin-bottom: 15px; color: #2563eb;">Daftar Hak Akses Pengguna</h2>
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>UID Kartu</th>
                    <th>Nama Pemilik</th>
                    <th>Tanggal Registrasi</th>
                    <th>Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php
                $user_query = mysqli_query($conn, "SELECT * FROM card_table ORDER BY id DESC");
                if(mysqli_num_rows($user_query) > 0){
                    while($row = mysqli_fetch_assoc($user_query)){
                        $id = $row['id'];
                        echo "<tr>";
                        echo "<td>".$id."</td>";
                        echo "<td><code style='color: #df1c5c; font-weight: bold; background: #ffe4e6; padding: 4px 8px; border-radius: 4px;'>".$row['card_uid']."</code></td>";
                        
                        // Kolom nama: Teks biasa & Form edit menggunakan petik satu agar aman
                        echo "<td>
                                <span id='text-nama-".$id."' style='font-weight: 500;'>".htmlspecialchars($row['owner_name'])."</span>
                                
                                <form id='form-edit-".$id."' method='POST' style='display:none;'>
                                    <input type='hidden' name='id' value='".$id."'>
                                    <input type='text' name='owner_name' value='".htmlspecialchars($row['owner_name'])."'>
                                    <button type='submit' name='update_user' class='btn-action btn-save'><i class='fa-solid fa-check'></i> Simpan</button>
                                </form>
                              </td>";
                              
                        echo "<td>".$row['created_at']."</td>";
                        
                        // Kolom Aksi dengan perbaikan tanda petik satu pada ikon
                        echo "<td>
                                <button id='btn-edit-trigger-".$id."' class='btn-action btn-edit' onclick='aktifkanEditForm(".$id.")'><i class='fa-solid fa-pen-to-square'></i> Edit</button>
                                <a href='users.php?delete=".$id."' class='btn-action btn-delete' onclick='return confirm(\"Hapus kartu ini?\")'><i class='fa-solid fa-trash'></i> Hapus</a>
                              </td>";
                        echo "</tr>";
                    }
                } else {
                    echo "<tr><td colspan='5' style='text-align:center; color: #64748b;'>Belum ada data pengguna terdaftar.</td></tr>";
                }
                ?>
            </tbody>
        </table>
    </div>
</div>

</body>
</html>