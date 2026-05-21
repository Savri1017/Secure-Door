<?php
include 'connection.php';

if (isset($_POST['update_user'])) {
    $id = $_POST['id'];
    $new_name = mysqli_real_escape_string($conn, $_POST['owner_name']);
    mysqli_query($conn, "UPDATE card_table SET owner_name = '$new_name' WHERE id = $id");
    header("Location: users.php");
    exit();
}

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
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SecureDoor - User Access Management</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * { box-sizing: border-box; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; margin: 0; padding: 0; }
        body { background-color: #f4f6f9; color: #1e293b; display: flex; min-height: 100vh; }
        
        .sidebar { width: 260px; background-color: #1e40af; padding: 35px 24px; box-shadow: 4px 0 20px rgba(0,0,0,0.05); }
        .sidebar h1 { font-size: 24px; color: #ffffff; margin-bottom: 40px; text-align: center; font-weight: 700; display: flex; align-items: center; justify-content: center; gap: 12px; }
        .sidebar a { display: flex; align-items: center; gap: 12px; color: #93c5fd; padding: 14px 16px; text-decoration: none; border-radius: 12px; margin-bottom: 8px; font-weight: 600; font-size: 14px; transition: all 0.25s ease; }
        .sidebar a:hover { background-color: rgba(255,255,255,0.1); color: #f8fafc; }
        .sidebar a.active { background-color: #2563eb; color: white; box-shadow: 0 4px 12px rgba(37, 99, 235, 0.2); }
        
        .main-content { flex: 1; padding: 40px 50px; background-color: #f8fafc; overflow-y: auto; }
        .page-header { margin-bottom: 30px; border-bottom: 2px solid #e2e8f0; padding-bottom: 15px; }
        .page-header h2 { font-size: 24px; font-weight: 700; color: #0f172a; }
        .page-header p { color: #64748b; font-size: 14px; margin-top: 4px; }
        
        .table-container { background: white; border-radius: 20px; border: 1px solid rgba(226, 232, 240, 0.8); box-shadow: 0 4px 24px rgba(0, 0, 0, 0.02); overflow: hidden; }
        .table-header-flex { padding: 24px 30px; border-bottom: 1px solid #e2e8f0; }
        .table-header-flex h3 { font-size: 18px; font-weight: 700; color: #0f172a; }
        
        table { width: 100%; border-collapse: collapse; text-align: left; }
        th, td { padding: 18px 30px; vertical-align: middle; }
        th { background-color: #f8fafc; color: #475569; font-weight: 700; text-transform: uppercase; font-size: 12px; letter-spacing: 0.5px; border-bottom: 1px solid #e2e8f0; }
        td { border-bottom: 1px solid #f1f5f9; color: #334155; font-size: 14px; font-weight: 600; }
        tr:last-child td { border-bottom: none; }
        tr:hover td { background-color: #f8fafc; }
        
        input[type="text"] { background-color: #f8fafc; border: 1px solid #cbd5e1; color: #0f172a; padding: 10px 16px; border-radius: 8px; width: 240px; font-size: 14px; font-weight: 600; transition: all 0.2s; }
        input[type="text"]:focus { outline: none; border-color: #2563eb; background-color: #fff; box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.15); }
        
        .btn-action { padding: 8px 14px; border: none; border-radius: 6px; cursor: pointer; font-weight: 700; font-size: 13px; text-decoration: none; display: inline-flex; align-items: center; gap: 6px; transition: all 0.2s; }
        .btn-edit { background-color: #f1f5f9; color: #475569; border: 1px solid #e2e8f0; }
        .btn-edit:hover { background-color: #e2e8f0; color: #0f172a; }
        .btn-save { background-color: #10b981; color: white; box-shadow: 0 2px 8px rgba(16, 185, 129, 0.2); }
        .btn-save:hover { background-color: #059669; }
        .btn-delete { background-color: #fee2e2; color: #ef4444; border: 1px solid #fca5a5; margin-left: 5px; }
        .btn-delete:hover { background-color: #ef4444; color: white; }
    </style>
    <script>
        function toggleEditForm(id) {
            document.getElementById('text-name-' + id).style.display = 'none';
            document.getElementById('form-edit-' + id).style.display = 'inline-block';
            document.getElementById('btn-edit-trigger-' + id).style.display = 'none';
        }
    </script>
</head>
<body>

<div class="sidebar">
    <h1><i class="fa-solid fa-shield-halved"></i> SecureDoor</h1>
    <a href="index.php"><i class="fa-solid fa-chart-line"></i> Dashboard</a>
    <a href="users.php" class="active"><i class="fa-solid fa-users"></i> Manage Users</a>
</div>

<div class="main-content">
    <div class="page-header">
        <h2>User Access Control Management</h2>
        <p>Manage registered identity credentials and assign access rights for authorized RFID keyholders.</p>
    </div>

    <div class="table-container">
        <div class="table-header-flex">
            <h3>Authorized Credentials Registry</h3>
        </div>
        <table>
            <thead>
                <tr>
                    <th>System ID</th>
                    <th>RFID Card UID</th>
                    <th>Keyholder Name</th>
                    <th>Registration Date</th>
                    <th>Management Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php
                $user_query = mysqli_query($conn, "SELECT * FROM card_table ORDER BY id DESC");
                if(mysqli_num_rows($user_query) > 0){
                    while($row = mysqli_fetch_assoc($user_query)){
                        $id = $row['id'];
                        echo "<tr>";
                        echo "<td style='color:#94a3b8;'>#".$id."</td>";
                        echo "<td><code style='color: #0f172a; font-weight: 700; background: #f1f5f9; padding: 4px 10px; border-radius: 6px; border: 1px solid #e2e8f0; font-size:13px;'>".$row['card_uid']."</code></td>";
                        
                        echo "<td>
                                <span id='text-name-".$id."' style='color:#1e293b;'>".htmlspecialchars($row['owner_name'])."</span>
                                <form id='form-edit-".$id."' method='POST' style='display:none;'>
                                    <input type='hidden' name='id' value='".$id."'>
                                    <input type='text' name='owner_name' value='".htmlspecialchars($row['owner_name'])."'>
                                    <button type='submit' name='update_user' class='btn-action btn-save'><i class='fa-solid fa-floppy-disk'></i> Save</button>
                                </form>
                              </td>";
                              
                        echo "<td style='color: #64748b; font-size:13px;'>".$row['created_at']."</td>";
                        
                        echo "<td>
                                <button id='btn-edit-trigger-".$id."' class='btn-action btn-edit' onclick='toggleEditForm(".$id.")'><i class='fa-solid fa-user-pen'></i> Edit Name</button>
                                <a href='users.php?delete=".$id."' class='btn-action btn-delete' onclick='return confirm(\"Are you absolutely sure you want to revoke access rights for this card?\")'><i class='fa-solid fa-trash-can'></i> Delete</a>
                              </td>";
                        echo "</tr>";
                    }
                } else {
                    echo "<tr><td colspan='5' style='text-align:center; color: #94a3b8; padding: 30px;'>No registered user credentials found in the system registry.</td></tr>";
                }
                ?>
            </tbody>
        </table>
    </div>
</div>

</body>
</html>