<?php
// Start session for secure login handling
session_start();

// 1. Dynamic Timezone Handler via Cookie / Fallback
$userTimezone = 'UTC';
if (isset($_COOKIE['user_timezone']) && !empty($_COOKIE['user_timezone'])) {
    $userTimezone = $_COOKIE['user_timezone'];
}
@date_default_timezone_set($userTimezone);

// 2. Handle Login / Logout Actions
$errorMsg = '';
if (isset($_POST['action']) && $_POST['action'] === 'login') {
    $username = isset($_POST['username']) ? trim($_POST['username']) : '';
    $password = isset($_POST['password']) ? trim($_POST['password']) : '';

    // Hardcoded credentials for demonstration (admin / admin123)
    if ($username === 'admin' && $password === 'admin123') {
        $_SESSION['logged_in'] = true;
        $_SESSION['username'] = $username;
        $_SESSION['login_time'] = date('Y-m-d H:i:s');
        header('Location: /');
        exit;
    } else {
        $errorMsg = 'Invalid username or password.';
    }
}

if (isset($_GET['action']) && $_GET['action'] === 'logout') {
    session_destroy();
    header('Location: /');
    exit;
}

$isLoggedIn = isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true;

// 3. System Diagnostics & MySQL Connection Test
$dbStatus = 'Disconnected';
$dbColor = 'bg-red-500';
try {
    // Connects to the Docker MySQL service container ('mysql')
    $pdo = new PDO("mysql:host=mysql;dbname=app_db;charset=utf8", "app_user", "app_password", array(
        PDO::ATTR_TIMEOUT => 2,
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
    ));
    $dbStatus = 'Connected (Online)';
    $dbColor = 'bg-emerald-500';
} catch (Exception $e) {
    $dbStatus = 'Offline / Starting...';
    $dbColor = 'bg-amber-500';
}

$uptime = @shell_exec("uptime -p 2>/dev/null");
if (!$uptime) {
    $uptime = "Container Active";
} else {
    $uptime = trim($uptime);
}

$phpVersion = PHP_VERSION;
$memoryUsage = round(memory_get_usage(true) / 1024 / 1024, 2) . ' MB';
$serverOS = PHP_OS;
$currentTime = date('Y-m-d H:i:s T');
?>
<!DOCTYPE html>
<html lang="en" class="h-full bg-slate-950">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>DevPulse Admin Control Center</title>
    <!-- Tailwind CSS CDN -->
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script>
      // Detect browser timezone and set cookie if missing
      const tz = Intl.DateTimeFormat().resolvedOptions().timeZone;
      function getCookie(name) {
        const value = `; ${document.cookie}`;
        const parts = value.split(`; ${name}=`);
        if (parts.length === 2) return parts.pop().split(';').shift();
      }
      if (tz && getCookie('user_timezone') !== tz) {
        document.cookie = "user_timezone=" + tz + "; path=/; max-age=31536000; SameSite=Lax";
        window.location.reload();
      }
    </script>
</head>
<body class="h-full text-slate-100 font-sans antialiased selection:bg-indigo-500 selection:text-white">

