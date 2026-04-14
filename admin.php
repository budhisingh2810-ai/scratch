<?php
session_start();
date_default_timezone_set("Asia/Kolkata");

/* ===== 1. SECURE ACCESS ===== */
$valid_users = [
    'gajju' => 'gajju2004',
    'superuser' => 'superpass'
];

/* ===== 2. LOGIN SYSTEM ===== */
if (isset($_POST['login'])) {
    $u = trim($_POST['username'] ?? '');
    $p = trim($_POST['password'] ?? '');

    if (isset($valid_users[$u]) && $valid_users[$u] === $p) {
        $_SESSION['admin_auth'] = $u;
        $_SESSION['user_sig'] = md5($_SERVER['HTTP_USER_AGENT']);
        header("Location: " . $_SERVER['PHP_SELF']);
        exit;
    } else {
        $error = "Access Denied: Incorrect Credentials";
    }
}

if (isset($_GET['logout'])) {
    session_destroy();
    header("Location: " . $_SERVER['PHP_SELF']);
    exit;
}

/* ===== 3. AUTH CHECK ===== */
$is_logged_in = isset($_SESSION['admin_auth']) && ($_SESSION['user_sig'] ?? '') === md5($_SERVER['HTTP_USER_AGENT']);

/* ===== 4. DIRECTORY & ENGINE ===== */
$folders = array_values(array_filter(glob('*'), 'is_dir'));
$selectedFolder = $_POST['folder'] ?? ($_SESSION['last_folder'] ?? ($folders[0] ?? "")); $_SESSION['last_folder'] = $selectedFolder;
$currentUpi = ""; 
$currentAmt = "";

