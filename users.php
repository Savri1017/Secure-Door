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
        body { display: flex; min-height: 100vh; transition: background-color 0.3s, color 0.3s; }
        
        :root {
            --bg-body: #f4f6f9;
            --bg-sidebar: #1e40af;
            --text-sidebar-h1: #ffffff;
            --text-sidebar-menu: #93c5fd;
            --bg-sidebar-hover: rgba(255,255,255,0.1);
            --text-sidebar-hover: #f8fafc;
            --bg-sidebar-active: #2563eb;
            --text-sidebar-active: white;
            --box-sidebar-active: rgba(37, 99, 235, 0.2);
            --bg-main: #f8fafc;
            --text-main-h2: #0f172a;
            --text-main-p: #64748b;
            --border-header: #e2e8f0;
            --bg-card: #ffffff;
            --border-card: rgba(226, 232, 240, 0.8);
            --bg-table-th: #f8fafc;
            --text-table-th: #475569;
            --border-table-th: #e2e8f0;
            --border-table-td: #f1f5f9;
            --text-table-td: #334155;
            --bg-table-row-hover: #f8fafc;
            --bg-input: #f8fafc;
            --border-input: #cbd5e1;
            --text-input: #0f172a;
            --bg-btn-edit: #f1f5f9;
            --text-btn-edit: #475569;
            --border-btn-edit: #e2e8f0;
            --hover-btn-edit: #e2e8f0;
            --text-uid: #0f172a;
            --bg-uid: #f1f5f9;
        }

        [data-theme="dark"] {
            --bg-body: #0b0f19;
            --bg-sidebar: #111827;
            --text-sidebar-h1: #eab308;
            --text-sidebar-menu: #9ca3af;
            --bg-sidebar-hover: rgba(234, 179, 8, 0.1);
            --text-sidebar-hover: #eab308;
            --bg-sidebar-active: #eab308;
            --text-sidebar-active: #0b0f19;
            --box-sidebar-active: rgba(234, 179, 8, 0.2);
            --bg-main: #0b0f19;
            --text-main-h2: #ffffff;
            --text-main-p: #9ca3af;
            --border-header: #1f2937;
            --bg-card: #111827;
            --border-card: #1f2937;
            --bg-table-th: #1f2937;
            --text-table-th: #9ca3af;
            --border-table-th: #374151;
            --border-table-td: #1f2937;
            --text-table-td: #e5e7eb;
            --bg-table-row-hover: #1f2937;
            --bg-input: #1f2937;
            --border-input: #374151;
            --text-input: #ffffff;
            --bg-btn-edit: #1f2937;
            --text-btn-edit: #9ca3af;
            --border-btn-edit: #374151;
            --hover-btn-edit: #374151;
            --text-uid: #eab308;
            --bg-uid: #1f2937;
        }

        body { background-color: var(--bg-body); }
        .sidebar { width: 260px; background-color: var(--bg-sidebar); padding: 35px 24px; box-shadow: 4px 0 20px rgba(0,0,0,0.05); transition: all 0.3s; }
        .sidebar h1 { font-size: 24px; color: var(--text-sidebar-h1); margin-bottom: 40px; text-align: center; font-weight: 700; display: flex; align-items: center; justify-content: center; gap: 12px; }
        .sidebar a { display: flex; align-items: center; gap: 12px; color: var(--text-sidebar-menu); padding: 14px 16px; text-decoration: none; border-radius: 12px; margin-bottom: 8px; font-weight: 600; font-size: 14px; transition: all 0.25s ease; }
        .sidebar a:hover { background-color: var(--bg-sidebar-hover); color: var(--text-sidebar-hover); }
        .sidebar a.active { background-color: var(--bg-sidebar-active); color: var(--text-sidebar-active); box-shadow: 0 4px 12px var(--box-sidebar-active); }
        
        .main-content { flex: 1; padding: 40px 50px; background-color: var(--bg-main); overflow-y: auto; transition: all 0.3s; }
        .page-header-flex { display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px; border-bottom: 2px solid var(--border-header); padding-bottom: 15px; }
        .page-header h2 { font-size: 24px; font-weight: 700; color: var(--text-main-h2); }
        .page-header p { color: var(--text-main-p); font-size: 14px; margin-top: 4px; }
        
        .btn-theme-toggle { background-color: var(--bg-card); border: 1px solid var(--border-card); color: var(--text-main-h2); padding: 10px 14px; border-radius: 10px; cursor: pointer; font-size: 16px; transition: all 0.2s; display: flex; align-items: center; justify-content: center; }
        .btn-theme-toggle:hover { transform: scale(1.05); }

        .table-container { background: var(--bg-card); border-radius: 20px; border: 1px solid var(--border-card); box-shadow: 0 4px 24px rgba(0, 0, 0, 0.02); overflow: hidden; }
        .table-container .table-header-flex { padding: 24px 30px; border-bottom: 1px solid var(--border-card); }
        .table-header-flex h3 { font-size: 18px; font-weight: 700; color: var(--text-main-h2); }
        
        table { width: 100%; border-collapse: collapse; text-align: left; }
        th, td { padding: 18px 30px; vertical-align: middle; }
        th { background-color: var(--bg-table-th); color: var(--text-table-th); font-weight: 700; text-transform: uppercase; font-size: 12px; letter-spacing: 0.5px; border-bottom: 1px solid var(--border-table-th); }
        td { border-bottom: 1px solid var(--border-table-td); color: var(--text-table-td); font-size: 14px; font-weight: 600; }
        tr:last-child td { border-bottom: none; }
        tr:hover td { background-color: var(--bg-table-row-hover); }
        
        input[type="text"] { background-color: var(--bg-input); border: 1px solid var(--border-input); color: var(--text-input); padding: 10px 16px; border-radius: 8px; width: 240px; font-size: 14px; font-weight: 600; transition: all 0.2s; }
        input[type="text"]:focus { outline: none; border-color: #eab308; box-shadow: 0 0 0 3px rgba(234, 179, 8, 0.15); }
        
        .btn-action { padding: 8px 14px; border: none; border-radius: 6px; cursor: pointer; font-weight: 700; font-size: 13px; text-decoration: none; display: inline-flex; align-items: center; gap: 6px; transition: all 0.2s; }
        .btn-edit { background-color: var(--bg-btn-edit); color: var(--text-btn-edit); border: 1px solid var(--border-btn-edit); }
        .btn-edit:hover { background-color: var(--hover-btn-edit); color: var(--text-main-h2); }
        .btn-save { background-color: #10b981; color: white; box-shadow: 0 2px 8px rgba(16, 185, 129, 0.2); }
        .btn-save:hover { background-color: #059669; }
        .btn-delete { background-color: rgba(239, 68, 68, 0.15); color: #fca5a5; border: 1px solid #ef4444; margin-left: 5px; }
        .btn-delete:hover { background-color: #ef4444; color: white; }
    </style>
    <script>
        function toggleEditForm(id) {
            document.getElementById('text-name-' + id).style.display = 'none';
            document.getElementById('form-edit-' + id).style.display = 'inline-block';
            document.getElementById('btn-edit-trigger-' + id).style.display = 'none';
        }

        function toggleTheme() {
            const currentTheme = document.documentElement.getAttribute('data-theme');
            let newTheme = 'light';
            if (currentTheme !== 'dark') {
                newTheme = 'dark';
            }
            document.documentElement.setAttribute('data-theme', newTheme);
            localStorage.setItem('theme', newTheme);
            updateThemeButtonIcon(newTheme);
        }

        function updateThemeButtonIcon(theme) {
            const icon = document.getElementById('theme-icon');
            if(icon) {
                if (theme === 'dark') {
                    icon.className = 'fa-solid fa-sun';
                } else {
                    icon.className = 'fa-solid fa-moon';
                }
            }
        }

        const savedTheme = localStorage.getItem('theme') || 'light';
        document.documentElement.setAttribute('data-theme', savedTheme);

        window.onload = function() {
            updateThemeButtonIcon(savedTheme);
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
    <div class="page-header-flex">
        <div class="page-header">
            <h2>User Access Control Management</h2>
            <p>Manage registered identity credentials and assign access rights for authorized RFID keyholders.</p>
        </div>
        <button class="btn-theme-toggle" onclick="toggleTheme()">
            <i id="theme-icon" class="fa-solid fa-moon"></i>
        </button>
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
                        echo "<td style='color:#6b7280;'>#".$id."</td>";
                        echo "<td><code style='color: var(--text-uid); font-weight: 700; background: var(--bg-uid); padding: 4px 10px; border-radius: 6px; border: 1px solid var(--border-input); font-size:13px;'>".$row['card_uid']."</code></td>";
                        
                        echo "<td>
                                <span id='text-name-".$id."' style='color: var(--text-table-td);'>".htmlspecialchars($row['owner_name'])."</span>
                                <form id='form-edit-".$id."' method='POST' style='display:none;'>
                                    <input type='hidden' name='id' value='".$id."'>
                                    <input type='text' name='owner_name' value='".htmlspecialchars($row['owner_name'])."'>
                                    <button type='submit' name='update_user' class='btn-action btn-save'><i class='fa-solid fa-floppy-disk'></i> Save</button>
                                </form>
                              </td>";
                              
                        echo "<td style='color: var(--text-table-th); font-size:13px;'>".$row['created_at']."</td>";
                        
                        echo "<td>
                                <button id='btn-edit-trigger-".$id."' class='btn-action btn-edit' onclick='toggleEditForm(".$id.")'><i class='fa-solid fa-user-pen'></i> Edit Name</button>
                                <a href='users.php?delete=".$id."' class='btn-action btn-delete' onclick='return confirm(\"Are you absolutely sure you want to revoke access rights for this card?\")'><i class='fa-solid fa-trash-can'></i> Delete</a>
                              </td>";
                        echo "</tr>";
                    }
                } else {
                    echo "<tr><td colspan='5' style='text-align:center; color: #6b7280; padding: 30px;'>No registered user credentials found in the system registry.</td></tr>";
                }
                ?>
            </tbody>
        </table>
    </div>
</div>

</body>
</html>