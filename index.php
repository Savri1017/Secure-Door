<?php
require_once 'config.php';

// --- LOGIKA PEMROSESAN DATA ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['toggle_siaga'])) {
        $conn->query("UPDATE system_status SET mode_siaga = NOT mode_siaga WHERE id = 1");
    }
    if (isset($_POST['toggle_registrasi'])) {
        $conn->query("UPDATE system_status SET registration_mode = NOT registration_mode WHERE id = 1");
    }
    if (isset($_POST['update_card'])) {
        $card_id = intval($_POST['card_id']);
        $owner_name = $conn->real_escape_string($_POST['owner_name']);
        $conn->query("UPDATE cards SET owner_name = '$owner_name' WHERE id = $card_id");
    }
    if (isset($_POST['delete_card'])) {
        $card_id = intval($_POST['card_id']);
        $conn->query("DELETE FROM cards WHERE id = $card_id");
    }
    header("Location: index.php");
    exit();
}

// --- AMBIL DATA DARI DATABASE ---
$statusRes = $conn->query("SELECT * FROM system_status WHERE id = 1");
$status = $statusRes->fetch_assoc();
$modeSiaga = $status['mode_siaga'] ?? 1;
$regMode = $status['registration_mode'] ?? 0;

$logsQuery = $conn->query("SELECT * FROM security_logs ORDER BY created_at DESC LIMIT 50");
$cardsQuery = $conn->query("SELECT * FROM cards ORDER BY id DESC");