if ($is_logged_in) {
    $targetFile = "$selectedFolder/index.html";

    // ACTION: LOAD VALUES
    if (isset($_POST['load']) && file_exists($targetFile)) {
        $content = file_get_contents($targetFile);
        preg_match('/window\.upiAddress\s*=\s*[\'"]([^\'"]+)[\'"]/', $content, $upiMatch);
        preg_match('/window\.paymentAmount\s*=\s*(\d+)/', $content, $amtMatch);
        $currentUpi = $upiMatch[1] ?? "";
        $currentAmt = $amtMatch[1] ?? "";
    }

    // ACTION: UPDATE SINGLE FOLDER
    if (isset($_POST['save'])) {
        $newUpi = $_POST['upi'];
        $newAmt = $_POST['amount'];

        if (file_exists($targetFile)) {
            $content = file_get_contents($targetFile);
            
            // Fix for UPI (Supports ' or " and optional ;)
            $content = preg_replace("/window\.upiAddress\s*=\s*['\"](.*?)['\"];?/", "window.upiAddress = '$newUpi';", $content);
            // Fix for Amount (Supports optional ;)
            $content = preg_replace("/window\.paymentAmount\s*=\s*\d+;?/", "window.paymentAmount = $newAmt;", $content);
            
            file_put_contents($targetFile, $content);
            $msg = "Success: $selectedFolder Updated";
            
            $log = date("d/m H:i") . " | " . $_SESSION['admin_auth'] . " | $selectedFolder | UPDATED\n";
            file_put_contents("admin_logs.txt", $log, FILE_APPEND);
        }
    }

    // ACTION: UPDATE ALL FOLDERS (GLOBAL SYNC)
    if (isset($_POST['update_all_upi'])) {
        $syncUpi = $_POST['upi'];
        $count = 0;
        foreach ($folders as $f) {
            $fPath = "$f/index.html";
            if (file_exists($fPath)) {
                $content = file_get_contents($fPath);
                $content = preg_replace("/window\.upiAddress\s*=\s*['\"](.*?)['\"];?/", "window.upiAddress = '$syncUpi';", $content);
                file_put_contents($fPath, $content);
                $count++;
            }
        }
        $msg = "Global Sync: $count Folders Updated!";
        $log = date("d/m H:i") . " | " . $_SESSION['admin_auth'] . " | ALL | SYNCED\n";
        file_put_contents("admin_logs.txt", $log, FILE_APPEND);
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>UPI Management Panel</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;600;700;800&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Plus Jakarta Sans', sans-serif; background-color: #f9fafb; -webkit-tap-highlight-color: transparent; }
        .glass-card { background: white; border-radius: 2.5rem; border: 1px solid #f1f5f9; box-shadow: 0 10px 25px -5px rgba(0,0,0,0.05); }
        select, input { -webkit-appearance: none; outline: none; }
        .custom-scroll::-webkit-scrollbar { width: 4px; }
        .custom-scroll::-webkit-scrollbar-thumb { background: #e2e8f0; border-radius: 10px; }
    </style>
</head>
<body class="min-h-screen">

<?php if (!$is_logged_in): ?>
    <div class="flex items-center justify-center min-h-screen p-6">
        <div class="w-full max-w-sm glass-card p-10 text-center">
            <div class="w-20 h-20 bg-indigo-600 rounded-3xl mx-auto mb-6 flex items-center justify-center shadow-xl shadow-indigo-100">
                <svg class="w-10 h-10 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path></svg>
            </div>
            <h1 class="text-3xl font-extrabold text-slate-900 tracking-tight mb-2">Login</h1>
            <p class="text-slate-400 text-sm mb-8">Secure Access Portal</p>
            
            <?php if (isset($error)): ?>
                <div class="bg-rose-50 text-rose-500 p-4 rounded-2xl text-[11px] font-bold mb-6 border border-rose-100"><?php echo $error; ?></div>
            <?php endif; ?>

            <form method="POST" class="space-y-4">
                <input type="text" name="username" placeholder="Username" required autocomplete="off" class="w-full bg-slate-50 p-5 rounded-2xl font-bold ring-1 ring-slate-200 focus:ring-2 focus:ring-indigo-600 transition-all">
                <input type="password" name="password" placeholder="Password" required class="w-full bg-slate-50 p-5 rounded-2xl font-bold ring-1 ring-slate-200 focus:ring-2 focus:ring-indigo-600 transition-all">
                <button name="login" class="w-full bg-indigo-600 text-white font-black py-5 rounded-3xl shadow-lg shadow-indigo-100 active:scale-95 transition-all">SIGN IN</button>
            </form>
        </div>
    </div>
<?php else: ?>
    <div class="max-w-md mx-auto p-6 flex flex-col min-h-screen">
        
        <header class="flex justify-between items-center py-6">
            <div>
                <h2 class="text-2xl font-extrabold text-slate-900 tracking-tighter italic">UPI.CONTROL</h2>
                <div class="flex items-center gap-1.5">
                    <span class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></span>
                    <span class="text-[10px] font-bold text-slate-400 uppercase tracking-widest"><?php echo $_SESSION['admin_auth']; ?></span>
                </div>
            </div>
            <a href="?logout=1" class="bg-white border border-slate-100 px-5 py-2.5 rounded-full text-[10px] font-black text-slate-400 shadow-sm uppercase">Logout</a>
        </header>

        <div class="flex-1 space-y-6">
            <?php if (isset($msg)): ?>
                <div class="bg-indigo-600 text-white p-4 rounded-[2rem] text-center font-bold text-xs shadow-xl animate-bounce"><?php echo $msg; ?></div>
            <?php endif; ?>

            <form method="POST" class="glass-card p-8 space-y-8 border-t-[6px] border-t-indigo-600">
                <div>
                    <label class="text-[10px] font-black text-slate-300 uppercase tracking-[0.2em] mb-3 block px-1">Active Folder</label>
                    <div class="flex gap-2">
                        <select name="folder" class="flex-1 bg-slate-50 p-4 rounded-2xl font-bold text-slate-800 ring-1 ring-slate-100">
                            <?php foreach($folders as $f): ?>
                                <option value="<?php echo $f; ?>" <?php echo ($f==$selectedFolder) ? "selected" : ""; ?>><?php echo $f; ?></option>
                            <?php endforeach; ?>
                        </select>
                        <button name="load" class="bg-slate-900 text-white px-6 rounded-2xl font-bold active:scale-90 transition-all text-xs">LOAD</button>
                    </div>
                </div>

                <div class="space-y-5">
                    <div class="bg-slate-50 p-6 rounded-[2rem] ring-1 ring-slate-100 transition-all focus-within:ring-2 focus-within:ring-indigo-600 focus-within:bg-white">
                        <label class="text-[9px] font-black text-slate-400 uppercase tracking-widest block mb-1">UPI Address</label>
                        <input name="upi" id="upi_box" value="<?php echo htmlspecialchars($currentUpi); ?>" placeholder="user@bank" class="w-full bg-transparent font-bold text-slate-900 text-lg outline-none">
                        <div id="upi_hint" class="text-[9px] font-bold text-indigo-400 uppercase mt-1 h-2 tracking-tighter"></div>
                    </div>
                    
                    <div class="bg-slate-50 p-6 rounded-[2rem] ring-1 ring-slate-100 transition-all focus-within:ring-2 focus-within:ring-indigo-600 focus-within:bg-white">
                        <label class="text-[9px] font-black text-slate-400 uppercase tracking-widest block mb-1">Set Amount (₹)</label>
                        <input name="amount" value="<?php echo htmlspecialchars($currentAmt); ?>" placeholder="0" class="w-full bg-transparent font-black text-4xl text-slate-900 outline-none">
                    </div>
                </div>

                <div class="space-y-3 pt-4">
                    <button name="save" class="w-full bg-indigo-600 text-white font-black py-6 rounded-[2rem] shadow-xl shadow-indigo-100 active:scale-95 transition-all text-sm tracking-widest">UPDATE SINGLE</button>
                    
                    <button name="update_all_upi" onclick="return confirm('⚠️ GLOBAL WARNING: This will overwrite the UPI Address in EVERY folder. Continue?')" class="w-full bg-white text-rose-500 border border-rose-100 font-bold py-3 rounded-2xl text-[9px] uppercase tracking-widest active:bg-rose-50 transition-all">
                        Sync UPI Global
                    </button>
                </div>
            </form>

            <div class="glass-card p-6 mb-10">
                <p class="text-[10px] font-black text-slate-300 uppercase tracking-widest mb-4 border-b border-slate-50 pb-2">Activity History</p>
                <div class="space-y-2 max-h-40 overflow-y-auto custom-scroll pr-2">
                    <?php
                    if (file_exists("admin_logs.txt")) {
                        $logLines = array_reverse(explode("\n", trim(file_get_contents("admin_logs.txt"))));
                        foreach(array_slice($logLines, 0, 8) as $line) {
                            echo "<div class='text-[10px] font-bold text-slate-500 bg-slate-50 p-3 rounded-xl border-l-4 border-indigo-400 truncate'>" . htmlspecialchars($line) . "</div>";
                        }
                    } else {
                        echo "<p class='text-[10px] text-slate-300 italic'>No logs recorded yet.</p>";
                    }
                    ?>
                </div>
            </div>
        </div>
    </div>

    <script>
        const upiInput = document.getElementById('upi_box');
        const upiHint = document.getElementById('upi_hint');
        
        function updateHint() {
            if(upiInput.value.includes('@')) {
                const parts = upiInput.value.split('@');
                upiHint.innerText = "Handle: " + parts[0];
            } else {
                upiHint.innerText = "";
            }
        }

        upiInput.addEventListener('input', updateHint);
        updateHint();
    </script>
<?php endif; ?>

</body>
</html>