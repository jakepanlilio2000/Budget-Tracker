<?php
declare(strict_types=1);
define('IS_INSTALLER', true);
require_once __DIR__ . '/../app/Helpers/functions.php';

if (file_exists(__DIR__ . '/../config/config.php')) {
    die("Application is already installed. Delete config/config.php to reinstall.");
}

$step = (int) ($_GET['step'] ?? 1);
$errors = [];
$success = false;

// Ensure storage directories exist
@mkdir(__DIR__ . '/../storage/logs', 0755, true);
@mkdir(__DIR__ . '/../storage/backups', 0755, true);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if ($step === 2) {
        // Test DB Connection & Import Schema
        $host = trim($_POST['db_host'] ?? 'localhost');
        $port = trim($_POST['db_port'] ?? '3306');
        $name = trim($_POST['db_name'] ?? 'expense_tracker');
        $user = trim($_POST['db_user'] ?? 'root');
        $pass = $_POST['db_pass'] ?? '';

        try {
            $dsn = "mysql:host={$host};port={$port};charset=utf8mb4";
            $pdo = new PDO($dsn, $user, $pass, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);

            // Create DB if not exists
            $pdo->exec("CREATE DATABASE IF NOT EXISTS `{$name}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
            $pdo->exec("USE `{$name}`");

            // Import Schema
            $schemaFile = __DIR__ . '/../database/migrations/001_initial_schema.sql';
            $schemaFile2 = __DIR__ . '/../database/migrations/003_transactions.sql'; // Will be created below

            if (file_exists($schemaFile))
                $pdo->exec(file_get_contents($schemaFile));
            if (file_exists($schemaFile2))
                $pdo->exec(file_get_contents($schemaFile2));

            session_start();
            $_SESSION['install_db'] = compact('host', 'port', 'name', 'user', 'pass');
            redirect('install.php?step=3');
        } catch (PDOException $e) {
            $errors[] = "Database Error: " . $e->getMessage();
        }
    } elseif ($step === 3) {
        // Create Admin & Finalize
        session_start();
        $db = $_SESSION['install_db'] ?? null;
        if (!$db)
            redirect('install.php?step=2');

        $username = trim($_POST['admin_user'] ?? '');
        $email = trim($_POST['admin_email'] ?? '');
        $password = $_POST['admin_pass'] ?? '';
        $fullName = trim($_POST['admin_full_name'] ?? $username);

        if (strlen($password) < 8) {
            $errors[] = "Password must be at least 8 characters.";
        } elseif (empty($username) || empty($email)) {
            $errors[] = "Username and Email are required.";
        } else {
            try {
                $dsn = "mysql:host={$db['host']};port={$db['port']};dbname={$db['name']};charset=utf8mb4";
                $pdo = new PDO($dsn, $db['user'], $db['pass'], [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);

                $hash = password_hash($password, PASSWORD_BCRYPT);
                $stmt = $pdo->prepare("INSERT INTO users (username, email, password_hash, full_name, role) VALUES (?, ?, ?, ?, 'admin')");
                $stmt->execute([$username, $email, $hash, $fullName]);

                // Insert default base currency and categories
                $pdo->exec("INSERT INTO currencies (code, name, symbol, is_base) VALUES ('USD', 'US Dollar', '\$', 1)");

                // Write config file securely
                $configDir = __DIR__ . '/../config';
                if (!is_dir($configDir))
                    mkdir($configDir, 0755, true);

                $configContent = "<?php\nreturn [\n" .
                    "    'app_name' => 'Expense Tracker',\n" .
                    "    'env' => 'production',\n" .
                    "    'db' => [\n" .
                    "        'host' => '{$db['host']}',\n" .
                    "        'port' => '{$db['port']}',\n" .
                    "        'name' => '{$db['name']}',\n" .
                    "        'user' => '{$db['user']}',\n" .
                    "        'pass' => '{$db['pass']}',\n" .
                    "        'charset' => 'utf8mb4'\n" .
                    "    ],\n" .
                    "    'mail' => [\n" .
                    "        'from_email' => 'noreply@yourdomain.com',\n" .
                    "        'from_name' => 'Expense Tracker'\n" .
                    "    ]\n];\n";

                file_put_contents($configDir . '/config.php', $configContent);
                chmod($configDir . '/config.php', 0600);

                session_destroy();
                $success = true;
            } catch (Exception $e) {
                $errors[] = "Setup Error: " . $e->getMessage();
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en" data-theme="light">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Installation - Expense Tracker</title>
    <link rel="stylesheet" href="assets/css/app.css">
</head>

<body class="auth-body">
    <div class="glass-card" style="max-width: 500px; width: 100%;">
        <div class="steps" style="display: flex; justify-content: space-between; margin-bottom: 2rem;">
            <div class="step-dot <?= $step >= 1 ? 'active' : '' ?>">1</div>
            <div class="step-dot <?= $step >= 2 ? 'active' : '' ?>">2</div>
            <div class="step-dot <?= $step >= 3 ? 'active' : '' ?>">3</div>
        </div>

        <?php if ($success): ?>
            <div class="alert alert-success">Installation successful! Redirecting to login...</div>
            <script>setTimeout(() => window.location.href = '<?= url('/login') ?>', 2000);</script>
        <?php else: ?>
            <?php foreach ($errors as $error): ?>
                <div class="alert alert-danger"><?= e($error) ?></div>
            <?php endforeach; ?>

            <?php if ($step === 1): ?>
                <h2>System Requirements</h2>
                <ul style="line-height: 2; margin: 1rem 0;">
                    <li>PHP Version: <?= PHP_VERSION ?>         <?= version_compare(PHP_VERSION, '8.0.0', '>=') ? '✅' : '❌' ?></li>
                    <li>PDO MySQL: <?= extension_loaded('pdo_mysql') ? '✅' : '❌' ?></li>
                    <li>MBString: <?= extension_loaded('mbstring') ? '✅' : '❌' ?></li>
                    <li>JSON: <?= extension_loaded('json') ? '✅' : '❌' ?></li>
                    <li>Config Writable: <?= is_writable(__DIR__ . '/../config') || is_writable(__DIR__ . '/..') ? '✅' : '❌' ?>
                    </li>
                    <li>Storage Writable: <?= is_writable(__DIR__ . '/../storage') ? '✅' : '❌' ?></li>
                </ul>
                <a href="install.php?step=2" class="btn btn-primary btn-block">Continue to Database Setup</a>

            <?php elseif ($step === 2): ?>
                <h2>Database Configuration</h2>
                <form method="POST" class="auth-form">
                    <div class="form-group"><label>Host</label><input type="text" name="db_host" value="localhost" required>
                    </div>
                    <div class="form-group"><label>Port</label><input type="text" name="db_port" value="3306" required></div>
                    <div class="form-group"><label>Database Name</label><input type="text" name="db_name"
                            value="expense_tracker" required></div>
                    <div class="form-group"><label>Username</label><input type="text" name="db_user" value="root" required>
                    </div>
                    <div class="form-group"><label>Password</label><input type="password" name="db_pass"></div>
                    <button type="submit" class="btn btn-primary btn-block">Test Connection & Import Schema</button>
                </form>

            <?php elseif ($step === 3): ?>
                <h2>Create Administrator</h2>
                <form method="POST" class="auth-form">
                    <div class="form-group"><label>Full Name</label><input type="text" name="admin_full_name" required></div>
                    <div class="form-group"><label>Username</label><input type="text" name="admin_user" required></div>
                    <div class="form-group"><label>Email</label><input type="email" name="admin_email" required></div>
                    <div class="form-group"><label>Password</label><input type="password" name="admin_pass" minlength="8"
                            required></div>
                    <button type="submit" class="btn btn-primary btn-block">Finish Installation</button>
                </form>
            <?php endif; ?>
        <?php endif; ?>
    </div>
    <script src="assets/js/app.js"></script>
</body>

</html>