<?php if (!$isLoggedIn): ?>
    <!-- LOGIN VIEW -->
    <div class="min-h-full flex flex-col justify-center py-12 sm:px-6 lg:px-8">
        <div class="sm:mx-auto sm:w-full sm:max-w-md">
            <div class="flex justify-center">
                <div class="h-12 w-12 rounded-xl bg-indigo-600 flex items-center justify-center shadow-lg shadow-indigo-500/30">
                    <i class="fa-solid fa-shield-halved text-2xl text-white"></i>
                </div>
            </div>
            <h2 class="mt-6 text-center text-3xl font-extrabold tracking-tight text-white">DevPulse Control</h2>
            <p class="mt-2 text-center text-sm text-slate-400">Sign in to access your multi-PHP infrastructure</p>
        </div>

        <div class="mt-8 sm:mx-auto sm:w-full sm:max-w-md">
            <div class="bg-slate-900 py-8 px-4 shadow-xl sm:rounded-2xl sm:px-10 border border-slate-800">
                <?php if (!empty($errorMsg)): ?>
                    <div class="mb-4 bg-red-950/50 border border-red-800 text-red-300 px-4 py-3 rounded-lg text-sm flex items-center gap-2">
                        <i class="fa-solid fa-triangle-exclamation"></i>
                        <span><?php echo htmlspecialchars($errorMsg, ENT_QUOTES, 'UTF-8'); ?></span>
                    </div>
                <?php endif; ?>

                <form class="space-y-6" action="/" method="POST">
                    <input type="hidden" name="action" value="login">
                    <div>
                        <label class="block text-sm font-medium text-slate-300">Username</label>
                        <div class="mt-1">
                            <input type="text" name="username" required value="admin" class="appearance-none block w-full px-3 py-2 border border-slate-700 rounded-lg shadow-sm placeholder-slate-500 bg-slate-950 text-white focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
                        </div>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-slate-300">Password</label>
                        <div class="mt-1">
                            <input type="password" name="password" required value="admin123" class="appearance-none block w-full px-3 py-2 border border-slate-700 rounded-lg shadow-sm placeholder-slate-500 bg-slate-950 text-white focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
                        </div>
                    </div>

                    <div class="flex items-center justify-between text-xs text-slate-400 bg-slate-950/50 p-3 rounded-lg border border-slate-800">
                        <span>Default User: <strong>admin</strong></span>
                        <span>Pass: <strong>admin123</strong></span>
                    </div>

                    <div>
                        <button type="submit" class="w-full flex justify-center py-2.5 px-4 border border-transparent rounded-lg shadow-sm text-sm font-medium text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 transition-all">
                            Sign In to Dashboard
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