$totalLogs = $conn->query("SELECT COUNT(*) as total FROM security_logs")->fetch_assoc()['total'];
$totalCards = $conn->query("SELECT COUNT(*) as total FROM cards")->fetch_assoc()['total'];
$totalMaling = $conn->query("SELECT COUNT(*) as total FROM security_logs WHERE type='MALING'")->fetch_assoc()['total'];
$totalTapValid = $conn->query("SELECT COUNT(*) as total FROM security_logs WHERE type='PEMILIK'")->fetch_assoc()['total'];
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>doorSecure Pro - Dashboard Control</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://unpkg.com/aos@next/dist/aos.css" />
    <style>
        body { 
            font-family: 'Plus Jakarta Sans', sans-serif; 
            background-color: #F0F4F8; 
            background-image: radial-gradient(at 0% 0%, rgba(59, 130, 246, 0.05) 0px, transparent 50%);
        }
        .sidebar { background: #FFFFFF; border-right: 1px solid #E2E8F0; }
        .main-card { 
            background: rgba(255, 255, 255, 0.9); 
            backdrop-filter: blur(8px); 
            border: 1px solid rgba(255, 255, 255, 1); 
            border-radius: 24px; 
            box-shadow: 0 4px 20px -5px rgba(0,0,0,0.05); 
        }
        .tab-content { display: none; }
        .tab-content.active { display: block; animation: fadeIn 0.4s ease-out; }
        @keyframes fadeIn { from { opacity: 0; transform: translateY(5px); } to { opacity: 1; transform: translateY(0); } }
    </style>
</head>
<body class="text-slate-700 min-h-screen flex antialiased">

    <aside class="hidden lg:flex flex-col w-72 h-screen sticky top-0 sidebar p-8 shadow-sm">
        <div class="flex items-center gap-3 mb-12">
            <div class="p-2.5 bg-blue-600 rounded-xl text-white shadow-lg shadow-blue-500/30">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 002-2V9a2 2 0 00-2-2h-1V5a5 5 0 00-10 0v2H7a2 2 0 00-2 2v2a2 2 0 002 2h-1a2 2 0 00-2 2v6a2 2 0 002 2z" /></svg>
            </div>
            <h1 class="text-xl font-extrabold text-slate-900 tracking-tight">doorSecure<span class="text-blue-600">.</span></h1>
        </div>

        <nav class="space-y-2 flex-1">
            <button onclick="switchTab('dashboard')" id="nav-dashboard" class="w-full flex items-center gap-3 px-4 py-3.5 rounded-2xl text-sm font-bold transition-all bg-blue-50 text-blue-600">
                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z" /></svg>
                Dashboard Ringkasan
            </button>
            <button onclick="switchTab('pengguna')" id="nav-pengguna" class="w-full flex items-center gap-3 px-4 py-3.5 rounded-2xl text-sm font-bold transition-all text-slate-400 hover:bg-slate-50 hover:text-slate-900">
                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" /></svg>
                Otoritas Akses
            </button>
        </nav>

        <div class="pt-6 border-t border-slate-100 text-[11px] text-slate-400">
            System Status Monitoring v2.1<br>© 2026 doorSecure Lab.
        </div>
    </aside>

    <main class="flex-1 p-6 lg:p-10">
        
        <div class="flex flex-col md:flex-row justify-between items-start md:items-center mb-10 gap-6">
            <div>
                <h2 id="page-title" class="text-2xl font-extrabold text-slate-900 tracking-tight">Intelligence Hub</h2>
                <p id="page-subtitle" class="text-sm text-slate-400 mt-1">Pemantauan keamanan perimeter secara real-time.</p>
            </div>

            <div class="flex items-center gap-4 bg-white p-2 pr-5 rounded-full border border-slate-200 shadow-sm">
                <div id="realtime-clock" class="bg-blue-600 text-white px-4 py-1.5 rounded-full text-xs font-bold tracking-wider shadow-md shadow-blue-500/20">
                    00:00:00
                </div>
                <div id="realtime-date" class="text-[10px] font-bold text-slate-500 uppercase tracking-widest">
                    -- --- ----
                </div>
            </div>
        </div>

        <div id="tab-dashboard" class="tab-content active">
            <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-10">
                <div class="main-card p-6 flex flex-col justify-between group transition-all hover:border-blue-300">
                    <span class="text-[10px] font-extrabold text-slate-400 uppercase tracking-widest">Akses Valid</span>
                    <div class="flex items-end justify-between mt-4">
                        <span class="text-4xl font-extrabold text-slate-900"><?php echo $totalCards; ?></span>
                        <div class="text-blue-600 bg-blue-50 p-2 rounded-xl border border-blue-100">
                             <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" /></svg>
                        </div>
                    </div>
                </div>

                <div class="main-card p-6 flex flex-col justify-between border-l-4 border-l-orange-500">
                    <span class="text-[10px] font-extrabold text-slate-400 uppercase tracking-widest">Akses Ditolak</span>
                    <div class="flex items-end justify-between mt-4">
                        <span class="text-4xl font-extrabold text-orange-600"><?php echo $totalMaling; ?></span>
                        <div class="text-orange-600 bg-orange-50 p-2 rounded-xl border border-orange-100 <?php echo $totalMaling > 0 ? 'animate-pulse' : ''; ?>">
                             <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" /></svg>
                        </div>
                    </div>
                </div>

                <div class="main-card p-6 flex flex-col justify-between">
                    <span class="text-[10px] font-extrabold text-slate-400 uppercase tracking-widest">Aktivitas Tap</span>
                    <div class="flex items-end justify-between mt-4">
                        <span class="text-4xl font-extrabold text-slate-900"><?php echo $totalTapValid; ?></span>
                        <div class="text-slate-600 bg-slate-50 p-2 rounded-xl border border-slate-100">
                             <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" /></svg>
                        </div>
                    </div>
                </div>

                <div class="main-card p-5 <?php echo $modeSiaga ? 'bg-blue-600' : 'bg-slate-800'; ?> text-white flex flex-col gap-3 border-none transition-all duration-500 shadow-xl shadow-blue-900/10">
                    <div class="flex items-center gap-2 mb-1 bg-black/10 p-2 rounded-xl">
                        <div class="relative flex h-3 w-3">
                            <?php if ($modeSiaga): ?>
                                <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-blue-400 opacity-75"></span>
                                <span class="relative inline-flex rounded-full h-3 w-3 bg-blue-300"></span>
                            <?php else: ?>
                                <span class="relative inline-flex rounded-full h-3 w-3 bg-slate-500"></span>
                            <?php endif; ?>
                        </div>
                        <span class="text-[10px] font-bold tracking-widest uppercase">
                            Sistem: <?php echo $modeSiaga ? 'ONLINE' : 'OFFLINE'; ?>
                        </span>
                    </div>

                    <form method="POST">
                        <input type="hidden" name="toggle_siaga" value="1">
                        <button type="submit" class="w-full py-2.5 rounded-xl text-[10px] font-extrabold uppercase tracking-widest border border-white/20 hover:bg-white/10 transition-all flex items-center justify-center gap-2">
                             <?php echo $modeSiaga ? 'Matikan Keamanan' : 'Aktifkan Keamanan'; ?>
                        </button>
                    </form>

                    <form method="POST">
                        <input type="hidden" name="toggle_registrasi" value="1">
                        <button type="submit" class="w-full py-2.5 rounded-xl text-[10px] font-extrabold uppercase tracking-widest bg-white text-slate-900 hover:shadow-lg transition-all flex items-center justify-center gap-2">
                             <?php echo $regMode ? 'Batalkan Scan' : 'Tambah Kartu'; ?>
                        </button>
                    </form>
                </div>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
                <div class="lg:col-span-2 main-card overflow-hidden">
                    <div class="p-6 border-b border-slate-100 flex justify-between items-center">
                        <h3 class="font-bold text-slate-900 tracking-tight">System Feed Logs</h3>
                        <span class="px-3 py-1 bg-blue-50 text-blue-600 rounded-full text-[9px] font-extrabold uppercase tracking-tighter border border-blue-100">Live Updating</span>
                    </div>
                    <div class="p-6 overflow-y-auto max-h-[500px] space-y-3">
                        <?php if ($logsQuery->num_rows > 0): while ($log = $logsQuery->fetch_assoc()): 
                            $isWarning = $log['type'] === 'MALING';
                        ?>
                            <div class="flex items-center gap-4 p-4 rounded-2xl transition-all border <?php echo $isWarning ? 'bg-orange-50/40 border-orange-100' : 'bg-slate-50/50 border-slate-50 hover:border-blue-200'; ?>">
                                <div class="text-[10px] font-bold text-slate-400 shrink-0 bg-white px-2 py-1 rounded-lg border border-slate-100">
                                    <?php echo date('H:i', strtotime($log['created_at'])); ?>
                                </div>
                                <div class="flex-1">
                                    <p class="text-xs font-bold <?php echo $isWarning ? 'text-orange-800' : 'text-slate-800'; ?>">
                                        <?php echo htmlspecialchars($log['description']); ?>
                                    </p>
                                </div>
                                <span class="text-[8px] font-extrabold uppercase px-2 py-1 rounded-md <?php echo $isWarning ? 'bg-orange-600 text-white shadow-md shadow-orange-500/20' : 'bg-blue-600 text-white shadow-md shadow-blue-500/20'; ?>">
                                    <?php echo $log['type']; ?>
                                </span>
                            </div>
                        <?php endwhile; else: echo '<p class="text-center text-slate-400 py-20 text-sm italic">Belum ada aktivitas terdeteksi.</p>'; endif; ?>
                    </div>
                </div>

                <div class="main-card p-6 flex flex-col">
                    <h3 class="font-bold text-slate-900 tracking-tight mb-6">Verified Access</h3>
                    <div class="space-y-4 flex-1">
                        <?php
                        $recentTap = $conn->query("SELECT * FROM security_logs WHERE type='PEMILIK' ORDER BY created_at DESC LIMIT 8");
                        if ($recentTap->num_rows > 0): while($rtap = $recentTap->fetch_assoc()):
                            $name = 'User';
                            if (preg_match('/\[(.*?)\]/', $rtap['description'], $matches)) $name = $matches[1];
                        ?>
                            <div class="flex items-center gap-3 p-3 bg-slate-50 rounded-2xl border border-slate-100">
                                <div class="w-9 h-9 rounded-xl bg-blue-600 flex items-center justify-center text-white font-bold text-xs shadow-sm">
                                    <?php echo substr($name, 0, 1); ?>
                                </div>
                                <div class="flex-1 min-w-0">
                                    <p class="text-xs font-bold text-slate-900 truncate"><?php echo htmlspecialchars($name); ?></p>
                                    <p class="text-[9px] text-slate-400 font-medium">RFID Authenticated</p>
                                </div>
                                <div class="text-[9px] font-bold text-slate-400">
                                    <?php echo date('H:i', strtotime($rtap['created_at'])); ?>
                                </div>
                            </div>
                        <?php endwhile; else: echo '<p class="text-slate-400 text-xs italic py-10 text-center">Belum ada pemilik rumah terdeteksi.</p>'; endif; ?>
                    </div>
                </div>
            </div>
        </div>

        <div id="tab-pengguna" class="tab-content">
            <div class="main-card p-8">
                <div class="mb-8">
                    <h3 class="text-xl font-bold text-slate-900">Database Token RFID</h3>
                    <p class="text-sm text-slate-400">Kelola identitas kartu yang memiliki izin masuk.</p>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-6">
                    <?php if ($cardsQuery->num_rows > 0): while ($card = $cardsQuery->fetch_assoc()): ?>
                        <div class="p-6 bg-slate-50 rounded-3xl border border-slate-200/60 hover:border-blue-500 transition-all group relative">
                            <div class="flex items-center gap-3 mb-6">
                                <div class="p-2.5 bg-white rounded-xl shadow-sm text-blue-600 border border-slate-100">
                                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z" /></svg>
                                </div>
                                <span class="text-[10px] font-extrabold text-slate-400 uppercase tracking-widest">UID: <?php echo $card['uid']; ?></span>
                            </div>

                            <form method="POST" class="space-y-3">
                                <input type="hidden" name="update_card" value="1">
                                <input type="hidden" name="card_id" value="<?php echo $card['id']; ?>">
                                <input type="text" name="owner_name" value="<?php echo htmlspecialchars($card['owner_name']); ?>" class="w-full bg-white border border-slate-200 rounded-2xl px-4 py-3 text-sm font-bold text-slate-900 focus:border-blue-500 focus:outline-none transition-all">
                                <div class="flex gap-2">
                                    <button type="submit" class="flex-1 bg-slate-900 text-white text-[10px] font-extrabold uppercase tracking-widest py-3 rounded-xl hover:bg-blue-600 transition-all">Simpan</button>
                                    <button type="submit" form="del-<?php echo $card['id']; ?>" class="p-3 bg-white border border-slate-200 text-slate-400 hover:text-rose-600 rounded-xl transition-all">
                                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-4v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" /></svg>
                                    </button>
                                </div>
                            </form>
                            <form id="del-<?php echo $card['id']; ?>" method="POST" onsubmit="return confirm('Hapus akses kartu ini?');">
                                <input type="hidden" name="delete_card" value="1">
                                <input type="hidden" name="card_id" value="<?php echo $card['id']; ?>">
                            </form>
                        </div>
                    <?php endwhile; else: echo '<p class="col-span-full text-center py-20 text-slate-400 text-sm italic">Belum ada kartu terdaftar.</p>'; endif; ?>
                </div>
            </div>
        </div>

    </main>

    <script src="https://unpkg.com/aos@next/dist/aos.js"></script>
    <script>
        AOS.init({ duration: 500, once: true });

        function startRealtimeClock() {
            const now = new Date();
            document.getElementById('realtime-clock').textContent = now.toLocaleTimeString('id-ID', { hour12: false });
            document.getElementById('realtime-date').textContent = now.toLocaleDateString('id-ID', { day: 'numeric', month: 'short', year: 'numeric' });
        }
        setInterval(startRealtimeClock, 1000); startRealtimeClock();

        function switchTab(tab) {
            document.querySelectorAll('.tab-content').forEach(el => el.classList.remove('active'));
            document.getElementById('tab-' + tab).classList.add('active');
            
            const navs = ['nav-dashboard', 'nav-pengguna'];
            navs.forEach(id => {
                const el = document.getElementById(id);
                el.classList.remove('bg-blue-50', 'text-blue-600');
                el.classList.add('text-slate-400', 'hover:bg-slate-50');
            });
            const activeNav = document.getElementById('nav-' + tab);
            activeNav.classList.remove('text-slate-400', 'hover:bg-slate-50');
            activeNav.classList.add('bg-blue-50', 'text-blue-600');

            document.getElementById('page-title').textContent = tab === 'dashboard' ? 'Intelligence Hub' : 'Access Token Management';
            document.getElementById('page-subtitle').textContent = tab === 'dashboard' ? 'Pemantauan keamanan perimeter secara real-time.' : 'Kelola identitas dan izin akses kartu RFID.';
        }
    </script>
</body>
</html>