<?php else: ?>
    <!-- ADMIN DASHBOARD VIEW -->
    <div class="min-h-full flex">
        <!-- Sidebar -->
        <div class="hidden md:flex md:w-64 md:flex-col border-r border-slate-800 bg-slate-900/60 backdrop-blur">
            <div class="flex flex-col flex-grow pt-5 pb-4 overflow-y-auto">
                <div class="flex items-center flex-shrink-0 px-4 space-x-3">
                    <div class="h-9 w-9 rounded-lg bg-indigo-600 flex items-center justify-center shadow-md shadow-indigo-500/20">
                        <i class="fa-solid fa-server text-white"></i>
                    </div>
                    <span class="font-bold text-lg text-white tracking-wide">DevPulse <span class="text-indigo-400 text-xs uppercase px-1.5 py-0.5 bg-indigo-950 rounded border border-indigo-800">Docker</span></span>
                </div>
                <div class="mt-8 flex-grow flex flex-col">
                    <nav class="flex-1 px-3 space-y-1">
                        <a href="#" class="bg-indigo-600/10 text-indigo-400 group flex items-center px-3 py-2.5 text-sm font-medium rounded-lg border border-indigo-500/20">
                            <i class="fa-solid fa-chart-pie mr-3 text-indigo-400"></i> Dashboard
                        </a>
                        <a href="#" class="text-slate-400 hover:bg-slate-800 hover:text-white group flex items-center px-3 py-2.5 text-sm font-medium rounded-lg transition-colors">
                            <i class="fa-solid fa-database mr-3 text-slate-500"></i> MySQL Database
                        </a>
                        <a href="#" class="text-slate-400 hover:bg-slate-800 hover:text-white group flex items-center px-3 py-2.5 text-sm font-medium rounded-lg transition-colors">
                            <i class="fa-solid fa-terminal mr-3 text-slate-500"></i> PHP Containers
                        </a>
                        <a href="#" class="text-slate-400 hover:bg-slate-800 hover:text-white group flex items-center px-3 py-2.5 text-sm font-medium rounded-lg transition-colors">
                            <i class="fa-solid fa-gear mr-3 text-slate-500"></i> Settings
                        </a>
                    </nav>
                </div>
                <div class="flex-shrink-0 p-4 border-t border-slate-800">
                    <a href="?action=logout" class="flex items-center w-full px-3 py-2.5 text-sm font-medium text-red-400 hover:bg-red-950/40 rounded-lg transition-colors">
                        <i class="fa-solid fa-right-from-bracket mr-3"></i> Sign Out
                    </a>
                </div>
            </div>
        </div>

        <!-- Main Content Area -->
        <div class="flex flex-col flex-1 overflow-y-auto">
            <!-- Top Navbar -->
            <div class="sticky top-0 z-10 flex-shrink-0 flex h-16 bg-slate-900/80 backdrop-blur border-b border-slate-800 px-6 items-center justify-between">
                <div class="flex items-center space-x-3">
                    <span class="text-sm font-semibold text-slate-400">Active Environment:</span>
                    <span class="px-2.5 py-1 bg-emerald-500/10 text-emerald-400 border border-emerald-500/20 rounded-md text-xs font-mono font-medium">PHP <?php echo htmlspecialchars($phpVersion, ENT_QUOTES, 'UTF-8'); ?></span>
                </div>
                <div class="flex items-center space-x-4">
                    <div class="text-right hidden sm:block">
                        <div class="text-xs font-semibold text-white"><?php echo htmlspecialchars($_SESSION['username'], ENT_QUOTES, 'UTF-8'); ?></div>
                        <div class="text-[10px] text-slate-400"><?php echo htmlspecialchars($currentTime, ENT_QUOTES, 'UTF-8'); ?></div>
                    </div>
                    <a href="?action=logout" class="md:hidden text-red-400 p-2"><i class="fa-solid fa-right-from-bracket"></i></a>
                </div>
            </div>

            <!-- Page Content -->
            <main class="flex-1 p-6 lg:p-8 space-y-6">
                <!-- Welcome Banner -->
                <div class="bg-gradient-to-r from-indigo-900/40 via-slate-900 to-slate-900 border border-indigo-500/20 rounded-2xl p-6 shadow-xl">
                    <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
                        <div>
                            <h1 class="text-2xl font-bold text-white">System Operations Center</h1>
                            <p class="text-sm text-slate-400 mt-1">Docker multi-version environment running with active MySQL linkage and cookie session management.</p>
                        </div>
                        <div class="flex items-center space-x-2">
                            <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium bg-indigo-500/10 text-indigo-300 border border-indigo-500/20">
                                <span class="w-2 h-2 mr-1.5 rounded-full bg-indigo-400 animate-pulse"></span>
                                Session Active
                            </span>
                        </div>
                    </div>
                </div>

                <!-- Stats Grid -->
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                    <!-- PHP Version -->
                    <div class="bg-slate-900 border border-slate-800 rounded-xl p-5 shadow-sm">
                        <div class="flex items-center justify-between">
                            <span class="text-xs font-medium uppercase tracking-wider text-slate-400">PHP Core</span>
                            <i class="fa-brands fa-php text-indigo-400 text-lg"></i>
                        </div>
                        <div class="mt-3 flex items-baseline justify-between">
                            <div class="text-xl font-bold text-white"><?php echo htmlspecialchars($phpVersion, ENT_QUOTES, 'UTF-8'); ?></div>
                            <span class="text-xs text-emerald-400 font-mono">CLI Server</span>
                        </div>
                    </div>

                    <!-- MySQL Status -->
                    <div class="bg-slate-900 border border-slate-800 rounded-xl p-5 shadow-sm">
                        <div class="flex items-center justify-between">
                            <span class="text-xs font-medium uppercase tracking-wider text-slate-400">Database</span>
                            <i class="fa-solid fa-database text-indigo-400 text-lg"></i>
                        </div>
                        <div class="mt-3 flex items-baseline justify-between">
                            <div class="text-sm font-bold text-white flex items-center gap-2">
                                <span class="w-2.5 h-2.5 rounded-full <?php echo $dbColor; ?>"></span>
                                <?php echo htmlspecialchars($dbStatus, ENT_QUOTES, 'UTF-8'); ?>
                            </div>
                        </div>
                    </div>

                    <!-- Memory Usage -->
                    <div class="bg-slate-900 border border-slate-800 rounded-xl p-5 shadow-sm">
                        <div class="flex items-center justify-between">
                            <span class="text-xs font-medium uppercase tracking-wider text-slate-400">Memory Load</span>
                            <i class="fa-solid fa-memory text-indigo-400 text-lg"></i>
                        </div>
                        <div class="mt-3 flex items-baseline justify-between">
                            <div class="text-xl font-bold text-white"><?php echo htmlspecialchars($memoryUsage, ENT_QUOTES, 'UTF-8'); ?></div>
                            <span class="text-xs text-slate-400">Allocated</span>
                        </div>
                    </div>

                    <!-- OS / Uptime -->
                    <div class="bg-slate-900 border border-slate-800 rounded-xl p-5 shadow-sm">
                        <div class="flex items-center justify-between">
                            <span class="text-xs font-medium uppercase tracking-wider text-slate-400">System OS</span>
                            <i class="fa-solid fa-linux text-indigo-400 text-lg"></i>
                        </div>
                        <div class="mt-3 flex items-baseline justify-between">
                            <div class="text-sm font-bold text-white truncate max-w-[150px]"><?php echo htmlspecialchars($serverOS, ENT_QUOTES, 'UTF-8'); ?></div>
                            <span class="text-xs text-slate-400 font-mono">Docker</span>
                        </div>
                    </div>
                </div>

                <!-- Docker Ports Overview Table -->
                <div class="bg-slate-900 border border-slate-800 rounded-xl overflow-hidden shadow-sm">
                    <div class="px-6 py-4 border-b border-slate-800 flex justify-between items-center">
                        <h3 class="text-base font-semibold text-white"><i class="fa-solid fa-network-wired mr-2 text-indigo-400"></i>Active Docker Endpoints</h3>
                        <span class="text-xs text-slate-400">Local Ports 8080 - 8083</span>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-slate-800 text-sm">
                            <thead class="bg-slate-950/50 text-slate-400 text-left">
                                <tr>
                                    <th class="px-6 py-3 font-medium">Container Name</th>
                                    <th class="px-6 py-3 font-medium">Port</th>
                                    <th class="px-6 py-3 font-medium">PHP Engine</th>
                                    <th class="px-6 py-3 font-medium">Access Link</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-800 text-slate-300">
                                <tr>
                                    <td class="px-6 py-4 font-mono text-xs">php_5_6</td>
                                    <td class="px-6 py-4 font-mono text-indigo-400">8080</td>
                                    <td class="px-6 py-4">PHP 5.6</td>
                                    <td class="px-6 py-4"><a href="http://localhost:8080" target="_blank" class="text-indigo-400 hover:underline">http://localhost:8080 <i class="fa-solid fa-arrow-up-right-from-square text-[10px]"></i></a></td>
                                </tr>
                                <tr>
                                    <td class="px-6 py-4 font-mono text-xs">php_8_4</td>
                                    <td class="px-6 py-4 font-mono text-indigo-400">8081</td>
                                    <td class="px-6 py-4">PHP 8.4</td>
                                    <td class="px-6 py-4"><a href="http://localhost:8081" target="_blank" class="text-indigo-400 hover:underline">http://localhost:8081 <i class="fa-solid fa-arrow-up-right-from-square text-[10px]"></i></a></td>
                                </tr>
                                <tr>
                                    <td class="px-6 py-4 font-mono text-xs">php_8_2</td>
                                    <td class="px-6 py-4 font-mono text-indigo-400">8082</td>
                                    <td class="px-6 py-4">PHP 8.2</td>
                                    <td class="px-6 py-4"><a href="http://localhost:8082" target="_blank" class="text-indigo-400 hover:underline">http://localhost:8082 <i class="fa-solid fa-arrow-up-right-from-square text-[10px]"></i></a></td>
                                </tr>
                                <tr>
                                    <td class="px-6 py-4 font-mono text-xs">php_8_4_26</td>
                                    <td class="px-6 py-4 font-mono text-indigo-400">8083</td>
                                    <td class="px-6 py-4">PHP 8.4.26</td>
                                    <td class="px-6 py-4"><a href="http://localhost:8083" target="_blank" class="text-indigo-400 hover:underline">http://localhost:8083 <i class="fa-solid fa-arrow-up-right-from-square text-[10px]"></i></a></td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </main>
        </div>
    </div>
<?php endif; ?>

</body>
</html>